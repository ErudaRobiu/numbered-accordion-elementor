/**
 * Eruda Toolkit - Customer Logo Tabs
 *
 * ARIA tabs with arrow keys, segment chips that filter the wall, the URL hash
 * choosing a tab, and a short fade as tiles come in. Every panel is in the
 * markup from the server, hidden ones included, so all the logos are in the
 * page for search engines whichever tab is showing.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the default tab
 * still shows, complete.
 */
( function () {
	'use strict';

	var ROOT = '.eclt';
	var READY = 'data-eclt-ready';

	// The stagger step and its cap: at most 10 x 12ms of delay plus the 160ms
	// fade, so a whole wall is in within 300ms however many logos it has.
	var STEP_MS = 12;
	var STEP_CAP = 10;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Size a logo the server could not measure (an SVG, or an image from
	 * outside the media library) by the same rule the server uses.
	 *
	 * @param {HTMLImageElement} img Logo.
	 */
	function fit( img ) {
		function apply() {
			if ( ! img.naturalWidth || ! img.naturalHeight ) {
				return;
			}

			var aspect = img.naturalWidth / img.naturalHeight;
			var width = Math.round( Math.min( 118, Math.sqrt( 4000 * aspect ), 58 * aspect ) );

			img.style.width = width + 'px';
			img.removeAttribute( 'data-eclt-fit' );
		}

		if ( img.complete ) {
			apply();
		} else {
			img.addEventListener( 'load', apply, { once: true } );
		}
	}

	/**
	 * Fade in the tiles now showing in a panel.
	 *
	 * @param {Element} root  Widget root.
	 * @param {Element} panel Panel.
	 */
	function freshen( root, panel ) {
		if ( ! root.classList.contains( 'eclt--animate' ) || prefersLessMotion() ) {
			return;
		}

		var tiles = toArray( panel.querySelectorAll( '.eclt__tile' ) ).filter( function ( tile ) {
			return ! tile.hidden;
		} );

		tiles.forEach( function ( tile ) {
			tile.classList.remove( 'is-fresh' );
		} );

		// One reflow for the lot, so removing and re-adding the class restarts
		// the animation instead of being collapsed into no change.
		void panel.offsetWidth;

		tiles.forEach( function ( tile, i ) {
			tile.style.animationDelay = Math.min( i, STEP_CAP ) * STEP_MS + 'ms';
			tile.classList.add( 'is-fresh' );
		} );
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

		var tabs = toArray( root.querySelectorAll( '.eclt__tab' ) );

		if ( ! tabs.length ) {
			return;
		}

		root.setAttribute( READY, '1' );

		function panelOf( tab ) {
			return document.getElementById( tab.getAttribute( 'aria-controls' ) );
		}

		function select( index, focus ) {
			tabs.forEach( function ( tab, i ) {
				var on = i === index;
				var panel = panelOf( tab );

				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.setAttribute( 'tabindex', on ? '0' : '-1' );

				if ( panel ) {
					panel.hidden = ! on;
				}
			} );

			if ( focus ) {
				tabs[ index ].focus();
			}

			var shown = panelOf( tabs[ index ] );

			if ( shown ) {
				freshen( root, shown );
			}
		}

		function current() {
			for ( var i = 0; i < tabs.length; i++ ) {
				if ( 'true' === tabs[ i ].getAttribute( 'aria-selected' ) ) {
					return i;
				}
			}

			return 0;
		}

		tabs.forEach( function ( tab, i ) {
			tab.addEventListener( 'click', function () {
				if ( i !== current() ) {
					select( i, false );
				}
			} );
		} );

		// Arrow keys move and select at once, the way the WAI-ARIA tabs
		// pattern does it; up and down too, because on a phone the tabs stack.
		root.querySelector( '.eclt__tabs' ).addEventListener( 'keydown', function ( event ) {
			var last = tabs.length - 1;
			var now = current();
			var next = null;

			switch ( event.key ) {
				case 'ArrowRight':
				case 'ArrowDown':
					next = now === last ? 0 : now + 1;
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					next = now === 0 ? last : now - 1;
					break;
				case 'Home':
					next = 0;
					break;
				case 'End':
					next = last;
					break;
			}

			if ( null === next ) {
				return;
			}

			event.preventDefault();
			select( next, true );
		} );

		toArray( root.querySelectorAll( '.eclt__panel' ) ).forEach( function ( panel ) {
			var chips = toArray( panel.querySelectorAll( '.eclt__chip' ) );
			var tiles = toArray( panel.querySelectorAll( '.eclt__tile' ) );

			chips.forEach( function ( chip ) {
				chip.addEventListener( 'click', function () {
					var seg = chip.getAttribute( 'data-seg' );

					chips.forEach( function ( other ) {
						other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
					} );

					tiles.forEach( function ( tile ) {
						tile.hidden = 'all' !== seg && tile.getAttribute( 'data-seg' ) !== seg;
					} );

					freshen( root, panel );
				} );
			} );
		} );

		toArray( root.querySelectorAll( 'img[data-eclt-fit]' ) ).forEach( fit );

		// #ind, #rest, #hosp: a link from another page opens that audience.
		function fromHash() {
			var key = decodeURIComponent( ( window.location.hash || '' ).slice( 1 ) );

			if ( ! key ) {
				return;
			}

			for ( var i = 0; i < tabs.length; i++ ) {
				if ( tabs[ i ].getAttribute( 'data-key' ) === key ) {
					if ( i !== current() ) {
						select( i, false );
					}

					return;
				}
			}
		}

		fromHash();
		window.addEventListener( 'hashchange', fromHash );
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
				'frontend/element_ready/eclt-customer-logo-tabs.default',
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
