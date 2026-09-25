<?php
/**
 * The shared half of every module that needs Elementor.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What every widget-providing module answers the same way.
 *
 * Thirteen of the sixteen modules ship an Elementor widget, and all thirteen
 * had character-for-character the same four methods: whether Elementor is
 * loaded, whether it is recent enough, whether the module can therefore run,
 * and what to tell somebody on the settings screen when it cannot. Four
 * methods times thirteen copies is the largest single piece of repetition in
 * the plugin, and none of it was ever going to diverge -- they all ask the
 * same question about the same plugin.
 *
 * A module extending this still declares its own id, label, description and
 * boot, which are the parts that genuinely differ. The three modules that need
 * nothing but a browser -- Duplicate Pages, Smooth Scrolling and Page
 * Transitions -- do not extend it, because "can this run?" is simply "yes" for
 * them and inheriting a check they do not need would be worse than the two
 * lines they keep.
 */
abstract class Elementor_Module implements Module {

	/**
	 * Is Elementor loaded?
	 *
	 * @return bool
	 */
	protected static function elementor_loaded() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/**
	 * Is the Elementor version high enough?
	 *
	 * @return bool
	 */
	protected static function elementor_recent_enough() {
		return defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ERUDA_MIN_ELEMENTOR, '>=' );
	}

	/**
	 * Can this module run here?
	 *
	 * Do NOT test for \Elementor\Widget_Base here -- it does not exist at
	 * plugins_loaded, which is when this is asked. A module checks for the
	 * base class inside its own widget registration instead, where Elementor
	 * has finally defined it.
	 *
	 * Called on every page load, so it stays cheap and calls no translation
	 * function: WordPress 6.7 and later warn when a text domain is loaded
	 * before init, and init has not run yet. Building the human-readable
	 * version is requirement_messages()'s job.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return self::elementor_loaded() && self::elementor_recent_enough();
	}

	/**
	 * Why the module cannot run, for the settings screen.
	 *
	 * Only ever called while rendering wp-admin, which is long after init, so
	 * translating here is safe.
	 *
	 * @return string[] Human-readable failure reasons. Empty when satisfied.
	 */
	public static function requirement_messages() {
		if ( ! self::elementor_loaded() ) {
			return array( esc_html__( 'Elementor is not installed or not activated.', 'numbered-accordion' ) );
		}

		if ( ! self::elementor_recent_enough() ) {
			return array(
				sprintf(
					/* translators: %s: required Elementor version */
					esc_html__( 'Elementor %s or greater is required.', 'numbered-accordion' ),
					ERUDA_MIN_ELEMENTOR
				),
			);
		}

		return array();
	}
}
