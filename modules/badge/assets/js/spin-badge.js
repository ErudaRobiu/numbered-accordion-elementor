/**
 * Eruda Toolkit - Spin Badge
 *
 * Turns the ring of text, and slows it to a stop under the pointer rather than
 * stopping it dead.
 *
 * That last part is the only reason this file exists. A CSS animation can be
 * paused, and `animation-play-state: paused` stops it on the frame it is told
 * to -- which on something turning steadily reads as a jam, not a stop. The
 * Web Animations API can have its playback rate changed while it runs, keeping
 * its position, so easing that rate from one to nought is a real spin-down:
 * constant angular deceleration, the way a wheel actually stops.
 *
 * The stylesheet keeps a CSS animation for anyone whose browser has no
 * element.animate, and that animation is switched off the moment this file
 * marks the badge ready, so the two can never both be turning it.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var ROOT = '.ebdg';
	var READY = 'data-ebdg-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	function readData( el, name, fallback ) {
		var value = parseFloat( el.getAttribute( name ) );

		return isNaN( value ) ? fallback : value;
	}

	/**
	 * Make the ring text go exactly once around, whatever it says.
	 *
	 * The phrase is repeated a fixed number of times, so on its own it either
	 * falls short of the circle or laps itself and collides. Setting
	 * textLength to the circle's own circumference, with lengthAdjust spacing,
	 * hands the browser the job of distributing the difference between the
	 * letters -- so it meets itself exactly, at any size, in any font, and
	 * after a webfont swaps in.
	 *
	 * @param {Element} root Badge.
	 */
	function fit( root ) {
		var path = root.querySelector( '.ebdg__ring path' );
		var textPath = root.querySelector( '.ebdg__ring textPath' );

		if ( ! path || ! textPath ) {
			return;
		}

		var length = 0;

		try {
			length = path.getTotalLength();
		} catch ( e ) {
			length = 0;
		}

		if ( length > 0 ) {
			textPath.setAttribute( 'textLength', length.toFixed( 2 ) );
			textPath.setAttribute( 'lengthAdjust', 'spacing' );
		}
	}

	/**
	 * Set up one badge.
	 *
	 * @param {Element} root Badge.
	 */
	function init( root ) {
		if ( ! root || root.erudaBadgeReady ) {
			return;
		}

		root.erudaBadgeReady = true;

		var ring = root.querySelector( '.ebdg__ring' );

		if ( ! ring ) {
			return;
		}

		fit( root );

		// A webfont arriving changes every glyph's width, and the ring was
		// fitted to the one before it.
		if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
			document.fonts.ready.then( function () {
				fit( root );
			} ).catch( function () {} );
		}

		if ( prefersReducedMotion() || typeof ring.animate !== 'function' ) {
			// Either it should not be turning at all, or the stylesheet's own
			// animation is the best that can be done here. Leaving the badge
			// un-ready is what keeps that animation running.
			return;
		}

		var turn = readData( root, 'data-ebdg-speed', 18 ) * 1000;
		var ramp = readData( root, 'data-ebdg-ramp', 700 );
		var reverse = root.hasAttribute( 'data-ebdg-reverse' );
		var to = reverse ? -360 : 360;

		var spin = ring.animate(
			[
				{ transform: 'rotate(0deg)' },
				{ transform: 'rotate(' + to + 'deg)' },
			],
			{ duration: turn, iterations: Infinity, easing: 'linear' }
		);

		// Taking the stylesheet's animation off now, rather than before the
		// Web Animations one exists, means the ring never stops turning in
		// between the two.
		root.setAttribute( READY, '' );

		var rate = 1;
		var target = 1;
		var frame = null;
		var last = null;

		function step( now ) {
			if ( null === last ) {
				last = now;
			}

			// The rate moves at a constant number of units per millisecond, so
			// the wheel slows evenly rather than trailing off forever.
			var delta = ramp > 0 ? ( now - last ) / ramp : 1;

			last = now;

			if ( rate < target ) {
				rate = Math.min( target, rate + delta );
			} else {
				rate = Math.max( target, rate - delta );
			}

			try {
				spin.playbackRate = rate;
			} catch ( e ) {
				// A finished or cancelled animation is not worth chasing.
				frame = null;
				return;
			}

			if ( rate !== target ) {
				frame = window.requestAnimationFrame( step );
			} else {
				frame = null;
				last = null;
			}
		}

		function aim( value ) {
			target = value;

			if ( null === frame ) {
				last = null;
				frame = window.requestAnimationFrame( step );
			}
		}

		function slow() {
			aim( 0 );
		}

		function go() {
			aim( 1 );
		}

		root.addEventListener( 'mouseenter', slow );
		root.addEventListener( 'mouseleave', go );
		root.addEventListener( 'focus', slow );
		root.addEventListener( 'blur', go );

		// A finger is not a pointer: there is no hovering out of it, so the
		// badge would stay stopped for good after one tap.
		root.addEventListener( 'touchstart', slow, { passive: true } );
		root.addEventListener( 'touchend', go, { passive: true } );
		root.addEventListener( 'touchcancel', go, { passive: true } );

		if ( typeof window.matchMedia === 'function' ) {
			var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

			if ( typeof motion.addEventListener === 'function' ) {
				motion.addEventListener( 'change', function ( event ) {
					if ( event.matches ) {
						spin.cancel();
					}
				} );
			}
		}
	}

	function initAll( scope ) {
		toArray( ( scope || document ).querySelectorAll( ROOT ) ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eanm-spin-badge.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					// A re-render replaces the markup, so the guard on the old
					// node does not carry over.
					initAll( el );
				}
			} );
		} );
	}
}() );
