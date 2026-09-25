<?php
/**
 * Flow Schematic module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Schematic;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the stylesheet and the Flow Schematic widget.
 */
final class Schematic_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'efs-flow-schematic';
	const SCRIPT_HANDLE = 'efs-flow-schematic';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'schematic';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Flow Schematic', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Flow Schematic" widget: a process diagram drawn in CSS — stages, connectors, a return path, a monitoring bar and a boundary line — so every label stays real text instead of being baked into a picture.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/schematic/assets/css/flow-schematic.css',
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
			ERUDA_URL . 'modules/schematic/assets/js/flow-schematic.js',
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

			require_once ERUDA_PATH . 'modules/schematic/class-schematic-content.php';
			require_once ERUDA_PATH . 'modules/schematic/class-schematic-svg.php';
			require_once ERUDA_PATH . 'modules/schematic/widgets/class-flow-schematic-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Schematic\Widgets\Flow_Schematic_Widget' ) ) {
				$widgets_manager->register( new Widgets\Flow_Schematic_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the flow schematic widget - ' . $e->getMessage() );
			}
		}
	}
}
