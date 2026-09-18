/**
 * Eruda Toolkit - Hotspot Stats
 *
 * Draws the leader line between each label and the point on the picture it
 * belongs to, and counts the figures up when the section arrives.
 *
 * The lines have to be script-drawn: a leader runs between two points given as
 * percentages of a picture whose size is whatever the column happens to be, so
 * it only exists in rendered pixels and has to be rebuilt on every resize.
 *
 * Vanilla, no dependencies, safe to run twice. If it never runs the figure is
 * finished rather than empty -- every label is readable and every number is
 * already its final value, because the markup carries them and this file only
 * counts up to what is already there.
 */
( function () {
	'use strict';

	var ROOT = '.espot';
	var READY = 'data-espot-ready';
	var IN = 'data-espot-in';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	function readData( el, name, fallback ) {
		var value = parseFloat( el.getAttribute( name ) );

		return isNaN( value ) ? fallback : value;
	}

	/**
	 * Is this narrow enough that the annotation comes off the picture?
	 *
	 * Checked live rather than once, because a window gets resized.
	 *
	 * @return {bool}
	 */
	function isNarrow() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(max-width: 767px)' ).matches
		);
	}

	/**
	 * Read a percentage custom property as a fraction.
	 *
	 * @param {Element} el   Element to read from.
	 * @param {string}  name Property name.
	 * @return {number} 0 to 1, or 0.5 when unreadable.
	 */
	function readPercent( el, name ) {
		var raw = '';

		try {
			raw = ( window.getComputedStyle( el ).getPropertyValue( name ) || '' ).trim();
		} catch ( e ) {
			raw = '';
		}

		var value = parseFloat( raw );

		return isNaN( value ) ? 0.5 : value / 100;
	}

	/**
	 * Split a figure into the number and whatever is written around it.
	 *
	 * Someone types "13+" or "$1.2m", and only the middle of that can be
	 * counted. Keeping the rest verbatim means the control stays a plain text
	 * field rather than three.
	 *
	 * @param {string} text The figure as written.
	 * @return {Object|null} {before, number, after, decimals} or null.
	 */
	function parseFigure( text ) {
		var match = /^(\D*?)([\d][\d,\s]*(?:\.\d+)?)(.*)$/.exec( String( text ).trim() );

		if ( ! match ) {
			return null;
		}

		var digits = match[2].replace( /[,\s]/g, '' );
		var number = parseFloat( digits );

		if ( isNaN( number ) ) {
			return null;
		}

		var dot = digits.indexOf( '.' );

		return {
			before: match[1],
			number: number,
			after: match[3],
			decimals: dot === -1 ? 0 : digits.length - dot - 1,
			grouped: /[,\s]/.test( match[2] ),
		};
	}

	/**
	 * Write a counted value back in the shape it was written in.
	 *
	 * @param {Object} figure From parseFigure.
	 * @param {number} value  Current value.
	 * @return {string}
	 */
	function format( figure, value ) {
		var fixed = value.toFixed( figure.decimals );

		if ( figure.grouped ) {
			var parts = fixed.split( '.' );
			parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
			fixed = parts.join( '.' );
		}

		return figure.before + fixed + figure.after;
	}

	/**
	 * Set up one figure.
	 *
	 * @param {Element} root Section wrapper.
	 */
	function init( root ) {
		if ( ! root || root.erudaSpotsReady ) {
			return;
		}

		root.erudaSpotsReady = true;

		var frame = root.querySelector( '.espot__frame' );
		var lines = root.querySelector( '.espot__lines' );
		var items = toArray( root.querySelectorAll( '.espot__item' ) );

		if ( ! frame || ! items.length ) {
			return;
		}

		var reduced = prefersReducedMotion();
		var stagger = readData( root, 'data-espot-stagger', 90 );
		var paths = [];

		// Each hotspot's line is delayed by its place in the row, so they
		// arrive in reading order rather than all at once.
		items.forEach( function ( item, i ) {
			item.style.setProperty( '--espot-delay', Math.round( i * stagger ) + 'ms' );
		} );

		if ( lines ) {
			items.forEach( function ( item ) {
				var path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );

				lines.appendChild( path );
				paths.push( path );
			} );
		}

		/**
		 * Rebuild every leader from the picture's rendered size.
		 *
		 * The line is an elbow rather than a straight run: it leaves the label
		 * horizontally for a short stub and only then turns for the dot. A
		 * single diagonal from a word to a rivet reads as a stray mark; the
		 * stub is what makes it read as a leader drawn on a drawing.
		 */
		function draw() {
			if ( ! lines || ! paths.length ) {
				return;
			}

			var box = frame.getBoundingClientRect();

			if ( ! box.width || ! box.height ) {
				return;
			}

			lines.setAttribute( 'viewBox', '0 0 ' + box.width + ' ' + box.height );

			items.forEach( function ( item, i ) {
				var path = paths[ i ];
				var label = item.querySelector( '.espot__label' );

				if ( ! path ) {
					return;
				}

				var ax = readPercent( item, '--espot-ax' ) * box.width;
				var ay = readPercent( item, '--espot-ay' ) * box.height;
				var lx = readPercent( item, '--espot-lx' ) * box.width;
				var ly = readPercent( item, '--espot-ly' ) * box.height;

				// The leader leaves from the label's own edge, not from the
				// point it is positioned at, or it would start underneath the
				// words.
				if ( label ) {
					var lb = label.getBoundingClientRect();

					ly = lb.top - box.top + lb.height / 2;
					lx = 'left' === item.getAttribute( 'data-espot-side' )
						? lb.left - box.left
						: lb.right - box.left;
				}

				var toward = ax >= lx ? 1 : -1;
				var stub = Math.min( readData( root, 'data-espot-stub', 18 ), Math.abs( ax - lx ) / 2 );
				var turn = lx + stub * toward;

				path.setAttribute( 'd', 'M ' + lx.toFixed( 1 ) + ',' + ly.toFixed( 1 ) +
					' L ' + turn.toFixed( 1 ) + ',' + ly.toFixed( 1 ) +
					' L ' + ax.toFixed( 1 ) + ',' + ay.toFixed( 1 ) );

				// The dash has to be the path's own length or the draw would
				// finish early on a short leader and late on a long one.
				var length = 0;

				try {
					length = path.getTotalLength();
				} catch ( e ) {
					length = 0;
				}

				path.style.setProperty( '--espot-len', length.toFixed( 1 ) );
				path.style.setProperty( '--espot-delay', Math.round( i * stagger ) + 'ms' );
			} );
		}

		/**
		 * Count one figure up to the value already in the markup.
		 *
		 * @param {Element} el Value element.
		 */
		function count( el ) {
			var figure = parseFigure( el.getAttribute( 'data-espot-value' ) || el.textContent );

			if ( ! figure || reduced ) {
				return;
			}

			var duration = readData( root, 'data-espot-count', 1200 );

			if ( duration <= 0 ) {
				return;
			}

			var started = null;

			el.textContent = format( figure, 0 );

			function step( now ) {
				if ( null === started ) {
					started = now;
				}

				var t = Math.min( 1, ( now - started ) / duration );

				// Out, not linear: a figure that decelerates into its value
				// looks counted, one that stops dead looks cut off.
				var eased = 1 - Math.pow( 1 - t, 3 );

				el.textContent = format( figure, figure.number * eased );

				if ( t < 1 ) {
					window.requestAnimationFrame( step );
				} else {
					el.textContent = format( figure, figure.number );
				}
			}

			window.requestAnimationFrame( step );
		}

		var arrived = false;

		function arrive() {
			if ( arrived ) {
				return;
			}

			arrived = true;
			root.setAttribute( IN, '' );

			toArray( root.querySelectorAll( '.espot__value' ) ).forEach( function ( el, i ) {
				window.setTimeout( function () {
					count( el );
				}, i * stagger );
			} );
		}

		function onResize() {
			if ( isNarrow() ) {
				// The lines are not drawn on a phone, and measuring a label
				// that has been re-laid-out into a list would place them
				// nowhere useful anyway.
				return;
			}

			draw();
		}

		root.setAttribute( READY, '' );
		draw();

		// A picture still loading has no height, and a leader measured against
		// no height lands at the top of the frame.
		toArray( root.querySelectorAll( 'img' ) ).forEach( function ( image ) {
			if ( ! image.complete ) {
				image.addEventListener( 'load', onResize );
				image.addEventListener( 'error', onResize );
			}
		} );

		if ( typeof window.IntersectionObserver === 'function' ) {
			var observer = new window.IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						arrive();
						observer.disconnect();
					}
				} );
			}, { threshold: 0.25 } );

			observer.observe( root );
		} else {
			arrive();
		}

		window.addEventListener( 'resize', onResize, { passive: true } );

		if ( typeof window.ResizeObserver === 'function' ) {
			// A label reflows when its column changes width without the window
			// changing at all, which a resize listener never hears about.
			new window.ResizeObserver( onResize ).observe( frame );
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

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eanm-hotspot-stats.default', function ( scope ) {
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
