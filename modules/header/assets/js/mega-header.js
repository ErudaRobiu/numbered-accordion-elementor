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
	var HIDDEN = 'data-ehdr-hidden';

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

		// How far you have to keep going in one direction before the bar
		// agrees you meant it.
		var grab = readNumber( root, 'data-ehdr-grab', 60 );

		/*
		 * Which direction folds the bar.
		 *
		 *   'up'   -- the folded bar appears when you scroll back towards the
		 *             top, and the full-width one is what you see on the way
		 *             down. Reading gets the plain header; going back for the
		 *             menu is what summons the compact one.
		 *   'down' -- the other way round, which is what the reference does.
		 *
		 * Either way the very top of the page is always the full-width state.
		 */
		var scrollMode = root.getAttribute( 'data-ehdr-scroll' ) || 'hide';

		if ( 'hide' !== scrollMode && 'up' !== scrollMode && 'down' !== scrollMode ) {
			scrollMode = 'hide';
		}
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
				root.removeAttribute( HIDDEN );
				hidden = false;
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

		/*
		 * 1199 and not 1024.
		 *
		 * A six-item menu, a logo and a button do not fit across a 1025px
		 * window whatever the padding does -- the row overflowed its own bar
		 * and the button hung off the end. The drawer is the right layout for
		 * that band, so the breakpoint sits above it.
		 */
		function isNarrow() {
			return (
				typeof window.matchMedia === 'function' &&
				window.matchMedia( '(max-width: 1199px)' ).matches
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
		 * Which way you are going, not how far down you are.
		 *
		 * This is the reference's behaviour and it took tracing the inline
		 * style through a scroll to see it. At 600px down, scrolling *down*,
		 * their bar is 80% wide and 16px from the top; at the same 600px,
		 * scrolling *up*, it is back to full width and flush. It is not a
		 * threshold on scroll position at all -- the position only decides
		 * whether the compact state is allowed yet.
		 *
		 * Scrolling down compacts it; scrolling up gives it back. That is why
		 * a threshold flip felt wrong: it fights you on the way back up, where
		 * the reference gets out of the way.
		 *
		 * The travel is accumulated rather than acted on per event, because a
		 * trackpad emits a stream of one and two pixel deltas and flipping the
		 * state on any of them is a bar that flickers between two layouts.
		 */
		var stuck = null;
		var lastY = window.pageYOffset || document.documentElement.scrollTop || 0;
		var travel = 0;
		var compactWidth = 0;
		var hidden = false;
		var foldTimer = null;

		// How long the slide takes, so the fold can be timed to land after it.
		var slideMs = 520;

		function setFold( on ) {
			if ( on ) {
				root.setAttribute( STUCK, '' );
			} else {
				root.removeAttribute( STUCK );
			}
		}

		/**
		 * Fold the bar, and do it where it cannot be seen.
		 *
		 * While the bar is on its way out there is nothing to watch, so the
		 * change waits for it to be gone and is then made with the transitions
		 * switched off for a frame. You see the full-width bar leave and the
		 * folded one arrive, and never the morph between them -- which is the
		 * part that looked wrong however well it was eased.
		 *
		 * Unfolding at the top of the page is left alone: the bar is on screen
		 * and in front of you there, and an expansion you can see is the point
		 * rather than the problem.
		 *
		 * @param {boolean} on Fold it, or let it out.
		 */
		function applyFold( on ) {
			if ( foldTimer ) {
				window.clearTimeout( foldTimer );
				foldTimer = null;
			}

			if ( ! hidden ) {
				setFold( on );

				return;
			}

			foldTimer = window.setTimeout( function () {
				foldTimer = null;

				// It may have come back in the meantime, in which case there
				// is something to watch again and the change should be eased.
				if ( ! hidden ) {
					setFold( on );

					return;
				}

				root.classList.add( 'is-instant' );
				setFold( on );

				// Read something geometric to force the style through before
				// the class comes off, or the freeze never applies to the very
				// change it was added for.
				void root.offsetWidth;

				window.requestAnimationFrame( function () {
					root.classList.remove( 'is-instant' );
				} );
			}, slideMs );
		}

		function onScroll() {
			var y = window.pageYOffset || document.documentElement.scrollTop || 0;
			var delta = y - lastY;

			lastY = y;

			// Direction changed: start counting this way from nothing.
			if ( ( delta > 0 ) !== ( travel > 0 ) ) {
				travel = 0;
			}

			travel += delta;

			var now = stuck;
			var away = hidden;
			var goingDown = travel > grab;
			var goingUp = travel < -grab;

			if ( y <= stickAt ) {
				// The top of the page is always the full-width state, on show,
				// whatever the last gesture was.
				now = false;
				away = false;
			} else if ( 'hide' === scrollMode ) {
				/*
				 * Out of the way on the way down, back on the way up.
				 *
				 * It comes back already folded rather than full width: coming
				 * back up the page is when you want the menu, and the folded
				 * bar is the one that says so.
				 */
				if ( goingDown ) {
					away = true;
					now = true;
				} else if ( goingUp ) {
					away = false;
					now = true;
				}
			} else if ( 'up' === scrollMode ) {
				away = false;

				if ( goingUp ) {
					now = true;
				} else if ( goingDown ) {
					now = false;
				}
			} else {
				away = false;

				if ( goingDown ) {
					now = true;
				} else if ( goingUp ) {
					now = false;
				}
			}

			// Never hidden with a panel open -- that would take the panel with
			// it, and something you just opened must not leave on its own.
			if ( current ) {
				away = false;
			}

			/*
			 * And never fold into something that does not fit.
			 *
			 * The folded bar is as wide as its own contents plus the clearance
			 * it keeps either side, and on a narrow window that is wider than
			 * the window. Folding anyway clamped the bar and left the row at
			 * its full size, so the button hung out past the rounded edge --
			 * which is exactly what it looked like between about 1025 and
			 * 1150px. Staying full width is the honest answer: the menu still
			 * fits there, it just cannot be a floating bar as well.
			 */
			if ( now && compactWidth > 0 && compactWidth > window.innerWidth ) {
				now = false;
			}

			if ( away !== hidden ) {
				hidden = away;

				if ( away ) {
					root.setAttribute( HIDDEN, '' );
				} else {
					root.removeAttribute( HIDDEN );
				}
			}

			if ( now === stuck ) {
				return;
			}

			stuck = now;
			applyFold( now );

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
		/**
		 * What the row needs when it is sized to its contents.
		 *
		 * Read by holding the compact layout for one synchronous measurement
		 * and dropping it again before the frame is painted, so nothing
		 * flashes. It costs a forced layout, which is why it happens on
		 * startup and on resize rather than on scroll.
		 *
		 * Without it the compact width is `fit-content`, and `width` cannot
		 * interpolate from a percentage to an intrinsic keyword -- the change
		 * snaps most of the way on the first frame and eases the rest.
		 */
		function measureCompact() {
			var inner = root.querySelector( '.ehdr__inner' );

			if ( ! inner ) {
				return 0;
			}

			root.classList.add( 'is-measuring' );

			// Rounded up: a fractional content width that rounds down clips
			// the last letter of the last menu item.
			var width = Math.ceil( bar.getBoundingClientRect().width );

			// The clearance it is asked to keep either side, which is part of
			// what the folded bar needs rather than something it can eat into.
			var inset = parseFloat(
				window.getComputedStyle( root ).getPropertyValue( '--ehdr-stuck-inset' )
			);

			root.classList.remove( 'is-measuring' );

			return width + ( isNaN( inset ) ? 32 : inset ) * 2;
		}

		function measure() {
			var ms = parseFloat(
				window.getComputedStyle( root ).getPropertyValue( '--ehdr-ms' )
			);

			if ( ! isNaN( ms ) && ms > 0 ) {
				// A value in seconds is still a number; anything under 20 is
				// not a plausible millisecond count for this.
				slideMs = ms < 20 ? ms * 1000 : ms;
			}

			var compact = measureCompact();

			compactWidth = compact;

			if ( compact > 0 ) {
				// The property is the bar's own width, so the clearance that
				// measureCompact() added for the fit test comes back off.
				var inset = parseFloat(
					window.getComputedStyle( root ).getPropertyValue( '--ehdr-stuck-inset' )
				);

				inset = isNaN( inset ) ? 32 : inset;

				root.style.setProperty( '--ehdr-stuck-width-px', ( compact - inset * 2 ) + 'px' );
			}

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

			// The folded bar may no longer fit, or may fit again.
			if ( null !== stuck ) {
				stuck = null;
				travel = 0;
				onScroll();
			}

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

		/*
		 * Webfonts change the answer.
		 *
		 * The row is measured in whatever font is available at startup, and a
		 * webfont arriving afterwards makes every label a different width. One
		 * more measurement once the fonts have settled costs a single layout
		 * and saves a compact bar that is the wrong size all session.
		 */
		if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
			document.fonts.ready.then( function () {
				measure();
			} ).catch( function () {} );
		}

		window.addEventListener( 'scroll', onScrollFrame, { passive: true } );
		window.addEventListener( 'resize', onResize, { passive: true } );
	}

	/*
	 * The scramble, which shuffles the word's own letters.
	 *
	 * Traced off the reference a character at a time. Hovering "About Us"
	 * there gives, in order:
	 *
	 *   About Us -> UosuAtb  -> AbsUAt o -> AbAot us -> About U  -> About Us
	 *
	 * Not random glyphs. Every frame is an anagram of the label: the front of
	 * the word locks in one character at a time, left to right, and whatever
	 * has not locked yet is the remaining real characters in a shuffled order.
	 * It reads as the word sorting itself out rather than as static, which is
	 * the whole difference between this and every other scramble effect.
	 *
	 * It is also why their markup pins every label's width in three places at
	 * once -- the same letters in a different order measure differently in a
	 * proportional font, and an unpinned label would jitter its neighbours
	 * about for the length of the effect.
	 */
	function shuffle( list ) {
		for ( var i = list.length - 1; i > 0; i-- ) {
			var j = Math.floor( Math.random() * ( i + 1 ) );
			var t = list[ i ];

			list[ i ] = list[ j ];
			list[ j ] = t;
		}

		return list;
	}

	function scramble( el, step ) {
		var text = el.getAttribute( 'data-ehdr-text' ) || el.textContent;
		var chars = text.split( '' );
		var done = 0;

		if ( el.eanmScramble ) {
			window.clearInterval( el.eanmScramble );
		}

		/*
		 * The width is pinned before anything moves, in all three properties.
		 * min-width alone is not enough inside a flex row, where the item can
		 * still be grown by its siblings' shrinking.
		 */
		if ( ! el.style.width ) {
			var w = el.getBoundingClientRect().width;

			el.style.width = w + 'px';
			el.style.minWidth = w + 'px';
			el.style.maxWidth = w + 'px';
			el.style.display = 'inline-block';
			el.style.whiteSpace = 'nowrap';
		}

		el.eanmScramble = window.setInterval( function () {
			done += 1;

			if ( done >= chars.length ) {
				window.clearInterval( el.eanmScramble );
				el.eanmScramble = null;
				el.textContent = text;

				return;
			}

			// The settled head, then the rest of the real characters in some
			// other order.
			var rest = shuffle( chars.slice( done ) );

			el.textContent = chars.slice( 0, done ).join( '' ) + rest.join( '' );
		}, step || 45 );
	}

	function wireScramble( root ) {
		if ( prefersReducedMotion() ) {
			return;
		}

		var step = readNumber( root, 'data-ehdr-scramble-step', 45 );

		toArray( root.querySelectorAll( '.ehdr__scramble' ) ).forEach( function ( el ) {
			var link = el.closest ? el.closest( '.ehdr__link, .ehdr__panel-link' ) : null;
			var target = link || el;

			target.addEventListener( 'mouseenter', function () {
				scramble( el, step );
			} );

			target.addEventListener( 'focus', function () {
				scramble( el, step );
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
