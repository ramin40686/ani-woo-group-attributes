/**
 * Anishop mobile bottom navigation — cart count sync.
 *
 * Keeps the cart count badge in the mobile bottom navigation in sync with the
 * real WooCommerce cart after add-to-cart / remove-from-cart / quantity change,
 * without a page reload.
 *
 * The store front uses three different cart mechanisms, each of which signals a
 * cart change differently. All of them funnel the REAL cart count (never a
 * client-side increment) into a single shared sync function, syncCartCount():
 *
 *   1. WooCommerce Blocks data store (Cart Block): quantity +/- and remove
 *      update the `wc/store/cart` store. A `wp.data.subscribe(callback,
 *      'wc/store/cart')` subscription reads the real count from
 *      `getCartData().itemsCount` and updates the badge the moment the store
 *      changes. This is the source of truth on pages where the store is
 *      registered (e.g. the Cart page). The badge is also initialized from the
 *      current store state so it is correct immediately after the page loads.
 *
 *   2. WooCommerce Blocks Interactivity API (Product Collection on the
 *      Homepage): add/remove-to-cart fires the native `wc-blocks_added_to_cart`
 *      / `wc-blocks_removed_from_cart` event after the iAPI commit is
 *      committed, but no data store is registered on that page. The real count
 *      is fetched immediately from the Store API (`/wp-json/wc/store/v1/cart`
 *      → `items_count`) — no debounce, no polling.
 *
 *   3. Classic WooCommerce (Shop / Single Product): the jQuery
 *      `added_to_cart` / `removed_from_cart` event carries a fragments payload.
 *      A dedicated cart-count fragment (`.anishop-mobile-nav__cart-count`, see
 *      bootstrap.php) holds the server-side count, read directly from
 *      `data-count` (the classic add is synchronous, so no delay is needed).
 *
 *   4. Transport fallback (safety path for the two fetch()-based transports):
 *      on some real devices the fetch()-based Add-to-Cart requests (Homepage
 *      Interactivity API batch, Blocksy single-product AJAX) fail to complete,
 *      so their success events never fire. A non-invasive window.fetch
 *      observer detects exactly those cart-mutation requests and, when one
 *      settles (success or error), performs at most one coalesced Store API
 *      cart read through the same fetchStoreApiCount() pipeline. It never
 *      modifies the request, never resends it, never consumes the response,
 *      and returns the original Promise untouched. The classic Shop XHR is
 *      not a fetch() call and is left completely untouched.
 *
 * No polling, no MutationObserver, no timers. The badge element is re-queried
 * from the DOM on every update, so it survives any WooCommerce fragment
 * replacement. syncCartCount() is idempotent: it skips the DOM write when the
 * count is unchanged.
 *
 * @package Anishop
 */
( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof $ ) {
		return;
	}

	var COUNT_SELECTOR = '.anishop-mobile-nav__cart-count';
	var NAV_SELECTOR   = '.anishop-mobile-nav';
	var CART_KEY       = 'cart';
	var BADGE_SELECTOR = '.anishop-mobile-nav__badge';
	var STORE_API_URL  = window.anishopMobileNav && window.anishopMobileNav.storeApiUrl
		? window.anishopMobileNav.storeApiUrl
		: '/wp-json/wc/store/v1/cart';

	// Bounded one-shot delay (ms) for the issue-time reconciliation read:
	// gives the server time to commit the cart before the Store API read.
	// A single fixed delay per detected mutation — not polling.
	var RECONCILE_DELAY_MS = 3000;

	var apiFetching      = false;
	var apiPending       = false;
	var storeSubscribed  = false;
	var lastAppliedCount = null;
	var reconcilePending = false;

	/**
	 * Format an integer count the same way the server does
	 * ( number_format_i18n: Latin digits, comma thousands separator ).
	 *
	 * @param {number} count The count.
	 * @return {string}
	 */
	function formatCount( count ) {
		return String( count ).replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
	}

	/**
	 * Read the real cart count from the WooCommerce fragments payload
	 * (classic add/remove-to-cart path).
	 *
	 * @param {Object} fragments WooCommerce fragments (selector => HTML).
	 * @return {?number} The server-side count, or null if not present.
	 */
	function readCartCount( fragments ) {
		if ( ! fragments || ! fragments[ COUNT_SELECTOR ] ) {
			return null;
		}
		var $el   = $( fragments[ COUNT_SELECTOR ] );
		var count = parseInt( $el.attr( 'data-count' ), 10 );
		if ( isNaN( count ) ) {
			return null;
		}
		return count;
	}

	/**
	 * Get the WooCommerce Blocks cart data store, if it is registered.
	 *
	 * @return {?Object} The store, or null.
	 */
	function getCartStore() {
		if ( window.wp && window.wp.data && 'function' === typeof window.wp.data.select ) {
			var store = window.wp.data.select( 'wc/store/cart' );
			if ( store && 'function' === typeof store.getCartData ) {
				return store;
			}
		}
		return null;
	}

	/**
	 * Read the authoritative cart count from the Store API (the source of
	 * truth for the Homepage Product Collection, which uses the Interactivity
	 * API and has no data store registered).
	 *
	 * B6-13: the read uses XMLHttpRequest, not window.fetch. On the real
	 * device the fetch()-based requests do not complete (B6-8: the XHR Shop
	 * path works while both fetch() Add-to-Cart paths hang), so a fetch() read
	 * would hang the same way and never sync the badge. XHR is proven to
	 * complete on the device. The request is same-origin, so the session
	 * cookie is sent by default (equivalent to credentials: 'same-origin').
	 *
	 * Concurrent requests are coalesced: if a read is already in flight when a
	 * new cart-change event arrives, a single follow-up read is scheduled after
	 * the current one completes, so the badge always settles on the latest
	 * committed count. This is not polling or a debounce.
	 *
	 * @return {void}
	 */
	function fetchStoreApiCount() {
		if ( apiFetching ) {
			apiPending = true;
			return;
		}
		apiFetching = true;
		var xhr = new XMLHttpRequest();
		xhr.open( 'GET', STORE_API_URL, true );
		xhr.onreadystatechange = function () {
			if ( 4 !== xhr.readyState ) {
				return;
			}
			apiFetching = false;
			if ( xhr.status >= 200 && xhr.status < 300 ) {
				try {
					var data = JSON.parse( xhr.responseText );
					if ( data && 'number' === typeof data.items_count ) {
						syncCartCount( data.items_count );
					}
				} catch ( e ) {
					// Ignore a malformed response.
				}
			}
			if ( apiPending ) {
				apiPending = false;
				fetchStoreApiCount();
			}
		};
		try {
			xhr.send();
		} catch ( e ) {
			// Synchronous send failure (e.g. bad URL): reset so we are not
			// stuck, and drop any pending follow-up to avoid a loop.
			apiFetching = false;
			apiPending = false;
		}
	}

	/**
	 * The single shared cart-count sync function.
	 *
	 * Updates the visible badge + accessible label to the given real count. The
	 * badge element is re-queried from the DOM on every call, so this stays
	 * correct even if a WooCommerce fragment replacement swaps the markup. The
	 * function is idempotent: it skips the DOM write when the count is
	 * unchanged, creates the badge if it does not exist yet (count > 0), and
	 * removes it when the count reaches zero.
	 *
	 * @param {number} count The authoritative cart count.
	 */
	function syncCartCount( count ) {
		if ( 'number' !== typeof count || count < 0 ) {
			return;
		}
		if ( lastAppliedCount === count ) {
			return;
		}
		lastAppliedCount = count;
		// A real sync just applied a new count, so the issue-time delayed
		// reconciliation no longer needs to run (healthy-device no-op).
		reconcilePending = false;

		var $nav = $( NAV_SELECTOR );
		if ( ! $nav.length ) {
			return;
		}
		var $cartItem = $nav.find( '.anishop-mobile-nav__item[data-anishop-nav="' + CART_KEY + '"]' );
		if ( ! $cartItem.length ) {
			return;
		}
		var $label    = $cartItem.find( '.anishop-mobile-nav__label' );
		var labelText = $label.length ? $label.text() : '';
		var $badge    = $cartItem.find( BADGE_SELECTOR );
		var text      = formatCount( count );

		if ( count > 0 ) {
			if ( $badge.length ) {
				$badge.text( text );
			} else {
				$cartItem.append( $( '<span class="anishop-mobile-nav__badge" aria-hidden="true"></span>' ).text( text ) );
			}
			$cartItem.attr( 'aria-label', labelText + ' (' + text + ')' );
		} else {
			if ( $badge.length ) {
				$badge.remove();
			}
			$cartItem.removeAttr( 'aria-label' );
		}
	}

	/**
	 * Subscribe to the WooCommerce Blocks cart data store — the source of truth
	 * on pages where it is registered (e.g. the Cart page). Initializes the
	 * badge from the current store state, then updates it the moment
	 * `itemsCount` changes. No polling, no MutationObserver.
	 *
	 * The store is only registered where a cart block is rendered, so this is a
	 * no-op on pages without one (e.g. the Homepage, which uses the iAPI). The
	 * subscription uses `wp.data.subscribe(callback, 'wc/store/cart')` — the
	 * store object returned by `wp.data.select()` does not expose its own
	 * `subscribe` method in this build.
	 *
	 * @return {void}
	 */
	function initStoreSubscription() {
		if ( storeSubscribed ) {
			return;
		}
		if (
			! window.wp ||
			! window.wp.data ||
			'function' !== typeof window.wp.data.select ||
			'function' !== typeof window.wp.data.subscribe
		) {
			return;
		}
		var store = getCartStore();
		if ( ! store ) {
			return;
		}
		storeSubscribed = true;

		// Initialize the badge from the current store state so it is correct
		// immediately after the Cart page loads.
		var initial = store.getCartData();
		if ( initial && 'number' === typeof initial.itemsCount ) {
			syncCartCount( initial.itemsCount );
		}

		// Subscribe to store changes and funnel the authoritative itemsCount
		// into the shared sync function.
		window.wp.data.subscribe( function () {
			var current = getCartStore();
			if ( ! current ) {
				return;
			}
			var data = current.getCartData();
			if ( ! data || 'number' !== typeof data.itemsCount ) {
				return;
			}
			syncCartCount( data.itemsCount );
		}, 'wc/store/cart' );
	}

	// ------------------------------------------------------------------
	// Transport fallback (B6-9): event-driven Add-to-Cart mutation detector.
	//
	// B6-8 proved that on some real devices the fetch()-based Add-to-Cart
	// requests (Homepage Interactivity API batch, Blocksy single-product
	// AJAX) fail to complete, so their normal success events
	// (wc-blocks_added_to_cart / added_to_cart) never fire and the badge
	// would stay stale. This fallback observes exactly those mutation
	// requests and, when one settles (success OR error), performs at most
	// one coalesced Store API cart read through the existing
	// fetchStoreApiCount() -> syncCartCount() pipeline.
	//
	// It is a pure observer: the original window.fetch is preserved, request
	// semantics are never modified, the request is never resent, the response
	// body is never consumed, and the original Promise is returned untouched.
	// Only requests confidently identified as cart mutations are detected:
	//
	//   - POST to the Store API batch endpoint (/wc/store/v1/batch) whose
	//     JSON body carries a cart/add-item, cart/update-item or
	//     cart/remove-item operation (WooCommerce Blocks Interactivity API,
	//     Homepage Product Collection),
	//   - POST to a product URL with blocksy_add_to_cart=yes
	//     (Blocksy single-product AJAX).
	//
	// The fallback's own Store API read is a GET, so it can never be detected
	// as a mutation (no recursion). The classic Shop XHR
	// (/?wc-ajax=add_to_cart) is not a fetch() call and is left completely
	// untouched; its added_to_cart event path remains authoritative.
	// ------------------------------------------------------------------

	/**
	 * Resolve the URL of a fetch() input (string, Request or URL).
	 *
	 * @param {string|Request|URL} input The fetch input.
	 * @return {string} The URL, or '' if it cannot be resolved.
	 */
	function requestHref( input ) {
		if ( 'string' === typeof input ) {
			return input;
		}
		if ( input instanceof Request ) {
			return input.url;
		}
		if ( typeof URL !== 'undefined' && input instanceof URL ) {
			return input.href;
		}
		return '';
	}

	/**
	 * Identify a fetch() call as a cart mutation request, if it is one.
	 *
	 * Only the two fetch()-based Add-to-Cart transports established by the
	 * B6-8 diagnostic are recognized. Ordinary Store API GETs (including the
	 * fallback's own cart read) and all unrelated requests return null.
	 *
	 * @param {string|Request|URL} input The fetch input.
	 * @param {Object|undefined}   init  The fetch init object.
	 * @return {?string} 'batch' or 'blocksy', or null when not a mutation.
	 */
	function detectCartMutation( input, init ) {
		var method = '';
		if ( init && init.method ) {
			method = String( init.method ).toUpperCase();
		} else if ( input instanceof Request && input.method ) {
			method = String( input.method ).toUpperCase();
		}
		if ( 'POST' !== method ) {
			return null;
		}
		var href = requestHref( input );
		if ( ! href ) {
			return null;
		}
		// Blocksy single-product AJAX: POST to the product URL with
		// blocksy_add_to_cart=yes appended to the query string.
		if ( href.indexOf( 'blocksy_add_to_cart=yes' ) !== -1 ) {
			return 'blocksy';
		}
		// WooCommerce Blocks Interactivity API: POST to the Store API batch
		// endpoint. The batch endpoint is generic, so it only counts as a
		// cart mutation when the JSON body carries a cart mutation operation.
		// (A Request input has a streamed body that cannot be inspected
		// without consuming it; the iAPI always passes a plain string body,
		// so a non-string body simply means "cannot confirm".)
		if ( href.indexOf( '/wc/store/v1/batch' ) !== -1 ) {
			var body = init ? init.body : '';
			if (
				'string' === typeof body &&
				( body.indexOf( 'cart/add-item' ) !== -1 ||
					body.indexOf( 'cart/update-item' ) !== -1 ||
					body.indexOf( 'cart/remove-item' ) !== -1 )
			) {
				return 'batch';
			}
		}
		return null;
	}

	/**
	 * Schedule at most one coalesced Store API cart read after a detected
	 * cart-mutation request settles.
	 *
	 * The read is deferred by a single microtask so that a success event
	 * dispatched by the same request's own continuation (e.g.
	 * wc-blocks_added_to_cart) can claim the sync first through the existing
	 * event path; if that read is already in flight when the microtask runs,
	 * the fallback adds nothing (fetchStoreApiCount() coalesces concurrent
	 * requests). Net effect: one Add-to-Cart action produces at most one
	 * fallback Store API read. No timers, no polling.
	 *
	 * @param {string} kind The mutation kind ('batch' or 'blocksy').
	 * @return {void}
	 */
	function scheduleFallbackSync( kind ) {
		Promise.resolve().then( function () {
			// Batch mutations on a page where the wc/store/cart data store
			// subscription is active are processed by the store itself (Cart
			// page); the subscription is the authority there, so defer to it
			// and avoid a duplicate read.
			if ( 'batch' === kind && storeSubscribed ) {
				return;
			}
			// A read triggered by the normal event path for this same
			// operation is already in flight -> coalesced, nothing to do.
			if ( apiFetching ) {
				return;
			}
			fetchStoreApiCount();
		} );
	}

	/**
	 * Run the one-shot issue-time reconciliation read, if still needed.
	 *
	 * Safety net for real devices where the cart-mutation fetch() never
	 * settles (the server commits the cart but the response is lost), so
	 * the settlement handler in initTransportFallback() never runs. It
	 * performs at most one coalesced Store API read through the existing
	 * scheduleFallbackSync() pipeline. If a sync already applied a new
	 * count since the mutation was issued (healthy device), reconcilePending
	 * is false and this is a no-op.
	 *
	 * @param {string} kind The mutation kind ('batch' or 'blocksy').
	 * @return {void}
	 */
	function runDelayedReconciliation( kind ) {
		if ( ! reconcilePending ) {
			return;
		}
		scheduleFallbackSync( kind );
	}

	/**
	 * Install the transport fallback: a non-invasive window.fetch observer.
	 *
	 * The original fetch is preserved and called with its original `this`
	 * binding and arguments; the original Promise is returned untouched. A
	 * settlement handler is attached only to detected cart-mutation requests.
	 *
	 * @return {void}
	 */
	function initTransportFallback() {
		if ( 'function' !== typeof window.fetch ) {
			return;
		}
		var originalFetch = window.fetch;

		window.fetch = function ( input, init ) {
			var kind = null;
			try {
				kind = detectCartMutation( input, init );
			} catch ( e ) {
				kind = null;
			}
			var promise = originalFetch.apply( this, arguments );
			if ( kind ) {
				// Observe settlement only (success or error); never alter
				// the request or its response.
				promise.then(
					function () {
						scheduleFallbackSync( kind );
					},
					function () {
						scheduleFallbackSync( kind );
					}
				);
				// Issue-time reconciliation: on some real devices the
				// mutation fetch() never settles (the server commits the
				// cart but the response is lost), so the settlement handler
				// above never runs and the badge stays stale. Schedule ONE
				// bounded delayed Store API read, independent of whether
				// the original Promise settles. Skipped if a sync already
				// applied a new count (healthy device -> idempotent no-op).
				// A single one-shot setTimeout per mutation, not polling.
				reconcilePending = true;
				window.setTimeout( function () {
					runDelayedReconciliation( kind );
				}, RECONCILE_DELAY_MS );
			}
			return promise;
		};
	}

	// ------------------------------------------------------------------
	// Event wiring: every WooCommerce cart-change signal funnels into
	// syncCartCount() via the appropriate authoritative source.
	// ------------------------------------------------------------------

	// 1) Data store subscription (Cart Block quantity +/- / remove).
	//    The `wc/store/cart` store is registered by the WooCommerce Blocks
	//    scripts, which load AFTER this script (it is enqueued earlier in the
	//    footer). So the store is not available yet when this script runs.
	//    `wc-blocks_store_sync_required` is fired on window as soon as the store
	//    registers and fetches the initial cart, so it is used as the
	//    registration signal; a DOMContentLoaded/load fallback covers the case
	//    where the store is already ready. No polling.
	window.addEventListener( 'wc-blocks_store_sync_required', initStoreSubscription, true );
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initStoreSubscription, { once: true } );
		window.addEventListener( 'load', initStoreSubscription, { once: true } );
	} else {
		initStoreSubscription();
	}

	// 2) Interactivity API (Homepage Product Collection add/remove-to-cart).
	//    The event fires after the iAPI commit is committed, so an immediate
	//    Store API GET reads the real count. No debounce.
	document.addEventListener( 'wc-blocks_added_to_cart', fetchStoreApiCount, true );
	document.addEventListener( 'wc-blocks_removed_from_cart', fetchStoreApiCount, true );

	// 3) Classic WooCommerce jQuery events (carry the fragments payload).
	$( document.body ).on( 'added_to_cart removed_from_cart', function ( event, fragments ) {
		var count = readCartCount( fragments );
		if ( null !== count ) {
			syncCartCount( count );
		} else {
			fetchStoreApiCount();
		}
	} );

	// 4) Transport fallback (B6-9): observe the fetch()-based Add-to-Cart
	//    mutations (Homepage iAPI batch, Blocksy single-product AJAX) and,
	//    when one settles, run at most one coalesced Store API cart read.
	//    This is an additional safety path for devices where the normal
	//    success events never fire; it does not replace any listener above.
	initTransportFallback();
} )( jQuery );

