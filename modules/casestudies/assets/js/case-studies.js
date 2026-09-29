/**
 * Eruda Toolkit - Case Studies
 *
 * Sector chips filter the cards, and the cards left showing fade in. Every
 * card is in the markup from the server, so without this script they all
 * simply show.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var ROOT = '.ecs';
	var READY = 'data-ecs-ready';

	// 40ms apart, at most 5 steps, and a 200ms fade: in within 400ms.
	var STEP_MS = 40;
	var STEP_CAP = 5;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Wire one widget up.
	 *
	 * @param {Element} root Widget root.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		var chips = toArray( root.querySelectorAll( '.ecs__chip' ) );
		var cards = toArray( root.querySelectorAll( '.ecs__card' ) );

		root.setAttribute( READY, '1' );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var seg = chip.getAttribute( 'data-seg' );

				chips.forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
				} );

				var shown = [];

				cards.forEach( function ( card ) {
					var sectors = ( card.getAttribute( 'data-seg' ) || '' ).split( ' ' );
					var on = 'all' === seg || -1 !== sectors.indexOf( seg );

					card.hidden = ! on;
					card.classList.remove( 'is-fresh' );

					if ( on ) {
						shown.push( card );
					}
				} );

				if ( prefersLessMotion() ) {
					return;
				}

				// One reflow for the lot, so re-adding the class restarts it.
				void root.offsetWidth;

				shown.forEach( function ( card, i ) {
					card.style.animationDelay = Math.min( i, STEP_CAP ) * STEP_MS + 'ms';
					card.classList.add( 'is-fresh' );
				} );
			} );
		} );
	}

	/**
	 * Wire up every widget inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
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

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/ecs-case-studies.default',
				function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;

					if ( el && 1 === el.nodeType ) {
						initAll( el );
					}
				}
			);
		} );
	}
}() );
