/**
 * Eruda Toolkit - Spin
 *
 * Turns whatever a widget holds, and slows it to a stop under the pointer
 * rather than stopping it dead.
 *
 * That last part is the only reason this file exists. A CSS animation can be
 * paused, and `animation-play-state: paused` stops it on the frame it is told
 * to -- which on something turning steadily reads as a jam, not a stop. The
 * Web Animations API can have its playback rate changed while it runs, keeping
 * its position, so easing that rate from one to nought is a real spin-down:
 * constant angular deceleration, the way a wheel actually stops.
 *
 * The stylesheet keeps a plain CSS rotation for anything without
 * element.animate, and that rotation is switched off only once this file's own
 * animation exists, so the thing is never stopped in between the two.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var ROOT = '.espin-yes';
	var READY = 'data-espin-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Read a custom property as a plain number.
	 *
	 * @param {Element} el       Element to read from.
	 * @param {string}  name     Property name.
	 * @param {number}  fallback Value when unreadable.
	 * @return {number}
	 */
	function readNumber( el, name, fallback ) {
		var raw = '';

		try {
			raw = ( window.getComputedStyle( el ).getPropertyValue( name ) || '' ).trim();
		} catch ( e ) {
			raw = '';
		}

		var value = parseFloat( raw );

		return isNaN( value ) ? fallback : value;
	}

	/**
	 * What in this widget is the thing that turns.
	 *
	 * @param {Element} root Widget.
	 * @return {Element|null}
	 */
	function target( root ) {
		if ( root.classList.contains( 'espin-target-self' ) ) {
			return root.querySelector( '.elementor-widget-container' ) || root.firstElementChild;
		}

		return root.querySelector( 'img, svg' );
	}

	/**
	 * Set up one widget.
	 *
	 * @param {Element} root Widget.
	 */
	function init( root ) {
		if ( ! root || root.erudaSpinReady ) {
			return;
		}

		root.erudaSpinReady = true;

		var el = target( root );

		if ( ! el || prefersReducedMotion() || typeof el.animate !== 'function' ) {
			// Either there is nothing to turn, it should not be turning, or
			// the stylesheet's own animation is the best that can be done.
			// Leaving the widget un-ready is what keeps that animation going.
			return;
		}

		var turn = readNumber( root, '--espin-speed', 18 ) * 1000;
		var ramp = readNumber( root, '--espin-ramp', 700 );
		var reverse = root.classList.contains( 'espin-dir-reverse' );
		var stops = ! root.classList.contains( 'espin-hover-keep' );

		if ( turn <= 0 ) {
			return;
		}

		var spin = el.animate(
			[
				{ transform: 'rotate(0deg)' },
				{ transform: 'rotate(' + ( reverse ? -360 : 360 ) + 'deg)' },
			],
			{ duration: turn, iterations: Infinity, easing: 'linear' }
		);

		// Taking the stylesheet's animation off now, rather than before this
		// one exists, means the thing never stops turning in between the two.
		root.setAttribute( READY, '' );

		if ( ! stops ) {
			return;
		}

		var rate = 1;
		var aimed = 1;
		var frame = null;
		var last = null;

		function step( now ) {
			if ( null === last ) {
				last = now;
			}

			// The rate moves a constant amount per millisecond, so it slows
			// evenly rather than trailing off forever.
			var delta = ramp > 0 ? ( now - last ) / ramp : 1;

			last = now;

			if ( rate < aimed ) {
				rate = Math.min( aimed, rate + delta );
			} else {
				rate = Math.max( aimed, rate - delta );
			}

			try {
				spin.playbackRate = rate;
			} catch ( e ) {
				frame = null;
				return;
			}

			if ( rate !== aimed ) {
				frame = window.requestAnimationFrame( step );
			} else {
				frame = null;
				last = null;
			}
		}

		function aim( value ) {
			aimed = value;

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
		root.addEventListener( 'focusin', slow );
		root.addEventListener( 'focusout', go );

		// A finger is not a pointer: there is no hovering out of it, so
		// without this the thing would stay stopped for good after one tap.
		root.addEventListener( 'touchstart', slow, { passive: true } );
		root.addEventListener( 'touchend', go, { passive: true } );
		root.addEventListener( 'touchcancel', go, { passive: true } );
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

			// Any widget can carry the class, so this listens to all of them.
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function ( scope ) {
				var node = scope && scope[0] ? scope[0] : scope;

				if ( ! node || 1 !== node.nodeType ) {
					return;
				}

				// A re-render replaces the markup, so the guard on the old
				// node does not carry over.
				if ( node.classList && node.classList.contains( 'espin-yes' ) ) {
					init( node );
				}

				initAll( node );
			} );
		} );
	}
}() );
