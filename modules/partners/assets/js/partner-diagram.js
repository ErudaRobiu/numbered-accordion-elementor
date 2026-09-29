/**
 * Eruda Toolkit - Partner Diagram
 *
 * Only decides *when*: arms the diagram, marks it in once it has been scrolled
 * to, and keeps track of whether it is on screen so the heat pulse can pause.
 * Everything that moves is CSS -- see the note at the top of
 * partner-diagram.css.
 *
 * Vanilla, no dependencies, safe to run twice. If it never runs, nothing is
 * hidden: the diagram simply sits there complete, which is also what anybody
 * who asked for reduced motion gets.
 */
( function () {
	'use strict';

	var ROOT = '.epdg';
	var READY = 'data-epdg-ready';

	// The last stroke of the entrance (the bracket's drops) ends at 2.1s.
	var SETTLE_MS = 2400;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Wire one diagram up.
	 *
	 * @param {Element} root Widget root.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		if ( ! root.classList.contains( 'epdg--animate' ) || prefersLessMotion() ) {
			return;
		}

		// In the editor every change re-renders the widget, and replaying the
		// entrance on each keystroke is noise. Show it finished, pulse running.
		var editing = document.body && document.body.classList.contains( 'elementor-editor-active' );

		if ( editing || ! ( 'IntersectionObserver' in window ) ) {
			root.classList.add( 'is-in', 'is-live' );
			return;
		}

		root.classList.add( 'epdg--armed' );

		// Once the last line has drawn, the entrance rules have nothing left
		// to do, so take them away rather than leave finished animations on
		// every line. See partner-diagram.css.
		function settle() {
			root.classList.remove( 'epdg--armed' );
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					// A tall diagram on a short phone never reaches a large
					// ratio, so the bar for "seen" stays low.
					if ( entry.isIntersecting && entry.intersectionRatio >= 0.15 && ! root.classList.contains( 'is-in' ) ) {
						root.classList.add( 'is-in' );
						window.setTimeout( settle, SETTLE_MS );
					}

					root.classList.toggle( 'is-live', entry.isIntersecting );
				} );
			},
			{ threshold: [ 0, 0.15 ] }
		);

		observer.observe( root );
	}

	/**
	 * Wire up every diagram inside a scope.
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
				'frontend/element_ready/epdg-partner-diagram.default',
				function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;

					if ( el && 1 === el.nodeType ) {
						// A re-render replaces the markup, so the guard on the
						// old node does not carry over.
						initAll( el );
					}
				}
			);
		} );
	}
}() );
