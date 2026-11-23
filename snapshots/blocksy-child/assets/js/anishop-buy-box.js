/**
 * Anishop Buy Box — frontend behavior (Stage 2).
 *
 * Three-Column Buy Box project, Stage 2. This module is PRESENTATION-ONLY:
 * it never re-implements variation logic, never recomputes price/stock, and
 * never creates a second form or add-to-cart path. It:
 *
 *  1. Wires the Buy Box TRIGGER to drive the single native WooCommerce form /
 *     button (the real cart-submission path). It refuses to submit when the
 *     native form is not in a ready state (no valid variation selected) and
 *     instead surfaces the native "please select" prompt.
 *  2. Keeps the Buy Box PRICE and STOCK as a read-only MIRROR of the
 *     authoritative native state (the summary range for no selection; the
 *     selected variation's `price_html` / `availability_html` otherwise).
 *  3. Mirrors the native button's enabled/disabled state and label onto the
 *     trigger.
 *  4. Adds the `anishop-buy-box-js` class to <html> ONLY after the trigger is
 *     successfully wired. The CSS hides the native button only when this class
 *     is present, so a JavaScript failure leaves the native button visible and
 *     the original purchase path fully usable.
 *
 * All variation behavior (matching, price, stock, SKU, validation,
 * add-to-cart transport, cart-badge sync) is delegated to native WooCommerce.
 *
 * @package Anishop
 */
/* global jQuery, anishopBuyBox */
( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof anishopBuyBox || ! anishopBuyBox.enabled ) {
		return;
	}

	$( function () {
		var $buyBox = $( '.anishop-buy-box' ).first();
		if ( ! $buyBox.length ) {
			return;
		}

		var $price    = $buyBox.find( '.anishop-buy-box__price' );
		var $stock    = $buyBox.find( '.anishop-buy-box__stock' );
		var $trigger  = $buyBox.find( '.anishop-buy-box__trigger' );
		var isVariable = 'variable' === $buyBox.attr( 'data-product_type' );

		// The single native form + button (the source of truth).
		var $form   = $( 'form.cart, form.variations_form' ).first();
		var $native = $form.find( 'button.single_add_to_cart_button' ).first();

		if ( ! $form.length || ! $native.length ) {
			// No native form/button found — do NOT hide the native button and
			// do not wire the trigger. The original purchase path is untouched.
			return;
		}

		// Stage 5: the mobile-only sticky buy bar (a MIRROR of the Buy Box).
		// It is rendered in `wp_footer` (outside the product) only when the
		// `mobile_sticky_buy_bar` setting is on; when absent, every reference
		// below is a no-op and the Buy Box behaves exactly as before. Its
		// button drives the SAME single native form (no second form).
		var $mobileBar     = $( '.anishop-buy-box-mobile-bar' ).first();
		var $mobilePrice   = $mobileBar.find( '.anishop-buy-box-mobile-bar__price' );
		var $mobileTrigger = $mobileBar.find( '.anishop-buy-box-mobile-bar__trigger' );

		// When the mobile navbar is present, sit the bar above it (the CSS
		// `--nav` modifier). The navbar is rendered in `wp_footer` (priority
		// 20), so by DOM-ready it is in the DOM when enabled.
		if ( $mobileBar.length && $( '.anishop-mobile-nav' ).length ) {
			$mobileBar.addClass( 'anishop-buy-box-mobile-bar--nav' );
		}

		// The initial (range / simple) price + stock, captured before any
		// variation is selected, so `reset_data` can restore them.
		var initialPriceHtml = $price.html();
		var initialStockHtml = $stock.html();

		// The selected variation's stock state (the authoritative source for
		// whether the trigger can be enabled). Defaults to `true` so that,
		// before any variation is selected, the trigger is not blocked by a
		// stale out-of-stock state. Updated on `show_variation` and reset on
		// `reset_data`. We do NOT rely on the native button's disabled state
		// or the add-to-cart container's visibility — WooCommerce leaves the
		// native button enabled/visible even when the selected variation is
		// out of stock (it shows the "notify me" flow instead).
		var currentVariationInStock = true;

		// Whether a valid variation is currently selected. For a variable
		// product this starts `false` (no default selection) and is set to
		// `true` only when WooCommerce fires `show_variation` for a matched
		// variation. It is reset to `false` on `reset_data` / `hide_variation`.
		// While `false`, the Buy Box must NOT show a price/stock (the parent
		// range is not a variation price) and the trigger must stay disabled.
		var hasValidVariation = false;

		// The trigger's initial label (from the PHP: "Choose options" for
		// variable products). Restored when no valid variation is selected, so
		// a stale "ناموجود" label from a previous out-of-stock selection cannot
		// persist after a reset.
		var initialTriggerText = $.trim( $trigger.text() );

		/**
		 * Whether the native form is in a ready state (a valid, purchasable
		 * selection exists) and can be submitted.
		 *
		 * @return {boolean}
		 */
		function nativeReady() {
			if ( $native.prop( 'disabled' ) ) {
				return false;
			}
			// For variable products, the add-to-cart container is hidden until a
			// variation is selected. (The button itself is hidden by our CSS, so
			// we check the container's visibility, not the button's.)
			if ( isVariable ) {
				// A selected variation that is out of stock must NOT leave the
				// trigger enabled. This is the authoritative check — the native
				// button stays enabled/visible even when the variation is out of
				// stock (WooCommerce shows the "notify me" flow instead).
				if ( ! currentVariationInStock ) {
					return false;
				}
				var $container = $native.closest( '.woocommerce-variation-add-to-cart' );
				if ( $container.length && 'none' === $container.css( 'display' ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Sync the trigger's enabled state + label with the native button.
		 */
		function syncTrigger() {
			// For a variable product with no valid variation selected, the Buy
			// Box must not show a price/stock (the parent range is not a
			// variation price) and the trigger must stay disabled with its
			// initial label (no stale "ناموجود" from a previous selection).
			if ( isVariable && ! hasValidVariation ) {
				$price.hide();
				$stock.hide();
				$trigger.prop( 'disabled', true );
				if ( initialTriggerText ) {
					$trigger.text( initialTriggerText );
				}
				syncMobileBar();
				return;
			}
			$price.show();
			$stock.show();
			var ready = nativeReady();
			$trigger.prop( 'disabled', ! ready );
			// For a variable product whose selected variation is out of stock,
			// mirror the availability text (e.g. "ناموجود") instead of the native
			// button's label (which stays "Add to cart" even when out of stock).
			if ( isVariable && ! currentVariationInStock ) {
				var $avail = $stock.find( '.stock' ).first();
				var availText = $avail.length ? $.trim( $avail.text() ) : '';
				if ( availText ) {
					$trigger.text( availText );
					syncMobileBar();
					return;
				}
			}
			// Mirror the native button's label (e.g. "Choose options" vs
			// "Add to cart").
			var nativeLabel = $.trim( $native.text() );
			if ( nativeLabel ) {
				$trigger.text( nativeLabel );
			}
			syncMobileBar();
		}

		/**
		 * Stage 5: mirror the Buy Box's price + trigger state onto the mobile
		 * sticky bar. The bar is a pure presentation mirror — it reads the
		 * Buy Box's CURRENT price visibility, price HTML, and trigger
		 * disabled/label state (which themselves mirror the native state).
		 * No price/stock is recomputed here.
		 */
		function syncMobileBar() {
			if ( ! $mobileBar.length ) {
				return;
			}
			// Mirror the price (respecting the Buy Box's price visibility —
			// hidden for a variable product with no valid selection).
			if ( $price.is( ':visible' ) ) {
				$mobilePrice.html( $price.html() ).show();
			} else {
				$mobilePrice.hide();
			}
			// Mirror the trigger's enabled state + label.
			$mobileTrigger.prop( 'disabled', $trigger.prop( 'disabled' ) );
			$mobileTrigger.text( $trigger.text() );
		}

		/**
		 * Surface the native "please select your options" prompt by focusing
		 * the first variation select (and letting WooCommerce's own state show).
		 */
		function promptSelect() {
			var $firstSelect = $form.find( 'select.variable' ).first();
			if ( $firstSelect.length ) {
				$firstSelect.trigger( 'focus' );
			}
			// Nudge the native form to (re)render its "selection needed" state.
			$form.trigger( 'woocommerce_variation_has_changed' );
		}

		// Trigger click: drive the real native form/button.
		$trigger.on( 'click', function ( event ) {
			event.preventDefault();
			if ( ! nativeReady() ) {
				promptSelect();
				return;
			}
			// Submit the native form with the native button as the submitter.
			// This runs WooCommerce's full validation + the real add-to-cart
			// transport (the correct variation_id is carried by the hidden input).
			var formEl = $form.get( 0 );
			var nativeEl = $native.get( 0 );
			if ( formEl && 'function' === typeof formEl.requestSubmit ) {
				formEl.requestSubmit( nativeEl );
			} else if ( nativeEl ) {
				nativeEl.click();
			}
		} );

		// Stage 5: the mobile bar's button drives the SAME single native form
		// (identical path to the Buy Box trigger above). No second form, no
		// second add-to-cart transport.
		if ( $mobileTrigger.length ) {
			$mobileTrigger.on( 'click', function ( event ) {
				event.preventDefault();
				if ( ! nativeReady() ) {
					promptSelect();
					return;
				}
				var formEl = $form.get( 0 );
				var nativeEl = $native.get( 0 );
				if ( formEl && 'function' === typeof formEl.requestSubmit ) {
					formEl.requestSubmit( nativeEl );
				} else if ( nativeEl ) {
					nativeEl.click();
				}
			} );
		}

		if ( isVariable ) {
			// Price + stock mirror: subscribe to the native variation events.
			$form.on( 'show_variation', function ( event, variation ) {
				if ( variation && variation.price_html ) {
					$price.html( variation.price_html );
				}
				if ( variation && variation.availability_html ) {
					$stock.html( variation.availability_html );
				}
				// A matched variation is now selected.
				hasValidVariation = true;
				// Track the selected variation's stock state (the authoritative
				// source for the trigger's enabled state).
				currentVariationInStock = variation ? !! variation.is_in_stock : true;
				syncTrigger();
			} );

			$form.on( 'reset_data', function () {
				$price.html( initialPriceHtml );
				$stock.html( initialStockHtml );
				// No variation is selected anymore — clear the stale state so a
				// previous selection cannot keep the trigger enabled or show a
				// stale price/stock.
				hasValidVariation = false;
				currentVariationInStock = true;
				syncTrigger();
			} );

			// The selection became invalid (no matching variation). Clear the
			// stale state and re-sync so a previous selection cannot leave the
			// trigger enabled or show a stale price/stock.
			$form.on( 'hide_variation', function () {
				hasValidVariation = false;
				currentVariationInStock = true;
				syncTrigger();
			} );

			// Keep the trigger in sync whenever the native selection changes.
			$form.on( 'woocommerce_variation_has_changed', function () {
				// Defer so the native JS has updated the button state first.
				window.setTimeout( syncTrigger, 0 );
			} );
		}

		// Initial sync.
		syncTrigger();

		/**
		 * Extend the sticky range (Stage 4.1).
		 *
		 * The Buy Box is `position: sticky` inside `.anishop-buy-box-sticky-wrap`,
		 * an absolutely-positioned container whose height defaults to 100% of
		 * `.entry-summary`. A sticky element can only travel within its
		 * containing block, so the Buy Box stopped sticking exactly where the
		 * summary ends (where the product tabs begin).
		 *
		 * Fix: grow the wrapper to the main product's full height (summary +
		 * tabs), so the Buy Box stays sticky through the tabs and releases
		 * right before the related/upsell sections (which live OUTSIDE the
		 * product element). The wrapper stays absolutely positioned inside the
		 * summary, so its x/width are unchanged; only its height grows.
		 *
		 * Viewport gating (Stage 4.1 mobile fix): the wrapper is only
		 * absolutely positioned at `lg` (>=992px) — see anishop-buy-box.css.
		 * Below `lg` it is in NORMAL document flow (the approved mobile
		 * reading order: gallery → info → Buy Box). Setting an inline height
		 * there would stretch the in-flow wrapper to the product's full
		 * height, creating a huge blank area after the Buy Box; worse, the
		 * ResizeObserver below would feed the grown product height back into
		 * the wrapper on every callback (a runaway loop that grew the page
		 * to millions of px and pushed the tabs / related / upsells far
		 * below the fold). So the height is managed ONLY while the `lg`
		 * media query matches; below it the inline height is cleared and the
		 * wrapper keeps its natural (content) height.
		 *
		 * A ResizeObserver on the product element keeps the height in sync
		 * when the tabs' height changes (accordion expand/collapse) or on
		 * resize; a matchMedia listener handles crossing the `lg` boundary.
		 * No scroll listeners, no polling.
		 */
		function setupStickyBoundary() {
			if ( ! document.body.classList.contains( 'anishop-buy-box-sticky' ) ) {
				return;
			}
			var $wrap = $( '.anishop-buy-box-sticky-wrap' ).first();
			if ( ! $wrap.length ) {
				return;
			}
			var $product = $wrap.closest( '.product.type-product' );
			if ( ! $product.length ) {
				return;
			}
			var wrapEl    = $wrap.get( 0 );
			var productEl = $product.get( 0 );
			var lgQuery   = 'function' === typeof window.matchMedia ? window.matchMedia( '(min-width: 992px)' ) : null;

			function isLg() {
				return lgQuery ? lgQuery.matches : window.innerWidth >= 992;
			}

			function updateWrapHeight() {
				if ( ! isLg() ) {
					// Below lg the wrapper is in normal flow — it must keep
					// its natural height (an inline height would create a
					// blank area after the Buy Box).
					wrapEl.style.height = '';
					return;
				}
				var h = productEl.offsetHeight;
				if ( h > 0 ) {
					wrapEl.style.height = h + 'px';
				}
			}

			updateWrapHeight();

			if ( 'undefined' !== typeof ResizeObserver ) {
				var observer = new ResizeObserver( function () {
					updateWrapHeight();
				} );
				observer.observe( productEl );
			}

			if ( lgQuery ) {
				if ( 'function' === typeof lgQuery.addEventListener ) {
					lgQuery.addEventListener( 'change', updateWrapHeight );
				} else if ( 'function' === typeof lgQuery.addListener ) {
					lgQuery.addListener( updateWrapHeight ); // Safari < 14.
				}
			}

			$( window ).on( 'resize.anishopBuyBoxSticky', updateWrapHeight );
		}

		setupStickyBoundary();

		// The trigger is now wired — safe to hide the native button (CSS-gated
		// on this class). If any of the above threw, this line is never reached
		// and the native button stays visible.
		$( 'html' ).addClass( 'anishop-buy-box-js' );
	} );
} )( jQuery );
