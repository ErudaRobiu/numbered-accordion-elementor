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
	 * What this showcase was set to, read off its own root.
	 *
	 * On the root rather than a global, because a page may carry more than one
	 * showcase and they do not have to agree.
	 *
	 * @param {Element} root Widget root.
	 * @return {Object}
	 */
	function options( root ) {
		var num = function ( name, fallback ) {
			var v = parseFloat( root.getAttribute( name ) );
			return isFinite( v ) ? v : fallback;
		};

		return {
			axis: root.getAttribute( 'data-eind-axis' ) === 'x' ? 'x' : 'y',
			flip: root.getAttribute( 'data-eind-flip' ) === '1',
			block: num( 'data-eind-block', 44 ),
			wipe: num( 'data-eind-wipe', 900 ),
			jitter: num( 'data-eind-jitter', 0.55 )
		};
	}

	/**
	 * The same hash the reference's shader uses, so a cell's number depends on
	 * where it is and nothing else.
	 *
	 * Deterministic on purpose: a cell keeps its number across a rebuild, so
	 * resizing the window does not reshuffle the pattern mid-sweep.
	 *
	 * @param {number} x Column.
	 * @param {number} y Row.
	 * @return {number} 0 to 1.
	 */
	function noise( x, y ) {
		var n = Math.sin( x * 12.9898 + y * 78.233 ) * 43758.5453123;
		return n - Math.floor( n );
	}

	/**
	 * Build the grid of blocks over a frame.
	 *
	 * Built here rather than in PHP because it depends on the frame's measured
	 * size: the blocks have to stay square, and the frame changes shape
	 * between a phone and a desktop.
	 *
	 * @param {Element} frame Frame element.
	 * @param {Object}  opts  Settings.
	 * @return {void}
	 */
	function buildGrid( frame, opts ) {
		var grid = frame.querySelector( '.eind__grid' );

		if ( ! grid ) {
			return;
		}

		var rect = frame.getBoundingClientRect();

		if ( ! rect.width || ! rect.height ) {
			return;
		}

		var size = Math.max( 8, opts.block );
		var cols = Math.max( 3, Math.round( rect.width / size ) );
		var rows = Math.max( 3, Math.round( rect.height / size ) );

		if ( grid.getAttribute( 'data-eind-grid' ) === cols + 'x' + rows ) {
			return;
		}

		grid.setAttribute( 'data-eind-grid', cols + 'x' + rows );
		grid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
		grid.style.gridTemplateRows = 'repeat(' + rows + ', 1fr)';

		var along = opts.axis === 'x' ? cols : rows;
		var html = '';
		var row;
		var col;
		var step;
		var d;
		var o;

		for ( row = 0; row < rows; row++ ) {
			for ( col = 0; col < cols; col++ ) {
				step = opts.axis === 'x' ? col : row;

				if ( opts.flip ) {
					step = along - 1 - step;
				}

				// Position along the sweep, nudged by the cell's own number.
				// The nudge is what ragged the front: without it the band is a
				// straight line and the blocks are decoration rather than the
				// effect.
				d = ( step + noise( col, row ) * opts.jitter ) / along;

				// A second number, from a different corner of the same hash,
				// so a cell's brightness is not tied to its position in the
				// queue.
				o = 0.45 + noise( row + 7, col + 31 ) * 0.55;

				html += '<span class="eind__cell" style="--d:' + d.toFixed( 4 ) +
					';--o:' + o.toFixed( 3 ) + '"></span>';
			}
		}

		grid.innerHTML = html;
	}

	/**
	 * Which way the hard edge clips in from.
	 *
	 * @param {Object} opts Settings.
	 * @return {string} A clip-path inset().
	 */
	function revealFrom( opts ) {
		if ( opts.axis === 'x' ) {
			return opts.flip ? 'inset(0 0 0 100%)' : 'inset(0 100% 0 0)';
		}

		return opts.flip ? 'inset(100% 0 0 0)' : 'inset(0 0 100% 0)';
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

		var opts   = options( root );
		var names  = root.querySelectorAll( '.eind__name' );
		var shots  = root.querySelectorAll( '.eind__shot' );
		var ticks  = root.querySelectorAll( '.eind__tick' );
		var panels = root.querySelectorAll( '.eind__panel' );
		var count  = root.querySelector( '.eind__count' );
		var frame  = root.querySelector( '.eind__frame' );
		var total  = names.length;
		var current = -1;
		var queued = false;
		var wipeTimer = null;

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

			var previous = current;

			current = index;

			var i;

			for ( i = 0; i < total; i++ ) {
				names[ i ].setAttribute( 'aria-current', i === index ? 'true' : 'false' );
			}

			// The picture leaving stays visible underneath until the front has
			// passed over it, so the sweep eats a picture rather than an empty
			// frame.
			var leaving = previous >= 0 && previous < shots.length ? shots[ previous ] : null;

			for ( i = 0; i < shots.length; i++ ) {
				shots[ i ].classList.toggle( 'eind__shot--on', i === index );
				shots[ i ].classList.remove( 'eind__shot--out' );
			}

			if ( frame && leaving && ! reducedMotion() ) {
				leaving.classList.add( 'eind__shot--out' );

				// Restarting an animation means taking the class off, forcing
				// the browser to notice, and putting it back. Without the
				// reflow the second change in a row does not animate at all.
				frame.classList.remove( 'eind__frame--wipe' );
				void frame.offsetWidth;
				frame.classList.add( 'eind__frame--wipe' );

				window.clearTimeout( wipeTimer );
				wipeTimer = window.setTimeout( function () {
					frame.classList.remove( 'eind__frame--wipe' );
					leaving.classList.remove( 'eind__shot--out' );
				}, opts.wipe + 120 );
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

		if ( frame ) {
			frame.style.setProperty( '--eind-reveal-from', revealFrom( opts ) );
			buildGrid( frame, opts );
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', function () {
			onScroll();

			if ( frame ) {
				buildGrid( frame, opts );
			}
		} );

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
