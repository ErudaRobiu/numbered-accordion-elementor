<?php
/**
 * Impact grid module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Impact;

use ErudaToolkit\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Impact Grid widget.
 */
final class Impact_Module implements Module {

	const STYLE_HANDLE  = 'eimp-impact-grid';
	const SCRIPT_HANDLE = 'eimp-impact-grid';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'impact';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Impact Grid', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Impact Grid" widget to the Elementor panel: numbered stat and checklist cards that animate into view.', 'numbered-accordion' );
	}

	/**
	 * Is Elementor present and recent enough?
	 *
	 * As with the accordion module, do NOT test for \Elementor\Widget_Base
	 * here -- it does not exist at plugins_loaded. See register_widgets().
	 *
	 * @return bool
	 */
	public static function is_available() {
		return self::elementor_loaded() && self::elementor_recent_enough();
	}

	/**
	 * The same checks, said out loud.
	 *
	 * @return string[]
	 */
	public static function requirement_messages() {
		if ( ! self::elementor_loaded() ) {
			return array( esc_html__( 'Elementor is not installed or not activated.', 'numbered-accordion' ) );
		}

		if ( ! self::elementor_recent_enough() ) {
			return array(
				sprintf(
					/* translators: %s: required Elementor version */
					esc_html__( 'Elementor %s or greater is required.', 'numbered-accordion' ),
					ERUDA_MIN_ELEMENTOR
				),
			);
		}

		return array();
	}

	/**
	 * Has Elementor booted?
	 *
	 * @return bool
	 */
	private static function elementor_loaded() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/**
	 * Is the Elementor version high enough?
	 *
	 * @return bool
	 */
	private static function elementor_recent_enough() {
		return defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ERUDA_MIN_ELEMENTOR, '>=' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'modules/impact/assets/css/impact-grid.css',
			array(),
			ERUDA_VERSION
		);
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			ERUDA_URL . 'modules/impact/assets/js/impact-grid.js',
			array(),
			ERUDA_VERSION,
			true
		);
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * Wrapped in a try/catch so a malformed widget can never take the editor
	 * or the front end down with it.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widget manager.
	 */
	public function register_widgets( $widgets_manager ) {
		try {
			// Widget_Base only exists from this hook onwards. See the note on
			// is_available().
			if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
				return;
			}

			require_once ERUDA_PATH . 'modules/impact/class-impact-content.php';
			require_once ERUDA_PATH . 'modules/impact/widgets/class-impact-grid-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Impact\Widgets\Impact_Grid_Widget' ) ) {
				$widgets_manager->register( new Widgets\Impact_Grid_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the impact grid widget - ' . $e->getMessage() );
			}
		}
	}
}
