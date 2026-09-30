/**
 * Eruda Toolkit - Award Wall
 *
 * Once, when the wall scrolls into view: the number counts up from zero and
 * the cards fade in (the fade is CSS). Without the script the wall simply
 * shows, with the final number.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var ROOT = '.eaw';
	var READY = 'data-eaw-ready';
	var COUNT_MS = 900;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function countUp( el ) {
		var to = parseInt( el.getAttribute( 'data-to' ), 10 ) || 0;
		var start = null;

		function frame( now ) {
			if ( null === start ) {
				start = now;
			}

			var p = Math.min( 1, ( now - start ) / COUNT_MS );
			// Ease out: quick at first, settling onto the number.
			var eased = 1 - Math.pow( 1 - p, 3 );

			el.textContent = String( Math.round( to * eased ) );

			if ( p < 1 ) {
				requestAnimationFrame( frame );
			}
		}

		requestAnimationFrame( frame );
	}

	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		if ( ! root.classList.contains( 'eaw--animate' ) || prefersLessMotion() ) {
			return;
		}

		var editing = document.body && document.body.classList.contains( 'elementor-editor-active' );

		if ( editing || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var num = root.querySelector( '.eaw__num' );

		root.classList.add( 'eaw--armed' );

		if ( num ) {
			// The final number stays readable to a screen reader throughout.
			num.setAttribute( 'aria-label', num.textContent );
			num.textContent = '0';
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}

				observer.disconnect();
				root.classList.add( 'is-in' );

				if ( num ) {
					countUp( num );
				}

				window.setTimeout( function () {
					root.classList.remove( 'eaw--armed' );
				}, 1400 );
			},
			{ threshold: 0.3 }
		);

		observer.observe( root );
	}

	function initAll( scope ) {
		var context = scope || document;

		if ( 1 === context.nodeType && context.matches && context.matches( ROOT ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOT ) ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eaw-award-wall.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					initAll( el );
				}
			} );
		} );
	}
}() );
