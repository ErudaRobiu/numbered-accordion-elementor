<?php
/**
 * Company chain module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Chain;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Company Chain widget.
 */
final class Chain_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'echn-company-chain';
	const SCRIPT_HANDLE = 'echn-company-chain';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'chain';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Company Chain', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Company Chain" widget: who makes, who distributes and who is served, as cards joined by arrows.', 'numbered-accordion' );
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
			ERUDA_URL . 'modules/chain/assets/css/company-chain.css',
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
			ERUDA_URL . 'modules/chain/assets/js/company-chain.js',
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

			require_once ERUDA_PATH . 'modules/chain/class-chain-content.php';
			require_once ERUDA_PATH . 'modules/chain/widgets/class-company-chain-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Chain\Widgets\Company_Chain_Widget' ) ) {
				$widgets_manager->register( new Widgets\Company_Chain_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the company chain widget - ' . $e->getMessage() );
			}
		}
	}
}
