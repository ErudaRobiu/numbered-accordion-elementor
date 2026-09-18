<?php
/**
 * The plugin's own section in the Elementor panel.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gives every widget in this plugin one place to live in the widget panel,
 * instead of scattering them through Elementor's General section.
 *
 * Registered once from Toolkit::boot() rather than per module, so three
 * modules do not race to create the same category.
 *
 * The slug is panel-only. Elementor stores a widget's name in saved layouts,
 * never its category, so changing this cannot affect an existing page.
 */
final class Panel_Category {

	const SLUG = 'eruda-toolkit';

	/**
	 * Hook it up.
	 */
	public static function boot() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register' ) );
	}

	/**
	 * Add the category.
	 *
	 * Wrapped because the manager's signature is Elementor's to change, and a
	 * missing category should cost a tidy panel, not the editor.
	 *
	 * @param mixed $manager Elementor's elements manager.
	 */
	public static function register( $manager ) {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'add_category' ) ) {
			return;
		}

		try {
			$manager->add_category(
				self::SLUG,
				array(
					'title' => esc_html__( 'Eruda Toolkit', 'numbered-accordion' ),
					'icon'  => 'eicon-nerd',
				)
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: could not add the panel category - ' . $e->getMessage() );
			}
		}
	}
}
