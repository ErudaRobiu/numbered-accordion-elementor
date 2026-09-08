<?php
/**
 * Plugin Name:       Eruda Toolkit
 * Plugin URI:        https://erudarobiu.com/
 * Description:       A small toolkit of site-building modules: a numbered accordion widget for Elementor, and a page duplicator.
 * Version:           2.0.0
 * Author:            Eruda Robiu
 * Author URI:        https://erudarobiu.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       numbered-accordion
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package ErudaToolkit
 */

/*
 * A note on names.
 *
 * The plugin is called Eruda Toolkit, but this file, and the directory holding
 * it, are still called numbered-accordion-elementor. That is deliberate.
 *
 * WordPress identifies an installed plugin by its path, and the bundled
 * plugin-update-checker keys its update channel on that same path. Renaming
 * either would orphan every existing install: PUC cannot carry an install
 * across a slug change, so each client site would need a manual
 * install-the-new-then-remove-the-old, in that order, with the accordion
 * missing from live pages in between.
 *
 * The rename is therefore display-only. Clients receive this as an ordinary
 * update and the entry in their plugin list simply renames itself. The path may
 * be tidied later, once no install remains on 1.0.x.
 *
 * The text domain stays 'numbered-accordion' for the same reason: existing
 * translation files are keyed to it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'ERUDA_VERSION', '2.0.0' );
define( 'ERUDA_FILE', __FILE__ );
define( 'ERUDA_PATH', plugin_dir_path( __FILE__ ) );
define( 'ERUDA_URL', plugin_dir_url( __FILE__ ) );

define( 'ERUDA_MIN_PHP', '7.4' );
define( 'ERUDA_MIN_ELEMENTOR', '3.5.0' );
define( 'ERUDA_REPO', 'https://github.com/ErudaRobiu/numbered-accordion-elementor/' );

/**
 * Boot the toolkit.
 *
 * Only the PHP version is checked here. Everything else is a per-module
 * requirement: a site without Elementor still gets a working duplicator, and
 * only the accordion module reports itself unavailable.
 *
 * This is also why the plugin deliberately does not declare a
 * `Requires Plugins: elementor` header. Elementor is a module requirement, not
 * a plugin requirement, and declaring it would stop the duplicator being
 * installable on a site that has no use for Elementor.
 */
function eruda_bootstrap() {
	if ( version_compare( PHP_VERSION, ERUDA_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'eruda_php_version_notice' );
		return;
	}

	require_once ERUDA_PATH . 'includes/interface-module.php';
	require_once ERUDA_PATH . 'includes/class-toolkit.php';
	require_once ERUDA_PATH . 'includes/class-settings.php';

	\ErudaToolkit\Toolkit::instance()->boot();
}
add_action( 'plugins_loaded', 'eruda_bootstrap', 20 );

/**
 * Tell an administrator that PHP is too old.
 *
 * The message is built here rather than at plugins_loaded because translating
 * anything before init makes WordPress 6.7 and later warn about loading a text
 * domain too early.
 */
function eruda_php_version_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'Eruda Toolkit requires PHP %1$s or greater. You are running %2$s.', 'numbered-accordion' ),
				ERUDA_MIN_PHP,
				PHP_VERSION
			)
		)
	);
}

/**
 * Wire up the update channel.
 *
 * Runs independently of every module, so a site can still receive a fix even
 * when every module is switched off. Everything is guarded so a missing or
 * broken library degrades to "no update notices" rather than a fatal error.
 */
add_action(
	'init',
	function () {
		$library = ERUDA_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

		if ( ! is_readable( $library ) ) {
			return;
		}

		require_once $library;

		$factory = '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory';

		if ( ! class_exists( $factory ) ) {
			return;
		}

		try {
			// The slug stays numbered-accordion-elementor: it must match the
			// directory this plugin is installed in, not the product name.
			$checker = $factory::buildUpdateChecker( ERUDA_REPO, ERUDA_FILE, 'numbered-accordion-elementor' );
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
				error_log( 'Eruda Toolkit: update checker failed - ' . $e->getMessage() );
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
		if ( isset( $item->plugin ) && plugin_basename( ERUDA_FILE ) === $item->plugin ) {
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
		load_plugin_textdomain( 'numbered-accordion', false, dirname( plugin_basename( ERUDA_FILE ) ) . '/languages' );
	}
);
