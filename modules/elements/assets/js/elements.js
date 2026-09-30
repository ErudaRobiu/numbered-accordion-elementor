/**
 * Eruda Toolkit - Element List and Annotated Mark
 *
 * The two widgets never look for each other. Pointing at an item announces
 * its key on the document as an `eruda:link` event, { group, key }, and every
 * widget in that group lights whatever it holds under that key. An empty key
 * clears. Anything else on the page may listen, or announce, the same way.
 *
 * Also draws the mark's leader lines from where each label really ends up,
 * plays the mark's entrance once, and, when asked, steps the list through its
 * items while nobody is pointing.
 *
 * Vanilla, no dependencies, safe to run twice. Without it both widgets show
 * complete and static, the lines from an estimate.
 */
( function () {
	'use strict';

	var ROOTS = '.eel, .eam';
	var ITEM = '[data-key][tabindex]';
	var EVENT = 'eruda:link';
	var READY = 'data-eel-ready';

	// The key lit in each group right now, whoever lit it.
	var current = {};

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
	 * Announce a key to a group.
	 *
	 * @param {string} group  Link group.
	 * @param {string} key    Key, or '' to clear.
	 * @param {string} source 'user' or 'cycle'.
	 */
	function emit( group, key, source ) {
		var detail = { group: group, key: key || '', source: source || 'user' };

		document.dispatchEvent( new CustomEvent( EVENT, { detail: detail } ) );
	}

	document.addEventListener( EVENT, function ( event ) {
		var d = event.detail || {};

		if ( d.group ) {
			current[ d.group ] = d.key || '';
		}
	} );

	/**
	 * Light one key in one widget.
	 *
	 * @param {Element} root Widget.
	 * @param {string}  key  Key, or ''.
	 */
	function apply( root, key ) {
		var any = false;

		toArray( root.querySelectorAll( '[data-key]' ) ).forEach( function ( el ) {
			var on = '' !== key && el.getAttribute( 'data-key' ) === key;

			el.classList.toggle( 'is-on', on );
			any = any || on;
		} );

		root.classList.toggle( 'has-on', any );
	}

	/* ----------------------------------------------------- interaction --- */

	function bind( root, group ) {
		var touchWasOn = null;

		function itemOf( node ) {
			var item = node && node.closest ? node.closest( ITEM ) : null;

			return item && root.contains( item ) ? item : null;
		}

		function keyOf( item ) {
			return item ? item.getAttribute( 'data-key' ) || '' : '';
		}

		function set( key ) {
			if ( current[ group ] !== key ) {
				emit( group, key, 'user' );
			}
		}

		function leave( item, next ) {
			// Moving onto another item: its own enter takes over.
			if ( itemOf( next ) ) {
				return;
			}

			if ( current[ group ] === keyOf( item ) ) {
				set( '' );
			}
		}

		root.addEventListener( 'mouseover', function ( event ) {
			var item = itemOf( event.target );

			if ( item ) {
				set( keyOf( item ) );
			}
		} );

		root.addEventListener( 'mouseout', function ( event ) {
			var item = itemOf( event.target );

			if ( item && ! item.contains( event.relatedTarget ) ) {
				leave( item, event.relatedTarget );
			}
		} );

		root.addEventListener( 'focusin', function ( event ) {
			var item = itemOf( event.target );

			if ( item ) {
				set( keyOf( item ) );
			}
		} );

		root.addEventListener( 'focusout', function ( event ) {
			var item = itemOf( event.target );

			if ( item ) {
				leave( item, event.relatedTarget );
			}
		} );

		root.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && current[ group ] ) {
				set( '' );
			}
		} );

		// A tap lights an item; tapping it again, or anywhere else, clears.
		root.addEventListener( 'pointerdown', function ( event ) {
			var item = itemOf( event.target );

			touchWasOn = 'mouse' !== event.pointerType && item && current[ group ] === keyOf( item ) ? item : null;
		} );

		root.addEventListener( 'click', function ( event ) {
			var item = itemOf( event.target );

			if ( item && item === touchWasOn ) {
				set( '' );
				item.blur();
			}

			touchWasOn = null;
		} );
	}

	document.addEventListener( 'pointerdown', function ( event ) {
		if ( 'mouse' === event.pointerType ) {
			return;
		}

		var inside = event.target && event.target.closest ? event.target.closest( ITEM ) : null;

		toArray( document.querySelectorAll( '[' + READY + ']' ) ).forEach( function ( root ) {
			var group = root.getAttribute( 'data-link-group' );

			if ( current[ group ] && ! ( inside && root.contains( inside ) ) ) {
				// Only the widgets of the group the tap left clear it, once.
				if ( ! inside || inside.closest( '[data-link-group]' ).getAttribute( 'data-link-group' ) !== group ) {
					emit( group, '', 'user' );
				}
			}
		} );
	} );

	/* ----------------------------------------------------------- cycle --- */

	/**
	 * Step the list through its keys while it is on screen and nobody has
	 * pointed at anything in the group. The first pointing stops it for good.
	 */
	function cycle( root, group ) {
		var every = parseInt( root.getAttribute( 'data-cycle' ), 10 );
		var keys = toArray( root.querySelectorAll( ITEM ) ).map( function ( el ) {
			return el.getAttribute( 'data-key' );
		} );

		if ( ! every || keys.length < 2 || prefersLessMotion() || editing() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var timer = 0;
		var index = -1;
		var stopped = false;

		function tick() {
			index = ( index + 1 ) % keys.length;
			emit( group, keys[ index ], 'cycle' );
		}

		function pause() {
			window.clearInterval( timer );
			timer = 0;

			if ( keys.indexOf( current[ group ] ) > -1 && ! stopped ) {
				emit( group, '', 'cycle' );
			}
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( stopped ) {
					return;
				}

				if ( entries[ 0 ].isIntersecting && ! timer ) {
					timer = window.setInterval( tick, every );
				} else if ( ! entries[ 0 ].isIntersecting && timer ) {
					pause();
				}
			},
			{ threshold: 0.4 }
		);

		document.addEventListener( EVENT, function ( event ) {
			var d = event.detail || {};

			if ( d.group === group && 'cycle' !== d.source && ! stopped ) {
				stopped = true;
				window.clearInterval( timer );
				observer.disconnect();
			}
		} );

		observer.observe( root );
	}

	/* ----------------------------------------------------------- lines --- */

	/**
	 * Draw each leader line from where its label really is: out of the
	 * label's inner edge, level for an elbow's width, then down or up to the
	 * dot. Stage pixels, so strokes and elbows stay true at every size.
	 *
	 * @param {Element} root The mark.
	 */
	function lines( root ) {
		var svg = root.querySelector( '.eam__lines' );
		var stage = root.getBoundingClientRect();

		if ( ! svg || ! stage.width || ! stage.height ) {
			return;
		}

		var w = stage.width;
		var h = stage.height;
		var elbow = w * 0.125;

		svg.setAttribute( 'viewBox', '0 0 ' + round( w ) + ' ' + round( h ) );

		toArray( svg.querySelectorAll( '.eam__line' ) ).forEach( function ( path ) {
			var key = path.getAttribute( 'data-key' );
			var tag = root.querySelector( '.eam__tag[data-key="' + cssEscape( key ) + '"]' );
			var dot = root.querySelector( '.eam__dot[data-key="' + cssEscape( key ) + '"]' );

			if ( ! tag || ! dot ) {
				return;
			}

			var t = tag.getBoundingClientRect();
			var d = dot.getBoundingClientRect();
			var left = path.classList.contains( 'eam__line--l' );
			var x0 = ( left ? t.right : t.left ) - stage.left;
			var y0 = t.top + t.height / 2 - stage.top;
			var x2 = d.left + d.width / 2 - stage.left;
			var y2 = d.top + d.height / 2 - stage.top;
			var x1 = left ? Math.min( x0 + elbow, x2 ) : Math.max( x0 - elbow, x2 );
			var points = [ [ x0, y0 ] ];

			// A label that reaches past its dot (tiny stages): straight in.
			if ( left ? x0 < x2 : x0 > x2 ) {
				points.push( [ x1, y0 ] );
			}

			points.push( [ x2, y2 ] );

			path.setAttribute( 'd', points.map( function ( p, i ) {
				return ( i ? 'L' : 'M' ) + round( p[0] ) + ' ' + round( p[1] );
			} ).join( ' ' ) );
		} );
	}

	function round( n ) {
		return Math.round( n * 10 ) / 10;
	}

	function cssEscape( value ) {
		return window.CSS && CSS.escape ? CSS.escape( value ) : String( value ).replace( /["\\]/g, '\\$&' );
	}

	/* -------------------------------------------------------- entrance --- */

	function entrance( root ) {
		if ( ! root.classList.contains( 'eam--animate' ) || prefersLessMotion() || editing() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var count = root.querySelectorAll( '.eam__tag' ).length;

		root.classList.add( 'eam--armed' );

		var observer = new IntersectionObserver(
			function ( entries ) {
				if ( ! entries[ 0 ].isIntersecting ) {
					return;
				}

				observer.disconnect();
				lines( root );
				root.classList.add( 'is-in' );

				// Once everything has landed, let the entrance rules go.
				window.setTimeout( function () {
					root.classList.remove( 'eam--armed' );
				}, 1100 + count * 120 );
			},
			{ threshold: 0.3 }
		);

		observer.observe( root );
	}

	/* ------------------------------------------------------------ init --- */

	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var group = root.getAttribute( 'data-link-group' ) || 'logo-elements';

		root.setAttribute( 'data-link-group', group );

		document.addEventListener( EVENT, function ( event ) {
			var d = event.detail || {};

			// A widget the editor has re-rendered away just stops listening.
			if ( d.group === group && root.isConnected ) {
				apply( root, d.key || '' );
			}
		} );

		// Join a group that is already lit.
		if ( current[ group ] ) {
			apply( root, current[ group ] );
		}

		bind( root, group );

		if ( root.classList.contains( 'eam' ) ) {
			lines( root );

			if ( 'ResizeObserver' in window ) {
				var pending = 0;

				new ResizeObserver( function () {
					cancelAnimationFrame( pending );
					pending = requestAnimationFrame( function () {
						lines( root );
					} );
				} ).observe( root );
			}

			// A late web font changes how wide the labels are.
			if ( document.fonts && document.fonts.ready ) {
				document.fonts.ready.then( function () {
					lines( root );
				} );
			}

			entrance( root );
		} else {
			cycle( root, group );
		}
	}

	function initAll( scope ) {
		var context = scope || document;

		if ( 1 === context.nodeType && context.matches && context.matches( ROOTS ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOTS ) ).forEach( init );
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

			[ 'eel-element-list', 'eam-annotated-mark' ].forEach( function ( name ) {
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
