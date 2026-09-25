<?php
/**
 * Split explainer module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Explainer;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Scroll Explainer widget.
 */
final class Explainer_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eexp-split-explainer';
	const SCRIPT_HANDLE = 'eexp-split-explainer';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'explainer';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Split Slab and Ledger', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds two widgets: "Split Slab", a single rounded object divided by a coloured bar, with the problem on one side and the answer on the other; and "Comparison Ledger", a row-per-point table that sets the old way against the new one.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/explainer/assets/css/split-explainer.css',
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
			ERUDA_URL . 'modules/explainer/assets/js/split-explainer.js',
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

			require_once ERUDA_PATH . 'modules/explainer/class-explainer-content.php';
			require_once ERUDA_PATH . 'modules/explainer/widgets/class-split-slab-widget.php';
			require_once ERUDA_PATH . 'modules/explainer/widgets/class-comparison-ledger-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Explainer\Widgets\Split_Slab_Widget' ) ) {
				$widgets_manager->register( new Widgets\Split_Slab_Widget() );
			}

			if ( class_exists( '\ErudaToolkit\Modules\Explainer\Widgets\Comparison_Ledger_Widget' ) ) {
				$widgets_manager->register( new Widgets\Comparison_Ledger_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the explainer widgets - ' . $e->getMessage() );
			}
		}
	}
}
