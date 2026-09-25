<?php
/**
 * Data table module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Table;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers assets and the Data Table widget.
 */
final class Table_Module extends Elementor_Module {

	const STYLE_HANDLE = 'etbl-data-table';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'table';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Data Table', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a "Data Table" widget: a reference table of two or three columns with a filled header row, banded rows and a last column you can set apart. On a phone each row folds into a block with its headings beside the values, rather than scrolling sideways.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'modules/table/assets/css/data-table.css',
			array(),
			ERUDA_VERSION
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

			require_once ERUDA_PATH . 'modules/table/class-table-content.php';
			require_once ERUDA_PATH . 'modules/table/widgets/class-data-table-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Table\Widgets\Data_Table_Widget' ) ) {
				$widgets_manager->register( new Widgets\Data_Table_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the data table widget - ' . $e->getMessage() );
			}
		}
	}
}
