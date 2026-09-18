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

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
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
			if ( frame ) {
				var rect = root.getBoundingClientRect();
				var viewport = window.innerHeight || document.documentElement.clientHeight;
				var span = rect.height - viewport;
				var progress = span > 0 ? Math.min( Math.max( -rect.top / span, 0 ), 1 ) : 0;

				frame.style.setProperty( '--estry-band', ( 12 + progress * 76 ).toFixed( 2 ) + '%' );
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
