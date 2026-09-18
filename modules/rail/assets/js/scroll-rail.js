/**
 * Eruda Toolkit - Scroll Rail
 *
 * Pins a row of cards and moves it sideways by however far you have scrolled
 * down past it, so reading the row left to right is the same gesture as
 * reading the page.
 *
 * The runway -- the extra page height that the sideways travel is spent
 * against -- is measured from the row itself rather than guessed, so a row of
 * three cards is short and a row of twelve is long, and neither strands you
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

		// How much of the runway is spent standing still at each end, so the
		// row does not start moving the instant its first pixel appears.
		var hold = readData( root, 'data-erail-hold', 0.08 );
		var pinned = false;
		var distance = 0;
		var runway = 0;

		/**
		 * Should this rail pin at all?
		 *
		 * Below the breakpoint, and under reduced motion, the row stays a
		 * plain scroller. Both are checked live rather than once, because a
		 * window gets resized and an operating system setting gets changed.
		 *
		 * @return {bool}
		 */
		function shouldPin() {
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
			pinned = shouldPin();

			if ( ! pinned ) {
				root.style.height = '';
				track.style.transform = '';
				root.removeAttribute( READY );
				return;
			}

			root.setAttribute( READY, '' );

			// Cleared first: the track's own width is what is being measured,
			// and a transform left over from the last pass does not change it,
			// but a stale inline height on the root does change the stage.
			root.style.height = '';

			distance = Math.max( 0, track.scrollWidth - viewport.clientWidth );

			var stageHeight = stage.offsetHeight;

			// Travel one screen-height of scrolling for each screen-width of
			// row, so the sideways speed feels the same whatever the row's
			// length, plus the hold at each end.
			runway = distance > 0 ? distance * readData( root, 'data-erail-pace', 1 ) : 0;

			root.style.height = Math.round( stageHeight + runway ) + 'px';
		}

		/**
		 * How far through the pinned stretch we are, 0 to 1.
		 *
		 * @return {number}
		 */
		function progress() {
			var rect = root.getBoundingClientRect();
			var span = rect.height - stage.offsetHeight;

			if ( span <= 0 ) {
				return 0;
			}

			var raw = clamp01( -rect.top / span );

			// Ease the two ends so the row is still for a moment as it arrives
			// and as it leaves, instead of snapping into motion on the frame
			// the section reaches the top of the screen.
			if ( hold <= 0 || hold >= 0.5 ) {
				return raw;
			}

			return clamp01( ( raw - hold ) / ( 1 - hold * 2 ) );
		}

		function update() {
			if ( ! pinned ) {
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
			if ( pinned && viewport.scrollLeft !== 0 ) {
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
			if ( ! pinned || ! distance ) {
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
			var span = rect.height - stage.offsetHeight;
			var raw = hold > 0 && hold < 0.5 ? wanted * ( 1 - hold * 2 ) + hold : wanted;
			var target = window.pageYOffset + rect.top + raw * span;

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
