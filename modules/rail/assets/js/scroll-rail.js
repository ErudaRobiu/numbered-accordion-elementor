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

			var height = window.innerHeight || document.documentElement.clientHeight || 0;

			if ( 'pinned' !== mode ) {
				stage.style.top = '';

				// Flow adds nothing by default. Extra is opt-in, and it is the
				// only thing here that costs the page any height at all.
				var extra = readData( root, 'data-erail-extra', 0 );

				if ( extra > 0 ) {
					root.style.height = Math.round( stage.offsetHeight + extra * height ) + 'px';
				}

				return;
			}

			// Centred on the screen, from the height the stage turned out to
			// be rather than the height it was asked for -- which is usually
			// `auto`, because the cards decide it.
			stage.style.top = Math.max( 0, Math.round( ( height - stage.offsetHeight ) / 2 ) ) + 'px';

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
				/*
				 * The travel happens while the section is on screen, not
				 * across its whole journey on and off it.
				 *
				 * Spending the full journey is the obvious thing to do and it
				 * is wrong: the row is already moving while the section is a
				 * sliver at the bottom of the window, and it has finished
				 * while the section is a sliver at the top, so the cards you
				 * can actually see are the middle ones and the first and last
				 * go past unread.
				 *
				 * Both ends are instead given as how much of the section has
				 * to be on screen: `start` before the row sets off, `finish`
				 * still showing when it arrives. One is the top edge of the
				 * window the row travels in, the other is the bottom.
				 *
				 *   basis   the section, or the screen, whichever is smaller,
				 *           so a section taller than the window measures
				 *           against how much of the *screen* it fills
				 *   tStart  where the section's top is when it sets off
				 *   tEnd    where it is when the row is done
				 */
				var h = rect.height;
				var basis = Math.min( h, height );
				var tStart = height - basis * readData( root, 'data-erail-start', 0.8 );
				var tEnd = basis * readData( root, 'data-erail-finish', 0.8 ) - h;
				var span = tStart - tEnd;

				if ( span <= 0 ) {
					return rect.top <= tEnd ? 1 : 0;
				}

				return clamp01( ( tStart - rect.top ) / span );
			}

			// Pinned only. The runway is one long stretch of scrolling with
			// nothing else happening in it, so a moment of stillness at each
			// end stops the row snapping into motion on the frame the section
			// reaches the top of the screen. Flow has its start and finish
			// controls for the same job.
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
			var target;

			if ( 'pinned' === mode ) {
				target = window.pageYOffset + rect.top + raw * ( rect.height - stage.offsetHeight );
			} else {
				var basis = Math.min( rect.height, height );
				var tStart = height - basis * readData( root, 'data-erail-start', 0.8 );
				var tEnd = basis * readData( root, 'data-erail-finish', 0.8 ) - rect.height;

				target = window.pageYOffset + rect.top - tStart + wanted * ( tStart - tEnd );
			}

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
