/**
 * Eruda Toolkit - text animation, editor side
 *
 * Runs in the Elementor panel, not in the preview. The animation itself lives
 * in the preview iframe, so this reaches across to it.
 *
 * Every step is guarded: Elementor's internals are not a public API, and a
 * Replay button that quietly does nothing is a far better failure than an
 * editor that throws.
 */
( function ( $ ) {
	'use strict';

	/**
	 * The preview iframe's window, where text-animation.js is running.
	 *
	 * @return {Window|null}
	 */
	function previewWindow() {
		try {
			return window.elementor.$preview[0].contentWindow;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * The widget's wrapper element inside the preview.
	 *
	 * The control view is handed to us by Elementor's button control. Its
	 * container carries the element view, whose el is the wrapper. Older and
	 * newer versions disagree about where the container hangs, so try the
	 * shapes in turn and fall back to finding it by id.
	 *
	 * @param {Object} view The control view.
	 * @return {Element|null}
	 */
	function widgetElement( view ) {
		var preview = previewWindow();

		if ( ! preview || ! view ) {
			return null;
		}

		var container = view.container || ( view.options && view.options.container );

		if ( container && container.view && container.view.el ) {
			return container.view.el;
		}

		var id = null;

		if ( container ) {
			id = container.id || ( container.model && container.model.get( 'id' ) );
		}

		if ( id && preview.document ) {
			return preview.document.querySelector( '.elementor-element-' + id );
		}

		return null;
	}

	$( window ).on( 'elementor:init', function () {
		if ( ! window.elementor || ! window.elementor.channels ) {
			return;
		}

		window.elementor.channels.editor.on( 'eanm:replay', function ( view ) {
			var preview = previewWindow();
			var element = widgetElement( view );

			if ( preview && preview.erudaMotion && element ) {
				preview.erudaMotion.replay( element );
			}
		} );
	} );
}( jQuery ) );
