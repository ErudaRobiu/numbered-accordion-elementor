/**
 * Eruda Toolkit - text animations
 *
 * Vanilla JS, no dependencies. Safe to load twice, safe to run against the
 * same DOM twice, and it never throws if the markup is not what it expects.
 *
 * The script's only jobs are to cut the text up and to decide when it moves.
 * What the movement looks like lives entirely in the stylesheet, and the text
 * is already complete in the HTML -- so if this file fails to load, or bails
 * out below, every heading is still there and still readable. Nothing here is
 * load-bearing for content.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '[class*="eanm-preset-"]';
	var PRESET_CLASS = /(?:^|\s)eanm-preset-([a-z-]+)/;
	var TARGET_SELECTOR = 'h1, h2, h3, h4, h5, h6, p, li, blockquote';
	var READY_ATTR = 'data-eanm-ready';
	var ARMED_ATTR = 'data-eanm-armed';
	var IN_ATTR = 'data-eanm-in';

	/**
	 * Elements whose contents must not be cut up.
	 */
	var SKIP_TAGS = /^(BR|IMG|SVG|IFRAME|VIDEO|AUDIO|SCRIPT|STYLE|INPUT|TEXTAREA|SELECT|BUTTON)$/;

	/**
	 * One observer per distinct threshold, shared by every element using it.
	 *
	 * @type {Object}
	 */
	var observers = {};

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * Would motion be unwelcome here?
	 *
	 * @return {boolean}
	 */
	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Does this browser have everything the module needs?
	 *
	 * IntersectionObserver is the only modern thing here. Without it, the text
	 * stays exactly as authored.
	 *
	 * @return {boolean}
	 */
	function isSupported() {
		return (
			typeof window.IntersectionObserver === 'function' &&
			typeof window.requestAnimationFrame === 'function'
		);
	}

	/**
	 * Read a CSS custom property off an element.
	 *
	 * @param {Element} element  Element to read from.
	 * @param {string}  name     Property name.
	 * @param {string}  fallback Value to use when unreadable.
	 * @return {string}
	 */
	function readProp( element, name, fallback ) {
		var raw = '';

		try {
			raw = ( window.getComputedStyle( element ).getPropertyValue( name ) || '' ).trim();
		} catch ( e ) {
			raw = '';
		}

		return raw || fallback;
	}

	/**
	 * The elements inside a widget whose text should animate.
	 *
	 * Falls back to the widget's container, which covers a Heading rendered as
	 * a bare link and a Text Editor holding an unwrapped string.
	 *
	 * @param {Element} root Widget wrapper.
	 * @return {Element[]}
	 */
	function targets( root ) {
		var found = toArray( root.querySelectorAll( TARGET_SELECTOR ) ).filter( function ( el ) {
			return ( el.textContent || '' ).trim() !== '';
		} );

		if ( found.length ) {
			return found;
		}

		var container = root.querySelector( '.elementor-widget-container' ) || root;

		return ( container.textContent || '' ).trim() ? [ container ] : [];
	}

	/**
	 * Put an element back the way it was authored, remembering it the first
	 * time. This is what makes re-running safe: the editor re-renders a widget
	 * on every keystroke.
	 *
	 * @param {Element} el Target element.
	 */
	function restore( el ) {
		el.removeAttribute( ARMED_ATTR );

		if ( typeof el.eanmOriginal === 'string' ) {
			el.innerHTML = el.eanmOriginal;
			return;
		}

		el.eanmOriginal = el.innerHTML;
	}

	/**
	 * Build one animating unit: an outer span that can mask, and an inner one
	 * that moves.
	 *
	 * @param {string} text  The unit's text.
	 * @param {Object} state Carries the running index.
	 * @return {Element}
	 */
	function unit( text, state ) {
		var outer = document.createElement( 'span' );
		var inner = document.createElement( 'span' );

		outer.className = 'eanm-u';
		outer.style.setProperty( '--eanm-i', state.index );
		state.index += 1;

		inner.className = 'eanm-i';
		inner.appendChild( document.createTextNode( text ) );
		outer.appendChild( inner );

		return outer;
	}

	/**
	 * Split a word into characters by code point, so an emoji or a surrogate
	 * pair stays one unit rather than becoming two broken halves.
	 *
	 * @param {string} word Word.
	 * @return {string[]}
	 */
	function toChars( word ) {
		if ( typeof Array.from === 'function' ) {
			return Array.from( word );
		}

		return word.split( '' );
	}

	/**
	 * Replace a text node with its split units.
	 *
	 * Whitespace is re-inserted as plain text rather than being absorbed into a
	 * span, so inline-block words still wrap across lines normally.
	 *
	 * @param {Text}   node  Text node.
	 * @param {string} mode  'words' or 'chars'.
	 * @param {Object} state Carries the running index.
	 */
	function splitTextNode( node, mode, state ) {
		var parts = ( node.data || '' ).split( /(\s+)/ );
		var fragment = document.createDocumentFragment();

		parts.forEach( function ( part ) {
			if ( '' === part ) {
				return;
			}

			if ( /^\s+$/.test( part ) ) {
				fragment.appendChild( document.createTextNode( part ) );
				return;
			}

			if ( 'chars' === mode ) {
				toChars( part ).forEach( function ( character ) {
					fragment.appendChild( unit( character, state ) );
				} );
				return;
			}

			fragment.appendChild( unit( part, state ) );
		} );

		node.parentNode.replaceChild( fragment, node );
	}

	/**
	 * Walk an element's children, splitting text and recursing into markup.
	 *
	 * Recursing rather than rewriting innerHTML is what keeps <strong>, <a> and
	 * <br> intact: the elements themselves are never touched, only the text
	 * nodes between them.
	 *
	 * @param {Element} node  Element to walk.
	 * @param {string}  mode  'words' or 'chars'.
	 * @param {Object}  state Carries the running index.
	 */
	function walk( node, mode, state ) {
		toArray( node.childNodes ).forEach( function ( child ) {
			if ( 3 === child.nodeType ) {
				if ( '' === ( child.data || '' ).trim() ) {
					return;
				}

				splitTextNode( child, mode, state );
				return;
			}

			if ( 1 === child.nodeType && ! SKIP_TAGS.test( child.tagName ) ) {
				walk( child, mode, state );
			}
		} );
	}

	/**
	 * Give every word on the same rendered line the same stagger index.
	 *
	 * This is the whole of the line treatment. The words are already split and
	 * already masked, so lines need no wrapper element of their own -- sharing
	 * a delay is what makes them move as a line. That also means a heading with
	 * bold or a link inside it groups correctly, which a wrapper-based approach
	 * could not manage without restructuring markup it has no business
	 * restructuring.
	 *
	 * @param {Element} el    Target element.
	 * @param {number}  first Line index to start from.
	 * @return {number} The next unused line index.
	 */
	function assignLineIndices( el, first ) {
		var line = ( first || 0 ) - 1;
		var top = null;

		toArray( el.querySelectorAll( '.eanm-u' ) ).forEach( function ( span ) {
			var offset = span.offsetTop;

			// A couple of pixels of slack: sub-pixel layout and mixed font
			// sizes on one line do not mean a new line.
			if ( null === top || Math.abs( offset - top ) > 2 ) {
				line += 1;
				top = offset;
			}

			span.style.setProperty( '--eanm-i', line );
		} );

		return line + 1;
	}

	/**
	 * Re-group lines when the element's width changes.
	 *
	 * Line breaks are a property of the rendered box, so they are the one thing
	 * here that has to be measured again after a resize.
	 *
	 * @param {Element} el Target element.
	 */
	function watchResize( el, first ) {
		if ( typeof window.ResizeObserver !== 'function' || el.eanmResize ) {
			return;
		}

		var timer = null;

		el.eanmResize = new window.ResizeObserver( function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				assignLineIndices( el, first );
			}, 150 );
		} );

		el.eanmResize.observe( el );
	}

	/**
	 * Elements whose progress is tied to the scroll position, and the frame
	 * loop that updates them. The loop only runs while at least one of them
	 * is on screen.
	 *
	 * @type {Element[]}
	 */
	var scrubbing = [];
	var scrubRunning = false;

	/**
	 * How wide the lighting-up band is, in words.
	 *
	 * 1 would light each word the instant the one before it finished, which
	 * reads as a hard edge travelling along the line. A little over 1 keeps
	 * two or three words in flight and reads as a sweep.
	 */
	var SCRUB_SOFTNESS = 1.6;

	/**
	 * How far the element has travelled through the middle of the screen.
	 *
	 * 0 when its top reaches the centre, 1 when its bottom does -- the same
	 * band as a scrub from "top center" to "bottom center".
	 *
	 * @param {Element} el Target element.
	 * @return {number} 0 to 1.
	 */
	function scrollProgress( el ) {
		var rect = el.getBoundingClientRect();
		var viewport = window.innerHeight || document.documentElement.clientHeight;
		var middle = viewport / 2;

		if ( rect.height <= 0 ) {
			return 0;
		}

		// The band is the element's own height, but never less than a good
		// part of the screen. A one-line heading is barely forty pixels tall,
		// and scrubbing a whole sentence across forty pixels of scroll is a
		// flicker rather than a sweep. Taller paragraphs are unaffected, so
		// this matches the reference wherever the reference makes sense.
		var band = Math.max( rect.height, viewport * 0.45 );

		return Math.min( Math.max( ( middle - rect.top ) / band, 0 ), 1 );
	}

	/**
	 * Write each word's own share of that progress.
	 *
	 * Word i starts lighting once the sweep reaches it and is fully lit a
	 * softness later, so the words light in order with a little overlap.
	 *
	 * @param {Element} el Target element.
	 */
	function scrubElement( el ) {
		var units = el.eanmUnits;

		if ( ! units || ! units.length ) {
			return;
		}

		var progress = scrollProgress( el );
		var reach = progress * ( units.length - 1 + SCRUB_SOFTNESS );

		for ( var i = 0; i < units.length; i++ ) {
			var own = ( reach - i ) / SCRUB_SOFTNESS;

			units[ i ].style.setProperty( '--eanm-p', Math.min( Math.max( own, 0 ), 1 ).toFixed( 3 ) );
		}
	}

	/**
	 * Run the scrub loop while anything needs it, and stop when nothing does.
	 */
	function scrubFrame() {
		for ( var i = 0; i < scrubbing.length; i++ ) {
			scrubElement( scrubbing[ i ] );
		}

		if ( scrubbing.length ) {
			window.requestAnimationFrame( scrubFrame );
			return;
		}

		scrubRunning = false;
	}

	/**
	 * Watch an element for as long as it is on screen.
	 *
	 * A scrubbed element is only interesting while it is visible, so the
	 * frame loop is started and stopped by an observer rather than running
	 * for the life of the page.
	 *
	 * @param {Element} el Target element.
	 */
	function scrub( el ) {
		el.eanmUnits = toArray( el.querySelectorAll( '.eanm-u' ) );

		// Correct from the first frame, even if the page loads part-scrolled.
		scrubElement( el );

		if ( el.eanmScrubObserver ) {
			el.eanmScrubObserver.disconnect();
		}

		el.eanmScrubObserver = new window.IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				var at = scrubbing.indexOf( entry.target );

				if ( entry.isIntersecting && at === -1 ) {
					scrubbing.push( entry.target );
				} else if ( ! entry.isIntersecting && at !== -1 ) {
					scrubbing.splice( at, 1 );
				}
			} );

			if ( scrubbing.length && ! scrubRunning ) {
				scrubRunning = true;
				window.requestAnimationFrame( scrubFrame );
			}
		} );

		el.eanmScrubObserver.observe( el );
	}

	/**
	 * Clamp a percentage into a usable IntersectionObserver threshold.
	 *
	 * 1.0 is deliberately unreachable: an element taller than the viewport can
	 * never be 100% visible, and an animation that never fires would leave its
	 * text hidden.
	 *
	 * @param {number} percent Percentage from the control.
	 * @return {number} Ratio between 0 and 0.99.
	 */
	function clampThreshold( percent ) {
		if ( isNaN( percent ) ) {
			return 0.2;
		}

		return Math.min( Math.max( percent / 100, 0 ), 0.99 );
	}

	/**
	 * React to an element crossing its threshold.
	 *
	 * @param {IntersectionObserverEntry[]} entries Entries.
	 */
	function onIntersect( entries ) {
		entries.forEach( function ( entry ) {
			var el = entry.target;

			if ( entry.isIntersecting ) {
				el.setAttribute( IN_ATTR, '' );

				if ( ! el.eanmReplay && el.eanmObserver ) {
					el.eanmObserver.unobserve( el );
				}

				return;
			}

			if ( el.eanmReplay ) {
				el.removeAttribute( IN_ATTR );
			}
		} );
	}

	/**
	 * Watch an element until it comes into view.
	 *
	 * @param {Element} el        Target.
	 * @param {number}  threshold Ratio.
	 * @param {boolean} replay    Animate again on every entry?
	 */
	function observe( el, threshold, replay ) {
		var key = String( threshold );

		if ( ! observers[ key ] ) {
			observers[ key ] = new window.IntersectionObserver( onIntersect, { threshold: threshold } );
		}

		// Re-arming an element the editor just re-rendered.
		if ( el.eanmObserver ) {
			el.eanmObserver.unobserve( el );
		}

		el.eanmReplay = !! replay;
		el.eanmObserver = observers[ key ];
		observers[ key ].observe( el );
	}

	/**
	 * Split and arm one widget.
	 *
	 * @param {Element} root Widget wrapper.
	 */
	function initRoot( root ) {
		if ( ! root || 1 !== root.nodeType || ! PRESET_CLASS.test( root.className || '' ) ) {
			return;
		}

		if ( root.classList.contains( 'eanm-preset-none' ) ) {
			return;
		}

		var mode = readProp( root, '--eanm-split', 'none' );
		var scrubbed = 'scrub' === readProp( root, '--eanm-mode', 'reveal' );
		var onLoad = root.classList.contains( 'eanm-trigger-load' );
		var replay = root.classList.contains( 'eanm-replay-yes' );
		var threshold = clampThreshold( parseFloat( readProp( root, '--eanm-threshold', '20' ) ) );

		// One counter for the whole widget. A Text Editor of several
		// paragraphs is staggered as a single run sweeping top to bottom,
		// rather than every paragraph restarting and moving in parallel.
		var state = { index: 0 };

		targets( root ).forEach( function ( el ) {
			restore( el );
			el.removeAttribute( IN_ATTR );

			if ( 'words' === mode || 'chars' === mode ) {
				walk( el, mode, state );
			}

			if ( 'lines' === mode ) {
				// Split to words first; the grouping then rewrites the indices
				// so each rendered line shares one.
				walk( el, 'words', { index: 0 } );
				var firstLine = state.index;
				state.index = assignLineIndices( el, firstLine );
				watchResize( el, firstLine );
			}

			// Arming is two steps, and the order matters.
			//
			// READY applies the start state while no transition exists, so
			// the element snaps to it. Reading a layout property forces the
			// browser to commit that. Only then does ARMED switch the
			// transitions on, so the journey back out is the one that
			// animates.
			//
			// Doing it in one step is what broke Fade In and Blur In: those
			// presets animate the target itself, which is already on the page
			// and already visible, so "become invisible" was itself a
			// transition. Measured in Chrome, an above-the-fold Fade In went
			// 1 -> 0.996 -> 1 and never appeared to animate at all.
			el.removeAttribute( ARMED_ATTR );
			el.setAttribute( READY_ATTR, '' );
			void el.offsetWidth;
			el.setAttribute( ARMED_ATTR, '' );

			if ( scrubbed ) {
				// The scroll position is the timeline. No trigger, no
				// finished state, nothing to replay.
				scrub( el );
				return;
			}

			if ( onLoad ) {
				window.requestAnimationFrame( function () {
					el.setAttribute( IN_ATTR, '' );
				} );
				return;
			}

			observe( el, threshold, replay );
		} );
	}

	/**
	 * Arm every widget inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
	function initAll( scope ) {
		toArray( ( scope || document ).querySelectorAll( ROOT_SELECTOR ) ).forEach( initRoot );
	}

	/**
	 * Re-arm a widget Elementor has just re-rendered.
	 *
	 * frontend/element_ready/global fires for every widget on the front end and
	 * again in the editor on every re-render, which is exactly when a preset
	 * change needs a fresh split. initRoot() restores from its stash first, so
	 * running against already-split DOM is safe.
	 */
	function hookElementor() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( scope ) {
			var el = scope && scope[0] ? scope[0] : scope;

			if ( el && 1 === el.nodeType ) {
				initRoot( el );
			}
		} );
	}

	/**
	 * Play a widget's animation again from the start, wherever it is on the
	 * page and whatever its trigger says.
	 *
	 * For the editor's Replay button. Re-splitting alone is not enough: an
	 * element that has already animated is sitting in its finished state, and
	 * simply taking the finished state away would animate it backwards. So
	 * disarm, reset, commit, re-arm, then play -- the same ordering the
	 * initial arming uses, for the same reason.
	 *
	 * @param {Element} root Widget wrapper.
	 */
	function replay( root ) {
		if ( ! root || 1 !== root.nodeType || ! PRESET_CLASS.test( root.className || '' ) ) {
			return;
		}

		initRoot( root );

		targets( root ).forEach( function ( el ) {
			el.removeAttribute( ARMED_ATTR );
			el.removeAttribute( IN_ATTR );
			void el.offsetWidth;
			el.setAttribute( ARMED_ATTR, '' );

			window.requestAnimationFrame( function () {
				el.setAttribute( IN_ATTR, '' );
			} );
		} );
	}

	/**
	 * The editor needs a way in. Nothing on the front end uses this.
	 */
	window.erudaMotion = {
		init: initAll,
		replay: replay,
	};

	if ( prefersReducedMotion() || ! isSupported() ) {
		return;
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	} else {
		initAll();
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
}() );
