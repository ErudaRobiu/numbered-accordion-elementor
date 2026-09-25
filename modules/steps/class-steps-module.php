<?php
/**
 * Process steps module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Steps;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Process Steps widget.
 */
final class Steps_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'estp-process-steps';
	const SCRIPT_HANDLE = 'estp-process-steps';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'steps';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Process Steps', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Process Steps" widget: a numbered process read downwards, with the words on the left and a small object standing in space on the right. Scrolling turns the objects rather than fading them in, and pointing at one opens it. It carries no background of its own, so it sits on whatever colour the section is.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/steps/assets/css/process-steps.css',
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
			ERUDA_URL . 'modules/steps/assets/js/process-steps.js',
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

			require_once ERUDA_PATH . 'modules/steps/class-steps-content.php';
			require_once ERUDA_PATH . 'modules/steps/widgets/class-process-steps-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Steps\Widgets\Process_Steps_Widget' ) ) {
				$widgets_manager->register( new Widgets\Process_Steps_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the process steps widget - ' . $e->getMessage() );
			}
		}
	}
}
