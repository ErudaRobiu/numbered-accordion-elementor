<?php
/**
 * Customer logo tabs module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\LogoTabs;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Customer Logo Tabs widget.
 */
final class LogoTabs_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eclt-customer-logo-tabs';
	const SCRIPT_HANDLE = 'eclt-customer-logo-tabs';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'logotabs';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Customer Logo Tabs', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Customer Logo Tabs" widget: a customer logo wall split into audience tabs, with segment chips that filter it.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/logotabs/assets/css/customer-logo-tabs.css',
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
			ERUDA_URL . 'modules/logotabs/assets/js/customer-logo-tabs.js',
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

			require_once ERUDA_PATH . 'modules/logotabs/class-logotabs-content.php';
			require_once ERUDA_PATH . 'modules/logotabs/widgets/class-customer-logo-tabs-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\LogoTabs\Widgets\Customer_Logo_Tabs_Widget' ) ) {
				$widgets_manager->register( new Widgets\Customer_Logo_Tabs_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the customer logo tabs widget - ' . $e->getMessage() );
			}
		}
	}
}
