/**
 * Anishop inline variations — frontend renderer (Phase 4B).
 *
 * Presentation-only layer over the native WooCommerce variation form:
 *
 *  - The native `<select>` elements remain the source of truth in the DOM.
 *  - Inline controls (chip / radio / swatch) are rendered from each select's
 *    live `<option>` list and are a visual mirror of it.
 *  - Activating an inline control sets the native select's value and triggers
 *    its native `change` event; WooCommerce's own add-to-cart-variation.js
 *    then runs its full flow (matching, price, stock, SKU, image, validation,
 *    add-to-cart). No variation logic is re-implemented here.
 *  - Selected / unavailable state is mirrored FROM the native select (which
 *    WooCommerce rewrites as the user changes attributes) via the native
 *    events: woocommerce_variation_has_changed,
 *    woocommerce_update_variation_values, reset_data.
 *
 * Keyboard model (WAI-ARIA APG radiogroup): one tab stop per group (roving
 * tabindex), arrow keys move within the group (direction follows the visual
 * RTL/LTR order), Space/Enter activates. Unavailable controls stay focusable;
 * activating one announces «این گزینه در دسترس نیست» and changes nothing.
 *
 * Progressive enhancement: the native select is visually hidden only after a
 * group renders successfully. If JS is disabled or rendering fails, the native
 * dropdowns remain fully visible and usable.
 *
 * @package Anishop
 */
( function ( $ ) {
	'use strict';

	var config = window.anishopInlineVariations;

	if ( ! config || ! $.isPlainObject( config.selects ) || $.isEmptyObject( config.selects ) ) {
		return; // Nothing to render (feature off or no configured selects).
	}

	// Normalize the config values (wp_localize_script may serialize numbers /
	// booleans as strings; the JS must not rely on the wire type).
	config.size = parseInt( config.size, 10 );
	if ( isNaN( config.size ) ) {
		config.size = 32;
	}
	config.spacing = parseInt( config.spacing, 10 );
	if ( isNaN( config.spacing ) ) {
		config.spacing = 0;
	}
	config.show_selected = ( config.show_selected === true || config.show_selected === '1' || config.show_selected === 1 );

	var NEUTRAL_HEX = '#E1E8ED';
	var UNAVAILABLE_MSG = 'این گزینه در دسترس نیست';

	/* ---------------------------------------------------------------------
	 * Utilities
	 * ------------------------------------------------------------------- */

	/**
	 * Escape a string for safe insertion into an HTML attribute.
	 *
	 * @param {string} value Raw value.
	 * @return {string}
	 */
	function escapeAttr( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	/**
	 * Determine whether an element renders right-to-left.
	 *
	 * @param {jQuery} $el Element to check.
	 * @return {boolean}
	 */
	function isRTL( $el ) {
		var dir = ( $el.attr( 'dir' ) || '' ).toLowerCase();
		if ( 'rtl' === dir || 'ltr' === dir ) {
			return 'rtl' === dir;
		}
		var computed = $el.css( 'direction' );
		if ( 'rtl' === computed || 'ltr' === computed ) {
			return 'rtl' === computed;
		}
		var htmlDir = ( $( 'html' ).attr( 'dir' ) || '' ).toLowerCase();
		if ( 'rtl' === htmlDir || 'ltr' === htmlDir ) {
			return 'rtl' === htmlDir;
		}
		return 'rtl' === $( 'html' ).css( 'direction' );
	}

	/**
	 * Announce a message through the form's live region (created once per form).
	 *
	 * @param {jQuery} $form   The variations form.
	 * @param {string} message Message to announce.
	 */
	function announce( $form, message ) {
		var $live = $form.find( '.anishop-inline__live' );
		if ( ! $live.length ) {
			$live = $( '<div class="anishop-inline__live" role="status" aria-live="polite"></div>' );
			$form.append( $live );
		}
		$live.text( '' );
		// A short delay ensures screen readers re-announce repeated text.
		window.setTimeout( function () {
			$live.text( message );
		}, 50 );
	}

	/* ---------------------------------------------------------------------
	 * Form initialization
	 * ------------------------------------------------------------------- */

	/**
	 * Initialize the inline renderer for one variations form.
	 *
	 * @param {jQuery} $form The `form.variations_form` element.
	 */
	function initForm( $form ) {
		if ( $form.data( 'anishopInlineInit' ) ) {
			return;
		}
		$form.data( 'anishopInlineInit', true );

		// Apply global presentation settings as CSS custom properties.
		$form
			.css( '--anishop-inline-spacing', config.spacing + 'px' )
			.css( '--anishop-inline-swatch-size', config.size + 'px' );
		if ( 'square' === config.shape ) {
			$form.addClass( 'anishop-inline--square' );
		}

		$form.find( '.variations select' ).each( function () {
			var $select = $( this );
			var key = $select.attr( 'data-attribute_name' );
			if ( ! key || ! config.selects[ key ] ) {
				return; // No config for this select: leave it native.
			}
			var selectConfig = config.selects[ key ];
			if ( 'dropdown' === selectConfig.renderer ) {
				return; // Resolved renderer is the native dropdown.
			}
			renderGroup( $form, $select, selectConfig );
		} );

		// State reflection: mirror the native select state onto the controls.
		$form.on( 'woocommerce_variation_has_changed', onVariationChanged );
		$form.on( 'woocommerce_update_variation_values', onUpdateVariationValues );
		$form.on( 'reset_data', onResetData );
	}

	/**
	 * Render the inline control group for one select (in-place wrap).
	 *
	 * @param {jQuery} $form        The variations form.
	 * @param {jQuery} $select      The native select.
	 * @param {Object} selectConfig Per-select config (renderer, label, options).
	 */
	function renderGroup( $form, $select, selectConfig ) {
		var renderer = selectConfig.renderer;
		var label = selectConfig.label || '';

		var $wrapper = $( '<div class="anishop-inline" data-anishop-renderer="' + renderer + '"></div>' );
		$select.wrap( $wrapper );
		$wrapper = $select.parent( '.anishop-inline' );

		var $controls = $(
			'<div class="anishop-inline__controls anishop-inline__' + renderer +
			'" role="radiogroup" aria-label="' + escapeAttr( label ) + '"></div>'
		);
		$wrapper.append( $controls );

		buildControls( $select, $controls, selectConfig );

		// Visually hide the native select only after a successful render.
		// It stays in the DOM as the source of truth for WooCommerce.
		$select
			.addClass( 'anishop-inline__native-hidden' )
			.attr( 'aria-hidden', 'true' )
			.attr( 'tabindex', '-1' );

		$wrapper.data( 'anishopSelect', $select );
		$wrapper.data( 'anishopConfig', selectConfig );

		wireInteraction( $form, $select, $controls );

		// Initial state sync (selected option + availability).
		syncGroup( $select, $controls );
	}

	/**
	 * Build the control elements from the select's current options.
	 *
	 * @param {jQuery} $select      The native select.
	 * @param {jQuery} $controls    The controls container.
	 * @param {Object} selectConfig Per-select config.
	 */
	function buildControls( $select, $controls, selectConfig ) {
		$controls.empty();
		$select.find( 'option' ).each( function () {
			var $option = $( this );
			var value = $option.val();
			if ( '' === value ) {
				return; // Skip the placeholder option.
			}
			$controls.append( createControl( value, $option.text(), selectConfig ) );
		} );
	}

	/**
	 * Create one control element (role="radio") for an option.
	 *
	 * @param {string} value        The option value.
	 * @param {string} text         The option text.
	 * @param {Object} selectConfig Per-select config.
	 * @return {jQuery}
	 */
	function createControl( value, text, selectConfig ) {
		var renderer = selectConfig.renderer;
		var $control = $(
			'<button type="button" role="radio" class="anishop-inline__control anishop-inline__' +
			renderer + '-item" data-value="' + escapeAttr( value ) +
			'" aria-checked="false" aria-disabled="false" tabindex="-1"></button>'
		);
		$control.attr( 'aria-label', text );
		$control.attr( 'title', text );

		if ( 'swatch' === renderer ) {
			var hex = '';
			if ( selectConfig.options && selectConfig.options[ value ] ) {
				hex = selectConfig.options[ value ].hex || '';
			}
			if ( hex ) {
				$control.css( 'background-color', hex );
			} else {
				// Missing / invalid HEX: neutral swatch (safe failure).
				$control.addClass( 'anishop-inline__swatch-neutral' );
				if ( 'square' === config.shape ) {
					// Square swatches with a missing HEX show the label inside.
					$control.text( text );
					$control.addClass( 'anishop-inline__swatch-label' );
				}
			}
		} else {
			$control.text( text );
		}

		return $control;
	}

	/* ---------------------------------------------------------------------
	 * Interaction (user -> native select)
	 * ------------------------------------------------------------------- */

	/**
	 * Wire click + keyboard interaction for one control group.
	 *
	 * @param {jQuery} $form     The variations form.
	 * @param {jQuery} $select   The native select.
	 * @param {jQuery} $controls The controls container.
	 */
	function wireInteraction( $form, $select, $controls ) {
		// Activation (mouse / touch / keyboard).
		$controls.on( 'click', '.anishop-inline__control', function () {
			var $control = $( this );
			if ( 'true' === $control.attr( 'aria-disabled' ) ) {
				announce( $form, UNAVAILABLE_MSG );
				return; // Unavailable: no change, announce only.
			}
			activateControl( $form, $select, $control );
		} );

		// Keyboard: arrows move within the group, Space/Enter activate.
		$controls.on( 'keydown', '.anishop-inline__control', function ( event ) {
			var $control = $( this );
			var key = event.key;
			var rtl = isRTL( $controls );

			if ( 'ArrowRight' === key || 'ArrowDown' === key ) {
				event.preventDefault();
				focusSibling( $controls, $control, rtl ? -1 : 1 );
			} else if ( 'ArrowLeft' === key || 'ArrowUp' === key ) {
				event.preventDefault();
				focusSibling( $controls, $control, rtl ? 1 : -1 );
			} else if ( ' ' === key || 'Enter' === key ) {
				event.preventDefault();
				$control.trigger( 'click' );
			}
		} );
	}

	/**
	 * Activate a control: set the native select value + trigger its change.
	 *
	 * The native WooCommerce handler then runs its full flow (check
	 * variations -> update values -> show/hide variation). The inline UI only
	 * mirrors the resulting state (via the native events).
	 *
	 * @param {jQuery} $form    The variations form.
	 * @param {jQuery} $select  The native select.
	 * @param {jQuery} $control The activated control.
	 */
	function activateControl( $form, $select, $control ) {
		var value = $control.data( 'value' );
		if ( $select.val() === value ) {
			return; // Already selected: nothing to do.
		}
		// The verified delegation mechanism: native select + native change.
		$select.val( value ).trigger( 'change' );
	}

	/**
	 * Move focus to the next/previous control within the group (wrapping).
	 *
	 * @param {jQuery} $controls The controls container.
	 * @param {jQuery} $current  The currently focused control.
	 * @param {number} direction 1 = next in DOM order, -1 = previous.
	 */
	function focusSibling( $controls, $current, direction ) {
		var $items = $controls.find( '.anishop-inline__control' );
		var index = $items.index( $current );
		if ( -1 === index || 0 === $items.length ) {
			return;
		}
		var nextIndex = ( index + direction + $items.length ) % $items.length;
		$items.eq( nextIndex ).trigger( 'focus' );
	}

	/* ---------------------------------------------------------------------
	 * State reflection (native select -> inline controls)
	 * ------------------------------------------------------------------- */

	/**
	 * Sync one group's controls from its native select:
	 * presence, disabled state, and selected state.
	 *
	 * @param {jQuery} $select   The native select.
	 * @param {jQuery} $controls The controls container.
	 */
	function syncGroup( $select, $controls ) {
		var currentOptions = {};
		$select.find( 'option' ).each( function () {
			var value = $( this ).val();
			if ( '' === value ) {
				return;
			}
			currentOptions[ value ] = {
				disabled: $( this ).is( ':disabled' ),
				selected: $( this ).is( ':selected' )
			};
		} );

		$controls.find( '.anishop-inline__control' ).each( function () {
			var $control = $( this );
			var value = $control.data( 'value' );

			if ( ! Object.prototype.hasOwnProperty.call( currentOptions, value ) ) {
				// The option was removed by WooCommerce (no matching
				// variation): hide the control, keep it in the DOM.
				$control.addClass( 'anishop-inline__control-removed' );
				$control.attr( 'tabindex', '-1' );
				return;
			}

			$control.removeClass( 'anishop-inline__control-removed' );
			var state = currentOptions[ value ];
			$control.attr( 'aria-checked', state.selected ? 'true' : 'false' );
			$control.toggleClass( 'anishop-inline__control-selected', state.selected );
			$control.attr( 'aria-disabled', state.disabled ? 'true' : 'false' );
			$control.toggleClass( 'anishop-inline__control-unavailable', state.disabled );
		} );

		updateRovingTabindex( $controls );
	}

	/**
	 * Roving tabindex: exactly one tab stop per group — the selected control,
	 * or the first available one when nothing is selected.
	 *
	 * @param {jQuery} $controls The controls container.
	 */
	function updateRovingTabindex( $controls ) {
		var $items = $controls.find( '.anishop-inline__control' );
		var $target = $items.filter( '.anishop-inline__control-selected' ).first();
		if ( ! $target.length ) {
			$target = $items
				.not( '.anishop-inline__control-removed' )
				.filter( function () {
					return 'true' !== $( this ).attr( 'aria-disabled' );
				} )
				.first();
		}
		if ( ! $target.length ) {
			$target = $items.first();
		}
		$items.attr( 'tabindex', '-1' );
		$target.attr( 'tabindex', '0' );
	}

	/**
	 * Native event: a variation was (re)selected. Re-read every select's value
	 * and update the selected state on the controls + the selected line.
	 *
	 * @param {jQuery.Event} event Native event.
	 */
	function onVariationChanged( event ) {
		var $form = $( event.currentTarget );
		$form.find( '.anishop-inline' ).each( function () {
			var $wrapper = $( this );
			var $select = $wrapper.data( 'anishopSelect' );
			var $controls = $wrapper.find( '.anishop-inline__controls' );
			if ( $select && $select.length && $controls.length ) {
				syncGroup( $select, $controls );
			}
		} );
		updateSelectedLine( $form );
	}

	/**
	 * Native event: WooCommerce rewrote the selects' options (availability).
	 * Rebuild each group's availability from the live option list.
	 *
	 * @param {jQuery.Event} event Native event.
	 */
	function onUpdateVariationValues( event ) {
		var $form = $( event.currentTarget );
		$form.find( '.anishop-inline' ).each( function () {
			var $wrapper = $( this );
			var $select = $wrapper.data( 'anishopSelect' );
			var $controls = $wrapper.find( '.anishop-inline__controls' );
			if ( $select && $select.length && $controls.length ) {
				syncGroup( $select, $controls );
			}
		} );
	}

	/**
	 * Native event: the reset link cleared all selects. Clear the selected
	 * states and the selected-value line.
	 *
	 * @param {jQuery.Event} event Native event.
	 */
	function onResetData( event ) {
		var $form = $( event.currentTarget );
		$form.find( '.anishop-inline' ).each( function () {
			var $wrapper = $( this );
			var $select = $wrapper.data( 'anishopSelect' );
			var $controls = $wrapper.find( '.anishop-inline__controls' );
			if ( $select && $select.length && $controls.length ) {
				syncGroup( $select, $controls );
			}
		} );
		updateSelectedLine( $form );
	}

	/* ---------------------------------------------------------------------
	 * Selected value line (show_selected)
	 * ------------------------------------------------------------------- */

	/**
	 * Update the selected-value line under the inline controls.
	 *
	 * Reads the native selects' current values (never computed independently).
	 * Only attributes that have an inline group are shown. Hidden when nothing
	 * is selected (e.g. after a reset) or when `show_selected` is off.
	 *
	 * @param {jQuery} $form The variations form.
	 */
	function updateSelectedLine( $form ) {
		if ( ! config.show_selected ) {
			return;
		}

		var $line = $form.find( '.anishop-inline__selected' );
		if ( ! $line.length ) {
			$line = $( '<p class="anishop-inline__selected" aria-live="polite"></p>' );
			// Insert after the variations table (inside the form).
			$form.find( 'table.variations' ).first().after( $line );
		}

		var parts = [];
		$form.find( '.anishop-inline' ).each( function () {
			var $wrapper = $( this );
			var $select = $wrapper.data( 'anishopSelect' );
			var selectConfig = $wrapper.data( 'anishopConfig' );
			if ( ! $select || ! $select.length || ! selectConfig ) {
				return;
			}
			var value = $select.val();
			if ( ! value ) {
				return; // Unselected attributes are omitted.
			}
			var $option = $select.find( 'option' ).filter( function () {
				return $( this ).val() === value;
			} );
			var text = $option.length ? $option.text() : value;
			parts.push( ( selectConfig.label ? selectConfig.label + ': ' : '' ) + text );
		} );

		if ( parts.length ) {
			$line.text( parts.join( ' · ' ) ).show();
		} else {
			$line.text( '' ).hide();
		}
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */

	$( function () {
		$( '.variations_form' ).each( function () {
			initForm( $( this ) );
		} );
	} );

	// Namespaced handle for controlled testing (no other globals touched).
	window.AnishopInlineVariations = {
		initForm: initForm
	};
} )( jQuery );