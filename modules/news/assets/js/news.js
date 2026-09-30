/**
 * Eruda Toolkit - News
 *
 * News Grid: category chips, #news-<slug>, Load more, and a short fade as
 * cards arrive. The audio player on podcast cards and in the Post Source Box. News Carousel: arrow buttons (its own, or any two buttons in
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

	function initGrid( root ) {
		if ( root.getAttribute( 'data-enws-ready' ) ) {
			return;
		}

		root.setAttribute( 'data-enws-ready', '1' );

		var bento = root.querySelector( '.enws-cards' );
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
				'tags=' + encodeURIComponent( root.getAttribute( 'data-tags' ) || '' ),
				'listen=' + encodeURIComponent( root.getAttribute( 'data-listen' ) || '' )
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

					added.forEach( function ( card ) {
						toArray( card.querySelectorAll( '.enws-player[data-audio-src]' ) ).forEach( initPlayer );
					} );

					// Anything else that decorates cards can hear about new ones.
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

	/* -------------------------------------------------------- player --- */

	var playing = null;

	function clock( seconds ) {
		if ( ! isFinite( seconds ) || seconds < 0 ) {
			return '0:00';
		}

		var s = Math.floor( seconds % 60 );

		return Math.floor( seconds / 60 ) + ':' + ( s < 10 ? '0' : '' ) + s;
	}

	/**
	 * Wire one audio player: the button plays and pauses, the waveform fills
	 * with the progress, and the invisible range input over it seeks.
	 *
	 * @param {Element} player The .enws-player.
	 */
	function initPlayer( player ) {
		if ( player.getAttribute( 'data-enws-ready' ) ) {
			return;
		}

		var audio = player.querySelector( 'audio' );
		var btn = player.querySelector( '.enws-player__btn' );
		var seek = player.querySelector( '.enws-player__seek' );
		var time = player.querySelector( '.enws-player__time' );
		var title = player.getAttribute( 'data-audio-title' ) || '';

		if ( ! audio || ! btn ) {
			return;
		}

		player.setAttribute( 'data-enws-ready', '1' );

		function paint() {
			var pct = audio.duration ? ( audio.currentTime / audio.duration ) * 100 : 0;

			player.style.setProperty( '--p', pct + '%' );

			if ( seek ) {
				seek.value = String( pct );
				seek.setAttribute( 'aria-valuetext', clock( audio.currentTime ) + ' of ' + clock( audio.duration ) );
			}

			if ( time ) {
				time.textContent = audio.duration && ! audio.paused ? clock( audio.currentTime ) : clock( audio.currentTime || audio.duration );
			}
		}

		function state( on ) {
			player.classList.toggle( 'is-playing', on );
			btn.setAttribute( 'aria-label', ( on ? player.getAttribute( 'data-pause' ) || 'Pause' : player.getAttribute( 'data-play' ) || 'Play' ) + ': ' + title );
			btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		}

		btn.addEventListener( 'click', function () {
			if ( audio.paused ) {
				// One voice at a time.
				if ( playing && playing !== audio ) {
					playing.pause();
				}

				var started = audio.play();

				if ( started && started.catch ) {
					started.catch( function () {
						state( false );
					} );
				}
			} else {
				audio.pause();
			}
		} );

		audio.addEventListener( 'play', function () {
			playing = audio;
			state( true );
		} );
		audio.addEventListener( 'pause', function () {
			state( false );
			paint();
		} );
		audio.addEventListener( 'ended', function () {
			audio.currentTime = 0;
			state( false );
			paint();
		} );
		audio.addEventListener( 'timeupdate', paint );
		audio.addEventListener( 'loadedmetadata', paint );

		if ( seek ) {
			seek.addEventListener( 'input', function () {
				var pct = parseFloat( seek.value ) || 0;

				player.style.setProperty( '--p', pct + '%' );

				if ( audio.duration ) {
					audio.currentTime = ( pct / 100 ) * audio.duration;
				} else {
					// Nothing loaded yet: load, then land where asked.
					audio.preload = 'metadata';
					audio.addEventListener( 'loadedmetadata', function () {
						audio.currentTime = ( pct / 100 ) * audio.duration;
					}, { once: true } );
					audio.load();
				}
			} );
		}

		// Show the length without downloading the file: metadata only, and
		// only once the player is near the screen.
		if ( 'IntersectionObserver' in window ) {
			var watch = new IntersectionObserver( function ( entries ) {
				if ( entries[ 0 ].isIntersecting ) {
					audio.preload = 'metadata';
					watch.disconnect();
				}
			}, { rootMargin: '200px' } );

			watch.observe( player );
		}

		state( false );
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

		toArray( context.querySelectorAll( '.enws-player[data-audio-src]' ) ).forEach( initPlayer );
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

			[ 'enws-news-grid', 'enws-news-carousel', 'enws-post-source' ].forEach( function ( name ) {
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
