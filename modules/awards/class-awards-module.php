<?php
/**
 * Award wall module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Awards;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Award Wall widget.
 */
final class Awards_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eaw-award-wall';
	const SCRIPT_HANDLE = 'eaw-award-wall';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'awards';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Award Wall', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Award Wall" widget: award badges in a grid beside a count that climbs as it scrolls into view.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/awards/assets/css/award-wall.css',
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
			ERUDA_URL . 'modules/awards/assets/js/award-wall.js',
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

			require_once ERUDA_PATH . 'modules/awards/class-awards-content.php';
			require_once ERUDA_PATH . 'modules/awards/widgets/class-award-wall-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Awards\Widgets\Award_Wall_Widget' ) ) {
				$widgets_manager->register( new Widgets\Award_Wall_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the award wall widget - ' . $e->getMessage() );
			}
		}
	}
}
