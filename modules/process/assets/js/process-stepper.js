/**
 * Eruda Toolkit - Process Stepper
 *
 * Picks the layout from the widget's own width (an inline script in the
 * markup already did once, before first paint), moves the selection on
 * hover, focus, click and the arrow keys, measures where the panel's caret
 * goes, keeps the picked stage in view when the rail scrolls, and plays the
 * entrance once. Optionally steps through the stages while nobody is
 * pointing. All the movement itself is CSS.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the widget shows
 * its first stage, laid out for a wide space.
 */
( function () {
	'use strict';

	var ROOT = '.eps';
	var READY = 'data-eps-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersLessMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function editing() {
		return !! ( document.body && document.body.classList.contains( 'elementor-editor-active' ) );
	}

	/**
	 * Wire one stepper up.
	 *
	 * @param {Element} root The stepper.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var items = toArray( root.querySelectorAll( '.eps__item' ) );
		var stages = items.map( function ( item ) {
			return item.querySelector( '.eps__stage' );
		} );
		var slides = toArray( root.querySelectorAll( '.eps__slide' ) );
		var scroller = root.querySelector( '.eps__scroller' );
		var panel = root.querySelector( '.eps__panel' );
		var current = Math.max( 0, items.indexOf( root.querySelector( '.eps__item.is-sel' ) ) );
		var stopAuto = function () {};
		var pending = 0;

		if ( ! items.length ) {
			return;
		}

		function mode() {
			var w = root.clientWidth;
			var next = w < +root.getAttribute( 'data-vertical-below' ) ? 'v' : ( w < +root.getAttribute( 'data-scroll-below' ) ? 'scroll' : 'h' );

			if ( w && root.getAttribute( 'data-mode' ) !== next ) {
				root.setAttribute( 'data-mode', next );
			}

			return root.getAttribute( 'data-mode' );
		}

		// Fade the rail's edges only where there is more to scroll to.
		function edges() {
			if ( 'scroll' !== root.getAttribute( 'data-mode' ) ) {
				return;
			}

			var more = scroller.scrollWidth - scroller.clientWidth;

			root.style.setProperty( '--eps-fl', scroller.scrollLeft > 2 ? '40px' : '0px' );
			root.style.setProperty( '--eps-fr', scroller.scrollLeft < more - 2 ? '40px' : '0px' );
		}

		// The caret under the picked circle, never past the card's corners.
		function caret() {
			if ( ! panel || 'v' === root.getAttribute( 'data-mode' ) ) {
				return;
			}

			var dot = items[ current ].querySelector( '.eps__dot' ).getBoundingClientRect();
			var box = panel.getBoundingClientRect();
			var inset = 26;
			var x = dot.left + dot.width / 2 - box.left;

			x = Math.max( inset, Math.min( box.width - inset, x ) );
			panel.style.setProperty( '--eps-x', Math.round( x ) + 'px' );
		}

		function reveal( smooth ) {
			if ( 'scroll' !== root.getAttribute( 'data-mode' ) ) {
				return;
			}

			var item = items[ current ];
			var left = item.offsetLeft + item.offsetWidth / 2 - scroller.clientWidth / 2;

			scroller.scrollTo( { left: Math.max( 0, left ), behavior: smooth && ! prefersLessMotion() ? 'smooth' : 'auto' } );
		}

		function layout() {
			mode();
			edges();
			caret();
		}

		/**
		 * Pick a stage.
		 *
		 * @param {number}  k       Index.
		 * @param {Object}  options focus: move focus there; scroll: bring it into view.
		 */
		function select( k, options ) {
			options = options || {};
			k = Math.max( 0, Math.min( items.length - 1, k ) );

			var changed = k !== current;

			current = k;
			root.style.setProperty( '--eps-k', k );

			items.forEach( function ( item, i ) {
				item.classList.toggle( 'is-sel', i === k );
				item.classList.toggle( 'is-done', i < k );
				stages[ i ].setAttribute( 'tabindex', i === k ? '0' : '-1' );

				if ( i === k ) {
					stages[ i ].setAttribute( 'aria-current', 'step' );
				} else {
					stages[ i ].removeAttribute( 'aria-current' );
				}
			} );

			slides.forEach( function ( slide, i ) {
				slide.hidden = i !== k;

				if ( i === k && changed ) {
					slide.classList.remove( 'is-swap' );
					void slide.offsetWidth;
					slide.classList.add( 'is-swap' );
				}
			} );

			if ( options.focus ) {
				stages[ k ].focus( { preventScroll: true } );
			}

			if ( options.scroll ) {
				reveal( true );
			}

			caret();
		}

		function user( k, options ) {
			stopAuto();
			select( k, options );
		}

		stages.forEach( function ( stage, i ) {
			// Hover picks on the wide rail only: in the vertical stepper it
			// would open rows under a passing pointer. And it never scrolls,
			// or the rail would slide the next stage under the pointer.
			stage.addEventListener( 'pointerenter', function ( event ) {
				if ( 'mouse' === event.pointerType && 'v' !== root.getAttribute( 'data-mode' ) ) {
					user( i );
				}
			} );

			stage.addEventListener( 'click', function () {
				user( i, { scroll: true } );
			} );

			stage.addEventListener( 'focus', function () {
				if ( i !== current ) {
					user( i, { scroll: true } );
				}
			} );
		} );

		root.addEventListener( 'keydown', function ( event ) {
			if ( ! event.target.closest || ! event.target.closest( '.eps__stage' ) ) {
				return;
			}

			var keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
			var next = null;

			if ( keys[ event.key ] ) {
				next = ( current + keys[ event.key ] + items.length ) % items.length;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = items.length - 1;
			}

			if ( null !== next ) {
				event.preventDefault();
				user( next, { focus: true, scroll: true } );
			}
		} );

		scroller.addEventListener( 'scroll', function () {
			cancelAnimationFrame( pending );
			pending = requestAnimationFrame( function () {
				edges();
				caret();
			} );
		}, { passive: true } );

		layout();
		reveal( false );
		caret();

		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( function () {
				cancelAnimationFrame( pending );
				pending = requestAnimationFrame( function () {
					var before = root.getAttribute( 'data-mode' );

					layout();

					if ( before !== root.getAttribute( 'data-mode' ) ) {
						reveal( false );
						caret();
					}
				} );
			} ).observe( root );
		}

		// A late web font changes where the labels wrap.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( layout );
		}

		if ( editing() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		/* Auto-advance, while on screen, until the visitor takes over. */
		var every = parseInt( root.getAttribute( 'data-auto' ), 10 );

		if ( every && items.length > 1 && ! prefersLessMotion() ) {
			var timer = 0;
			var stopped = false;
			var watch = new IntersectionObserver(
				function ( entries ) {
					if ( stopped ) {
						return;
					}

					if ( entries[ 0 ].isIntersecting && ! timer ) {
						timer = window.setInterval( function () {
							// Leave a focused stepper alone; keyboard users are reading it.
							if ( ! root.contains( document.activeElement ) ) {
								select( ( current + 1 ) % items.length, { scroll: true } );
							}
						}, every );
					} else if ( ! entries[ 0 ].isIntersecting && timer ) {
						window.clearInterval( timer );
						timer = 0;
					}
				},
				{ threshold: 0.4 }
			);

			stopAuto = function () {
				stopped = true;
				window.clearInterval( timer );
				watch.disconnect();
			};

			root.addEventListener( 'pointerdown', function () {
				stopAuto();
			} );

			watch.observe( root );
		}

		/* The entrance, once. */
		if ( ! root.classList.contains( 'eps--animate' ) || prefersLessMotion() ) {
			return;
		}

		root.classList.add( 'eps--armed' );

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}

				observer.disconnect();
				root.classList.add( 'is-in' );

				window.setTimeout( function () {
					root.classList.remove( 'eps--armed' );
				}, 900 + items.length * 70 );
			},
			{ threshold: 0.25 }
		);

		observer.observe( root );
	}

	function initAll( scope ) {
		var context = scope || document;

		if ( 1 === context.nodeType && context.matches && context.matches( ROOT ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOT ) ).forEach( init );
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

			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eps-process-stepper.default', function ( scope ) {
				var el = scope && scope[0] ? scope[0] : scope;

				if ( el && 1 === el.nodeType ) {
					initAll( el );
				}
			} );
		} );
	}
}() );
