<?php
/**
 * Partner diagram module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Partners;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Partner Diagram widget.
 */
final class Partners_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'epdg-partner-diagram';
	const SCRIPT_HANDLE = 'epdg-partner-diagram';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'partners';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Partner Diagram', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Partner Diagram" widget: who supplies what to whom, as linked cards over a blurred photograph, drawn in on scroll.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/partners/assets/css/partner-diagram.css',
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
			ERUDA_URL . 'modules/partners/assets/js/partner-diagram.js',
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

			require_once ERUDA_PATH . 'modules/partners/class-partners-content.php';
			require_once ERUDA_PATH . 'modules/partners/widgets/class-partner-diagram-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Partners\Widgets\Partner_Diagram_Widget' ) ) {
				$widgets_manager->register( new Widgets\Partner_Diagram_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the partner diagram widget - ' . $e->getMessage() );
			}
		}
	}
}
