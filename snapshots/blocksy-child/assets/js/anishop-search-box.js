/**
 * Anishop Search Box — frontend behavior (Phase 3.3C).
 *
 * Integrates with the Blocksy Free Live Search engine (reused, never
 * replaced). The engine (lazy-loaded by the theme on the first keystroke)
 * owns: the debounced input handling, the REST request, the results
 * rendering, the "Show more" link, the Escape handling, and the
 * aria-expanded management.
 *
 * This script adds only what the Free engine does not provide:
 *
 *  1. A single global `fetch` wrapper that observes the engine's
 *     `ct_live_search` requests (and its batched Store API price/stock
 *     enrichment fetch). It distinguishes the four request outcomes —
 *     success, genuine failure, abort/supersede, and slow — with
 *     best-effort form attribution. The engine's request layer is a
 *     module-level singleton shared by ALL live forms on the page, so a
 *     new search from any form aborts the in-flight request of any form,
 *     and exact request→form attribution is not guaranteed (Phase 2
 *     design §10.3 — documented limitation).
 *  2. Per-instance state (one closure per `.anishop-header-search` form,
 *     duplicate-init guarded by a WeakSet): an 8-second watchdog that
 *     shows a NON-error "still searching" state (C4 — a slow request is
 *     not a failure), a confirmed-failure error row + stuck-loader
 *     recovery (the engine's catch is console-only and leaves the loader
 *     spinning), and direct-focus ArrowUp/ArrowDown keyboard navigation
 *     (C3 — the engine has no arrow-key support; its Escape handler is
 *     reused, never duplicated).
 *  3. Scoped text post-processing (D5): the dashboard stock-status and
 *     no-results texts are applied to THIS instance's rendered DOM only —
 *     the global `ct_localizations` is never mutated, so the modal,
 *     no-results page, 404, and sidebar forms keep the Blocksy defaults.
 *
 * Dependency-free vanilla JS (IIFE), house convention.
 *
 * @package Anishop
 */
/* global anishopSearchBox */
( function () {
	'use strict';

	var config = window.anishopSearchBox;

	if ( ! config || ! config.texts ) {
		return;
	}

	var WATCHDOG_MS    = parseInt( config.watchdogMs, 10 ) || 8000;
	var INPUT_QUIET_MS = 1000;

	var ERROR_ICON = '<svg viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">' +
		'<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"></circle>' +
		'<line x1="8" y1="4.5" x2="8" y2="8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line>' +
		'<circle cx="8" cy="11.5" r="1" fill="currentColor"></circle></svg>';

	var CLEAR_ICON = '<svg viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">' +
		'<line x1="4" y1="4" x2="12" y2="12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line>' +
		'<line x1="12" y1="4" x2="4" y2="12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></line></svg>';

	// Forms that already have a controller (prevents duplicate
	// initialization if the script runs twice).
	var initializedForms = new WeakSet();

	// formEl -> controller (per-instance state).
	var controllers = new WeakMap();

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	function defer( fn ) {
		if ( typeof window.queueMicrotask === 'function' ) {
			window.queueMicrotask( fn );
		} else {
			window.setTimeout( fn, 0 );
		}
	}

	function isLiveSearchUrl( url ) {
		return url.indexOf( 'ct_live_search=true' ) > -1;
	}

	function isEnrichmentUrl( url ) {
		// The engine's batched Store API price/stock enrichment fetch.
		return url.indexOf( 'wc/store/products' ) > -1 && url.indexOf( 'include=' ) > -1;
	}

	/**
	 * Best-effort request → form attribution (C2).
	 *
	 * The engine adds `ct-searching` to the originating form synchronously
	 * before calling `fetch`, so at fetch time the originating form has the
	 * class. With exactly one such form, attribution is reliable (the common
	 * case — the desktop and mobile instances are not both interactive at
	 * once). With several (a stuck form from an aborted request), prefer the
	 * form whose input is focused, then the most recently typed form.
	 *
	 * @returns {HTMLElement|null} The attributed form, or null (unattributed
	 *   — a confirmed failure then renders no specific error; the
	 *   per-instance watchdogs surface a stuck state instead).
	 */
	function attributeForm() {
		var searching = document.querySelectorAll( '.ct-search-form.ct-searching' );

		if ( searching.length === 1 ) {
			return searching[ 0 ];
		}

		if ( searching.length === 0 ) {
			return null;
		}

		var active = document.activeElement;
		var i;

		for ( i = 0; i < searching.length; i++ ) {
			if ( active && searching[ i ].contains( active ) ) {
				return searching[ i ];
			}
		}

		var best   = null;
		var bestAt = -1;

		for ( i = 0; i < searching.length; i++ ) {
			var ctrl = controllers.get( searching[ i ] );
			var at   = ctrl ? ctrl.lastInputAt() : -1;

			if ( at > bestAt ) {
				best   = searching[ i ];
				bestAt = at;
			}
		}

		return best;
	}

	// ---------------------------------------------------------------------
	// The single global fetch wrapper (installed once).
	// ---------------------------------------------------------------------

	function installFetchWrapper() {
		if ( typeof window.fetch !== 'function' ) {
			return;
		}

		var originalFetch = window.fetch;

		var wrapper = function ( input, init ) {
			var url = '';

			try {
				url = typeof input === 'string' ? input : ( input && input.url ) || '';
			} catch ( e ) {
				url = '';
			}

			var isTracked = isLiveSearchUrl( url ) || isEnrichmentUrl( url );

			var promise = originalFetch.apply( this, arguments );

			if ( ! isTracked ) {
				return promise;
			}

			var form = attributeForm();
			var ctrl = form ? controllers.get( form ) : null;

			if ( ctrl ) {
				try {
					ctrl.onRequestStarted();
				} catch ( e ) {
					/* never break the engine's request path */
				}
			}

			promise.then(
				function () {
					if ( ctrl ) {
						try {
							ctrl.onRequestSucceeded();
						} catch ( e ) {
							/* ignore */
						}
					}
				},
				function ( error ) {
					if ( ! ctrl ) {
						return;
					}

					try {
						var aborted = ! ! (
							error &&
							( error.name === 'AbortError' || error.message === 'New request' )
						);

						if ( aborted ) {
							ctrl.onRequestAborted();
						} else {
							ctrl.onRequestFailed();
						}
					} catch ( e ) {
						/* ignore */
					}
				}
			);

			return promise;
		};

		wrapper.__anishopSearchBox = true;
		window.fetch = wrapper;
	}

	// ---------------------------------------------------------------------
	// Per-instance controller.
	// ---------------------------------------------------------------------

	function createController( formEl ) {
		var inputEl   = formEl.querySelector( 'input[type="search"]' );
		var statusEl  = formEl.querySelector( '[aria-live]' );
		var resultsId = inputEl ? inputEl.getAttribute( 'aria-controls' ) : null;

		var state = {
			lastInputAt: 0,
			requestGeneration: 0,
			watchdogTimer: null,
			stateContainer: null, // the waiting/error container this instance created
			stateKind: null       // 'waiting' | 'error' | null
		};

		// -- loader / expand state ------------------------------------------
		// Mirrors the engine's set-loader-state + setExpandedState; used only
		// to RECOVER states the engine leaves stuck (its catch returns early
		// on aborts and logs to the console on failures). The success path
		// is always the engine's own.

		function clearStuckLoader() {
			formEl.classList.remove( 'ct-searching' );

			var anim = formEl.querySelector( '.ct-ajax-loader animateTransform' );

			if ( anim && typeof anim.endElement === 'function' ) {
				try {
					anim.endElement();
				} catch ( e ) {
					/* the animation may not have been started */
				}
			}
		}

		function setExpanded( isExpanded ) {
			if ( ! inputEl ) {
				return;
			}

			inputEl.setAttribute( 'aria-expanded', isExpanded ? 'true' : 'false' );
			formEl.classList.toggle( 'ct-has-dropdown', isExpanded );
		}

		// -- watchdog (C4: a slow request is NOT a confirmed failure) --------

		function clearWatchdog() {
			if ( state.watchdogTimer ) {
				window.clearTimeout( state.watchdogTimer );
				state.watchdogTimer = null;
			}
		}

		function startWatchdog() {
			clearWatchdog();
			state.watchdogTimer = window.setTimeout( onWatchdogFire, WATCHDOG_MS );
		}

		function onWatchdogFire() {
			state.watchdogTimer = null;

			// Still searching? (the engine removes the class on success)
			if ( ! formEl.classList.contains( 'ct-searching' ) ) {
				return;
			}

			// The user is still typing (a new debounced search is coming)?
			if ( Date.now() - state.lastInputAt < INPUT_QUIET_MS ) {
				return;
			}

			// Real results already rendered?
			var results = formEl.querySelector( '.ct-search-results' );

			if ( results && results !== state.stateContainer ) {
				return;
			}

			showStateRow( 'waiting' );
		}

		// -- state rows (waiting / error) ------------------------------------

		function removeStateRow() {
			if ( state.stateContainer && state.stateContainer.parentNode ) {
				state.stateContainer.parentNode.removeChild( state.stateContainer );
			}

			state.stateContainer = null;
			state.stateKind = null;
		}

		function showStateRow( kind ) {
			if ( state.stateKind === kind ) {
				return;
			}

			removeStateRow();
			clearWatchdog();

			var container = document.createElement( 'div' );
			container.className = 'ct-search-results anishop-sb-results';

			if ( resultsId ) {
				container.id = resultsId;
			}

			var row = document.createElement( 'div' );
			row.className = 'anishop-sb-state anishop-sb-state--' + kind;

			if ( kind === 'error' ) {
				row.innerHTML = ERROR_ICON;

				var text = document.createElement( 'span' );
				text.textContent = config.texts.error;
				row.appendChild( text );
			} else {
				row.textContent = config.texts.waiting;
			}

			container.appendChild( row );
			formEl.appendChild( container );

			state.stateContainer = container;
			state.stateKind = kind;

			setExpanded( true );

			if ( statusEl ) {
				statusEl.textContent = kind === 'error' ? config.texts.error : config.texts.waiting;
			}
		}

		// -- request lifecycle (from the fetch wrapper) ----------------------

		function onRequestStarted() {
			state.requestGeneration++;
			state.lastInputAt = Date.now();

			// A new search supersedes any previous state of this instance
			// (error row, waiting row) and restarts the watchdog.
			removeStateRow();
			startWatchdog();
		}

		function onRequestSucceeded() {
			// The engine owns the success path (loader off, results
			// rendered). The watchdog keeps running until the results are
			// actually in the DOM — the Store API enrichment can still be in
			// flight — and the observer clears it.
		}

		function onRequestAborted() {
			// The abort rejection is delivered as a microtask AFTER any
			// synchronous new-request start, so a superseded request of this
			// same instance is detected by the generation check and ignored
			// (its newer request owns the loader now).
			var gen = state.requestGeneration;

			defer( function () {
				if ( gen !== state.requestGeneration ) {
					return;
				}

				// No newer request for this instance: its request is dead and
				// the engine left the loader stuck (its catch returns early
				// on aborts). Recover it. An abort is NEVER an error.
				clearStuckLoader();
				clearWatchdog();

				if ( state.stateKind === 'waiting' ) {
					removeStateRow();
				}

				var results = formEl.querySelector( '.ct-search-results' );

				// Close the dropdown only when no real results are shown
				// (previous results stay visible — the engine's own
				// abort behavior keeps them).
				if ( ! results || results === state.stateContainer ) {
					setExpanded( false );
				}
			} );
		}

		function onRequestFailed() {
			var gen = state.requestGeneration;

			defer( function () {
				if ( gen !== state.requestGeneration ) {
					return;
				}

				// Confirmed failure (genuine network/HTTP error): recover the
				// stuck loader and show the error row.
				clearStuckLoader();
				showStateRow( 'error' );
			} );
		}

		// -- scoped text post-processing (D5) --------------------------------

		function postProcessResults() {
			var results = formEl.querySelector( '.ct-search-results' );

			if ( ! results || results === state.stateContainer ) {
				return;
			}

			var loc = window.ct_localizations;

			if ( ! loc || ! loc.search_live_stock_status_texts ) {
				return;
			}

			var statuses = results.querySelectorAll( '.stock-status' );

			for ( var i = 0; i < statuses.length; i++ ) {
				var el   = statuses[ i ];
				var text = el.textContent;

				if ( config.texts.instock && text === loc.search_live_stock_status_texts.instock ) {
					el.textContent = config.texts.instock;
				} else if ( config.texts.outofstock && text === loc.search_live_stock_status_texts.outofstock ) {
					el.textContent = config.texts.outofstock;
				}
			}
		}

		function postProcessStatus() {
			if ( ! statusEl || ! config.texts.noResult ) {
				return;
			}

			var loc = window.ct_localizations;

			if ( loc && statusEl.textContent === loc.search_live_no_result ) {
				statusEl.textContent = config.texts.noResult;
			}
		}

		// -- clear button (Phase 7.3) ----------------------------------------

		var clearBtn = null;

		function createClearButton() {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'anishop-sb-clear';
			btn.setAttribute( 'aria-label', 'پاک کردن عبارت جستجو' );
			btn.innerHTML = CLEAR_ICON;

			// Insert into the form's inner container (the flex container that
			// holds the input and submit button). The button is positioned
			// absolutely, so its DOM position does not affect the layout.
			var inner = formEl.querySelector( '.ct-search-form-inner' );

			if ( inner ) {
				inner.appendChild( btn );
			} else {
				formEl.appendChild( btn );
			}

			clearBtn = btn;

			btn.addEventListener( 'click', handleClearClick );
		}

		function updateClearButton() {
			if ( ! clearBtn || ! inputEl ) {
				return;
			}

			var hasText = inputEl.value.length > 0;
			clearBtn.classList.toggle( 'anishop-sb-clear--visible', hasText );
		}

		function handleClearClick() {
			if ( ! inputEl ) {
				return;
			}

			// 1. Clear the input value.
			inputEl.value = '';

			// 2. Remove the results container (if any).
			var results = formEl.querySelector( '.ct-search-results' );

			if ( results && results !== state.stateContainer ) {
				results.parentNode.removeChild( results );
			}

			// 3. Remove any state row (waiting/error).
			removeStateRow();

			// 4. Clear the stuck loader (if any).
			clearStuckLoader();

			// 5. Increment the request generation (so any in-flight request's
			//    response is ignored by this instance).
			state.requestGeneration++;

			// 6. Close the dropdown.
			setExpanded( false );

			// 7. Update the clear button visibility.
			updateClearButton();

			// 8. Return focus to the input.
			inputEl.focus();
		}

		// -- DOM observation (results lifecycle + watchdog fallback) ---------

		function onFormChange() {
			var hasSearching   = formEl.classList.contains( 'ct-searching' );
			var results        = formEl.querySelector( '.ct-search-results' );
			var hasRealResults = ! ! ( results && results !== state.stateContainer );

			if ( hasRealResults ) {
				// Results arrived (or were re-rendered): the request cycle is
				// done — stop the watchdog, drop any state row (the engine
				// already replaced its container), apply the scoped texts.
				clearWatchdog();
				removeStateRow();
				postProcessResults();
			} else if ( ! hasSearching ) {
				// The engine removed the searching class without rendering
				// results (e.g. the empty state closed the dropdown): stop
				// the watchdog and drop the waiting row.
				clearWatchdog();

				if ( state.stateKind === 'waiting' ) {
					removeStateRow();
				}
			} else if ( ! state.watchdogTimer && ! state.stateKind ) {
				// Fallback (defense in depth): the searching class is present
				// but no request start was recorded (the wrapper was
				// bypassed) — start the watchdog anyway.
				startWatchdog();
			}

			postProcessStatus();

			// Sync the clear button visibility (covers the engine's Escape
			// handler, which clears the input without firing an `input` event).
			updateClearButton();
		}

		if ( typeof MutationObserver === 'function' ) {
			var observer = new MutationObserver( onFormChange );

			observer.observe( formEl, {
				attributes: true,
				attributeFilter: [ 'class' ],
				childList: true,
				subtree: true
			} );
		}

		// -- keyboard navigation (C3: direct focus, the engine's pattern) ----
		// The engine manages Escape (keyup) and has no arrow-key support;
		// this adds ArrowUp/ArrowDown only. Enter and Tab are native (the
		// options are focusable anchors).

		function getOptions() {
			var results = formEl.querySelector( '.ct-search-results' );

			if ( ! results || results === state.stateContainer ) {
				return [];
			}

			return Array.prototype.slice.call( results.querySelectorAll( 'a[role="option"]' ) );
		}

		function handleKeydown( e ) {
			if ( e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Escape' ) {
				return;
			}

			var options     = getOptions();
			var active      = document.activeElement;
			var activeIndex = -1;
			var i;

			for ( i = 0; i < options.length; i++ ) {
				if ( options[ i ] === active ) {
					activeIndex = i;
					break;
				}
			}

			if ( e.key === 'ArrowDown' ) {
				if ( ! options.length ) {
					return;
				}

				e.preventDefault();

				var next = activeIndex === -1 ? 0 : Math.min( activeIndex + 1, options.length - 1 );
				options[ next ].focus();
			} else if ( e.key === 'ArrowUp' ) {
				if ( activeIndex === -1 ) {
					return; // focus on the input: nothing above
				}

				e.preventDefault();

				if ( activeIndex === 0 ) {
					inputEl.focus();
				} else {
					options[ activeIndex - 1 ].focus();
				}
			} else if ( e.key === 'Escape' && activeIndex !== -1 ) {
				// Focus is on an option: return it to the input and let the
				// ENGINE's own keyup handler close the results (no logic
				// duplication).
				e.preventDefault();
				inputEl.focus();
				inputEl.dispatchEvent( new KeyboardEvent( 'keyup', { key: 'Escape', bubbles: true } ) );

				// Fallback: if the engine's handler did not close (it is
				// always mounted when options exist), close manually.
				window.setTimeout( function () {
					var current = formEl.querySelector( '.ct-search-results' );

					if ( ! current ) {
						return;
					}

					if ( current.parentNode ) {
						current.parentNode.removeChild( current );
					}

					setExpanded( false );
					inputEl.value = '';
				}, 0 );
			}
			// Escape on the input: the engine handles it (no handler here).
		}

		// -- init -------------------------------------------------------------

		function init() {
			if ( ! inputEl ) {
				return;
			}

			// Create the clear button (Phase 7.3).
			createClearButton();

			inputEl.addEventListener( 'input', function () {
				state.lastInputAt = Date.now();

				// A new search is starting — the waiting state is obsolete.
				if ( state.stateKind === 'waiting' ) {
					removeStateRow();
				}

				// Update the clear button visibility.
				updateClearButton();
			} );

			// Bound to the FORM (the common ancestor of the input AND the result
		// options), not the input alone: keydown bubbles, so this covers
		// ArrowUp/ArrowDown/Escape whether focus is on the input or on a
		// focused option (A3/A5). The handler keys off document.activeElement,
		// so the same logic serves both. The engine's own Escape handler stays
		// input-scoped (keyup) and is reused, never duplicated.
		formEl.addEventListener( 'keydown', handleKeydown );

			// The status div is pre-populated with the default "No results"
			// text in the HTML — apply the scoped override once at init.
			postProcessStatus();
		}

		return {
			init: init,
			lastInputAt: function () {
				return state.lastInputAt;
			},
			onRequestStarted: onRequestStarted,
			onRequestSucceeded: onRequestSucceeded,
			onRequestAborted: onRequestAborted,
			onRequestFailed: onRequestFailed
		};
	}

	// ---------------------------------------------------------------------
	// Boot
	// ---------------------------------------------------------------------

	function init() {
		installFetchWrapper();

		// Self-verification (Phase 2 §12): the wrapper must be installed.
		if ( ! ( window.fetch && window.fetch.__anishopSearchBox ) ) {
			// eslint-disable-next-line no-console
			console.warn( 'Anishop Search Box: the fetch wrapper could not be installed.' );
		}

		var wrappers = document.querySelectorAll( '.anishop-header-search' );

		for ( var i = 0; i < wrappers.length; i++ ) {
			var formEl = wrappers[ i ].querySelector( '.ct-search-form' );

			if ( ! formEl || initializedForms.has( formEl ) ) {
				continue;
			}

			initializedForms.add( formEl );

			var ctrl = createController( formEl );
			controllers.set( formEl, ctrl );
			ctrl.init();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
