/**
 * Eruda Toolkit - smooth scrolling
 *
 * Starts Lenis and hands it a frame loop. Everything that decides *whether* to
 * run lives here; Lenis itself is vendored untouched.
 *
 * If this file bails out, or Lenis fails to load, the page scrolls normally.
 * There is no state to restore and nothing to clean up -- native scrolling is
 * simply what happens when nobody intervenes.
 */
( function () {
	'use strict';

	/**
	 * Would motion be unwelcome here?
	 *
	 * Smooth scrolling is the single worst thing to force on someone who has
	 * asked for less motion: it makes every scroll feel like it is fighting
	 * back, and for some people it causes nausea outright.
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
	 * Is this the Elementor editor?
	 *
	 * Smoothing the canvas fights the editor's own scrolling, its drag and
	 * drop, and its panel positioning. The live page is the only place this
	 * belongs.
	 *
	 * @return {boolean}
	 */
	function inEditor() {
		try {
			return (
				!! document.body.classList.contains( 'elementor-editor-active' ) ||
				window.self !== window.top
			);
		} catch ( e ) {
			// A cross-origin parent means we are framed, which is close
			// enough to "not the live page" for this purpose.
			return true;
		}
	}

	/**
	 * Settings, with the defaults applied. PHP may replace this wholesale.
	 *
	 * @return {Object}
	 */
	function options() {
		var given = window.erudaSmoothScroll && window.erudaSmoothScroll.options;

		return {
			duration: given && given.duration ? parseFloat( given.duration ) : 1.1,
			// Touch devices already have momentum scrolling, and it is better
			// than anything a script can impose. Smoothing it as well makes a
			// phone feel broken.
			syncTouch: false,
			smoothWheel: true,
			// Lenis handles in-page anchors, so a menu link glides rather
			// than jumping.
			anchors: true,
		};
	}

	function start() {
		if ( prefersReducedMotion() || inEditor() || typeof window.Lenis !== 'function' ) {
			return;
		}

		var lenis = new window.Lenis( options() );

		function frame( time ) {
			lenis.raf( time );
			window.requestAnimationFrame( frame );
		}

		window.requestAnimationFrame( frame );

		// Exposed so a theme, or the browser console, can stop it.
		window.erudaSmoothScroll = window.erudaSmoothScroll || {};
		window.erudaSmoothScroll.lenis = lenis;
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
