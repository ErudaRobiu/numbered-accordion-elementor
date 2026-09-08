<?php
/**
 * The module contract.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A toolkit module.
 *
 * Identity and requirements are static so the settings page can describe a
 * module without booting it, which matters for a module whose requirements are
 * not met.
 */
interface Module {

	/**
	 * Stable identifier. Used as the settings option key, so it must never
	 * change once shipped.
	 *
	 * @return string
	 */
	public static function id();

	/**
	 * Human-readable name, for the settings page.
	 *
	 * @return string
	 */
	public static function label();

	/**
	 * One-line description, for the settings page.
	 *
	 * @return string
	 */
	public static function description();

	/**
	 * Can this module run here?
	 *
	 * Called on every page load at plugins_loaded, so it must stay cheap and
	 * must not call any translation function: WordPress 6.7 and later warn
	 * when a text domain is loaded before init, and init has not run yet.
	 * Building the human-readable version is requirement_messages()'s job.
	 *
	 * @return bool
	 */
	public static function is_available();

	/**
	 * Why the module cannot run, for the settings screen.
	 *
	 * Only ever called while rendering wp-admin, which is long after init, so
	 * translating here is safe.
	 *
	 * @return string[] Human-readable failure reasons. Empty when satisfied.
	 */
	public static function requirement_messages();

	/**
	 * Add the module's hooks. Called only when the module is both enabled and
	 * has its requirements met.
	 *
	 * @return void
	 */
	public function boot();
}
