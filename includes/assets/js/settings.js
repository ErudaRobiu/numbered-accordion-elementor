/**
 * The settings screen's media fields.
 *
 * One frame is built per field and kept, rather than a new one per click: the
 * media library is expensive to open and reopening a kept frame remembers the
 * selection, which is what somebody swapping between two images expects.
 *
 * @package ErudaToolkit
 */

( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.media ) {
		// The picker could not load. The field still holds its value and the
		// Remove button still works, so the screen degrades to read-only
		// rather than to broken.
		return;
	}

	/**
	 * Wire one media field.
	 *
	 * @param {Element} root The field wrapper.
	 */
	function field( root ) {
		var input   = root.querySelector( '[data-etrn-value]' );
		var preview = root.querySelector( '[data-etrn-preview]' );
		var choose  = root.querySelector( '[data-etrn-choose]' );
		var remove  = root.querySelector( '[data-etrn-remove]' );
		var frame   = null;

		if ( ! input || ! choose ) {
			return;
		}

		/**
		 * Show or hide the preview and the Remove button for a given value.
		 *
		 * @param {string} url Image URL, or an empty string.
		 */
		function render( url ) {
			if ( preview ) {
				preview.innerHTML = '';

				if ( url ) {
					var img = document.createElement( 'img' );
					img.src = url;
					img.alt = '';
					img.style.maxWidth = '160px';
					img.style.maxHeight = '60px';
					img.style.display = 'block';
					preview.appendChild( img );
				}
			}

			if ( remove ) {
				remove.hidden = ! input.value || input.value === '0';
			}
		}

		choose.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: choose.getAttribute( 'data-etrn-choose' ),
					button: { text: choose.getAttribute( 'data-etrn-button' ) },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var sizes = attachment.sizes || {};

					input.value = attachment.id;

					// A thumbnail for the preview when there is one; the full
					// file otherwise, which is the case for an SVG.
					render( sizes.medium ? sizes.medium.url : attachment.url );
				} );
			}

			frame.open();
		} );

		if ( remove ) {
			remove.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				input.value = '0';
				render( '' );
			} );
		}

		render( root.getAttribute( 'data-etrn-url' ) || '' );
	}

	function boot() {
		var fields = document.querySelectorAll( '[data-etrn-media]' );
		var i;

		for ( i = 0; i < fields.length; i++ ) {
			field( fields[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}( window.wp ) );
