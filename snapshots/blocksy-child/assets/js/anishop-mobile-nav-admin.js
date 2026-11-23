/**
 * Anishop mobile bottom navbar — admin script (B6-15 Part 2, Step 2).
 *
 * Small, dependency-free helpers for the item editor:
 *  - move items up/down (the saved order is the DOM order on submit),
 *  - add a new custom item from the hidden <template>,
 *  - remove a custom item,
 *  - show/hide the URL field when a custom item's type changes,
 *  - keep the icon-picker selection highlight in sync.
 *
 * No drag-and-drop, no external libraries.
 */
( function () {
	'use strict';

	var list = document.getElementById( 'anishop-mnav-items' );
	if ( ! list ) {
		return;
	}

	var template = document.getElementById( 'anishop-mnav-item-template' );
	var addBtn = document.getElementById( 'anishop-mnav-add-item' );
	var i18n = window.anishopMobileNavAdmin && window.anishopMobileNavAdmin.i18n
		? window.anishopMobileNavAdmin.i18n
		: { confirmRemove: 'Remove this item?' };

	function rows() {
		return Array.prototype.slice.call( list.querySelectorAll( 'li[data-anishop-mnav-item]' ) );
	}

	/**
	 * Renumber the [items][N] index in every field name so the submitted
	 * order always matches the visible order.
	 */
	function renumber() {
		rows().forEach( function ( row, i ) {
			row.querySelectorAll( 'input[name], select[name]' ).forEach( function ( el ) {
				el.name = el.name.replace(
					/anishop_mobile_nav\[items\]\[[^\]]*\]/,
					'anishop_mobile_nav[items][' + i + ']'
				);
			} );
		} );
	}

	function updateButtons() {
		var all = rows();
		all.forEach( function ( row, i ) {
			var up = row.querySelector( '.anishop-mnav-admin__move[data-direction="up"]' );
			var down = row.querySelector( '.anishop-mnav-admin__move[data-direction="down"]' );
			if ( up ) {
				up.disabled = i === 0;
			}
			if ( down ) {
				down.disabled = i === all.length - 1;
			}
		} );
	}

	function nextCustomId() {
		var max = 0;
		rows().forEach( function ( row ) {
			var idInput = row.querySelector( 'input[name$="[id]"]' );
			if ( idInput && /^custom-(\d+)$/.test( idInput.value ) ) {
				max = Math.max( max, parseInt( idInput.value.replace( 'custom-', '' ), 10 ) );
			}
		} );
		return 'custom-' + ( max + 1 );
	}

	/**
	 * The URL field is only active (visible + submitted) for `link` items.
	 */
	function syncUrlField( row ) {
		var typeSelect = row.querySelector( '.anishop-mnav-admin__type' );
		var urlField = row.querySelector( '.anishop-mnav-admin__url-field' );
		if ( ! typeSelect || ! urlField ) {
			return;
		}
		var isLink = 'link' === typeSelect.value;
		urlField.classList.toggle( 'is-hidden', ! isLink );
		var input = urlField.querySelector( 'input' );
		if ( input ) {
			input.disabled = ! isLink;
		}
	}

	function syncIconHighlight( row ) {
		row.querySelectorAll( '.anishop-mnav-admin__icon-option' ).forEach( function ( option ) {
			var radio = option.querySelector( 'input[type="radio"]' );
			option.classList.toggle( 'is-selected', ! ! ( radio && radio.checked ) );
		} );
	}

	list.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest ? event.target.closest( 'button' ) : null;
		if ( ! btn ) {
			return;
		}
		var row = btn.closest( 'li[data-anishop-mnav-item]' );
		if ( ! row ) {
			return;
		}

		if ( btn.classList.contains( 'anishop-mnav-admin__move' ) ) {
			var sibling = 'up' === btn.dataset.direction
				? row.previousElementSibling
				: row.nextElementSibling;
			if ( ! sibling || ! sibling.hasAttribute( 'data-anishop-mnav-item' ) ) {
				return;
			}
			if ( 'up' === btn.dataset.direction ) {
				list.insertBefore( row, sibling );
			} else {
				list.insertBefore( sibling, row );
			}
			renumber();
			updateButtons();
		} else if ( btn.classList.contains( 'anishop-mnav-admin__remove' ) ) {
			if ( window.confirm( i18n.confirmRemove ) ) {
				row.remove();
				renumber();
				updateButtons();
			}
		}
	} );

	list.addEventListener( 'change', function ( event ) {
		var target = event.target;
		if ( ! target.classList ) {
			return;
		}
		var row = target.closest( 'li[data-anishop-mnav-item]' );
		if ( ! row ) {
			return;
		}
		if ( target.classList.contains( 'anishop-mnav-admin__type' ) ) {
			syncUrlField( row );
		}
		if ( 'radio' === target.type && target.name.indexOf( '[icon]' ) !== -1 ) {
			syncIconHighlight( row );
		}
	} );

	if ( addBtn && template ) {
		addBtn.addEventListener( 'click', function () {
			var frag = template.content.cloneNode( true );
			var row = frag.querySelector( 'li[data-anishop-mnav-item]' );
			if ( ! row ) {
				return;
			}
			var idInput = row.querySelector( 'input[name$="[id]"]' );
			if ( idInput ) {
				idInput.value = nextCustomId();
			}
			list.appendChild( frag );
			renumber();
			updateButtons();
			syncUrlField( row );
			syncIconHighlight( row );
		} );
	}

	updateButtons();
} )();
