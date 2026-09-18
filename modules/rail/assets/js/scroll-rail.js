/**
 * Eruda Toolkit - Scroll Rail
 *
 * Moves a row of cards sideways as you scroll down past it, so reading the row
 * left to right is the same gesture as reading the page.
 *
 * Two ways of doing that, and the difference is what it costs the page.
 *
 * Flow, the default, adds no height at all. The section is exactly as tall as
 * its row, and the travel is spent against the section's own passage across
 * the screen: the row starts as the section arrives from the bottom and
 * finishes as it leaves at the top. Nothing below it moves down by a pixel.
 *
 * Pinned holds the section still and hands it extra page height to spend, so
 * the row crosses while the screen does not. It reads more deliberately and it
 * is the more familiar effect, but that extra height is real -- everything
 * after the rail sits further down the page by exactly the width of the row.
 * The runway is measured from the row rather than guessed, so a row of three
 * cards is short and a row of twelve is long, and neither strands you
 * scrolling past a rail that stopped moving.
 *
 * Vanilla, no dependencies, safe to run twice. If it never runs, or if the
 * screen is narrow, or if reduced motion is asked for, the section is an
 * ordinary horizontally scrollable row with snap points -- which is why the
 * stylesheet leaves the viewport scrollable until this file has set
 * data-erail-ready.
 */
( function () {
	'use strict';

	var ROOT = '.erail';
	var READY = 'data-erail-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function clamp01( value ) {
		return value < 0 ? 0 : value > 1 ? 1 : value;
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Read a number off a data attribute.
	 *
	 * @param {Element} el       Element to read from.
	 * @param {string}  name     Attribute name.
	 * @param {number}  fallback Value when absent or unparseable.
	 * @return {number}
	 */
	function readData( el, name, fallback ) {
		var value = parseFloat( el.getAttribute( name ) );

		return isNaN( value ) ? fallback : value;
	}

	/**
	 * Set up one rail.
	 *
	 * @param {Element} root Section wrapper.
	 */
	function init( root ) {
		if ( ! root || root.erudaRailReady ) {
			return;
		}

		root.erudaRailReady = true;

		var stage = root.querySelector( '.erail__stage' );
		var viewport = root.querySelector( '.erail__viewport' );
		var track = root.querySelector( '.erail__track' );
		var bar = root.querySelector( '.erail__progress span' );
		var cards = toArray( root.querySelectorAll( '.erail__card' ) );

		if ( ! stage || ! viewport || ! track || ! cards.length ) {
			return;
		}

		// How much of the travel is spent standing still at each end, so the
		// row does not start moving the instant its first pixel appears.
		var hold = readData( root, 'data-erail-hold', 0.08 );
		var mode = 'pinned' === root.getAttribute( 'data-erail-mode' ) ? 'pinned' : 'flow';
		var driving = false;
		var distance = 0;

		/**
		 * Should the script drive the row at all?
		 *
		 * Below the breakpoint, and under reduced motion, the row stays a
		 * plain scroller. Both are checked live rather than once, because a
		 * window gets resized and an operating system setting gets changed.
		 *
		 * @return {bool}
		 */
		function shouldDrive() {
			if ( prefersReducedMotion() ) {
				return false;
			}

			return ! (
				typeof window.matchMedia === 'function' &&
				window.matchMedia( '(max-width: 1024px)' ).matches
			);
		}

		/**
		 * Measure the row and give the section the height its travel needs.
		 *
		 * Called on load and on every resize, because a card's width is a
		 * custom property that can be responsive and the row's width follows
		 * its content.
		 */
		function measure() {
			driving = shouldDrive();

			// Cleared first either way: the track's own width is what is being
			// measured, and a stale inline height on the root changes the
			// stage it is measured against.
			root.style.height = '';

			if ( ! driving ) {
				track.style.transform = '';
				root.removeAttribute( READY );
				return;
			}

			root.setAttribute( READY, '' );

			distance = Math.max( 0, track.scrollWidth - viewport.clientWidth );

			if ( 'pinned' !== mode ) {
				// Flow adds nothing. This is the whole point of it.
				return;
			}

			// Spend one page-height of scrolling for each screen-width of row,
			// so the sideways speed feels the same whatever the row's length.
			var runway = distance > 0 ? distance * readData( root, 'data-erail-pace', 1 ) : 0;

			root.style.height = Math.round( stage.offsetHeight + runway ) + 'px';
		}

		/**
		 * How far through the pinned stretch we are, 0 to 1.
		 *
		 * @return {number}
		 */
		function progress() {
			var rect = root.getBoundingClientRect();
			var height = window.innerHeight || document.documentElement.clientHeight || 0;
			var raw;

			if ( 'pinned' === mode ) {
				// The travel is the runway: the stretch of scrolling during
				// which the stage is stuck to the screen.
				var span = rect.height - stage.offsetHeight;

				if ( span <= 0 ) {
					return 0;
				}

				raw = clamp01( -rect.top / span );
			} else {
				// The travel is the section's own passage across the screen:
				// nought when its top edge is at the bottom of the window, one
				// when its bottom edge is at the top. No page height is
				// involved, which is why nothing below the rail moves.
				var journey = height + rect.height;

				if ( journey <= 0 ) {
					return 0;
				}

				raw = clamp01( ( height - rect.top ) / journey );
			}

			// Ease the two ends so the row is still for a moment as it arrives
			// and as it leaves, instead of snapping into motion on the frame
			// the section reaches the top of the screen.
			if ( hold <= 0 || hold >= 0.5 ) {
				return raw;
			}

			return clamp01( ( raw - hold ) / ( 1 - hold * 2 ) );
		}

		function update() {
			if ( ! driving ) {
				if ( bar ) {
					bar.style.transform = 'scaleX(0)';
				}

				return;
			}

			var p = progress();

			lock();
			track.style.transform = 'translate3d(' + ( -p * distance ).toFixed( 2 ) + 'px, 0, 0)';

			if ( bar ) {
				bar.style.transform = 'scaleX(' + p.toFixed( 4 ) + ')';
			}
		}

		/*
		 * Keep the viewport's own scroll at zero while the row is being moved
		 * by transform.
		 *
		 * `overflow: hidden` stops a person scrolling an element; it does not
		 * stop the browser. Focusing a child scrolls its nearest scrollable
		 * ancestor to reveal it, and that ancestor is this viewport -- so a
		 * card would be shifted once by the transform and again by a scroll
		 * nobody asked for, landing it off the far side of the screen. The
		 * same happens on find-in-page and on some click handling.
		 *
		 * When the rail is not pinned the viewport is the scroller, and its
		 * scroll position is the whole point, so this only ever runs while it
		 * is not.
		 */
		function lock() {
			if ( driving && viewport.scrollLeft !== 0 ) {
				viewport.scrollLeft = 0;
			}
		}

		var ticking = false;

		function onScroll() {
			if ( ticking ) {
				return;
			}

			ticking = true;

			window.requestAnimationFrame( function () {
				update();
				ticking = false;
			} );
		}

		function onResize() {
			measure();
			update();
		}

		/*
		 * Tabbing to a card that is off to the side would otherwise focus
		 * something nobody can see: the row is moved by the page's scroll
		 * position, so bringing a card into view means scrolling the page, not
		 * the row. scrollIntoView would scroll the viewport element instead
		 * and fight the transform.
		 */
		function onFocus( event ) {
			if ( ! driving || ! distance ) {
				return;
			}

			var card = event.target;

			while ( card && card !== root && ! card.classList.contains( 'erail__card' ) ) {
				card = card.parentElement;
			}

			if ( ! card || ! card.classList.contains( 'erail__card' ) ) {
				return;
			}

			var wanted = clamp01(
				( card.offsetLeft + card.offsetWidth / 2 - viewport.clientWidth / 2 ) / distance
			);

			var rect = root.getBoundingClientRect();
			var height = window.innerHeight || document.documentElement.clientHeight || 0;
			var raw = hold > 0 && hold < 0.5 ? wanted * ( 1 - hold * 2 ) + hold : wanted;
			var target = 'pinned' === mode
				? window.pageYOffset + rect.top + raw * ( rect.height - stage.offsetHeight )
				: window.pageYOffset + rect.top - height + raw * ( height + rect.height );

			window.scrollTo( { top: Math.round( target ), behavior: 'auto' } );
			update();

			// The browser will have scrolled the viewport to reveal the card
			// as well. Undo that, or the card is shifted twice.
			lock();
			window.requestAnimationFrame( lock );
		}

		// A card whose picture has not loaded has no height yet, and a row
		// measured before its pictures arrive is the wrong width.
		toArray( root.querySelectorAll( 'img' ) ).forEach( function ( image ) {
			if ( ! image.complete ) {
				image.addEventListener( 'load', onResize );
				image.addEventListener( 'error', onResize );
			}
		} );

		measure();
		update();

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onResize, { passive: true } );
		root.addEventListener( 'focusin', onFocus );
		viewport.addEventListener( 'scroll', lock, { passive: true } );

		if ( typeof window.matchMedia === 'function' ) {
			var motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

			if ( typeof motion.addEventListener === 'function' ) {
				motion.addEventListener( 'change', onResize );
			}
		}
	}

	function initAll( scope ) {
		toArray( ( scope || document ).querySelectorAll( ROOT ) ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	// Pictures still loading change the row's width, and the row's width is
	// the whole measurement.
	window.addEventListener( 'load', function () {
		window.dispatchEvent( new Event( 'resize' ) );
	} );

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eanm-scroll-rail.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					// A re-render replaces the markup, so the guard on the old
					// node does not carry over.
					initAll( el );
				}
			} );
		} );
	}
}() );
