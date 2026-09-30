<?php
/**
 * Logo elements module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Elements;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the two linked logo element widgets.
 */
final class Elements_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eel-elements';
	const SCRIPT_HANDLE = 'eel-elements';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'elements';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Logo Elements', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds two linked widgets: an "Element List" and an "Annotated Mark". Pointing at an element in either lights it up in both, wherever they sit on the page.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/elements/assets/css/elements.css',
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
			ERUDA_URL . 'modules/elements/assets/js/elements.js',
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

			require_once ERUDA_PATH . 'modules/elements/class-elements-content.php';
			require_once ERUDA_PATH . 'modules/elements/widgets/class-element-list-widget.php';
			require_once ERUDA_PATH . 'modules/elements/widgets/class-annotated-mark-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Elements\Widgets\Element_List_Widget' ) ) {
				$widgets_manager->register( new Widgets\Element_List_Widget() );
			}

			if ( class_exists( '\ErudaToolkit\Modules\Elements\Widgets\Annotated_Mark_Widget' ) ) {
				$widgets_manager->register( new Widgets\Annotated_Mark_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the logo element widgets - ' . $e->getMessage() );
			}
		}
	}
}
