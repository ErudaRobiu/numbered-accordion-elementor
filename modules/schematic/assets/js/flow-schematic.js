/**
 * Eruda Toolkit - Flow Schematic.
 *
 * One job: keep the slider and the pan frame in step.
 *
 * The frame scrolls natively, so a wide diagram is reachable by swipe, by
 * trackpad and by keyboard whether this file runs or not. The slider is an
 * affordance on top of that -- a visible handle saying "there is more to the
 * right", which a native scrollbar does not say on a phone. It stays hidden
 * until this script has established there is actually something to pan, so it
 * never appears as a control that does nothing.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var READY = 'data-efs-ready';
	var STEPS = 1000;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * How far this frame can travel.
	 *
	 * @param {Element} pan Frame.
	 * @return {number} Pixels of overflow, possibly zero.
	 */
	function range( pan ) {
		return Math.max( 0, pan.scrollWidth - pan.clientWidth );
	}

	/**
	 * Wire one schematic up.
	 *
	 * @param {Element} root Widget root.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		var pan    = root.querySelector( '.efs__pan' );
		var slider = root.querySelector( '.efs__slider' );

		if ( ! pan || ! slider ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var syncing = false;

		/**
		 * Show or hide the slider, and put it where the frame currently is.
		 */
		function measure() {
			var travel = range( pan );

			root.classList.toggle( 'is-pannable', travel > 1 );

			if ( travel > 1 ) {
				syncing = true;
				slider.value = Math.round( ( pan.scrollLeft / travel ) * STEPS );
				syncing = false;
			}
		}

		slider.addEventListener( 'input', function () {
			if ( syncing ) {
				return;
			}

			pan.scrollLeft = ( slider.value / STEPS ) * range( pan );
		} );

		// The frame is the source of truth: a swipe moves the handle too.
		pan.addEventListener(
			'scroll',
			function () {
				if ( syncing ) {
					return;
				}

				var travel = range( pan );

				if ( travel < 1 ) {
					return;
				}

				syncing = true;
				slider.value = Math.round( ( pan.scrollLeft / travel ) * STEPS );
				syncing = false;
			},
			{ passive: true }
		);

		if ( typeof window.ResizeObserver === 'function' ) {
			new window.ResizeObserver( measure ).observe( pan );
		} else {
			window.addEventListener( 'resize', measure );
		}

		// An SVG or a picture may not have laid out yet.
		var art = pan.querySelector( '.efs__art' );

		if ( art && ! art.complete ) {
			art.addEventListener( 'load', measure );
		}

		measure();
	}

	/**
	 * Wire up every schematic inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
	function initAll( scope ) {
		var context = scope || document;

		if ( 1 === context.nodeType && context.matches && context.matches( '.efs' ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( '.efs' ) ).forEach( init );
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
				'frontend/element_ready/efs-flow-schematic.default',
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
