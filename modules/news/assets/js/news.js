/**
 * Eruda Toolkit - News
 *
 * News Grid: category chips, #news-<slug>, Load more, and a short fade as
 * cards arrive. News Carousel: arrow buttons (its own, or any two buttons in
 * its section with the classes enws-car-prev / enws-car-next) and arrow keys.
 *
 * Every card is in the markup from the server, so without this script the
 * grid shows its first page and the carousel still scrolls by hand.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	// 28ms apart, capped at 4 steps, plus a 160ms fade: every card is in by
	// 272ms however many arrive.
	var STEP_MS = 28;
	var STEP_CAP = 4;

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/* ---------------------------------------------------------- grid --- */

	/**
	 * Close the gap a short last row leaves.
	 *
	 * Dense packing fills every hole but the end of the last row: any count
	 * of cards that is not a whole number of rows leaves one. The last card
	 * in that row stretches to the right edge instead, so the bento always
	 * ends square, at every width and after every filter or load.
	 *
	 * @param {Element} bento The grid.
	 */
	function fill( bento ) {
		var cards = toArray( bento.children ).filter( function ( card ) {
			return ! card.hidden;
		} );

		cards.forEach( function ( card ) {
			card.style.gridColumn = '';
		} );

		if ( ! cards.length ) {
			return;
		}

		var box = bento.getBoundingClientRect();
		var lowest = null;

		cards.forEach( function ( card ) {
			var r = card.getBoundingClientRect();

			// The card that starts last, and of those the rightmost.
			if ( ! lowest || r.top > lowest.r.top + 1 || ( Math.abs( r.top - lowest.r.top ) <= 1 && r.left > lowest.r.left ) ) {
				lowest = { card: card, r: r };
			}
		} );

		if ( lowest.r.right >= box.right - 2 ) {
			return;
		}

		// Pin the start to the column the card already sits in: an end of -1
		// with an automatic start would move it to the last column instead.
		var gap = parseFloat( getComputedStyle( bento ).columnGap ) || 0;
		var widths = getComputedStyle( bento ).gridTemplateColumns.split( ' ' ).map( parseFloat );
		var left = box.left;
		var start = 1;

		for ( var i = 0; i < widths.length; i++ ) {
			// The last column whose left edge is at or before the card's.
			if ( lowest.r.left >= left - 1 ) {
				start = i + 1;
			}
			left += widths[ i ] + gap;
		}

		lowest.card.style.gridColumn = start + ' / -1';
	}

	function initGrid( root ) {
		if ( root.getAttribute( 'data-enws-ready' ) ) {
			return;
		}

		root.setAttribute( 'data-enws-ready', '1' );

		var bento = root.querySelector( '.enws-bento' );
		var empty = root.querySelector( '.enws-empty' );
		var chips = toArray( root.querySelectorAll( '.enws-chip' ) );
		var more = root.querySelector( '.enws-more__btn' );
		var prefix = root.getAttribute( 'data-hash-prefix' ) || 'news-';
		var current = 'all';

		if ( ! bento ) {
			return;
		}

		function cards() {
			return toArray( bento.querySelectorAll( '.enws-card:not(.enws-card--next)' ) );
		}

		function fresh( list ) {
			if ( prefersLessMotion() ) {
				return;
			}

			list.forEach( function ( card ) {
				card.classList.remove( 'is-fresh' );
			} );

			void bento.offsetWidth;

			list.forEach( function ( card, i ) {
				card.style.animationDelay = Math.min( i, STEP_CAP ) * STEP_MS + 'ms';
				card.classList.add( 'is-fresh' );
			} );
		}

		function apply( seg, animate ) {
			var shown = [];

			current = seg;

			chips.forEach( function ( chip ) {
				chip.setAttribute( 'aria-pressed', chip.getAttribute( 'data-seg' ) === seg ? 'true' : 'false' );
			} );

			cards().forEach( function ( card ) {
				var on = 'all' === seg || -1 !== ( ' ' + ( card.getAttribute( 'data-cat' ) || '' ) + ' ' ).indexOf( ' ' + seg + ' ' );

				card.hidden = ! on;

				if ( on ) {
					shown.push( card );
				}
			} );

			// The "Go deeper" tile belongs to the whole list, not a category.
			toArray( bento.querySelectorAll( '.enws-card--next' ) ).forEach( function ( tile ) {
				tile.hidden = 'all' !== seg;
			} );

			if ( empty ) {
				empty.hidden = shown.length > 0;
			}

			fill( bento );

			if ( animate ) {
				fresh( shown );
			}
		}

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				apply( chip.getAttribute( 'data-seg' ), true );
			} );
		} );

		function fromHash( animate ) {
			var hash = decodeURIComponent( ( window.location.hash || '' ).slice( 1 ) );

			if ( 0 !== hash.indexOf( prefix ) ) {
				return;
			}

			var seg = hash.slice( prefix.length );

			if ( chips.some( function ( chip ) {
				return chip.getAttribute( 'data-seg' ) === seg;
			} ) ) {
				apply( seg, animate );
			}
		}

		fill( bento );

		var pending = 0;
		window.addEventListener( 'resize', function () {
			cancelAnimationFrame( pending );
			pending = requestAnimationFrame( function () {
				fill( bento );
			} );
		} );

		// Pictures arriving can change a row's height, never its columns,
		// but a web font arriving late can: settle once they have.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				fill( bento );
			} );
		}

		if ( chips.length ) {
			fromHash( false );
			window.addEventListener( 'hashchange', function () {
				fromHash( true );
			} );
		}

		if ( ! more || ! window.fetch ) {
			return;
		}

		more.addEventListener( 'click', function () {
			if ( 'true' === more.getAttribute( 'aria-busy' ) ) {
				return;
			}

			var url = root.getAttribute( 'data-rest' );
			var query = [
				'offset=' + encodeURIComponent( root.getAttribute( 'data-next' ) || '0' ),
				'per=' + encodeURIComponent( root.getAttribute( 'data-per' ) || '9' ),
				'featured=' + encodeURIComponent( root.getAttribute( 'data-featured' ) || '0' ),
				'cats=' + encodeURIComponent( root.getAttribute( 'data-cats' ) || '' ),
				'tags=' + encodeURIComponent( root.getAttribute( 'data-tags' ) || '' )
			].join( '&' );

			more.setAttribute( 'aria-busy', 'true' );

			fetch( url + ( -1 === url.indexOf( '?' ) ? '?' : '&' ) + query, { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.ok ? response.json() : Promise.reject( response.status );
				} )
				.then( function ( data ) {
					var holder = document.createElement( 'div' );
					var tile = bento.querySelector( '.enws-card--next' );

					holder.innerHTML = data.html || '';

					var added = toArray( holder.children );

					added.forEach( function ( card ) {
						bento.insertBefore( card, tile );
					} );

					root.setAttribute( 'data-next', String( data.next ) );

					// Keep the chip that was on.
					apply( current, false );
					fresh( added.filter( function ( card ) {
						return ! card.hidden;
					} ) );

					// A player can take over the new audio slots.
					root.dispatchEvent( new CustomEvent( 'eruda:news-cards', { bubbles: true, detail: { cards: added } } ) );

					if ( ! data.more ) {
						more.parentNode.parentNode.removeChild( more.parentNode );
					} else {
						more.removeAttribute( 'aria-busy' );
					}
				} )
				.catch( function () {
					more.removeAttribute( 'aria-busy' );
				} );
		} );
	}

	/* ------------------------------------------------------ carousel --- */

	function initCarousel( wrap ) {
		if ( wrap.getAttribute( 'data-enws-ready' ) ) {
			return;
		}

		wrap.setAttribute( 'data-enws-ready', '1' );

		var track = wrap.querySelector( '.enws-car' );

		if ( ! track ) {
			return;
		}

		var buttons = toArray( wrap.querySelectorAll( '.enws-car__btn' ) );

		// "My own buttons": any two in the same Elementor section.
		if ( 'external' === wrap.getAttribute( 'data-arrows' ) ) {
			var section = wrap.closest( '.e-con.e-parent, .elementor-top-section, section' ) || document;

			toArray( section.querySelectorAll( '.enws-car-prev, .enws-car-next' ) ).forEach( function ( el ) {
				var target = el.matches( 'a, button' ) ? el : el.querySelector( 'a, button' ) || el;

				target.setAttribute( 'data-dir', el.classList.contains( 'enws-car-prev' ) ? '-1' : '1' );
				target.setAttribute( 'aria-label', el.classList.contains( 'enws-car-prev' ) ? 'Previous posts' : 'Next posts' );
				buttons.push( target );
			} );
		}

		function step() {
			var card = track.querySelector( '.enws-car__card' );
			var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;

			return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
		}

		function go( dir ) {
			track.scrollBy( { left: dir * step(), behavior: prefersLessMotion() ? 'auto' : 'smooth' } );
		}

		function edges() {
			var max = track.scrollWidth - track.clientWidth - 2;

			buttons.forEach( function ( btn ) {
				var dir = parseInt( btn.getAttribute( 'data-dir' ), 10 );
				var off = ( dir < 0 && track.scrollLeft <= 2 ) || ( dir > 0 && track.scrollLeft >= max );

				if ( 'BUTTON' === btn.tagName ) {
					btn.disabled = off;
				} else {
					btn.setAttribute( 'aria-disabled', off ? 'true' : 'false' );
				}
			} );
		}

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				go( parseInt( btn.getAttribute( 'data-dir' ), 10 ) || 1 );
			} );
		} );

		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowRight' === event.key || 'ArrowLeft' === event.key ) {
				event.preventDefault();
				go( 'ArrowRight' === event.key ? 1 : -1 );
			}
		} );

		track.addEventListener( 'scroll', edges, { passive: true } );
		window.addEventListener( 'resize', edges );
		edges();
	}

	/* ------------------------------------------------------------ boot --- */

	function initAll( scope ) {
		var context = scope || document;

		toArray( context.querySelectorAll( '.enws-grid' ) ).forEach( initGrid );
		toArray( context.querySelectorAll( '.enws-car-wrap' ) ).forEach( initCarousel );

		if ( 1 === context.nodeType && context.matches ) {
			if ( context.matches( '.enws-grid' ) ) {
				initGrid( context );
			}
			if ( context.matches( '.enws-car-wrap' ) ) {
				initCarousel( context );
			}
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			[ 'enws-news-grid', 'enws-news-carousel' ].forEach( function ( name ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/' + name + '.default', function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;

					if ( el && 1 === el.nodeType ) {
						initAll( el );
					}
				} );
			} );
		} );
	}
}() );
