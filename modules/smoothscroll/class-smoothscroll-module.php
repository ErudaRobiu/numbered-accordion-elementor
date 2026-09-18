<?php
/**
 * Smooth scrolling module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\SmoothScroll;

use ErudaToolkit\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Eases the whole page's scrolling, using the vendored Lenis.
 *
 * Unlike the other Elementor modules this one has no widget and no controls:
 * it is on or off for the whole site, from the settings screen. Tuning is done
 * through the eruda_smooth_scroll_options filter, because a scroll feel is set
 * once per site and then left alone.
 */
final class SmoothScroll_Module implements Module {

	const STYLE_HANDLE   = 'eanm-lenis';
	const LIBRARY_HANDLE = 'eanm-lenis';
	const SCRIPT_HANDLE  = 'eanm-smooth-scroll';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'smoothscroll';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Smooth Scrolling', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Eases the whole page\'s scrolling, the way a showcase site does. Off in the editor, and off for visitors who have asked for reduced motion.', 'numbered-accordion' );
	}

	/**
	 * Needs nothing but a browser.
	 *
	 * No Elementor requirement: a site running this on a classic theme gets
	 * the same benefit.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return true;
	}

	/**
	 * Nothing to report.
	 *
	 * @return string[]
	 */
	public static function requirement_messages() {
		return array();
	}

	/**
	 * Default scroll feel.
	 *
	 * Duration is seconds to settle. Much above 1.5 and the page feels like
	 * it is wading; much below 0.8 and there is no point having it on.
	 *
	 * @return array
	 */
	public static function options() {
		$options = apply_filters(
			'eruda_smooth_scroll_options',
			array( 'duration' => 1.1 )
		);

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$duration = isset( $options['duration'] ) ? (float) $options['duration'] : 1.1;

		// A zero or negative duration would divide by nothing inside the
		// library. Clamp rather than trust.
		if ( $duration <= 0 || $duration > 5 ) {
			$duration = 1.1;
		}

		return array( 'duration' => $duration );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Load the library and the starter.
	 *
	 * Deliberately not loaded in wp-admin, and the script itself declines to
	 * run inside the Elementor editor's preview frame.
	 */
	public function enqueue() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'vendor/lenis/lenis.css',
			array(),
			ERUDA_VERSION
		);

		wp_enqueue_script(
			self::LIBRARY_HANDLE,
			ERUDA_URL . 'vendor/lenis/lenis.min.js',
			array(),
			ERUDA_VERSION,
			true
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			ERUDA_URL . 'modules/smoothscroll/assets/js/smooth-scroll.js',
			array( self::LIBRARY_HANDLE ),
			ERUDA_VERSION,
			true
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.erudaSmoothScroll = window.erudaSmoothScroll || {}; window.erudaSmoothScroll.options = ' . wp_json_encode( self::options() ) . ';',
			'before'
		);
	}
}
