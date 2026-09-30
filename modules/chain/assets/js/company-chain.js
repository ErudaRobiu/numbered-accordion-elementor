/**
 * Eruda Toolkit - Company Chain
 *
 * Stacks the chain when the widget itself is narrower than its "stack below"
 * width, or on a phone, and plays the entrance once when it scrolls into
 * view. The movement is all CSS.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the chain shows
 * complete, in a row on wide screens and stacked on phones.
 */
( function () {
	'use strict';

	var ROOT = '.echn';
	var READY = 'data-echn-ready';
	var PHONE = 767;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var at = parseInt( root.getAttribute( 'data-stack-at' ), 10 ) || 520;

		// Measure the box the chain lives in, not the chain: stacking changes
		// the chain's own width and would flip it back.
		var box = root.parentElement || root;

		function fit() {
			var narrow = box.clientWidth < at || window.innerWidth <= PHONE;

			root.classList.toggle( 'is-stacked', narrow );
		}

		fit();

		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( fit ).observe( box );
		} else {
			window.addEventListener( 'resize', fit );
		}

		if ( ! root.classList.contains( 'echn--animate' ) || prefersLessMotion() ) {
			return;
		}

		var editing = document.body && document.body.classList.contains( 'elementor-editor-active' );

		if ( editing || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		root.classList.add( 'echn--armed' );

		var steps = parseInt( getComputedStyle( root ).getPropertyValue( '--echn-n' ), 10 ) || 5;
		var step = parseFloat( getComputedStyle( root ).getPropertyValue( '--echn-step' ) ) || 180;

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}

				observer.disconnect();
				root.classList.add( 'is-in' );

				window.setTimeout( function () {
					root.classList.remove( 'echn--armed' );
				}, ( steps + 1 ) * step + 900 );
			},
			{ threshold: 0.35 }
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

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/echn-company-chain.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					initAll( el );
				}
			} );
		} );
	}
}() );
