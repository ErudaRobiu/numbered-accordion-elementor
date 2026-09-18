/**
 * Eruda Toolkit - Scroll Rail
 *
 * Moves a row of cards sideways as you scroll down past it, so reading the row
 * left to right is the same gesture as reading the page.
 *
 * Two ways of doing that, and the difference is what it costs the page.
 *
 * Flow adds no height at all. The section is exactly as tall as
 * its row, and the travel is spent against the section's own passage across
 * the screen: the row starts as the section arrives from the bottom and
 * finishes as it leaves at the top. Nothing below it moves down by a pixel.
 *
 * Pinned, the default, holds the section still and hands it extra page height
 * to spend, so the row crosses while the screen does not, with a still stretch
 * at each end. That extra height is not a flaw in the effect, it *is* the
 * effect: the scrolling has to come from somewhere, and GSAP's ScrollTrigger
 * reserves it the same way, with a spacer the height of the pin.
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

		var mode = 'flow' === root.getAttribute( 'data-erail-mode' ) ? 'flow' : 'pinned';
		var driving = false;
		var distance = 0;

		// Pinned only, and all in pixels once measured: the still stretch
		// before the row sets off, the stretch it travels for, and where the
		// pinned element sticks.
		var pauseIn = 0;
		var travel = 0;
		var stickyTop = 0;

		/*
		 * What actually holds still.
		 *
		 * Pinning the widget alone holds the row and nothing else, so a
		 * heading and an introduction sitting above it in the same section
		 * scroll away while the cards are still crossing -- which looks like
		 * the row shoving the rest of the page out of the way, because from
		 * the reader's side that is what it is.
		 *
		 * `host` is the ancestor to hold instead: the whole section, so the
		 * heading, the copy and the row all stay exactly where they are and
		 * only the cards move. It is found with closest(), so it is whatever
		 * the page already has rather than anything this widget has to own.
		 */
		var host = null;
		var spacer = null;
		var wrapper = null;

		function resolveHost() {
			var want = ( root.getAttribute( 'data-erail-pin' ) || '' ).trim();

			if ( '' === want || 'self' === want ) {
				return null;
			}

			var selector = 'section' === want
				? '.e-con, .elementor-section, section'
				: want;
			var found;

			try {
				found = root.parentElement ? root.parentElement.closest( selector ) : null;
			} catch ( e ) {
				// A selector someone typed by hand is allowed to be nonsense.
				return null;
			}

			// Pinning something that cannot contain the runway, or that is the
			// scrolling element itself, would hold the whole page forever.
			if ( ! found || ! found.parentElement || found === document.body ||
				found === document.documentElement ) {
				return null;
			}

			return found;
		}

		/**
		 * Hold the host still for `runway` pixels of scrolling.
		 *
		 * The host and its runway go inside a wrapper of their own, and this
		 * is not tidiness -- it is the only thing that bounds the hold. A
		 * sticky element sticks for as long as its containing block has room
		 * left, so a section made sticky where it stands has the whole page
		 * for a containing block and stays stuck until the page runs out: the
		 * row finishes crossing and the section keeps holding, for screens.
		 * Wrapping it means the containing block is exactly the host plus the
		 * runway, and the hold is exactly the runway.
		 *
		 * GSAP's ScrollTrigger does the same thing for the same reason, which
		 * is what its pin-spacer element is.
		 *
		 * @param {number} runway Pixels of scrolling to hold for.
		 */
		function pin( runway ) {
			wrapper = document.createElement( 'div' );
			wrapper.className = 'erail__pin';
			wrapper.style.width = '100%';
			wrapper.style.flex = '0 0 auto';
			wrapper.style.alignSelf = 'stretch';

			host.parentNode.insertBefore( wrapper, host );
			wrapper.appendChild( host );

			spacer = document.createElement( 'div' );
			spacer.className = 'erail__spacer';
			spacer.setAttribute( 'aria-hidden', 'true' );
			spacer.style.flex = '0 0 auto';
			spacer.style.height = Math.round( runway ) + 'px';
			wrapper.appendChild( spacer );

			host.style.position = 'sticky';
			host.style.top = stickyTop + 'px';
			root.setAttribute( 'data-erail-host', '' );
		}

		/**
		 * Put the page back exactly as it was found.
		 */
		function release() {
			if ( host ) {
				host.style.position = '';
				host.style.top = '';
			}

			// The host goes back where it was before the wrapper goes away,
			// or it goes away with it.
			if ( wrapper && wrapper.parentNode ) {
				if ( host && host.parentNode === wrapper ) {
					wrapper.parentNode.insertBefore( host, wrapper );
				}

				wrapper.parentNode.removeChild( wrapper );
			}

			wrapper = null;
			spacer = null;
			root.removeAttribute( 'data-erail-host' );
		}

		/**
		 * The pinned element's top edge in the window, as it would be if it
		 * were not pinned.
		 *
		 * Once something is stuck its own rect stops moving -- it reports the
		 * offset it is stuck at, every frame -- so progress cannot be read
		 * from it. The spacer is never stuck, and it sits immediately after
		 * the host, so the host's unstuck top is the spacer's top less the
		 * host's height. When the widget pins itself the stage is the stuck
		 * thing and the widget's own box still moves, so that can be read
		 * directly.
		 *
		 * @return {number}
		 */
		function staticTop() {
			if ( host && spacer ) {
				return spacer.getBoundingClientRect().top - host.offsetHeight;
			}

			return root.getBoundingClientRect().top;
		}

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
				release();
				host = null;
				return;
			}

			root.setAttribute( READY, '' );

			distance = Math.max( 0, track.scrollWidth - viewport.clientWidth );

			var height = window.innerHeight || document.documentElement.clientHeight || 0;

			if ( 'pinned' !== mode ) {
				stage.style.top = '';
				release();
				host = null;

				// Flow adds nothing by default. Extra is opt-in, and it is the
				// only thing here that costs the page any height at all.
				var extra = readData( root, 'data-erail-extra', 0 );

				if ( extra > 0 ) {
					root.style.height = Math.round( stage.offsetHeight + extra * height ) + 'px';
				}

				return;
			}

			// Measured unpinned, so release first: a host still carrying last
			// pass's sticky offset reports the height it is stuck at.
			release();
			host = resolveHost();

			var pinned = host || stage;

			/*
			 * Centred on the screen, from the height the pinned element turned
			 * out to be rather than the height it was asked for -- the stage's
			 * is usually `auto`, because the cards decide it.
			 *
			 * A section taller than the window cannot be centred without
			 * cropping both ends, so it is held against the top instead. The
			 * heading is the part worth keeping, and it is the part at the
			 * top.
			 */
			stickyTop = Math.max( 0, Math.round( ( height - pinned.offsetHeight ) / 2 ) );

			/*
			 * The runway is three stretches, not one.
			 *
			 *   pause in    the section is held and nothing moves, so the row
			 *               arrives, settles and is read before it goes
			 *               anywhere
			 *   travel      the row crosses, a pixel of scroll per pixel of
			 *               row at a pace of 1
			 *   pause out   the row is finished and the section is still held,
			 *               so the last cards are read before the page is
			 *               given back
			 *
			 * Padding a pinned stretch at both ends is how this is done
			 * everywhere -- GSAP's own recipe pads the timeline for exactly
			 * the same reason. Without it the row starts moving on the frame
			 * the section pins and the page is released on the frame it stops,
			 * and both read as a jolt.
			 */
			pauseIn = distance > 0 ? readData( root, 'data-erail-pause-in', 0.3 ) * height : 0;

			var pauseOut = distance > 0 ? readData( root, 'data-erail-pause-out', 0.3 ) * height : 0;

			travel = distance > 0 ? distance * readData( root, 'data-erail-pace', 1 ) : 0;

			var runway = Math.round( pauseIn + travel + pauseOut );

			if ( host ) {
				pin( runway );
			} else {
				stage.style.top = stickyTop + 'px';
				root.style.height = Math.round( stage.offsetHeight + runway ) + 'px';
			}
		}

		/**
		 * How far through the pinned stretch we are, 0 to 1.
		 *
		 * @return {number}
		 */
		function progress() {
			var rect = root.getBoundingClientRect();
			var height = window.innerHeight || document.documentElement.clientHeight || 0;

			if ( 'pinned' === mode ) {
				if ( travel <= 0 ) {
					return 0;
				}

				/*
				 * The pinned element sticks at `stickyTop` down the screen, so
				 * the pin begins when its top edge reaches that line, not when
				 * it reaches the top of the window. Measuring from the
				 * window's top instead puts every position out by the sticky
				 * offset, which is most of a card's height once it is centred.
				 */
				var into = stickyTop - staticTop();

				return clamp01( ( into - pauseIn ) / travel );
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

		var resizing = false;

		function onResize() {
			if ( resizing ) {
				return;
			}

			resizing = true;

			// Measuring now takes the host out of its wrapper and puts it
			// back, so dragging a window edge must not do that on every one of
			// the hundred resize events that produces.
			window.requestAnimationFrame( function () {
				measure();
				update();
				resizing = false;
			} );
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
			var target;

			if ( 'pinned' === mode ) {
				target = window.pageYOffset + staticTop() - stickyTop + pauseIn + wanted * travel;
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
