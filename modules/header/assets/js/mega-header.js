/**
 * Eruda Toolkit - Mega Header
 *
 * Frosts the bar once the page has left the top, opens one panel at a time
 * with a beat of intent before it commits, and runs the drawer on a phone.
 *
 * The stylesheet opens panels on hover and on focus-within all by itself, so
 * the navigation works with this file removed. What is added here is the part
 * CSS cannot reach: a scroll state, a single open panel rather than one per
 * pointer, the scrim, Escape, and the aria that makes the whole thing
 * announce itself properly.
 *
 * The moment it is ready it sets data-ehdr-ready, which hands the stylesheet
 * over to attribute-driven state and switches the hover rules off. Two sources
 * of truth is how a panel ends up open with nothing under the pointer.
 */
( function () {
	'use strict';

	var ROOT = '.ehdr';
	var READY = 'data-ehdr-ready';
	var ON = 'data-ehdr-on';
	var OPEN = 'data-ehdr-open';
	var STUCK = 'data-ehdr-stuck';
	var DRAWER = 'data-ehdr-drawer';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	function readNumber( el, name, fallback ) {
		var value = parseFloat( el.getAttribute( name ) );

		return isNaN( value ) ? fallback : value;
	}

	/**
	 * Set up one header.
	 *
	 * @param {Element} root Header wrapper.
	 */
	function init( root ) {
		if ( ! root || root.eanmHeaderReady ) {
			return;
		}

		root.eanmHeaderReady = true;

		var bar = root.querySelector( '.ehdr__bar' );
		var items = toArray( root.querySelectorAll( '.ehdr__item' ) );
		var burger = root.querySelector( '.ehdr__burger' );
		var scrim = root.querySelector( '.ehdr__scrim' );
		var spacer = root.parentNode && root.parentNode.querySelector( '.ehdr-spacer' );

		if ( ! bar ) {
			return;
		}

		var intent = readNumber( root, 'data-ehdr-intent', 90 );
		var stickAt = readNumber( root, 'data-ehdr-stick-at', 10 );
		var reduced = prefersReducedMotion();

		// A panel that opens on a delay must not open after the pointer has
		// gone somewhere else, so both timers are cancelled by every move.
		var openTimer = null;
		var closeTimer = null;
		var current = null;

		/*
		 * Set while focus is being moved deliberately.
		 *
		 * Closing with Escape has to put focus back on the trigger, or it is
		 * left inside a panel that is no longer on screen. But moving focus
		 * fires focusin, and focusin opens the panel -- so Escape closed it and
		 * the focus that followed opened it straight back up.
		 */
		var moving = false;

		/*
		 * Only the items that actually have a panel. An ordinary link is left
		 * entirely alone -- no handlers, no aria, nothing to go wrong on the
		 * four items out of six that are just links.
		 */
		var owners = items.filter( function ( item ) {
			return !! item.querySelector( '.ehdr__panel' );
		} );

		owners.forEach( function ( item, i ) {
			var trigger = item.querySelector( '.ehdr__link' );
			var panel = item.querySelector( '.ehdr__panel' );

			if ( ! trigger || ! panel ) {
				return;
			}

			if ( ! panel.id ) {
				panel.id = 'ehdr-panel-' + ( root.id || 'x' ) + '-' + i;
			}

			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.setAttribute( 'aria-controls', panel.id );
		} );

		function clearTimers() {
			if ( openTimer ) {
				window.clearTimeout( openTimer );
				openTimer = null;
			}

			if ( closeTimer ) {
				window.clearTimeout( closeTimer );
				closeTimer = null;
			}
		}

		/**
		 * Show one panel, or none.
		 *
		 * @param {?Element} item Item to open, or null to close everything.
		 */
		function show( item ) {
			if ( item === current ) {
				return;
			}

			current = item;

			owners.forEach( function ( one ) {
				var trigger = one.querySelector( '.ehdr__link' );
				var on = one === item;

				if ( on ) {
					one.setAttribute( ON, '' );
				} else {
					one.removeAttribute( ON );
				}

				if ( trigger ) {
					trigger.setAttribute( 'aria-expanded', on ? 'true' : 'false' );
				}
			} );

			if ( item ) {
				root.setAttribute( OPEN, '' );
			} else {
				root.removeAttribute( OPEN );
			}
		}

		function openLater( item ) {
			clearTimers();

			// An open panel swapping to its neighbour should not pause on the
			// way: the delay exists to stop a panel opening at all, not to
			// stagger a move between two that are already open.
			if ( current || reduced ) {
				show( item );

				return;
			}

			openTimer = window.setTimeout( function () {
				show( item );
			}, intent );
		}

		function closeLater() {
			clearTimers();

			// A short grace period, because the gap between the bar and the
			// panel is a pixel the pointer passes through on its way down.
			closeTimer = window.setTimeout( function () {
				show( null );
			}, 120 );
		}

		var pointerIsCoarse = (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(hover: none)' ).matches
		);

		owners.forEach( function ( item ) {
			var trigger = item.querySelector( '.ehdr__link' );
			var panel = item.querySelector( '.ehdr__panel' );

			item.addEventListener( 'mouseenter', function () {
				if ( ! pointerIsCoarse && ! isNarrow() ) {
					openLater( item );
				}
			} );

			item.addEventListener( 'mouseleave', function () {
				if ( ! pointerIsCoarse && ! isNarrow() ) {
					closeLater();
				}
			} );

			/*
			 * Tab into anything inside the item and it opens; tab out of the
			 * last link in the panel and it closes. focusout fires before the
			 * focus lands, so where it is going is read a tick later.
			 *
			 * Keyboard focus only, and the distinction matters.
			 *
			 * focusin fires on the way to a click -- mousedown, focus, mouseup,
			 * click -- so opening on any focus at all meant a tap on a phone
			 * opened the panel and the click that followed found it already
			 * open and toggled it straight back shut. The caret flipped, the
			 * label lit, and nothing else happened. :focus-visible is exactly
			 * the line wanted here: true when the focus came from the keyboard,
			 * false when it came from a pointer that is about to click anyway.
			 */
			item.addEventListener( 'focusin', function ( event ) {
				var target = event.target;
				var keyboard = true;

				if ( moving ) {
					return;
				}

				try {
					keyboard = !! ( target && target.matches && target.matches( ':focus-visible' ) );
				} catch ( e ) {
					// Older engines without :focus-visible keep the old
					// behaviour, which is wrong only for touch -- and those
					// engines are not the ones on the phones.
					keyboard = true;
				}

				if ( ! keyboard ) {
					return;
				}

				clearTimers();
				show( item );
			} );

			item.addEventListener( 'focusout', function () {
				window.setTimeout( function () {
					if ( ! item.contains( document.activeElement ) ) {
						if ( current === item ) {
							show( null );
						}
					}
				}, 0 );
			} );

			if ( ! trigger || ! panel ) {
				return;
			}

			/*
			 * A tap, or a click on the trigger.
			 *
			 * On a touch screen the first tap on a top-level item opens its
			 * panel rather than following the link -- otherwise the panel is
			 * unreachable, since there is no hover to open it with. The second
			 * tap follows the link, which is the behaviour every phone menu
			 * has trained people to expect.
			 */
			trigger.addEventListener( 'click', function ( event ) {
				var narrow = isNarrow();

				if ( ! narrow && ! pointerIsCoarse ) {
					return;
				}

				if ( current !== item ) {
					event.preventDefault();
					clearTimers();
					show( item );
				} else if ( narrow ) {
					// In the drawer the trigger is a toggle both ways: there
					// is nowhere else to tap to close it.
					event.preventDefault();
					show( null );
				}
			} );
		} );

		/* --------------------------------------------------------- scrim --- */

		if ( scrim ) {
			scrim.addEventListener( 'click', function () {
				clearTimers();
				show( null );
			} );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key && 'Esc' !== event.key ) {
				return;
			}

			if ( current ) {
				var trigger = current.querySelector( '.ehdr__link' );

				clearTimers();
				show( null );

				// Focus goes back to what opened it, or it is left in a panel
				// that is no longer on screen.
				if ( trigger && trigger.focus ) {
					moving = true;
					trigger.focus();

					// Cleared a tick later, not straight away: focusin is
					// dispatched synchronously by focus() but focusout's own
					// deferred check runs after it.
					window.setTimeout( function () {
						moving = false;
					}, 0 );
				}
			}

			if ( root.hasAttribute( DRAWER ) ) {
				closeDrawer();
			}
		} );

		/* -------------------------------------------------------- drawer --- */

		function isNarrow() {
			return (
				typeof window.matchMedia === 'function' &&
				window.matchMedia( '(max-width: 1024px)' ).matches
			);
		}

		function closeDrawer() {
			root.removeAttribute( DRAWER );

			if ( burger ) {
				burger.setAttribute( 'aria-expanded', 'false' );
			}

			show( null );
		}

		if ( burger ) {
			burger.setAttribute( 'aria-expanded', 'false' );

			burger.addEventListener( 'click', function () {
				var open = root.hasAttribute( DRAWER );

				if ( open ) {
					closeDrawer();

					return;
				}

				root.setAttribute( DRAWER, '' );
				burger.setAttribute( 'aria-expanded', 'true' );
			} );
		}

		// Following a link inside the drawer has to shut it, or coming back to
		// a cached page finds the menu still hanging open over the content.
		root.addEventListener( 'click', function ( event ) {
			var link = event.target.closest ? event.target.closest( 'a[href]' ) : null;

			if ( ! link || ! root.contains( link ) ) {
				return;
			}

			if ( link.classList.contains( 'ehdr__link' ) && link.querySelector( '.ehdr__caret' ) ) {
				return;
			}

			if ( root.hasAttribute( DRAWER ) ) {
				closeDrawer();
			} else {
				show( null );
			}
		} );

		/* --------------------------------------------------------- frost --- */

		/*
		 * One attribute, flipped at a threshold.
		 *
		 * Nothing is measured per frame and nothing is written unless the
		 * state has actually changed: the frost is a CSS transition, so the
		 * scroll handler's whole job is to say which side of the line the page
		 * is on. A header that recalculated styles on every frame of every
		 * scroll would be the most expensive thing on the page, and it is on
		 * every page.
		 */
		var stuck = null;

		function onScroll() {
			var now = ( window.pageYOffset || document.documentElement.scrollTop || 0 ) > stickAt;

			if ( now === stuck ) {
				return;
			}

			stuck = now;

			if ( now ) {
				root.setAttribute( STUCK, '' );
			} else {
				root.removeAttribute( STUCK );
			}

			/*
			 * Crossing the threshold moves the bar -- it gains a gap at the
			 * top and narrows -- so the scrim's starting line moves with it.
			 * Measured after the transition rather than during it, because
			 * during it the number is whatever frame we happened to catch.
			 */
			window.setTimeout( measure, 340 );
		}

		/*
		 * The scrim starts below the bar, so it never blurs the bar itself.
		 * The bar's height is only read when it can have changed -- on resize,
		 * and once at startup -- rather than on every scroll.
		 */
		function measure() {
			var box = bar.getBoundingClientRect();

			/*
			 * The bottom edge, not the height.
			 *
			 * Once it frosts the bar comes away from the top by a gap, so its
			 * height and its bottom edge are no longer the same number. The
			 * scrim has to start at the bottom edge or it creeps up over the
			 * bar and blurs it.
			 */
			root.style.setProperty( '--ehdr-scrim-top', Math.round( box.bottom ) + 'px' );

			if ( spacer ) {
				spacer.style.height = spacer.hasAttribute( 'data-ehdr-hold' )
					? Math.round( box.height ) + 'px'
					: '';
			}
		}

		var ticking = false;

		function onScrollFrame() {
			if ( ticking ) {
				return;
			}

			ticking = true;

			window.requestAnimationFrame( function () {
				onScroll();
				ticking = false;
			} );
		}

		function onResize() {
			measure();

			// A drawer left open across a resize to desktop is a menu stuck
			// half way between two layouts.
			if ( ! isNarrow() && root.hasAttribute( DRAWER ) ) {
				closeDrawer();
			}
		}

		root.setAttribute( READY, '' );

		/*
		 * A held state, for looking at it.
		 *
		 * Nothing is wired up when one is set: the point is to freeze the
		 * header so it can be studied in the editor or on the page, and a
		 * frozen thing that still reacts to the pointer is not frozen. The
		 * scroll listener is skipped too, so "hold it frosted" stays frosted
		 * at the top of the page.
		 */
		var preview = root.getAttribute( 'data-ehdr-preview' ) || '';

		if ( 'open' === preview || 'stuck' === preview ) {
			if ( 'open' === preview && owners.length ) {
				show( owners[0] );
			}

			measure();

			return;
		}

		measure();
		onScroll();
		window.addEventListener( 'scroll', onScrollFrame, { passive: true } );
		window.addEventListener( 'resize', onResize, { passive: true } );
	}

	/*
	 * The scramble.
	 *
	 * Characters are replaced with noise and settle left to right, one more
	 * locking into place every few frames, so the word resolves rather than
	 * flickering as a whole. Spaces are never scrambled -- a word boundary
	 * that moves turns the label into a different shape on every frame.
	 *
	 * Driven by a timer rather than by requestAnimationFrame on purpose: it
	 * wants to be slow. At 60fps a six-letter word would resolve in a tenth of
	 * a second and read as a glitch instead of a decode.
	 */
	var NOISE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#%&$@/\\';

	function scramble( el ) {
		var text = el.getAttribute( 'data-ehdr-text' ) || el.textContent;
		var done = 0;
		var ticks = 0;

		if ( el.eanmScramble ) {
			window.clearInterval( el.eanmScramble );
		}

		// The width is pinned before anything moves: a proportional font
		// changes width with every swap, and the items beside it would be
		// shoved back and forth for the length of the effect.
		if ( ! el.style.minWidth ) {
			el.style.minWidth = el.getBoundingClientRect().width + 'px';
		}

		el.eanmScramble = window.setInterval( function () {
			ticks += 1;

			if ( ticks % 2 === 0 ) {
				done += 1;
			}

			var out = '';

			for ( var i = 0; i < text.length; i++ ) {
				if ( i < done || ' ' === text.charAt( i ) ) {
					out += text.charAt( i );
				} else {
					out += NOISE.charAt( Math.floor( Math.random() * NOISE.length ) );
				}
			}

			el.textContent = out;

			if ( done >= text.length ) {
				window.clearInterval( el.eanmScramble );
				el.eanmScramble = null;
				el.textContent = text;
			}
		}, 40 );
	}

	function wireScramble( root ) {
		if ( prefersReducedMotion() ) {
			return;
		}

		toArray( root.querySelectorAll( '.ehdr__scramble' ) ).forEach( function ( el ) {
			var link = el.closest ? el.closest( '.ehdr__link' ) : null;
			var target = link || el;

			target.addEventListener( 'mouseenter', function () {
				scramble( el );
			} );

			target.addEventListener( 'focus', function () {
				scramble( el );
			} );
		} );
	}

	function initAll( scope ) {
		toArray( ( scope || document ).querySelectorAll( ROOT ) ).forEach( function ( root ) {
			init( root );
			wireScramble( root );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eanm-mega-header.default', function ( scope ) {
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
