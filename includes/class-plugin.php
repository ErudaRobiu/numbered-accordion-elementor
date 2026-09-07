<?php
/**
 * Plugin controller.
 *
 * @package NumberedAccordion
 */

namespace NumberedAccordion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Elementor widget.
 */
final class Plugin {

	const STYLE_HANDLE  = 'nacc-numbered-accordion';
	const SCRIPT_HANDLE = 'nacc-numbered-accordion';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook everything up.
	 */
	private function __construct() {
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
			NACC_URL . 'assets/css/numbered-accordion.css',
			array(),
			NACC_VERSION
		);
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			NACC_URL . 'assets/js/numbered-accordion.js',
			array(),
			NACC_VERSION,
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
			/*
			 * By this point Elementor's widgets manager has already required
			 * includes/base/widget-base.php, so the parent class our widget
			 * extends is guaranteed to be defined. Verified here rather than at
			 * plugins_loaded, where it never is.
			 */
			if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
				return;
			}

			require_once NACC_PATH . 'widgets/class-numbered-accordion-widget.php';

			if ( class_exists( '\NumberedAccordion\Widgets\Numbered_Accordion_Widget' ) ) {
				$widgets_manager->register( new Widgets\Numbered_Accordion_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Numbered Accordion: failed to register widget - ' . $e->getMessage() );
			}
		}
	}
}
