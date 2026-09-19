/**
 * Eruda Toolkit - Scroll Story
 *
 * Scrubs a per-character highlight down the text as you scroll, and moves a
 * pinned panel to whichever item you have reached.
 *
 * The highlight is not played on a timer. How far an item has travelled up
 * the screen decides how many of its characters are lit, so the sweep runs
 * forwards as you scroll down and retreats as you scroll back up. A character
 * lighting up plays a short flash; a character going out simply transitions
 * back, which is the asymmetry that makes the reverse feel like an undo
 * rather than a second animation.
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

	function clamp01( value ) {
		return value < 0 ? 0 : value > 1 ? 1 : value;
	}

	function viewportHeight() {
		return window.innerHeight || document.documentElement.clientHeight || 0;
	}

	/**
	 * Build the clip path for a notched panel.
	 *
	 * The notch cuts *into* the panel along its left edge: flush at the top
	 * and bottom, stepping inwards by `depth` across a band, with rounded
	 * corners and a diagonal run between them.
	 *
	 * The proportions are read off the reference's own clip path at a 30px
	 * depth and expressed as ratios of the depth, so any depth keeps the same
	 * shape:
	 *
	 *   arc radius    28.17 / 30 = 0.939
	 *   arc rise      14.80 / 30 = 0.4933  arc run   4.21 / 30 = 0.1405
	 *   diagonal rise 34.90 / 30 = 1.1633  diag run 21.58 / 30 = 0.7190
	 *
	 * The two arc runs and the diagonal run add up to exactly the depth, and
	 * the rises add up to 2.15 x depth, which is one transition.
	 *
	 * The band does not sweep the whole edge. On the reference it travels 40%
	 * of the panel's height and is centred in what is left over, so the notch
	 * always stops well short of both corners -- 66px short at each end on a
	 * 985px panel. Letting it run to the corners is what made ours look like a
	 * bite out of the edge rather than a detail travelling along it.
	 *
	 * Generated in script rather than written as CSS because a curve in a clip
	 * path is in user units: it has to be rebuilt whenever the panel resizes.
	 *
	 * The four corners are rounded in the same path. They have to be: a
	 * clip path and a border radius clip the same box, so a notched panel
	 * cannot take its radius from CSS. Rounding them here is what lets a panel
	 * have both, and the band is kept inside the corner arcs so the two can
	 * never meet.
	 *
	 * @param {number} w        Panel width.
	 * @param {number} h        Panel height.
	 * @param {number} depth    How far the notch cuts in.
	 * @param {number} run      Straight length of the inset section.
	 * @param {number} progress How far through the section, 0 to 1.
	 * @param {number} span     Share of the panel height the band travels.
	 * @param {number} radius   Corner radius.
	 * @return {string} An SVG path.
	 */
	function notchPath( w, h, depth, run, progress, span, radius, across ) {
		/*
		 * Along the bottom instead of down the side.
		 *
		 * The shape is the same shape; only the edge differs. Rather than
		 * write it twice, the whole path is built for a vertical edge on a box
		 * with its sides swapped, and every point is turned a quarter turn on
		 * the way out: (x, y) leaves as (y, w - x), which puts the notched
		 * edge -- x = 0 -- along the bottom of the real box.
		 *
		 * A quarter turn is a rotation, not a mirror, so unlike a plain
		 * reflection it leaves the arcs the way round they were drawn and the
		 * sweep flags are passed through untouched.
		 *
		 * This is what lets a phone have the notch at all: stacked, a picture
		 * is wide and short, and a notch down its left edge would have nowhere
		 * to travel. The bottom edge and not the top, because the notch is the
		 * seam between a picture and the item under it -- above the picture it
		 * only cuts into the words it was meant to sit below.
		 */
		if ( across ) {
			var swapped = w;
			w = h;
			h = swapped;
		}

		var r = 0.939 * depth;
		var arcRise = 0.4933 * depth;
		var arcRun = 0.1405 * depth;
		var diagRise = 1.1633 * depth;
		var transition = 2.15 * depth;
		var c = Math.max( 0, Math.min( radius, w / 2, h / 2 ) );

		function at( x, y ) {
			// `w` is the swapped width by now, which is the real box's height,
			// so `w - x` is the distance measured up from its bottom edge.
			return across ? y.toFixed( 2 ) + ',' + ( w - x ).toFixed( 2 ) : x.toFixed( 2 ) + ',' + y.toFixed( 2 );
		}

		function arc( radius2, sweep, x, y ) {
			return 'A ' + radius2.toFixed( 2 ) + ',' + radius2.toFixed( 2 ) + ' 0 0 ' +
				sweep + ' ' + at( x, y );
		}

		// The band lives between the corner arcs, not between the corners.
		var usable = Math.max( 0, h - c * 2 );
		var needed = run + transition * 2;

		if ( needed > usable ) {
			run = Math.max( 0, usable - transition * 2 );
			needed = run + transition * 2;
		}

		/*
		 * The band never consumes all the room it has. One shoulder's worth is
		 * held back so there is always a straight stretch of edge between the
		 * notch and each corner -- without it, a generous corner radius or a
		 * long band leaves the notch starting exactly where the corner curve
		 * does, and the two read as one dent. On the reference's proportions
		 * this reserve changes nothing: it has room to spare.
		 */
		var free = Math.max( 0, usable - needed );
		var reserve = Math.min( transition, free * 0.4 );
		var travel = Math.min( span * h, Math.max( 0, free - reserve ) );
		var top = c + ( free - travel ) / 2 + clamp01( progress ) * travel;
		var bottom = top + needed;

		// Round the top, down the right, round the bottom, then up the left
		// edge, into the notch, along it, and back out.
		return [
			'M ' + at( c, 0 ),
			'L ' + at( w - c, 0 ),
			arc( c, 1, w, c ),
			'L ' + at( w, h - c ),
			arc( c, 1, w - c, h ),
			'L ' + at( c, h ),
			arc( c, 1, 0, h - c ),
			'L ' + at( 0, bottom ),
			arc( r, 1, arcRun, bottom - arcRise ),
			'L ' + at( depth - arcRun, bottom - arcRise - diagRise ),
			arc( r, 0, depth, bottom - transition ),
			'L ' + at( depth, top + transition ),
			arc( r, 0, depth - arcRun, top + arcRise + diagRise ),
			'L ' + at( arcRun, top + arcRise ),
			arc( r, 1, 0, top ),
			'L ' + at( 0, c ),
			arc( c, 1, c, 0 ),
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
	 * No per-character delay is written here. Under a scrubbed sweep the
	 * stagger between one character and the next is how fast you are
	 * scrolling; a fixed delay on top of that would fight it.
	 *
	 * @param {Element}   el    Element to split.
	 * @param {Element[]} sink  Collects the character spans, in reading order.
	 */
	function split( el, sink ) {
		toArray( el.childNodes ).forEach( function ( node ) {
			if ( 1 === node.nodeType && ! /^(BR|IMG|SVG)$/.test( node.tagName ) ) {
				split( node, sink );
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
					span.appendChild( document.createTextNode( character ) );
					word.appendChild( span );
					sink.push( span );
				} );

				fragment.appendChild( word );
			} );

			node.parentNode.replaceChild( fragment, node );
		} );
	}

	/**
	 * How far an item has travelled through the reading band, 0 to 1.
	 *
	 * The band is two lines across the viewport. The sweep starts when the
	 * item's top edge crosses the lower line and finishes when its bottom edge
	 * crosses the upper one, so a tall item takes proportionally longer to
	 * light than a short one and both finish while fully on screen.
	 *
	 * @param {Element} item Item.
	 * @param {number}  lead Lower line, as a fraction of the viewport.
	 * @param {number}  tail Upper line, as a fraction of the viewport.
	 * @return {number}
	 */
	function progressOf( item, lead, tail, slide ) {
		var height = viewportHeight();
		var rect = item.getBoundingClientRect();

		/*
		 * Only the words count.
		 *
		 * Stacked on a phone an item is its text and then its picture, and a
		 * picture is most of the item's height. Measuring the sweep against
		 * the whole thing would have the text still lighting while the reader
		 * is looking at the photograph below it -- the words finished long
		 * before, and the sweep would simply be late. The picture is the
		 * bottom of the item, so the text ends where it begins.
		 */
		var words = slide ? slide.getBoundingClientRect().top - rect.top : rect.height;
		var travel = words + height * ( lead - tail );

		if ( travel <= 0 ) {
			return rect.top <= height * lead ? 1 : 0;
		}

		return clamp01( ( height * lead - rect.top ) / travel );
	}

	/**
	 * Light or extinguish characters so that exactly `count` of them are lit.
	 *
	 * Only the characters between the old count and the new one are touched,
	 * so a scroll of a few pixels costs a few class changes rather than one
	 * per character in the section.
	 *
	 * @param {Object} entry Item record.
	 * @param {number} count How many should be lit.
	 */
	function setLit( entry, count ) {
		var chars = entry.chars;
		var i;

		if ( count === entry.lit ) {
			return;
		}

		if ( count > entry.lit ) {
			for ( i = entry.lit; i < count; i++ ) {
				chars[ i ].classList.add( 'is-on' );
			}
		} else {
			for ( i = count; i < entry.lit; i++ ) {
				chars[ i ].classList.remove( 'is-on' );
			}
		}

		entry.lit = count;
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

		if ( ! items.length ) {
			return;
		}

		var slides = toArray( root.querySelectorAll( '.estry__slide' ) );
		var frame = root.querySelector( '.estry__frame' );
		var notched = frame && frame.hasAttribute( 'data-estry-notch' );
		var reduced = prefersReducedMotion();

		var lead = readData( root, 'data-estry-lead', 0.85 );
		var tail = readData( root, 'data-estry-tail', 0.45 );
		var switchAt = readData( root, 'data-estry-switch', 0.15 );

		// A band with no height would divide by zero further down, and a tail
		// below the lead would run the sweep backwards.
		if ( ! ( lead > tail ) ) {
			lead = 0.85;
			tail = 0.45;
		}

		var clipPath = null;
		var outline = null;

		if ( notched && frame ) {
			uid += 1;

			var id = 'estry-notch-' + uid;
			var svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );

			svg.setAttribute( 'width', '0' );
			svg.setAttribute( 'height', '0' );
			svg.setAttribute( 'aria-hidden', 'true' );
			svg.style.position = 'absolute';

			var defs = document.createElementNS( 'http://www.w3.org/2000/svg', 'defs' );
			var clip = document.createElementNS( 'http://www.w3.org/2000/svg', 'clipPath' );

			clip.setAttribute( 'id', id );
			clip.setAttribute( 'clipPathUnits', 'userSpaceOnUse' );

			clipPath = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
			clip.appendChild( clipPath );
			defs.appendChild( clip );
			svg.appendChild( defs );
			frame.parentNode.insertBefore( svg, frame );

			frame.style.clipPath = 'url(#' + id + ')';
			frame.style.webkitClipPath = 'url(#' + id + ')';

			// A border cannot be drawn on a clipped box -- it would be cut
			// away with everything else outside the path -- so the panel is
			// outlined by a stroked copy of the same path. It is stroked at
			// twice the asked-for width and clipped by that path too, leaving
			// exactly the asked-for width on the inside of the edge.
			var overlay = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );

			overlay.setAttribute( 'class', 'estry__outline' );
			overlay.setAttribute( 'aria-hidden', 'true' );
			overlay.setAttribute( 'preserveAspectRatio', 'none' );

			outline = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
			outline.setAttribute( 'fill', 'none' );
			overlay.appendChild( outline );
			frame.appendChild( overlay );
		}

		var entries = items.map( function ( item ) {
			var chars = [];

			if ( ! reduced ) {
				toArray( item.querySelectorAll( '.estry__title, .estry__body' ) ).forEach( function ( el ) {
					split( el, chars );
				} );
			}

			return { item: item, chars: chars, lit: 0, slide: null };
		} );

		root.setAttribute( READY, '' );

		if ( frame ) {
			frame.setAttribute( READY, '' );
		}

		/*
		 * The notch is an attribute on the frame, but on a phone the pictures
		 * are moved out of the frame and in under their items, where no
		 * stylesheet rule can look back up at it. Mirroring it onto the
		 * section is what lets the stacked rules tell the two cases apart: a
		 * plain picture takes an ordinary CSS border, a notched one cannot --
		 * the clip path would cut it away -- and is outlined by a stroked copy
		 * of its own path instead.
		 */
		if ( notched ) {
			root.setAttribute( 'data-estry-notched', '' );
		}

		var videos = toArray( root.querySelectorAll( '.estry__vid' ) );

		/*
		 * Items and slides are not one to one: an item with no media of its
		 * own has no slide, and keeps showing whatever the item above it put
		 * up. Each slide says which item it belongs to, so an item maps to
		 * the last slide at or above it.
		 */
		var owners = slides.map( function ( slide, i ) {
			var owner = parseInt( slide.getAttribute( 'data-estry-for' ), 10 );

			return isNaN( owner ) ? i : owner;
		} );

		var slideFor = items.map( function ( ignored, i ) {
			var found = -1;

			owners.forEach( function ( owner, j ) {
				if ( owner <= i ) {
					found = j;
				}
			} );

			return found;
		} );

		/**
		 * Give every stacked picture a notch of its own, along its bottom edge,
		 * and the outline that stands in for its border.
		 */
		function buildAcross() {
			clearAcross();

			entries.forEach( function ( entry ) {
				// A placeholder rather than a skip. The scroll pass looks a
				// record up by the item's own index, and an item with no
				// picture of its own would otherwise shift every record after
				// it onto the wrong picture.
				if ( ! entry.slide ) {
					acrossPaths.push( null );

					return;
				}

				uid += 1;

				var id = 'estry-across-' + uid;
				var svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );

				svg.setAttribute( 'width', '0' );
				svg.setAttribute( 'height', '0' );
				svg.setAttribute( 'aria-hidden', 'true' );
				svg.style.position = 'absolute';

				var defs = document.createElementNS( 'http://www.w3.org/2000/svg', 'defs' );
				var clip = document.createElementNS( 'http://www.w3.org/2000/svg', 'clipPath' );

				clip.setAttribute( 'id', id );
				clip.setAttribute( 'clipPathUnits', 'userSpaceOnUse' );

				var path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );

				clip.appendChild( path );
				defs.appendChild( clip );
				svg.appendChild( defs );
				entry.slide.parentNode.insertBefore( svg, entry.slide );

				entry.slide.style.clipPath = 'url(#' + id + ')';
				entry.slide.style.webkitClipPath = 'url(#' + id + ')';

				var w = entry.slide.offsetWidth;
				var h = entry.slide.offsetHeight;

				/*
				 * The same trick as the pinned panel: a clipped box cannot
				 * carry a border, so the edge is a stroked copy of the very
				 * path doing the clipping, drawn at twice the asked-for width
				 * and clipped by it too, which leaves exactly the asked-for
				 * width on the inside.
				 *
				 * Stacked, this is the only border the picture can have, and
				 * without it a phone loses the outline the panel has on a
				 * desktop.
				 */
				var overlay = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );

				overlay.setAttribute( 'class', 'estry__outline' );
				overlay.setAttribute( 'aria-hidden', 'true' );
				overlay.setAttribute( 'preserveAspectRatio', 'none' );
				overlay.setAttribute( 'viewBox', '0 0 ' + w + ' ' + h );

				var outlineAcross = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );

				outlineAcross.setAttribute( 'fill', 'none' );
				outlineAcross.setAttribute( 'stroke', notch.stroke );
				outlineAcross.setAttribute( 'stroke-width', String( notch.width * 2 ) );
				overlay.appendChild( outlineAcross );
				entry.slide.appendChild( overlay );

				acrossPaths.push( {
					path: path,
					svg: svg,
					outline: outlineAcross,
					overlay: overlay,
					slide: entry.slide,
					w: w,
					h: h,
					d: '',
				} );
			} );
		}

		function clearAcross() {
			acrossPaths.forEach( function ( one ) {
				if ( ! one ) {
					return;
				}

				one.slide.style.clipPath = '';
				one.slide.style.webkitClipPath = '';

				if ( one.svg.parentNode ) {
					one.svg.parentNode.removeChild( one.svg );
				}

				// Inside the slide rather than beside it, so moving the slide
				// back into the frame would take it along.
				if ( one.overlay && one.overlay.parentNode ) {
					one.overlay.parentNode.removeChild( one.overlay );
				}
			} );

			acrossPaths = [];
		}

		var current = -1;
		var shown = -1;
		var zTop = 0;
		var leaveTimer = null;
		var stacked = null;

		/*
		 * The notch on a phone.
		 *
		 * Stacked, a picture is wide and short: a notch down its left edge
		 * would have almost nowhere to travel, so it runs along the top
		 * instead, and travels with that item's own reading rather than with
		 * the section's. Each picture needs a clip path of its own -- they are
		 * separate boxes now, not one frame -- so one is built per slide when
		 * the layout changes and thrown away when it changes back.
		 *
		 * Width and height are taken then too. Nothing about them changes
		 * while scrolling, and reading them per frame per picture is exactly
		 * the cost this pass was spent removing.
		 */
		var acrossPaths = [];

		/*
		 * Everything the notch needs, read once.
		 *
		 * These were being read with getComputedStyle inside the scroll loop:
		 * six calls a frame, each one a style recalculation the browser did
		 * not need to do. None of them can change without a resize or an
		 * editor re-render, both of which come back through here anyway.
		 */
		var notch = {
			depth: 30,
			run: 280,
			/*
			 * A phone's picture is wide and short, and the desktop numbers do
			 * not fit it: a 280px straight run plus two 65px shoulders is more
			 * than a 310px edge has, so the band is clamped flat against both
			 * corners and has nowhere left to travel. Its own, smaller, pair.
			 */
			depthAcross: 18,
			runAcross: 90,
			span: 0.4,
			radius: 16,
			width: 0,
			stroke: 'transparent',
			d: '',
			w: 0,
			h: 0,
		};

		function readNotch() {
			if ( ! frame ) {
				return;
			}

			notch.depth = readNumber( frame, '--estry-notch', 30 );
			notch.run = readNumber( frame, '--estry-band-size', 280 );
			notch.depthAcross = readNumber( root, '--estry-notch-mobile', 18 );
			notch.runAcross = readNumber( root, '--estry-band-mobile', 90 );
			notch.span = readNumber( frame, '--estry-notch-travel', 40 ) / 100;
			notch.radius = readNumber( frame, '--estry-radius', 16 );
			notch.width = readNumber( frame, '--estry-media-bw', 0 );
			notch.stroke = ( window.getComputedStyle( frame ).getPropertyValue( '--estry-media-border' ) || '' ).trim() || 'transparent';
			notch.d = '';
		}

		function isNarrow() {
			return (
				typeof window.matchMedia === 'function' &&
				window.matchMedia( '(max-width: 1024px)' ).matches
			);
		}

		/**
		 * Put each picture where it belongs at this width.
		 *
		 * Wide, they are a stack of slides in one pinned frame and the panel
		 * changes to whichever item is being read. Narrow, there is no panel:
		 * each picture is moved out of the frame and in under the item it
		 * belongs to, so the section reads text, picture, text, picture.
		 *
		 * Moved rather than duplicated. Two copies of every picture is two
		 * chances for a browser to fetch it, and the whole point of a slide is
		 * that it is one element with one source.
		 */
		function restack() {
			var want = isNarrow();

			if ( want === stacked ) {
				return;
			}

			stacked = want;

			if ( stacked ) {
				slides.forEach( function ( slide, i ) {
					var item = items[ owners[ i ] ];

					if ( item ) {
						item.appendChild( slide );
						entries[ owners[ i ] ].slide = slide;
					}

					slide.classList.remove( 'is-entering', 'is-instant', 'is-leaving' );
					slide.style.zIndex = '';
					slide.removeAttribute( ON );
				} );

				root.setAttribute( 'data-estry-stacked', '' );

				if ( notched ) {
					// After the attribute, so the pictures are laid out as the
					// stacked rules leave them before anything is measured.
					window.requestAnimationFrame( buildAcross );
				}

				return;
			}

			clearAcross();

			// Appended in their own order, so the stack is rebuilt as it was.
			slides.forEach( function ( slide ) {
				if ( frame ) {
					frame.appendChild( slide );
				}
			} );

			entries.forEach( function ( entry ) {
				entry.slide = null;
			} );

			root.removeAttribute( 'data-estry-stacked' );

			// Nothing is showing any more, so the next pass has to pick again
			// rather than deciding it is already on the right one.
			current = -1;
			shown = -1;
			zTop = 0;
		}

		/**
		 * Bring a slide to the top of the stack.
		 *
		 * Nothing ever fades *out*: the outgoing slide keeps its picture and
		 * its place, and the incoming one arrives over the top of it. That is
		 * what stops the panel's background showing through the middle of a
		 * change.
		 *
		 * @param {number} index Slide to show.
		 * @param {string} dir   'down' or 'up'.
		 * @param {bool}   arm   Play the entrance, rather than just appearing.
		 */
		function show( index, dir, arm ) {
			var slide = slides[ index ];

			if ( ! slide ) {
				return;
			}

			zTop += 1;
			slide.style.zIndex = String( zTop );

			slides.forEach( function ( other, i ) {
				if ( i === index ) {
					other.setAttribute( ON, '' );
				} else {
					other.removeAttribute( ON );
				}
			} );

			if ( frame ) {
				frame.setAttribute( 'data-estry-dir', dir );
			}

			if ( arm && ! reduced ) {
				var previous = shown > -1 ? slides[ shown ] : null;

				// The picture being replaced eases back while the new one
				// arrives over it. Under a feathered entrance both are on
				// screen at once, and two things moving at different speeds is
				// the difference between a change and a swap.
				slides.forEach( function ( other ) {
					other.classList.remove( 'is-leaving' );
				} );

				if ( previous && previous !== slide ) {
					previous.classList.add( 'is-leaving' );

					window.clearTimeout( leaveTimer );
					leaveTimer = window.setTimeout( function () {
						previous.classList.remove( 'is-leaving' );
					}, readNumber( root, '--estry-fade', 900 ) + 80 );
				}

				// Putting the slide into its entrance state must not itself
				// animate, so transitions are switched off for exactly as
				// long as it takes the browser to record that state.
				slide.classList.add( 'is-instant', 'is-entering' );
				void slide.offsetWidth;
				slide.classList.remove( 'is-instant' );

				window.requestAnimationFrame( function () {
					slide.classList.remove( 'is-entering' );
				} );
			}

			videos.forEach( function ( video ) {
				var mine = slide.contains( video );

				try {
					if ( mine && ! reduced ) {
						var playing = video.play();

						if ( playing && typeof playing.catch === 'function' ) {
							playing.catch( function () {} );
						}
					} else {
						video.pause();
					}
				} catch ( e ) {
					// An unplayable video is not a reason to stop scrolling.
				}
			} );
		}

		function update() {
			var reached = 0;

			entries.forEach( function ( entry, i ) {
				// Only a stacked item has a picture of its own inside it, and
				// only then does the sweep have to stop short of one.
				var progress = progressOf( entry.item, lead, tail, stacked ? entry.slide : null );

				if ( ! reduced && entry.chars.length ) {
					setLit( entry, Math.round( progress * entry.chars.length ) );
				}

				if ( progress > switchAt ) {
					reached = i;
				}

				// The phone's notch travels with the item it belongs to, and
				// the numbers it needs were all taken when the layout changed.
				if ( stacked && acrossPaths[ i ] ) {
					var one = acrossPaths[ i ];
					var across = notchPath(
						one.w, one.h, notch.depthAcross, notch.runAcross,
						progress, notch.span, notch.radius, true
					);

					if ( across !== one.d ) {
						one.d = across;
						one.path.setAttribute( 'd', across );

						if ( one.outline ) {
							one.outline.setAttribute( 'd', across );
						}
					}
				}
			} );

			if ( stacked ) {
				// No panel to change and no notch to travel. The sweep above
				// is the whole of it.
				return;
			}

			if ( reached !== current ) {
				var target = slideFor[ reached ];

				// An item without its own media leaves the panel alone.
				if ( target > -1 && target !== shown ) {
					show( target, reached > current ? 'down' : 'up', -1 !== shown );
					shown = target;
				}

				current = reached;
			}

			// The notch travels with how far you are through the section.
			if ( clipPath && frame ) {
				var rect = root.getBoundingClientRect();
				var runway = rect.height - viewportHeight();
				var through = runway > 0 ? clamp01( -rect.top / runway ) : 0;
				var box = frame.getBoundingClientRect();
				var w = Math.round( box.width );
				var h = Math.round( box.height );
				var d = notchPath( w, h, notch.depth, notch.run, through, notch.span, notch.radius, false );

				/*
				 * Only write when it has actually moved. A path rounded to
				 * hundredths does not change on most frames of a slow scroll,
				 * and every setAttribute on a clip path costs a style
				 * recalculation whether the value differs or not.
				 */
				if ( d !== notch.d ) {
					notch.d = d;
					clipPath.setAttribute( 'd', d );

					if ( outline ) {
						outline.setAttribute( 'd', d );

						if ( w !== notch.w || h !== notch.h ) {
							notch.w = w;
							notch.h = h;
							outline.setAttribute( 'stroke-width', String( notch.width * 2 ) );
							outline.setAttribute( 'stroke', notch.stroke );
							outline.parentNode.setAttribute( 'viewBox', '0 0 ' + w + ' ' + h );
						}
					}
				}
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
			readNotch();
			restack();

			// A picture that changed width has a notch built for the old one.
			if ( stacked && notched ) {
				buildAcross();
			}

			onScroll();
		}

		readNotch();
		restack();
		update();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onResize, { passive: true } );
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
