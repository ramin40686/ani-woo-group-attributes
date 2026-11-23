/**
 * Anishop homepage — product carousel Previous/Next navigation.
 *
 * Lightweight, scoped to the four front-page Product Collection
 * carousels only (body.home + .wp-block-woocommerce-product-collection).
 *
 * - Adds a Previous/Next button pair below each horizontal product track.
 * - The buttons scroll ONLY their own track via native scrollBy(); the
 *   direction is derived from the live DOM (RTL-aware), not assumed.
 * - Native touch / track scrolling is untouched — the buttons are an
 *   additional navigation mechanism, not a replacement.
 * - Buttons are real <button> elements: keyboard focusable, operable with
 *   Enter/Space, with a visible focus state. Product links and Add-to-Cart
 *   controls stay in the tab order; no focus is trapped.
 * - The disabled state reflects the current scroll position and is updated
 *   on the native scroll event (no polling, no timers, no rAF loops).
 * - No mouse-drag, no autoplay, no carousel library, no new dependency.
 */
( function () {
	'use strict';

	if ( ! document.body.classList.contains( 'home' ) ) {
		return;
	}

	var tracks = Array.prototype.slice.call(
		document.querySelectorAll(
			'.wp-block-woocommerce-product-collection .wc-block-product-template'
		)
	);
	if ( ! tracks.length ) {
		return;
	}

	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	/* ------------------------------------------------------------------
	 * RTL-aware scroll geometry
	 * ------------------------------------------------------------------
	 * Chrome/Firefox report a NEGATIVE scrollLeft in RTL: 0 at the visual
	 * start (first products) and -max at the visual end. LTR is 0..max.
	 * We normalize to 0..max (0 = start, max = end) so the disabled logic
	 * and the step direction are direction-agnostic.
	 */
	function isRTL( track ) {
		return 'rtl' === getComputedStyle( track ).direction;
	}

	function maxScroll( track ) {
		return track.scrollWidth - track.clientWidth;
	}

	function scrollPos( track ) {
		var max = maxScroll( track );
		if ( max <= 0 ) {
			return 0;
		}
		return isRTL( track ) ? -track.scrollLeft : track.scrollLeft;
	}

	/* One card + the inter-card gap, measured from the live DOM so the
	 * step stays correct at every viewport width. */
	function stepSize( track ) {
		var card = track.querySelector( '.wc-block-product' );
		if ( ! card ) {
			return track.clientWidth * 0.8;
		}
		var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;
		return card.getBoundingClientRect().width + gap;
	}

	function scrollByStep( track, dir ) {
		// dir: +1 = Next (toward the visual end), -1 = Previous (toward
		// the visual start). In RTL the end is to the left, so the raw
		// scrollLeft delta is negated.
		var step = stepSize( track );
		var delta = isRTL( track ) ? -dir * step : dir * step;
		track.scrollBy( {
			left: delta,
			behavior: reduceMotion.matches ? 'auto' : 'smooth'
		} );
	}

	function updateState( track, prevBtn, nextBtn ) {
		var max = maxScroll( track );
		if ( max <= 0 ) {
			prevBtn.disabled = true;
			nextBtn.disabled = true;
			return;
		}
		var pos = scrollPos( track );
		prevBtn.disabled = pos <= 1;
		nextBtn.disabled = pos >= max - 1;
	}

	/* ------------------------------------------------------------------
	 * Controls markup
	 * ------------------------------------------------------------------
	 * The site is RTL: the visual start is on the right and the visual end
	 * on the left. "Previous" points back toward the start (right chevron),
	 * "Next" points forward toward the end (left chevron).
	 */
	function chevron( points ) {
		return (
			'<svg class="anishop-carousel-controls__icon" viewBox="0 0 24 24" ' +
			'width="20" height="20" fill="none" stroke="currentColor" ' +
			'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' +
			'aria-hidden="true" focusable="false">' +
			'<polyline points="' + points + '"></polyline></svg>'
		);
	}

	var PREV_ICON = chevron( '9 18 15 12 9 6' );  // ">" points right
	var NEXT_ICON = chevron( '15 18 9 12 15 6' ); // "<" points left

	Array.prototype.forEach.call( tracks, function ( track ) {
		var controls = document.createElement( 'div' );
		controls.className = 'anishop-carousel-controls';

		var prevBtn = document.createElement( 'button' );
		prevBtn.type = 'button';
		prevBtn.className = 'anishop-carousel-controls__btn anishop-carousel-controls__btn--prev';
		prevBtn.setAttribute( 'aria-label', 'محصولات قبلی' );
		prevBtn.innerHTML = PREV_ICON;

		var nextBtn = document.createElement( 'button' );
		nextBtn.type = 'button';
		nextBtn.className = 'anishop-carousel-controls__btn anishop-carousel-controls__btn--next';
		nextBtn.setAttribute( 'aria-label', 'محصولات بعدی' );
		nextBtn.innerHTML = NEXT_ICON;

		controls.appendChild( prevBtn );
		controls.appendChild( nextBtn );
		track.insertAdjacentElement( 'afterend', controls );

		prevBtn.addEventListener( 'click', function () {
			scrollByStep( track, -1 );
		} );
		nextBtn.addEventListener( 'click', function () {
			scrollByStep( track, 1 );
		} );

		track.addEventListener( 'scroll', function () {
			updateState( track, prevBtn, nextBtn );
		}, { passive: true } );

		window.addEventListener( 'resize', function () {
			updateState( track, prevBtn, nextBtn );
		} );

		updateState( track, prevBtn, nextBtn );
	} );
} )();
