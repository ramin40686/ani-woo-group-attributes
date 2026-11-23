( function () {
	'use strict';

	const AUTOPLAY_MS = 5000;
	const STATE_CLASSES = [
		'is-prev-2',
		'is-prev-1',
		'is-active',
		'is-next-1',
		'is-next-2',
		'is-far',
	];

	function init( root ) {
		if ( root.dataset.anishopHeroInitialized === 'true' ) {
			return;
		}

		root.dataset.anishopHeroInitialized = 'true';

		const slides = Array.from(
			root.querySelectorAll( '[data-slide-index]' )
		);
		const stage = root.querySelector( '.anishop-hero-slider__track' );
		const prevButton = root.querySelector( '.anishop-hero-slider__nav--prev' );
		const nextButton = root.querySelector( '.anishop-hero-slider__nav--next' );
		const pagination = root.querySelector( '.anishop-hero-slider__pagination' );
		const playPause = root.querySelector( '.anishop-hero-slider__playpause' );
		const live = root.querySelector( '.anishop-hero-slider__live' );

		const count = slides.length;

		if ( ! count ) {
			return;
		}

		let activeIndex = 0;
		let timer = null;
		let paused = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;
		let hovering = false;
		let focusing = false;
		let pointerStartX = 0;
		let pointerStartY = 0;

		function circularDistance( index ) {
			let distance = index - activeIndex;

			if ( distance > count / 2 ) {
				distance -= count;
			}

			if ( distance < -count / 2 ) {
				distance += count;
			}

			return distance;
		}

		function stateForDistance( distance ) {
			if ( distance === 0 ) {
				return 'is-active';
			}

			if ( distance === 1 ) {
				return 'is-next-1';
			}

			if ( distance === -1 ) {
				return 'is-prev-1';
			}

			if ( distance === 2 ) {
				return 'is-next-2';
			}

			if ( distance === -2 ) {
				return 'is-prev-2';
			}

			return 'is-far';
		}

		function render() {
			slides.forEach( function ( slide, index ) {
				const distance = circularDistance( index );
				const state = stateForDistance( distance );
				const isActive = distance === 0;

				STATE_CLASSES.forEach( function ( className ) {
					slide.classList.remove( className );
				} );

				slide.classList.add( state );
				slide.setAttribute(
					'aria-hidden',
					isActive ? 'false' : 'true'
				);

				slide.querySelectorAll( 'a, button' ).forEach(
					function ( element ) {
						element.tabIndex = isActive ? 0 : -1;
					}
				);
			} );

			if ( pagination ) {
				pagination
					.querySelectorAll( '.anishop-hero-slider__dot' )
					.forEach( function ( dot, index ) {
						const isActive = index === activeIndex;
						dot.classList.toggle(
							'is-active',
							isActive
						);
						dot.setAttribute(
							'aria-current',
							isActive ? 'true' : 'false'
						);
					} );
			}

			if ( live ) {
				live.textContent =
					'اسلاید ' +
					( activeIndex + 1 ) +
					' از ' +
					count;
			}
		}

		function stopTimer() {
			if ( timer !== null ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function canAutoplay() {
			return (
				count > 1 &&
				! paused &&
				! hovering &&
				! focusing &&
				! document.hidden
			);
		}

		function restartTimer() {
			stopTimer();

			if ( canAutoplay() ) {
				timer = window.setInterval(
					function () {
						goTo( activeIndex + 1 );
					},
					AUTOPLAY_MS
				);
			}
		}

		function goTo( index ) {
			activeIndex =
				( ( index % count ) + count ) % count;

			render();
			restartTimer();
		}

		function next() {
			goTo( activeIndex + 1 );
		}

		function previous() {
			goTo( activeIndex - 1 );
		}

		if ( count === 1 ) {
			root.classList.add(
				'anishop-hero-slider--single'
			);
		}

		if ( pagination && count > 1 ) {
			slides.forEach( function ( _, index ) {
				const dot = document.createElement( 'button' );

				dot.type = 'button';
				dot.className = 'anishop-hero-slider__dot';
				dot.setAttribute(
					'aria-label',
					'رفتن به اسلاید ' + ( index + 1 )
				);
				dot.setAttribute(
					'aria-current',
					index === 0 ? 'true' : 'false'
				);

				dot.addEventListener( 'click', function () {
					goTo( index );
				} );

				pagination.appendChild( dot );
			} );
		}

		if ( nextButton ) {
			nextButton.addEventListener( 'click', next );
		}

		if ( prevButton ) {
			prevButton.addEventListener( 'click', previous );
		}

		slides.forEach( function ( slide, index ) {
			slide.addEventListener( 'click', function ( event ) {
				if ( index === activeIndex ) {
					return;
				}

				if ( event.target.closest( 'a, button' ) ) {
					return;
				}

				goTo( index );
			} );
		} );

		if ( stage ) {
			stage.addEventListener( 'keydown', function ( event ) {
				switch ( event.key ) {
					case 'ArrowLeft':
						event.preventDefault();
						next();
						break;

					case 'ArrowRight':
						event.preventDefault();
						previous();
						break;

					case 'Home':
						event.preventDefault();
						goTo( 0 );
						break;

					case 'End':
						event.preventDefault();
						goTo( count - 1 );
						break;

					default:
						break;
				}
			} );

			stage.addEventListener(
				'pointerdown',
				function ( event ) {
					if ( event.pointerType === 'mouse' ) {
						return;
					}

					pointerStartX = event.clientX;
					pointerStartY = event.clientY;
				},
				{ passive: true }
			);

			stage.addEventListener(
				'pointerup',
				function ( event ) {
					if ( event.pointerType === 'mouse' ) {
						return;
					}

					const deltaX =
						event.clientX - pointerStartX;
					const deltaY =
						event.clientY - pointerStartY;

					if (
						Math.abs( deltaX ) > 45 &&
						Math.abs( deltaX ) > Math.abs( deltaY )
					) {
						if ( deltaX < 0 ) {
							next();
						} else {
							previous();
						}
					}
				},
				{ passive: true }
			);
		}

		root.addEventListener( 'mouseenter', function () {
			hovering = true;
			restartTimer();
		} );

		root.addEventListener( 'mouseleave', function () {
			hovering = false;
			restartTimer();
		} );

		root.addEventListener( 'focusin', function () {
			focusing = true;
			restartTimer();
		} );

		root.addEventListener( 'focusout', function ( event ) {
			if ( ! root.contains( event.relatedTarget ) ) {
				focusing = false;
				restartTimer();
			}
		} );

		document.addEventListener(
			'visibilitychange',
			restartTimer
		);

		if ( playPause ) {
			playPause.classList.toggle( 'is-paused', paused );
			playPause.setAttribute(
				'aria-label',
				paused
					? 'پخش خودکار'
					: 'توقف پخش خودکار'
			);

			playPause.addEventListener( 'click', function () {
				paused = ! paused;

				playPause.classList.toggle(
					'is-paused',
					paused
				);
				playPause.setAttribute(
					'aria-label',
					paused
						? 'پخش خودکار'
						: 'توقف پخش خودکار'
				);

				restartTimer();
			} );
		}

		const motionQuery = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		);

		const handleMotionChange = function ( event ) {
			paused = event.matches;

			if ( playPause ) {
				playPause.classList.toggle(
					'is-paused',
					paused
				);
				playPause.setAttribute(
					'aria-label',
					paused
						? 'پخش خودکار'
						: 'توقف پخش خودکار'
				);
			}

			restartTimer();
		};

		if ( motionQuery.addEventListener ) {
			motionQuery.addEventListener(
				'change',
				handleMotionChange
			);
		}

		render();
		restartTimer();
	}

	function boot() {
		document
			.querySelectorAll( '[data-anishop-hero-slider]' )
			.forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
