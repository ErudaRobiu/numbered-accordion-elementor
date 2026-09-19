<?php
/**
 * Spin badge module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Badge;

use ErudaToolkit\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Eruda Spin controls and their assets.
 */
final class Badge_Module implements Module {

	const SPIN_STYLE_HANDLE  = 'espin-spin';
	const SPIN_SCRIPT_HANDLE = 'espin-spin';

	/**
	 * Has the spin extension's stylesheet been asked for yet this request?
	 *
	 * @var bool
	 */
	private $spun = false;

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'badge';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Spin', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Eruda Spin" section to image, icon and button widgets, which turns what they hold and slows it to a stop under the pointer.', 'numbered-accordion' );
	}

	/**
	 * Is Elementor present and recent enough?
	 *
	 * As with the accordion module, do NOT test for \Elementor\Widget_Base
	 * here -- it does not exist at plugins_loaded. This module registers no
	 * widget, but the rule is the same for the controls it injects: they are
	 * added from an Elementor hook, never at plugins_loaded.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return self::elementor_loaded() && self::elementor_recent_enough();
	}

	/**
	 * The same checks, said out loud.
	 *
	 * @return string[]
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

	/**
	 * Has Elementor booted?
	 *
	 * @return bool
	 */
	private static function elementor_loaded() {
		return (bool) did_action( 'elementor/loaded' );
	}

	/**
	 * Is the Elementor version high enough?
	 *
	 * @return bool
	 */
	private static function elementor_recent_enough() {
		return defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ERUDA_MIN_ELEMENTOR, '>=' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		require_once ERUDA_PATH . 'modules/badge/class-spin-controls.php';

		$controls = new Spin_Controls();

		add_action( 'elementor/element/after_section_end', array( $controls, 'inject' ), 10, 3 );

		// Loaded only for a widget that is actually turning.
		add_action( 'elementor/frontend/before_render', array( $this, 'maybe_enqueue_spin' ) );

		// The editor previews every widget, so it gets both unconditionally.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_spin' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_spin' ) );
	}

	/**
	 * Load the spin assets if this widget is one that turns.
	 *
	 * @param \Elementor\Element_Base $element Element about to render.
	 */
	public function maybe_enqueue_spin( $element ) {
		if ( $this->spun ) {
			return;
		}

		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! Spin_Controls::is_supported( $element->get_name() ) ) {
			return;
		}

		if ( 'yes' !== $element->get_settings_for_display( 'espin_on' ) ) {
			return;
		}

		$this->enqueue_spin();
	}

	/**
	 * Enqueue the spin extension's stylesheet and script.
	 */
	public function enqueue_spin() {
		$this->spun = true;

		wp_enqueue_style( self::SPIN_STYLE_HANDLE );
		wp_enqueue_script( self::SPIN_SCRIPT_HANDLE );
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style(
			self::SPIN_STYLE_HANDLE,
			ERUDA_URL . 'modules/badge/assets/css/spin.css',
			array(),
			ERUDA_VERSION
		);
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script(
			self::SPIN_SCRIPT_HANDLE,
			ERUDA_URL . 'modules/badge/assets/js/spin.js',
			array(),
			ERUDA_VERSION,
			true
		);
	}

}
