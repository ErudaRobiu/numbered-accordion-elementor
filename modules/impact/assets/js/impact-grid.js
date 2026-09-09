/**
 * Impact Grid for Elementor
 *
 * Vanilla JS, no dependencies. Safe to load more than once, safe to run
 * against the same DOM twice, and it never throws if the markup is missing.
 *
 * The script's only job is to decide *when* something animates. What the
 * animation looks like lives entirely in the stylesheet, and the figures are
 * already rendered at their final value in the HTML -- so if this file fails
 * to load, or bails out on an old browser, the grid is still complete and
 * correct. Nothing here is load-bearing for content.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '.eimp';
	var READY_ATTR = 'data-eimp-ready';

	/**
	 * Reveal a card once this much of it has scrolled into view.
	 */
	var THRESHOLD = 0.2;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * Would motion be unwelcome here?
	 *
	 * @return {boolean}
	 */
	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Read a CSS time custom property as a number of milliseconds.
	 *
	 * @param {CSSStyleDeclaration} styles Computed styles.
	 * @param {string}              name   Custom property name.
	 * @param {number}              fallback Value to use when unreadable.
	 * @return {number} Milliseconds.
	 */
	function readMs( styles, name, fallback ) {
		var raw = ( styles.getPropertyValue( name ) || '' ).trim();

		if ( ! raw ) {
			return fallback;
		}

		var number = parseFloat( raw );

		if ( isNaN( number ) ) {
			return fallback;
		}

		// A bare number, or one suffixed with s, is seconds. Anything ending
		// in ms is already what we want.
		return /ms\s*$/.test( raw ) ? number : number * 1000;
	}

	/**
	 * Re-apply the grouping and decimal places of the figure as it was typed.
	 *
	 * Deriving the format from the original string rather than from the
	 * visitor's locale means a figure written "165,000,000" counts through
	 * "1,234" and not "1.234" or "1 234", whatever their browser is set to.
	 *
	 * @param {number} value    Current value.
	 * @param {string} template The figure exactly as the editor typed it.
	 * @return {string}
	 */
	function formatLike( value, template ) {
		var dot = template.indexOf( '.' );
		var decimals = ( -1 === dot ) ? 0 : ( template.length - dot - 1 );
		var parts = value.toFixed( decimals ).split( '.' );

		if ( -1 !== template.indexOf( ',' ) ) {
			parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		}

		return parts.join( '.' );
	}

	/**
	 * Ease out expo. Fast off the mark, with a long settle -- the same shape
	 * as the CSS easing the cards rise on, so the two read as one movement.
	 *
	 * @param {number} t Progress, 0 to 1.
	 * @return {number}
	 */
	function easeOut( t ) {
		return ( 1 === t ) ? 1 : 1 - Math.pow( 2, -10 * t );
	}

	/**
	 * Count one figure up from zero.
	 *
	 * @param {Element} el       The figure element.
	 * @param {number}  duration Milliseconds.
	 * @param {number}  delay    Milliseconds to wait first.
	 */
	function countUp( el, duration, delay ) {
		var target = parseFloat( el.getAttribute( 'data-eimp-count' ) );

		if ( isNaN( target ) ) {
			return;
		}

		var template = el.textContent;

		if ( duration <= 0 ) {
			return;
		}

		// Held behind the card's own fade-in, so this never flashes a zero.
		el.textContent = formatLike( 0, template );

		window.setTimeout( function () {
			var start = null;

			function frame( now ) {
				if ( null === start ) {
					start = now;
				}

				var progress = Math.min( ( now - start ) / duration, 1 );

				el.textContent = formatLike( target * easeOut( progress ), template );

				if ( progress < 1 ) {
					window.requestAnimationFrame( frame );
					return;
				}

				// Land on the string the editor typed, never on something
				// rounding produced.
				el.textContent = template;
			}

			window.requestAnimationFrame( frame );
		}, delay );
	}

	/**
	 * Reveal a card and start whatever it contains.
	 *
	 * @param {Element} card    Card element.
	 * @param {number}  index   Position within this reveal batch.
	 * @param {number}  stagger Milliseconds between cards.
	 * @param {number}  count   Count-up duration in milliseconds.
	 */
	function reveal( card, index, stagger, count ) {
		/*
		 * The markup ships with a --eimp-i matching the card's position in the
		 * grid, which is the right cascade when the whole grid arrives at
		 * once. It is the wrong one for a card that scrolls in alone much
		 * later: that card should not sit waiting out five other cards' worth
		 * of delay. So the index is rewritten per batch of cards that crossed
		 * the threshold together.
		 */
		card.style.setProperty( '--eimp-i', index );
		card.classList.add( 'is-in' );

		toArray( card.querySelectorAll( '[data-eimp-count]' ) ).forEach( function ( el ) {
			countUp( el, count, index * stagger );
		} );
	}

	/**
	 * Set one grid going.
	 *
	 * @param {Element} root Grid element.
	 */
	function init( root ) {
		if ( ! root || root.getAttribute( READY_ATTR ) ) {
			return;
		}

		root.setAttribute( READY_ATTR, '1' );

		var cards = toArray( root.querySelectorAll( '.eimp-card' ) );

		if ( ! cards.length ) {
			return;
		}

		/*
		 * No observer, or motion is unwelcome: leave the stylesheet's finished
		 * state alone. .eimp--anim is never added, so nothing is ever hidden,
		 * and the figures keep the values already in the HTML.
		 */
		if ( typeof window.IntersectionObserver === 'undefined' || prefersReducedMotion() ) {
			return;
		}

		var styles = window.getComputedStyle( root );
		var stagger = readMs( styles, '--eimp-stagger', 90 );
		var count = readMs( styles, '--eimp-count-duration', 1600 );

		root.classList.add( 'eimp--anim' );

		var observer = new window.IntersectionObserver(
			function ( entries ) {
				var arrived = entries.filter( function ( entry ) {
					return entry.isIntersecting;
				} ).map( function ( entry ) {
					return entry.target;
				} );

				if ( ! arrived.length ) {
					return;
				}

				// entries arrive in observation order, not necessarily
				// document order. Sorting keeps the cascade running left to
				// right, top to bottom, as the eye expects.
				arrived.sort( function ( a, b ) {
					return cards.indexOf( a ) - cards.indexOf( b );
				} );

				arrived.forEach( function ( card, index ) {
					observer.unobserve( card );
					reveal( card, index, stagger, count );
				} );
			},
			{
				threshold: THRESHOLD,
				// Hold the reveal back a little past the fold, so a card is
				// never caught mid-animation right at the bottom edge.
				rootMargin: '0px 0px -8% 0px'
			}
		);

		cards.forEach( function ( card ) {
			observer.observe( card );
		} );
	}

	/**
	 * Initialise every grid inside a scope.
	 *
	 * @param {Element|Document} scope Container to search.
	 */
	function initAll( scope ) {
		var context = scope || document;

		if ( ! context || ! context.querySelectorAll ) {
			return;
		}

		if ( context.matches && context.matches( ROOT_SELECTOR ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOT_SELECTOR ) ).forEach( init );
	}

	/* ------------------------------------------------------- front end --- */

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	/* ---------------------------------------------- Elementor editor --- */

	function registerElementorHook() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/eimp-impact-grid.default',
			function ( $scope ) {
				var el = ( $scope && $scope[ 0 ] ) ? $scope[ 0 ] : $scope;
				initAll( el );
			}
		);
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', registerElementorHook );
	}

	// In case Elementor already booted before this script ran.
	registerElementorHook();
}() );
