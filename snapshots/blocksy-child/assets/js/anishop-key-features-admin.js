/**
 * Anishop Key Features — admin script (Phase 4C).
 *
 * Small, dependency-free helpers for the key-features repeater:
 *  - add a new row from the hidden <template>,
 *  - remove a row,
 *  - move a row up/down (the saved order is the DOM order on submit),
 *  - pre-fill rows from the product's non-variation attributes (each checked
 *    item appends an editable COPY of a row — never a live attribute ref).
 *
 * No drag-and-drop, no external libraries.
 */
( function () {
	'use strict';

	var list = document.getElementById( 'anishop-kf-items' );
	if ( ! list ) {
		return;
	}

	var template = document.getElementById( 'anishop-kf-item-template' );
	var addBtn = document.getElementById( 'anishop-kf-add' );
	var prefillToggle = document.getElementById( 'anishop-kf-prefill-toggle' );
	var prefillBox = document.getElementById( 'anishop-kf-prefill' );
	var i18n = window.anishopKeyFeaturesAdmin && window.anishopKeyFeaturesAdmin.i18n
		? window.anishopKeyFeaturesAdmin.i18n
		: { confirmRemove: 'Remove this row?' };

	function rows() {
		return Array.prototype.slice.call( list.querySelectorAll( 'li[data-anishop-kf-item]' ) );
	}

	/**
	 * Renumber the [N] index in every field name so the submitted order always
	 * matches the visible order.
	 */
	function renumber() {
		rows().forEach( function ( row, i ) {
			row.querySelectorAll( 'input[name]' ).forEach( function ( el ) {
				el.name = el.name.replace(
					/anishop_key_features\[[^\]]*\]/,
					'anishop_key_features[' + i + ']'
				);
			} );
		} );
	}

	function updateButtons() {
		var all = rows();
		all.forEach( function ( row, i ) {
			var up = row.querySelector( '.anishop-kf-admin__move[data-direction="up"]' );
			var down = row.querySelector( '.anishop-kf-admin__move[data-direction="down"]' );
			if ( up ) {
				up.disabled = i === 0;
			}
			if ( down ) {
				down.disabled = i === all.length - 1;
			}
		} );
	}

	/**
	 * Append a new row (from the template) with the given label/value.
	 */
	function appendRow( label, value ) {
		if ( ! template ) {
			return;
		}
		var frag = template.content.cloneNode( true );
		var row = frag.querySelector( 'li[data-anishop-kf-item]' );
		if ( ! row ) {
			return;
		}
		var labelInput = row.querySelector( 'input[name$="[label]"]' );
		var valueInput = row.querySelector( 'input[name$="[value]"]' );
		if ( labelInput ) {
			labelInput.value = label || '';
		}
		if ( valueInput ) {
			valueInput.value = value || '';
		}
		list.appendChild( frag );
		renumber();
		updateButtons();
	}

	list.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest ? event.target.closest( 'button' ) : null;
		if ( ! btn ) {
			return;
		}
		var row = btn.closest( 'li[data-anishop-kf-item]' );
		if ( ! row ) {
			return;
		}

		if ( btn.classList.contains( 'anishop-kf-admin__move' ) ) {
			var sibling = 'up' === btn.dataset.direction
				? row.previousElementSibling
				: row.nextElementSibling;
			if ( ! sibling || ! sibling.hasAttribute( 'data-anishop-kf-item' ) ) {
				return;
			}
			if ( 'up' === btn.dataset.direction ) {
				list.insertBefore( row, sibling );
			} else {
				list.insertBefore( sibling, row );
			}
			renumber();
			updateButtons();
		} else if ( btn.classList.contains( 'anishop-kf-admin__remove' ) ) {
			if ( window.confirm( i18n.confirmRemove ) ) {
				row.remove();
				renumber();
				updateButtons();
			}
		}
	} );

	if ( addBtn && template ) {
		addBtn.addEventListener( 'click', function () {
			appendRow( '', '' );
		} );
	}

	if ( prefillToggle && prefillBox ) {
		prefillToggle.addEventListener( 'click', function () {
			prefillBox.hidden = ! prefillBox.hidden;
		} );

		prefillBox.addEventListener( 'change', function ( event ) {
			var target = event.target;
			if ( ! target.classList || ! target.classList.contains( 'anishop-kf-admin__prefill-check' ) ) {
				return;
			}
			if ( target.checked ) {
				appendRow( target.dataset.label, target.dataset.value );
				target.checked = false;
			}
		} );
	}

	updateButtons();
} )();
