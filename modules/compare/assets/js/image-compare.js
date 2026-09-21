/**
 * Eruda Toolkit - Image Compare
 *
 * Keeps the divider and the clip in step with the range input, and adds the
 * hover mode on top. The input does the rest by itself: keyboard, focus and
 * the announcement all come from it being a real control rather than a div
 * with listeners.
 *
 * Vanilla, no dependencies, safe to run twice. If it never runs, the frame
 * still shows both pictures split at their starting position -- the server
 * writes that into the style attribute.
 */
( function () {
	'use strict';

	var ROOT = '.ecmp';
	var READY = 'data-ecmp-ready';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function clamp( value ) {
		if ( ! isFinite( value ) ) {
			return 50;
		}

		return value < 0 ? 0 : value > 100 ? 100 : value;
	}

	/**
	 * Paint one position.
	 *
	 * @param {Element} root     Widget root.
	 * @param {number}  position 0 to 100.
	 */
	function paint( root, position ) {
		var pos = clamp( position );
		var vertical = root.classList.contains( 'ecmp--vertical' );

		root.style.setProperty( '--ecmp-pos', pos + '%' );
		// Unitless copy, for the arithmetic behind the label fade.
		root.style.setProperty( '--ecmp-n', String( pos ) );
		root.style.setProperty(
			'--ecmp-clip',
			vertical ? 'inset(' + pos + '% 0 0 0)' : 'inset(0 0 0 ' + pos + '%)'
		);
	}

	/**
	 * Wire one widget up.
	 *
	 * @param {Element} root Widget root.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		var range = root.querySelector( '.ecmp__range' );
		var frame = root.querySelector( '.ecmp__frame' );

		if ( ! range || ! frame ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var start = parseFloat( range.value );

		paint( root, start );

		range.addEventListener( 'input', function () {
			paint( root, parseFloat( range.value ) );
		} );

		if ( ! root.classList.contains( 'ecmp--hover' ) ) {
			return;
		}

		// Hover mode. The divider follows the pointer and goes back where it
		// started when the pointer leaves, so the frame is never left in a
		// state nobody chose.
		var vertical = root.classList.contains( 'ecmp--vertical' );

		frame.addEventListener( 'pointermove', function ( event ) {
			// A pen or a finger has no hover, so let those fall through to the
			// range input and drag it instead.
			if ( 'mouse' !== event.pointerType ) {
				return;
			}

			var box = frame.getBoundingClientRect();
			var pos = vertical
				? ( ( event.clientY - box.top ) / box.height ) * 100
				: ( ( event.clientX - box.left ) / box.width ) * 100;

			range.value = clamp( pos );
			paint( root, pos );
		} );

		frame.addEventListener( 'pointerleave', function () {
			range.value = start;
			paint( root, start );
		} );
	}

	/**
	 * Wire up every widget inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
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

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/ecmp-image-compare.default',
				function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;

					if ( el && 1 === el.nodeType ) {
						// A re-render replaces the markup, so the guard on the
						// old node does not carry over.
						initAll( el );
					}
				}
			);
		} );
	}
}() );
