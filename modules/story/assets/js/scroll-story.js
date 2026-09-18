/**
 * Eruda Toolkit - Scroll Story
 *
 * Decides which item you are reading, lights its text, and cross-fades the
 * pinned panel to that item's image.
 *
 * Vanilla, no dependencies, safe to run twice. If it never runs, the section
 * is a column of readable text beside its first image -- which is why the
 * stylesheet dims nothing until this file has set data-estry-ready.
 */
( function () {
	'use strict';

	var ROOT = '.estry';
	var READY = 'data-estry-ready';
	var ON = 'data-estry-on';

	var uid = 0;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * Build the clip path for a notched panel.
	 *
	 * The notch cuts *into* the panel along its left edge: flush at the top
	 * and bottom, stepping inwards by `depth` across a band, with rounded
	 * corners and a diagonal run between them.
	 *
	 * The proportions are taken from the reference, measured off its own clip
	 * path at a 30px depth, then expressed as ratios of the depth so any depth
	 * keeps the same shape:
	 *
	 *   arc radius    21.5 / 30 = 0.717
	 *   arc rise      14.05 / 30 = 0.468   arc run  5.23 / 30 = 0.174
	 *   diagonal rise 22.64 / 30 = 0.755   run     19.54 / 30 = 0.651
	 *
	 * One transition is therefore 1.692 x depth tall. Generated in script
	 * rather than written as CSS because a curve in a clip path is in user
	 * units: it has to be rebuilt whenever the panel is resized.
	 *
	 * @param {number} w      Panel width.
	 * @param {number} h      Panel height.
	 * @param {number} depth  How far the notch cuts in.
	 * @param {number} run    Straight length of the inset section.
	 * @param {number} centre Where the notch sits, 0 to 1 down the panel.
	 * @return {string} An SVG path.
	 */
	function notchPath( w, h, depth, run, centre ) {
		var r = 0.717 * depth;
		var arcRise = 0.468 * depth;
		var arcRun = 0.174 * depth;
		var diagRise = 0.755 * depth;
		var transition = 1.692 * depth;

		// Keep the whole notch on the panel, however short the panel is.
		var needed = run + transition * 2;

		if ( needed > h ) {
			run = Math.max( 0, h - transition * 2 );
			needed = run + transition * 2;
		}

		var top = ( h - needed ) * Math.min( Math.max( centre, 0 ), 1 );
		var bottom = top + needed;

		// Down the left edge, into the notch, along it, and back out.
		return [
			'M 0,0',
			'L ' + w + ',0',
			'L ' + w + ',' + h,
			'L 0,' + h,
			'L 0,' + bottom.toFixed( 2 ),
			'A ' + r.toFixed( 2 ) + ',' + r.toFixed( 2 ) + ' 0 0 1 ' + arcRun.toFixed( 2 ) + ',' + ( bottom - arcRise ).toFixed( 2 ),
			'L ' + ( depth - arcRun ).toFixed( 2 ) + ',' + ( bottom - arcRise - diagRise ).toFixed( 2 ),
			'A ' + r.toFixed( 2 ) + ',' + r.toFixed( 2 ) + ' 0 0 0 ' + depth.toFixed( 2 ) + ',' + ( bottom - transition ).toFixed( 2 ),
			'L ' + depth.toFixed( 2 ) + ',' + ( top + transition ).toFixed( 2 ),
			'A ' + r.toFixed( 2 ) + ',' + r.toFixed( 2 ) + ' 0 0 0 ' + ( depth - arcRun ).toFixed( 2 ) + ',' + ( top + arcRise + diagRise ).toFixed( 2 ),
			'L ' + arcRun.toFixed( 2 ) + ',' + ( top + arcRise ).toFixed( 2 ),
			'A ' + r.toFixed( 2 ) + ',' + r.toFixed( 2 ) + ' 0 0 1 0,' + top.toFixed( 2 ),
			'Z',
		].join( ' ' );
	}

	/**
	 * Read a length custom property as a plain number of pixels.
	 *
	 * @param {Element} el       Element to read from.
	 * @param {string}  name     Property name.
	 * @param {number}  fallback Value when unreadable.
	 * @return {number}
	 */
	function readNumber( el, name, fallback ) {
		var raw = '';

		try {
			raw = ( window.getComputedStyle( el ).getPropertyValue( name ) || '' ).trim();
		} catch ( e ) {
			raw = '';
		}

		var value = parseFloat( raw );

		return isNaN( value ) ? fallback : value;
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Split an element's text into per-character spans, leaving any inline
	 * markup alone.
	 *
	 * Walks child nodes rather than rewriting innerHTML, so a <strong> or a
	 * link inside the copy survives. Whitespace goes back as plain text so
	 * words still wrap.
	 *
	 * @param {Element} el    Element to split.
	 * @param {Object}  state Carries the running index.
	 */
	function split( el, state ) {
		toArray( el.childNodes ).forEach( function ( node ) {
			if ( 1 === node.nodeType && ! /^(BR|IMG|SVG)$/.test( node.tagName ) ) {
				split( node, state );
				return;
			}

			if ( 3 !== node.nodeType || '' === ( node.data || '' ).trim() ) {
				return;
			}

			var fragment = document.createDocumentFragment();

			( node.data || '' ).split( /(\s+)/ ).forEach( function ( part ) {
				if ( '' === part ) {
					return;
				}

				if ( /^\s+$/.test( part ) ) {
					fragment.appendChild( document.createTextNode( part ) );
					return;
				}

				// A word is wrapped so it cannot be broken mid-word at a line
				// end, then split inside that wrapper.
				var word = document.createElement( 'span' );
				word.className = 'estry-w';
				word.style.whiteSpace = 'nowrap';

				( typeof Array.from === 'function' ? Array.from( part ) : part.split( '' ) ).forEach( function ( character ) {
					var span = document.createElement( 'span' );
					span.className = 'estry-c';
					span.style.setProperty( '--estry-d', ( state.index * state.step ).toFixed( 3 ) + 's' );
					span.appendChild( document.createTextNode( character ) );
					word.appendChild( span );
					state.index += 1;
				} );

				fragment.appendChild( word );
			} );

			node.parentNode.replaceChild( fragment, node );
		} );
	}

	/**
	 * Which item is closest to the middle of the screen?
	 *
	 * The middle is the natural reading line, and it is also where the panel
	 * should already be showing that item's picture.
	 *
	 * @param {Element[]} items Items.
	 * @return {number} Index, or -1 when none is near.
	 */
	function activeIndex( items ) {
		var middle = ( window.innerHeight || document.documentElement.clientHeight ) / 2;
		var best = -1;
		var bestDistance = Infinity;

		for ( var i = 0; i < items.length; i++ ) {
			var rect = items[ i ].getBoundingClientRect();
			var centre = rect.top + rect.height / 2;
			var distance = Math.abs( centre - middle );

			if ( distance < bestDistance ) {
				bestDistance = distance;
				best = i;
			}
		}

		return best;
	}

	/**
	 * Set up one section.
	 *
	 * @param {Element} root Section wrapper.
	 */
	function init( root ) {
		if ( ! root || root.eanmStoryReady ) {
			return;
		}

		root.eanmStoryReady = true;

		var items = toArray( root.querySelectorAll( '.estry__item' ) );
		var images = toArray( root.querySelectorAll( '.estry__img' ) );
		var frame = root.querySelector( '.estry__frame' );
		var notched = frame && frame.hasAttribute( 'data-estry-notch' );
		var clipPath = null;

		if ( notched ) {
			uid += 1;

			var id = 'estry-notch-' + uid;
			var svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );

			svg.setAttribute( 'width', '0' );
			svg.setAttribute( 'height', '0' );
			svg.setAttribute( 'aria-hidden', 'true' );
			svg.style.position = 'absolute';

			var defs = document.createElementNS( 'http://www.w3.org/2000/svg', 'defs' );
			clipPath = document.createElementNS( 'http://www.w3.org/2000/svg', 'clipPath' );
			clipPath.setAttribute( 'id', id );
			clipPath.setAttribute( 'clipPathUnits', 'userSpaceOnUse' );

			var path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
			clipPath.appendChild( path );
			defs.appendChild( clipPath );
			svg.appendChild( defs );
			frame.parentNode.insertBefore( svg, frame );

			frame.style.clipPath = 'url(#' + id + ')';
			frame.style.webkitClipPath = 'url(#' + id + ')';
			clipPath = path;
		}

		if ( ! items.length ) {
			return;
		}

		if ( ! prefersReducedMotion() ) {
			var step = parseFloat( root.getAttribute( 'data-estry-step' ) );

			items.forEach( function ( item ) {
				// Each item's sweep starts from zero, so a later item does not
				// inherit a delay from everything above it.
				var state = { index: 0, step: isNaN( step ) ? 0.014 : step };

				toArray( item.querySelectorAll( '.estry__title, .estry__body' ) ).forEach( function ( el ) {
					split( el, state );
				} );
			} );
		}

		root.setAttribute( READY, '' );

		if ( frame ) {
			frame.setAttribute( READY, '' );
		}

		var current = -1;

		function update() {
			var index = activeIndex( items );

			if ( index !== current && index > -1 ) {
				current = index;

				items.forEach( function ( item, i ) {
					if ( i === index ) {
						item.setAttribute( ON, '' );
					} else {
						item.removeAttribute( ON );
					}
				} );

				images.forEach( function ( image, i ) {
					if ( i === index ) {
						image.setAttribute( ON, '' );
					} else {
						image.removeAttribute( ON );
					}
				} );
			}

			// The notch travels with how far you are through the section.
			if ( clipPath ) {
				var rect = root.getBoundingClientRect();
				var viewport = window.innerHeight || document.documentElement.clientHeight;
				var span = rect.height - viewport;
				var progress = span > 0 ? Math.min( Math.max( -rect.top / span, 0 ), 1 ) : 0;
				var box = frame.getBoundingClientRect();
				var depth = parseFloat( readNumber( frame, '--estry-notch', 30 ) );
				var run = parseFloat( readNumber( frame, '--estry-band-size', 280 ) );

				clipPath.setAttribute(
					'd',
					notchPath( Math.round( box.width ), Math.round( box.height ), depth, run, progress )
				);
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

		update();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
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

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eanm-scroll-story.default', function ( scope ) {
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
