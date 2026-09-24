/**
 * Page Transitions.
 *
 * Three jobs, in this order of importance:
 *
 *   1. Never trap a visitor. Every wait here has a timeout that ends in the
 *      page being revealed, and the whole thing sits inside a try/catch that
 *      strips the curtain if anything throws. The failure this guards against
 *      is a client's site showing a solid coloured rectangle and nothing else,
 *      which is worse than having no transition at all.
 *   2. Reveal the page when it is actually ready, not when a timer says so.
 *   3. Move the columns.
 *
 * The covering state is handed across a real navigation in sessionStorage and
 * re-applied by an inline script in the head, before the browser paints. That
 * script is the reason there is no flash on arrival; this file takes over once
 * the document is parsed.
 *
 * @package ErudaToolkit
 */

( function () {
	'use strict';

	var COVERING = 'etrn:covering';
	var VISITED  = 'etrn:visited';

	var HTML = document.documentElement;

	/**
	 * Settings, with the defaults applied. PHP may replace this wholesale.
	 *
	 * @return {Object}
	 */
	function options() {
		var given = ( window.erudaTransitions && window.erudaTransitions.options ) || {};

		return {
			columns: given.columns || 6,
			travel: typeof given.travel === 'number' ? given.travel : 0.18,
			stagger: typeof given.stagger === 'number' ? given.stagger : 0.05,
			preloader: given.preloader !== false,
			percentage: given.percentage !== false,
			minimum: typeof given.minimum === 'number' ? given.minimum : 600,
			maximum: typeof given.maximum === 'number' ? given.maximum : 4000,
			sweep: typeof given.sweep === 'number' ? given.sweep : 0.43
		};
	}

	/**
	 * Has this visitor asked for less movement?
	 *
	 * @return {boolean}
	 */
	function reducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Is this the Elementor editor?
	 *
	 * A curtain that swallows link clicks would make the canvas unusable.
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
			return true;
		}
	}

	/**
	 * sessionStorage, or a shrug.
	 *
	 * Private browsing and blocked site data both make these throw rather than
	 * return null, and a throw here would leave the curtain up.
	 *
	 * @param {string} key   Key.
	 * @param {string|null} value Value to write, or null to read.
	 * @return {string|null}
	 */
	function session( key, value ) {
		try {
			if ( value === null ) {
				return window.sessionStorage.getItem( key );
			}

			if ( value === false ) {
				window.sessionStorage.removeItem( key );
				return null;
			}

			window.sessionStorage.setItem( key, value );
			return value;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Wait for an animation to finish, or give up.
	 *
	 * The reference this was modelled on waits on animationend alone, having
	 * already called preventDefault. If that event never lands — a display
	 * change mid-flight, a dropped frame at the wrong moment, an extension
	 * disabling animations — the link is simply dead. So every wait here is
	 * whichever comes first: the event, or the clock.
	 *
	 * @param {Element}  el    Element whose animation to wait on.
	 * @param {number}   ms    Fallback in milliseconds.
	 * @param {Function} done  Called exactly once.
	 */
	function whenSettled( el, ms, done ) {
		var finished = false;

		function finish() {
			if ( finished ) {
				return;
			}

			finished = true;

			if ( el ) {
				el.removeEventListener( 'animationend', finish );
			}

			window.clearTimeout( timer );
			done();
		}

		var timer = window.setTimeout( finish, ms );

		if ( el ) {
			el.addEventListener( 'animationend', finish );
		} else {
			// No column to watch means no animation to wait for.
			window.clearTimeout( timer );
			window.setTimeout( finish, 0 );
		}
	}

	/**
	 * The column that finishes last.
	 *
	 * Not simply the last one in the markup: below the mobile breakpoint the
	 * widest columns are hidden, and a hidden element's animation never fires
	 * an animationend for anybody to wait on.
	 *
	 * @param {Element} curtain Curtain root.
	 * @return {Element|null}
	 */
	function lastVisibleColumn( curtain ) {
		var columns = curtain ? curtain.querySelectorAll( '.etrn__col' ) : [];
		var last    = null;
		var i;

		for ( i = 0; i < columns.length; i++ ) {
			if ( window.getComputedStyle( columns[ i ] ).display !== 'none' ) {
				last = columns[ i ];
			}
		}

		return last;
	}

	/**
	 * Is this page loaded enough to be worth showing?
	 *
	 * Fonts, the images a visitor would actually see, and the load event —
	 * whichever of them finish, against a cap. Deliberately not every image on
	 * the page: a long page's footer images are nobody's reason to wait.
	 *
	 * @param {number}   cap  Milliseconds after which to stop caring.
	 * @param {Function} done Called once.
	 */
	function whenReady( cap, done ) {
		var settled = false;
		var pending = 0;

		function finish() {
			if ( settled ) {
				return;
			}

			settled = true;
			window.clearTimeout( timer );
			done();
		}

		var timer = window.setTimeout( finish, cap );

		function step() {
			pending--;

			if ( pending <= 0 ) {
				finish();
			}
		}

		var waits = [];

		if ( document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function' ) {
			waits.push( document.fonts.ready );
		}

		var images = document.querySelectorAll( 'img' );
		var i;
		var rect;

		for ( i = 0; i < images.length; i++ ) {
			rect = images[ i ].getBoundingClientRect();

			// Only what intersects the first screenful.
			if ( rect.top > window.innerHeight || rect.bottom < 0 ) {
				continue;
			}

			if ( images[ i ].complete ) {
				continue;
			}

			if ( typeof images[ i ].decode === 'function' ) {
				// A decode that rejects is still a decode that finished as far
				// as this is concerned: a broken image must not hold the page.
				waits.push( images[ i ].decode().catch( function () {} ) );
			}
		}

		if ( document.readyState !== 'complete' ) {
			waits.push(
				new Promise( function ( resolve ) {
					window.addEventListener( 'load', resolve, { once: true } );
				} )
			);
		}

		if ( ! waits.length ) {
			finish();
			return;
		}

		pending = waits.length;

		for ( i = 0; i < waits.length; i++ ) {
			waits[ i ].then( step, step );
		}
	}

	/**
	 * Run the preloader, then reveal.
	 *
	 * The bar eases toward ninety and waits there. Snapping it to a hundred is
	 * what readiness buys, which is the one honest thing a progress bar can do
	 * without knowing the future.
	 *
	 * @param {Element} curtain Curtain root.
	 * @param {Object}  opts    Settings.
	 * @param {Function} reveal Called when it is time to open.
	 */
	function preload( curtain, opts, reveal ) {
		var fill    = curtain.querySelector( '.etrn__fill' );
		var number  = curtain.querySelector( '.etrn__num' );
		var started = ( window.performance && window.performance.now )
			? window.performance.now()
			: Date.now();
		var ready   = false;
		var frame   = null;
		var snapAt  = 0;
		var belt    = null;
		var opened  = false;

		/**
		 * Open, once and only once.
		 *
		 * Two paths race to get here — the frame loop when the page is ready,
		 * and the belt timer when the frame loop is not running — and both
		 * have to be shut down by whichever arrives first.
		 *
		 * @param {number} delay Milliseconds to hold the finished bar.
		 */
		function done( delay ) {
			if ( opened ) {
				return;
			}

			opened = true;

			if ( belt !== null ) {
				window.clearTimeout( belt );
			}

			if ( frame !== null ) {
				window.cancelAnimationFrame( frame );
			}

			window.setTimeout( reveal, delay );
		}

		function now() {
			return ( window.performance && window.performance.now )
				? window.performance.now()
				: Date.now();
		}

		function paint( value ) {
			if ( fill ) {
				fill.style.transform = 'scaleX(' + ( value / 100 ) + ')';
			}

			if ( number && opts.percentage ) {
				number.textContent = String( Math.floor( value ) );
			}
		}

		function tick() {
			var elapsed = now() - started;

			// Approaches ninety and never reaches it, at a rate tuned so a
			// four-second wait still looks like it is moving.
			var crawl = 90 * ( 1 - Math.exp( -elapsed / ( opts.maximum * 0.45 ) ) );

			// The snap to a hundred does not begin until the page is both ready
			// and past its minimum. Starting it earlier would leave the
			// counter sitting on 100% waiting for a clock, which reads as the
			// page having stalled at the finish line.
			if ( ready && elapsed >= opts.minimum ) {
				if ( snapAt === 0 ) {
					snapAt = now();
				}

				var span = Math.min( ( now() - snapAt ) / 220, 1 );

				paint( crawl + ( 100 - crawl ) * span );

				if ( span >= 1 ) {
					paint( 100 );
					done( 120 );
					return;
				}
			} else {
				paint( crawl );
			}

			frame = window.requestAnimationFrame( tick );
		}

		whenReady( opts.maximum, function () {
			ready = true;
		} );

		// The belt to whenReady's braces. If the frame loop stops running — a
		// backgrounded tab throttles rAF to nothing — this still opens.
		//
		// It has to be cancelled by whichever path gets there first. Left
		// running, it fired long after the preloader had finished and swept a
		// second curtain across a page the visitor was already reading.
		belt = window.setTimeout( function () {
			paint( 100 );
			done( 0 );
		}, opts.maximum + 1200 );

		frame = window.requestAnimationFrame( tick );
	}

	/**
	 * Everything, once the document is parsed.
	 */
	function start() {
		var opts    = options();
		var curtain = document.querySelector( '.etrn' );

		if ( ! curtain ) {
			return;
		}

		// A sweep's worth of milliseconds, plus room for a slow frame.
		var sweepMs = ( opts.sweep * 1000 ) + 250;

		/**
		 * Take the curtain down and leave no state behind.
		 */
		function clear() {
			HTML.classList.remove( 'etrn-preloading', 'etrn-covering', 'etrn-entering', 'etrn-leaving' );
			document.body.removeAttribute( 'aria-busy' );
			curtain.style.display = '';
			session( COVERING, false );
		}

		var revealed = false;

		/**
		 * Sweep the columns off the top.
		 *
		 * Guarded because a second call does not repeat a finished animation,
		 * it starts a new one: the leaving class puts the curtain back on
		 * screen before moving it, so a stray call sweeps a fresh curtain
		 * across a page the visitor is already reading. A page is revealed
		 * once.
		 */
		function reveal() {
			if ( revealed ) {
				return;
			}

			revealed = true;

			// Removing the covering class and adding the leaving one in the
			// same frame lets the browser coalesce them, and the columns jump
			// instead of moving. Reading offsetWidth between the two forces
			// the intermediate state to be real.
			HTML.classList.remove( 'etrn-covering', 'etrn-preloading' );
			session( COVERING, false );

			void curtain.offsetWidth;

			HTML.classList.add( 'etrn-leaving' );

			whenSettled( lastVisibleColumn( curtain ), sweepMs, function () {
				clear();
				session( VISITED, '1' );
			} );
		}

		/**
		 * Draw the columns up over the page, then do the thing.
		 *
		 * @param {Function} then Called when covered.
		 */
		function cover( then ) {
			HTML.classList.add( 'etrn-entering' );
			whenSettled( lastVisibleColumn( curtain ), sweepMs, then );
		}

		// ---------------------------------------------------- arriving ---

		if ( HTML.classList.contains( 'etrn-preloading' ) ) {
			document.body.setAttribute( 'aria-busy', 'true' );
			preload( curtain, opts, reveal );
		} else if ( HTML.classList.contains( 'etrn-covering' ) ) {
			reveal();
		} else {
			// Nothing to undo, but the flag still has to be set or the next
			// page of this visit would run the preloader again.
			session( VISITED, '1' );
			clear();
		}

		// ---------------------------------------------------- leaving ----

		document.addEventListener( 'click', function ( event ) {
			if ( event.defaultPrevented || event.button !== 0 ) {
				return;
			}

			// A modified click is a request for a new tab or a download. It is
			// not ours to animate.
			if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}

			var link = event.target.closest ? event.target.closest( 'a' ) : null;

			if ( ! link || ! link.href || ! shouldIntercept( link ) ) {
				return;
			}

			event.preventDefault();

			var target = link.href;

			// Start fetching while the columns move, so the cover is not dead
			// time. Harmless if the browser declines.
			try {
				var hint = document.createElement( 'link' );
				hint.rel = 'prefetch';
				hint.href = target;
				document.head.appendChild( hint );
			} catch ( e ) {
				// Nothing to do: this is an optimisation, not a step.
			}

			cover( function () {
				session( COVERING, '1' );
				window.location.href = target;
			} );
		} );

		// Coming back through history restores the page as it was, curtain and
		// all. Reloading would fix it at the cost of a round trip the visitor
		// did not ask for; clearing it is instant and looks the same.
		window.addEventListener( 'pageshow', function ( event ) {
			if ( event.persisted ) {
				clear();
			}
		} );
	}

	/**
	 * Should a click on this link be animated?
	 *
	 * @param {HTMLAnchorElement} link Link.
	 * @return {boolean}
	 */
	function shouldIntercept( link ) {
		if ( link.hasAttribute( 'download' ) || link.target === '_blank' ) {
			return false;
		}

		if ( link.classList.contains( 'no-transition' ) || link.hasAttribute( 'data-no-transition' ) ) {
			return false;
		}

		if ( link.getAttribute( 'rel' ) === 'external' ) {
			return false;
		}

		if ( link.protocol !== 'http:' && link.protocol !== 'https:' ) {
			return false;
		}

		if ( link.host !== window.location.host ) {
			return false;
		}

		if ( link.pathname.indexOf( '/wp-admin' ) === 0 || link.pathname.indexOf( '/wp-login' ) === 0 ) {
			return false;
		}

		// An anchor on the page we are already on is a scroll, not a journey.
		if (
			link.hash &&
			link.pathname === window.location.pathname &&
			link.search === window.location.search
		) {
			return false;
		}

		// The same page entirely. Nothing would change behind the curtain.
		if (
			link.pathname === window.location.pathname &&
			link.search === window.location.search &&
			! link.hash
		) {
			return false;
		}

		return true;
	}

	/**
	 * Refuse to run, and make sure nothing is left covering the page.
	 */
	function standDown() {
		try {
			HTML.classList.remove( 'etrn-preloading', 'etrn-covering', 'etrn-entering', 'etrn-leaving' );
			session( COVERING, false );

			var curtain = document.querySelector( '.etrn' );

			if ( curtain ) {
				curtain.style.display = 'none';
			}
		} catch ( e ) {
			// There is nothing further to try.
		}
	}

	function boot() {
		if ( reducedMotion() || inEditor() ) {
			standDown();
			return;
		}

		try {
			start();
		} catch ( e ) {
			// Whatever went wrong, the one outcome that must not happen is a
			// visitor left looking at a coloured rectangle.
			standDown();

			if ( window.console && window.console.warn ) {
				window.console.warn( 'Eruda Toolkit: page transitions stood down.', e );
			}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
