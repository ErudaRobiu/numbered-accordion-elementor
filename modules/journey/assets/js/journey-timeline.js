/**
 * Eruda Toolkit - Journey Timeline
 *
 * Measures where the circles actually are, so the line runs centre to centre
 * whatever each row's height, and times each row to land as the line passes
 * it. Then arms the list and plays it once when it scrolls into view. All the
 * movement itself is CSS.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the timeline shows
 * complete, the line from an estimate.
 */
( function () {
	'use strict';

	var ROOT = '.ejny';
	var READY = 'data-ejny-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * The CSS easing the line draws with, cubic-bezier(.45, .05, .3, 1), and
	 * its inverse: at what point in time has the line covered a fraction f?
	 */
	var P1X = 0.45, P1Y = 0.05, P2X = 0.3, P2Y = 1;

	function bez( t, a, b ) {
		var u = 1 - t;

		return 3 * u * u * t * a + 3 * u * t * t * b + t * t * t;
	}

	function timeFor( fraction ) {
		var lo = 0;
		var hi = 1;

		// Find t where the drawn fraction (y) reaches the target, then map
		// that t to elapsed time through x.
		for ( var i = 0; i < 30; i++ ) {
			var mid = ( lo + hi ) / 2;

			if ( bez( mid, P1Y, P2Y ) < fraction ) {
				lo = mid;
			} else {
				hi = mid;
			}
		}

		return bez( ( lo + hi ) / 2, P1X, P2X );
	}

	function centre( el ) {
		var r = el.getBoundingClientRect();

		return r.top + r.height / 2;
	}

	/**
	 * Put the line between the first and last circle, and give each row the
	 * moment the line reaches it.
	 *
	 * @param {Element} root The list.
	 */
	function measure( root ) {
		var dots = toArray( root.querySelectorAll( '.ejny__dot' ) );

		if ( dots.length < 1 ) {
			return;
		}

		var top = root.getBoundingClientRect().top;
		var first = centre( dots[ 0 ] );
		var last = centre( dots[ dots.length - 1 ] );
		var len = Math.max( 0, last - first );
		var dur = parseFloat( getComputedStyle( root ).getPropertyValue( '--ejny-dur' ) ) || 1200;

		root.style.setProperty( '--ejny-top', ( first - top ) + 'px' );
		root.style.setProperty( '--ejny-len', len + 'px' );

		dots.forEach( function ( dot ) {
			var f = len ? ( centre( dot ) - first ) / len : 0;
			var item = dot.closest( '.ejny__item' );

			if ( item ) {
				item.style.setProperty( '--ejny-at', Math.round( timeFor( f ) * dur ) + 'ms' );
			}
		} );

		return dur;
	}

	/**
	 * Wire one timeline up.
	 *
	 * @param {Element} root The list.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var dur = measure( root ) || 1200;
		var pending = 0;

		window.addEventListener( 'resize', function () {
			cancelAnimationFrame( pending );
			pending = requestAnimationFrame( function () {
				measure( root );
			} );
		} );

		// A late web font can change row heights.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				measure( root );
			} );
		}

		if ( ! root.classList.contains( 'ejny--animate' ) || prefersLessMotion() ) {
			return;
		}

		// In the editor every change re-renders the widget; show it finished.
		var editing = document.body && document.body.classList.contains( 'elementor-editor-active' );

		if ( editing || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		// The pulse waits for the current step to have filled.
		root.style.setProperty( '--ejny-pulse-delay', ( dur + 650 ) + 'ms' );
		root.classList.add( 'ejny--armed' );

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}

				observer.disconnect();
				measure( root );
				root.classList.add( 'is-in' );

				// Once everything has landed, let the entrance rules go.
				window.setTimeout( function () {
					root.classList.remove( 'ejny--armed' );
				}, dur + 900 );
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

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ejny-journey-timeline.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					initAll( el );
				}
			} );
		} );
	}
}() );
