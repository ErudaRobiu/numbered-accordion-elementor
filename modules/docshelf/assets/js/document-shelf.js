/**
 * Eruda Toolkit - Document Shelf
 *
 * Chips filter the cards, the cards left showing fade in, and a link ending
 * in #docs-<filter> opens the shelf with that chip on. Every card is in the
 * markup from the server, so without this script they all simply show.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var ROOT = '.edoc';
	var READY = 'data-edoc-ready';

	// 25ms apart, at most 6 steps, and a 140ms fade: in by 290ms.
	var STEP_MS = 25;
	var STEP_CAP = 6;

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

		var chips = toArray( root.querySelectorAll( '.edoc__chip' ) );
		var cards = toArray( root.querySelectorAll( '.edoc__card' ) );
		var prefix = root.getAttribute( 'data-hash-prefix' ) || 'docs-';

		root.setAttribute( READY, '1' );

		if ( ! chips.length ) {
			return;
		}

		function apply( chip, animate ) {
			var seg = chip.getAttribute( 'data-seg' );
			var shown = [];

			chips.forEach( function ( other ) {
				other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
			} );

			cards.forEach( function ( card ) {
				var on = 'all' === seg || card.getAttribute( 'data-seg' ) === seg;

				card.hidden = ! on;
				card.classList.remove( 'is-fresh' );

				if ( on ) {
					shown.push( card );
				}
			} );

			if ( ! animate || prefersLessMotion() ) {
				return;
			}

			// One reflow for the lot, so re-adding the class restarts it.
			void root.offsetWidth;

			shown.forEach( function ( card, i ) {
				card.style.animationDelay = Math.min( i, STEP_CAP ) * STEP_MS + 'ms';
				card.classList.add( 'is-fresh' );
			} );
		}

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				apply( chip, true );
			} );
		} );

		// #docs-perf: the home page can link straight to the evidence.
		function fromHash( animate ) {
			var hash = decodeURIComponent( ( window.location.hash || '' ).slice( 1 ) );

			if ( 0 !== hash.indexOf( prefix ) ) {
				return;
			}

			var key = hash.slice( prefix.length );

			for ( var i = 0; i < chips.length; i++ ) {
				if ( chips[ i ].getAttribute( 'data-seg' ) === key ) {
					apply( chips[ i ], animate );
					return;
				}
			}
		}

		fromHash( false );
		window.addEventListener( 'hashchange', function () {
			fromHash( true );
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
				'frontend/element_ready/edoc-document-shelf.default',
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
