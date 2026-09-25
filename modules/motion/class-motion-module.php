<?php
/**
 * Text animation module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the animation controls and loads the assets that act on them.
 */
final class Motion_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eanm-text-animation';
	const SCRIPT_HANDLE = 'eanm-text-animation';
	const EDITOR_HANDLE = 'eanm-text-animation-editor';

	/**
	 * Have the assets been enqueued for this request?
	 *
	 * @var bool
	 */
	private $enqueued = false;

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'motion';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Text Animations', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Eruda Text Animation" section to the Advanced tab of the Heading and Text Editor widgets: words, letters or lines that animate as they scroll into view.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/motion/class-motion-presets.php';
		require_once ERUDA_PATH . 'modules/motion/class-motion-controls.php';

		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );

		$controls = new Motion_Controls();

		// The generic per-section hook, not the common one: common controls
		// are registered once on a shared stack and merged into every widget,
		// so a widget-name guard there never matches. See Motion_Controls.
		add_action( 'elementor/element/after_section_end', array( $controls, 'inject' ), 10, 3 );

		// Front end: load nothing until a widget actually asks for it.
		add_action( 'elementor/frontend/before_render', array( $this, 'maybe_enqueue' ) );

		// Editor preview: always load, so a preset animates the moment it is
		// picked. Four kilobytes inside the editor is not worth conditioning.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue' ) );

		// Editor panel: the Replay button's other half.
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Register the stylesheet.
	 */
	public function register_styles() {
		wp_register_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'modules/motion/assets/css/text-animation.css',
			array(),
			ERUDA_VERSION
		);
	}

	/**
	 * Register the script.
	 */
	public function register_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			ERUDA_URL . 'modules/motion/assets/js/text-animation.js',
			array(),
			ERUDA_VERSION,
			true
		);
	}

	/**
	 * Load the assets if this element is animated.
	 *
	 * get_style_depends() is not available here: the controls live on widgets
	 * this plugin does not own. A page with no animated widget therefore loads
	 * neither file.
	 *
	 * Enqueuing this late can put the stylesheet after first paint. That costs
	 * nothing, because the pre-animation state is "visible": the CSS only hides
	 * anything under [data-eanm-ready], which the script sets.
	 *
	 * @param mixed $element Element about to render.
	 */
	public function maybe_enqueue( $element ) {
		if ( $this->enqueued ) {
			return;
		}

		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! Motion_Controls::is_supported( $element->get_name() ) ) {
			return;
		}

		$preset = $element->get_settings_for_display( 'eanm_preset' );

		if ( 'none' === $preset || ! Motion_Presets::is_valid( $preset ) ) {
			return;
		}

		$this->enqueue();
	}

	/**
	 * Load the panel-side script behind the Replay button.
	 *
	 * Panel only. It talks to the preview iframe from outside, so it must not
	 * be the same handle as the animation itself.
	 */
	public function enqueue_editor() {
		wp_enqueue_script(
			self::EDITOR_HANDLE,
			ERUDA_URL . 'modules/motion/assets/js/editor.js',
			array( 'jquery' ),
			ERUDA_VERSION,
			true
		);
	}

	/**
	 * Load the assets.
	 */
	public function enqueue() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		$this->enqueued = true;
	}
}
