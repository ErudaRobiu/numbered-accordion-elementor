/**
 * Industry Showcase.
 *
 * Reads the page's own scroll position and turns it into an index. It does not
 * capture the scroll, hijack the wheel or animate the page: the section is
 * simply tall and the panel inside it is sticky, so the browser does the
 * scrolling and this only decides which item that position means. A visitor can
 * still flick, drag the scrollbar, page down or land mid-section from a link,
 * and all of those work because none of them are special-cased.
 *
 * @package ErudaToolkit
 */

( function () {
	'use strict';

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
	 * Set up one showcase.
	 *
	 * @param {Element} root Widget root.
	 */
	function showcase( root ) {
		if ( root.hasAttribute( 'data-eind-ready' ) ) {
			return;
		}

		root.setAttribute( 'data-eind-ready', '' );

		var names  = root.querySelectorAll( '.eind__name' );
		var shots  = root.querySelectorAll( '.eind__shot' );
		var ticks  = root.querySelectorAll( '.eind__tick' );
		var panels = root.querySelectorAll( '.eind__panel' );
		var count  = root.querySelector( '.eind__count' );
		var total  = names.length;
		var current = -1;
		var queued = false;

		if ( ! total ) {
			return;
		}

		/**
		 * Light up one item and put the others out.
		 *
		 * @param {number} index Zero-based.
		 */
		function show( index ) {
			if ( index === current ) {
				return;
			}

			current = index;

			var i;

			for ( i = 0; i < total; i++ ) {
				names[ i ].setAttribute( 'aria-current', i === index ? 'true' : 'false' );
			}

			for ( i = 0; i < shots.length; i++ ) {
				shots[ i ].classList.toggle( 'eind__shot--on', i === index );
			}

			for ( i = 0; i < ticks.length; i++ ) {
				ticks[ i ].classList.toggle( 'eind__tick--on', i === index );
			}

			for ( i = 0; i < panels.length; i++ ) {
				panels[ i ].classList.toggle( 'eind__panel--on', i === index );
				panels[ i ].setAttribute( 'aria-hidden', i === index ? 'false' : 'true' );
			}

			if ( count ) {
				count.textContent = count.getAttribute( 'data-eind-format' )
					.replace( '%1$s', pad( index + 1 ) )
					.replace( '%2$s', pad( total ) );
			}
		}

		/**
		 * Pad a number so the counter does not change width as it counts.
		 *
		 * @param {number} n Number.
		 * @return {string}
		 */
		function pad( n ) {
			var width = Math.max( 2, String( total ).length );
			var out = String( n );

			while ( out.length < width ) {
				out = '0' + out;
			}

			return out;
		}

		/**
		 * Work out where we are and act on it.
		 *
		 * The section is taller than the viewport by exactly the distance the
		 * panel is pinned for, so that difference is the full travel.
		 */
		function measure() {
			queued = false;

			var rect = root.getBoundingClientRect();
			var span = root.offsetHeight - window.innerHeight;

			// Below the breakpoint the panel is not pinned and the section is
			// its content's height, so there is no travel and nothing to do.
			if ( span <= 0 ) {
				show( 0 );
				return;
			}

			var progress = -rect.top / span;

			if ( progress < 0 ) {
				progress = 0;
			}

			if ( progress > 1 ) {
				progress = 1;
			}

			root.style.setProperty( '--eind-p', progress.toFixed( 4 ) );

			// Mirrors Industry_Content::index() in PHP. The nudge below one
			// keeps a progress of exactly 1 inside the last band rather than
			// one past it.
			var index = Math.floor( progress * total * 0.9999 );

			if ( index < 0 ) {
				index = 0;
			}

			if ( index > total - 1 ) {
				index = total - 1;
			}

			show( index );
		}

		/**
		 * Coalesce scroll events onto frames.
		 *
		 * Scroll fires far more often than the screen redraws, and every one
		 * of these reads layout.
		 */
		function onScroll() {
			if ( queued ) {
				return;
			}

			queued = true;
			window.requestAnimationFrame( measure );
		}

		// Clicking a name scrolls to the middle of that item's band, rather
		// than to its start where the next one is about to take over.
		for ( var i = 0; i < total; i++ ) {
			( function ( index ) {
				names[ index ].addEventListener( 'click', function () {
					var span = root.offsetHeight - window.innerHeight;

					if ( span <= 0 ) {
						show( index );
						return;
					}

					var top = root.getBoundingClientRect().top + window.pageYOffset;
					var to  = top + span * ( ( index + 0.5 ) / total );

					window.scrollTo( {
						top: to,
						behavior: reducedMotion() ? 'auto' : 'smooth'
					} );
				} );
			}( i ) );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll );

		measure();
	}

	/**
	 * Find every showcase on the page.
	 */
	function boot() {
		var roots = document.querySelectorAll( '.eind' );

		for ( var i = 0; i < roots.length; i++ ) {
			showcase( roots[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Elementor rebuilds a widget's markup on every edit, so the editor needs
	// telling to set the new copy up again.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/eind-industry-showcase.default',
				function ( $scope ) {
					var root = $scope[ 0 ].querySelector( '.eind' );

					if ( root ) {
						root.removeAttribute( 'data-eind-ready' );
						showcase( root );
					}
				}
			);
		} );
	}
}() );
