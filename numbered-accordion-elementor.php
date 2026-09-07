<?php
/**
 * Plugin Name:       Numbered Accordion for Elementor
 * Plugin URI:        https://erudarobiu.com/
 * Description:       A modern, lightly animated numbered accordion widget for Elementor. Works on both classic and V4 (Atomic) pages.
 * Version:           1.0.3
 * Author:            Eruda Robiu
 * Author URI:        https://erudarobiu.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       numbered-accordion
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package NumberedAccordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'NACC_VERSION', '1.0.3' );
define( 'NACC_FILE', __FILE__ );
define( 'NACC_PATH', plugin_dir_path( __FILE__ ) );
define( 'NACC_URL', plugin_dir_url( __FILE__ ) );

define( 'NACC_MIN_ELEMENTOR', '3.5.0' );
define( 'NACC_REPO', 'https://github.com/ErudaRobiu/numbered-accordion-elementor/' );
define( 'NACC_MIN_PHP', '7.4' );

/**
 * Boot the plugin only when every dependency is genuinely present.
 *
 * Every failure path below returns quietly and shows an admin notice. Nothing
 * here can produce a fatal error on the front end, which is the whole point:
 * if Elementor is deactivated, downgraded or restructured, the site keeps
 * running and the widget simply stops appearing.
 */
function nacc_bootstrap() {
	// 1. PHP version.
	if ( version_compare( PHP_VERSION, NACC_MIN_PHP, '<' ) ) {
		nacc_admin_notice(
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				esc_html__( 'Numbered Accordion for Elementor requires PHP %1$s or greater. You are running %2$s.', 'numbered-accordion' ),
				NACC_MIN_PHP,
				PHP_VERSION
			)
		);
		return;
	}

	// 2. Elementor loaded at all.
	if ( ! did_action( 'elementor/loaded' ) ) {
		nacc_admin_notice(
			esc_html__( 'Numbered Accordion for Elementor requires Elementor to be installed and activated.', 'numbered-accordion' )
		);
		return;
	}

	// 3. Elementor version.
	if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, NACC_MIN_ELEMENTOR, '<' ) ) {
		nacc_admin_notice(
			sprintf(
				/* translators: %s: required Elementor version */
				esc_html__( 'Numbered Accordion for Elementor requires Elementor %s or greater.', 'numbered-accordion' ),
				NACC_MIN_ELEMENTOR
			)
		);
		return;
	}

	/*
	 * Note: do NOT test for \Elementor\Widget_Base here. Elementor's autoloader
	 * cannot resolve it (its derived path is ELEMENTOR_PATH/widget-base.php,
	 * while the file actually lives in includes/base/), so the class only comes
	 * into existence when the widgets manager requires it -- which happens
	 * immediately before the elementor/widgets/register hook fires. That check
	 * belongs in the register callback, and lives in Plugin::register_widgets().
	 */

	require_once NACC_PATH . 'includes/class-plugin.php';

	\NumberedAccordion\Plugin::instance();
}
add_action( 'plugins_loaded', 'nacc_bootstrap', 20 );

/**
 * Queue a dismissible admin notice. Admin-side only, never front end.
 *
 * @param string $message Escaped message text.
 */
function nacc_admin_notice( $message ) {
	add_action(
		'admin_notices',
		function () use ( $message ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				wp_kses_post( $message )
			);
		}
	);
}

/**
 * Wire up the update channel.
 *
 * Runs independently of Elementor: a site should still be able to receive a fix
 * even if Elementor is deactivated at the time. Everything is guarded so a
 * missing or broken library degrades to "no update notices" rather than a
 * fatal error.
 */
add_action(
	'init',
	function () {
		$library = NACC_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

		if ( ! is_readable( $library ) ) {
			return;
		}

		require_once $library;

		$factory = '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory';

		if ( ! class_exists( $factory ) ) {
			return;
		}

		try {
			$checker = $factory::buildUpdateChecker( NACC_REPO, NACC_FILE, 'numbered-accordion-elementor' );
			$checker->setBranch( 'main' );

			// Serve the zip attached to each release rather than GitHub's
			// auto-generated source archive, whose root folder is named after
			// the tag and would rename the plugin directory on update.
			$api = $checker->getVcsApi();
			if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
				$api->enableReleaseAssets();
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Numbered Accordion: update checker failed - ' . $e->getMessage() );
			}
		}
	}
);

/**
 * Never auto-update this plugin.
 *
 * The update notice appears in wp-admin, but installing it stays a deliberate
 * click. A bad release must not roll itself out across live client sites.
 */
add_filter(
	'auto_update_plugin',
	function ( $update, $item ) {
		if ( isset( $item->plugin ) && plugin_basename( NACC_FILE ) === $item->plugin ) {
			return false;
		}
		return $update;
	},
	10,
	2
);

/**
 * Load translations.
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'numbered-accordion', false, dirname( plugin_basename( NACC_FILE ) ) . '/languages' );
	}
);
