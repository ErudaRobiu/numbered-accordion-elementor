<?php
/**
 * Industry showcase module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Industry;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Industry Showcase widget.
 */
final class Industry_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eind-industry-showcase';
	const SCRIPT_HANDLE = 'eind-industry-showcase';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'industry';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Industry Showcase', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Industry Showcase" widget: a list of industries walked by scrolling, with the names on the left, one picture in the middle that settles in as it changes, and the words on the right. Below the phone breakpoint it stops pinning and reads as a plain stack. It carries no background of its own, so it sits on whatever colour the section is.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/industry/assets/css/industry.css',
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
			ERUDA_URL . 'modules/industry/assets/js/industry.js',
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

			require_once ERUDA_PATH . 'modules/industry/class-industry-content.php';
			require_once ERUDA_PATH . 'modules/industry/widgets/class-industry-showcase-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Industry\Widgets\Industry_Showcase_Widget' ) ) {
				$widgets_manager->register( new Widgets\Industry_Showcase_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the industry showcase widget - ' . $e->getMessage() );
			}
		}
	}
}
