/**
 * Anishop Buy Box — admin script (Stage 3).
 *
 * Small, dependency-free helpers for the Buy Box rows repeater. The SAME
 * script powers both the global rows (in the «کانتینر Buy Box» tab) and the
 * product-specific rows (in the WooCommerce product-edit Advanced tab):
 *  - add a new row from the hidden <template>,
 *  - remove a row,
 *  - move a row up/down (the saved order is the DOM order on submit),
 *  - enable/disable a row (toggles a visual state).
 *
 * The repeater is generic: each `.anishop-bb-rows-admin` container has a list
 * (`.anishop-bb-rows-admin__list`) whose `data-anishop-bb-rows-base` attribute
 * holds the field-name base (e.g. `anishop_buy_box[rows]` or
 * `anishop_buy_box_rows`). On reorder, the `[N]` index in every field name is
 * renumbered so the submitted order always matches the visible order.
 *
 * No drag-and-drop, no external libraries.
 */
( function () {
	'use strict';

	var containers = Array.prototype.slice.call( document.querySelectorAll( '.anishop-bb-rows-admin' ) );
	if ( ! containers.length ) {
		return;
	}

	var i18n = window.anishopBuyBoxAdmin && window.anishopBuyBoxAdmin.i18n
		? window.anishopBuyBoxAdmin.i18n
		: { confirmRemove: 'Remove this row?' };

	function rows( list ) {
		return Array.prototype.slice.call( list.querySelectorAll( 'li[data-anishop-bb-item]' ) );
	}

	/**
	 * Replace the `[N]` index in a field name (the part right after the base
	 * name) with `newIndex`.
	 */
	function reindexName( name, base, newIndex ) {
		var prefix = base + '[';
		var start = name.indexOf( prefix );
		if ( -1 === start ) {
			return name;
		}
		var afterPrefix = start + prefix.length;
		var end = name.indexOf( ']', afterPrefix );
		if ( -1 === end ) {
			return name;
		}
		return name.substring( 0, afterPrefix ) + newIndex + name.substring( end );
	}

	/**
	 * Renumber the `[N]` index in every field name so the submitted order
	 * always matches the visible order.
	 */
	function renumber( list ) {
		var base = list.getAttribute( 'data-anishop-bb-rows-base' ) || '';
		rows( list ).forEach( function ( row, i ) {
			row.querySelectorAll( 'input[name], select[name]' ).forEach( function ( el ) {
				el.name = reindexName( el.name, base, i );
			} );
		} );
	}

	function updateButtons( list ) {
		var all = rows( list );
		all.forEach( function ( row, i ) {
			var up = row.querySelector( '.anishop-bb-admin__move[data-direction="up"]' );
			var down = row.querySelector( '.anishop-bb-admin__move[data-direction="down"]' );
			if ( up ) {
				up.disabled = i === 0;
			}
			if ( down ) {
				down.disabled = i === all.length - 1;
			}
		} );
	}

	/**
	 * Append a new row (from the template) with empty fields.
	 */
	function appendRow( list, template ) {
		if ( ! template ) {
			return;
		}
		var frag = template.content.cloneNode( true );
		var row = frag.querySelector( 'li[data-anishop-bb-item]' );
		if ( ! row ) {
			return;
		}
		// Replace the __INDEX__ placeholder with a provisional index; renumber()
		// below makes the final index sequential.
		var nextIndex = rows( list ).length;
		row.querySelectorAll( 'input[name], select[name]' ).forEach( function ( el ) {
			el.name = el.name.replace( /__INDEX__/g, String( nextIndex ) );
		} );
		list.appendChild( frag );
		renumber( list );
		updateButtons( list );
	}

	containers.forEach( function ( container ) {
		var list = container.querySelector( '.anishop-bb-rows-admin__list' );
		var template = container.querySelector( 'template[data-anishop-bb-template]' );
		var addBtn = container.querySelector( '.anishop-bb-admin__add' );
		if ( ! list ) {
			return;
		}

		list.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest ? event.target.closest( 'button' ) : null;
			if ( ! btn ) {
				return;
			}
			var row = btn.closest( 'li[data-anishop-bb-item]' );
			if ( ! row ) {
				return;
			}

			if ( btn.classList.contains( 'anishop-bb-admin__move' ) ) {
				var sibling = 'up' === btn.dataset.direction
					? row.previousElementSibling
					: row.nextElementSibling;
				if ( ! sibling || ! sibling.hasAttribute( 'data-anishop-bb-item' ) ) {
					return;
				}
				if ( 'up' === btn.dataset.direction ) {
					list.insertBefore( row, sibling );
				} else {
					list.insertBefore( sibling, row );
				}
				renumber( list );
				updateButtons( list );
			} else if ( btn.classList.contains( 'anishop-bb-admin__remove' ) ) {
				if ( window.confirm( i18n.confirmRemove ) ) {
					row.remove();
					renumber( list );
					updateButtons( list );
				}
			}
		} );

		// Enable/disable toggle (visual state only — the checkbox value is what
		// is submitted).
		list.addEventListener( 'change', function ( event ) {
			var target = event.target;
			if ( ! target.classList || ! target.classList.contains( 'anishop-bb-admin__enabled' ) ) {
				return;
			}
			var row = target.closest( 'li[data-anishop-bb-item]' );
			if ( row ) {
				row.classList.toggle( 'anishop-bb-admin__row--disabled', ! target.checked );
			}
		} );

		if ( addBtn && template ) {
			addBtn.addEventListener( 'click', function () {
				appendRow( list, template );
			} );
		}

		updateButtons( list );
	} );
} )();
