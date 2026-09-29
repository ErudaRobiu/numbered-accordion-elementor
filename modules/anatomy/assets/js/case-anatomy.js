/**
 * Eruda Toolkit - Case Anatomy
 *
 * ARIA tabs with roving tabindex, arrows, Home and End, and a fresh fade for
 * the answers each time another project is picked. Every panel is in the
 * markup from the server, so all the cases are in the page whichever shows.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the default project
 * still shows, complete.
 */
( function () {
	'use strict';

	var ROOT = '.ecan';
	var READY = 'data-ecan-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Replay the answers' entrance on a panel. The stagger is in the CSS.
	 *
	 * @param {Element} root  Widget root.
	 * @param {Element} panel Panel.
	 */
	function freshen( root, panel ) {
		if ( ! root.classList.contains( 'ecan--animate' ) || prefersLessMotion() ) {
			return;
		}

		panel.classList.remove( 'is-fresh' );
		// Reflow, so taking the class off and back on restarts the animation.
		void panel.offsetWidth;
		panel.classList.add( 'is-fresh' );
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

		var tabs = toArray( root.querySelectorAll( '.ecan__tab' ) );

		if ( ! tabs.length ) {
			return;
		}

		root.setAttribute( READY, '1' );

		function panelOf( tab ) {
			return document.getElementById( tab.getAttribute( 'aria-controls' ) );
		}

		function current() {
			for ( var i = 0; i < tabs.length; i++ ) {
				if ( 'true' === tabs[ i ].getAttribute( 'aria-selected' ) ) {
					return i;
				}
			}

			return 0;
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

		tabs.forEach( function ( tab, i ) {
			tab.addEventListener( 'click', function () {
				if ( i !== current() ) {
					select( i, false );
				}
			} );
		} );

		// Arrow keys move and select at once, the WAI-ARIA tabs pattern.
		root.querySelector( '.ecan__tabs' ).addEventListener( 'keydown', function ( event ) {
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
				'frontend/element_ready/ecan-case-anatomy.default',
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
