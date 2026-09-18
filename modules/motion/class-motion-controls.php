<?php
/**
 * The injected control section.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "Eruda Text Animation" section to the Style tab of the widgets whose
 * content is a single block of plain text.
 *
 * The module writes no markup and no inline styles. Every value reaches the
 * DOM through Elementor's own prefix_class and selectors, which Elementor
 * applies live in the editor as well as on the front end -- a render-time
 * attribute would never appear on a widget with a content_template(), and the
 * native Heading has one.
 *
 * A note on the hook, because the obvious one is wrong.
 *
 * 2.3.0 hooked elementor/element/common/_section_style/after_section_end and
 * filtered it by widget name. That hook never fires per widget: Elementor
 * registers the common controls once, on a shared Widget_Common stack whose
 * get_name() is 'common', and merges them into every widget afterwards. The
 * guard rejected every call and the section was never added to anything.
 *
 * So this hooks the generic per-section hook instead, which does fire on the
 * real widget, and adds the section the first time it sees one the list
 * allows. The first section end is used as the anchor because it is the only
 * one every widget is guaranteed to have -- the tab the section lands on is
 * declared, not inherited from the anchor.
 */
final class Motion_Controls {

	const SECTION_ID = 'eanm_section';

	/**
	 * Elements already given the section this request, by object id.
	 *
	 * Controls are registered once per widget type, but the generic hook fires
	 * for every section that widget closes, so without this the section would
	 * be added several times over and Elementor would die on the duplicate.
	 *
	 * @var array<int, bool>
	 */
	private static $injected = array();

	/**
	 * Which widgets get the section.
	 *
	 * Heading and Text Editor hold one block of text and nothing else. A
	 * composite widget -- an Icon Box, a Price Table -- raises a question this
	 * feature has no good answer to: whether its title and its description are
	 * one staggered run or two.
	 *
	 * Everything downstream is widget-agnostic, so a site that wants the
	 * controls elsewhere adds the widget name here and nothing else changes.
	 *
	 * @return string[]
	 */
	public static function supported_widgets() {
		$widgets = apply_filters(
			'eruda_motion_supported_widgets',
			array( 'heading', 'text-editor' )
		);

		return is_array( $widgets ) ? $widgets : array();
	}

	/**
	 * Is this widget one of them?
	 *
	 * @param mixed $name Widget name from Widget_Base::get_name().
	 * @return bool
	 */
	public static function is_supported( $name ) {
		if ( ! is_string( $name ) || '' === $name ) {
			return false;
		}

		return in_array( $name, self::supported_widgets(), true );
	}

	/**
	 * Every control in the section, in order.
	 *
	 * 'type' holds a token rather than a Controls_Manager constant so this
	 * method stays pure: the test suite asserts the whole section without
	 * Elementor loaded. inject() swaps the tokens for constants.
	 *
	 * @return array<string, array>
	 */
	public static function control_definitions() {
		$active = array( 'eanm_preset!' => 'none' );

		$on_scroll = array(
			'eanm_preset!' => 'none',
			'eanm_trigger' => 'scroll',
		);

		return array(
			'eanm_preset'    => array(
				'label'        => esc_html__( 'Animation', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'none',
				'options'      => Motion_Presets::options(),
				'prefix_class' => 'eanm-preset-',
				// A changed preset means a different split, so the widget has
				// to be re-rendered for the script to run again.
				'render_type'  => 'template',
			),
			'eanm_trigger'   => array(
				'label'        => esc_html__( 'Starts', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'scroll',
				'options'      => array(
					'scroll' => esc_html__( 'When scrolled into view', 'numbered-accordion' ),
					'load'   => esc_html__( 'On page load', 'numbered-accordion' ),
				),
				'prefix_class' => 'eanm-trigger-',
				'render_type'  => 'template',
				'condition'    => $active,
			),
			'eanm_replay_preview' => array(
				'label'       => esc_html__( 'Preview', 'numbered-accordion' ),
				'type'        => 'button',
				'button_type' => 'default',
				'text'        => esc_html__( 'Replay animation', 'numbered-accordion' ),
				'event'       => 'eanm:replay',
				'description' => esc_html__( 'Plays it again here in the editor. Does nothing on the live page.', 'numbered-accordion' ),
				'condition'   => $active,
			),
			'eanm_direction' => array(
				'label'        => esc_html__( 'Comes from', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'up',
				'options'      => Motion_Presets::directions(),
				'prefix_class' => 'eanm-dir-',
				'condition'    => array( 'eanm_preset' => Motion_Presets::directional() ),
			),
			'eanm_distance'  => array(
				'label'      => esc_html__( 'Travel', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 200,
						'step' => 2,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--eanm-distance: {{SIZE}}px;' ),
				'condition'  => array(
					'eanm_preset'    => Motion_Presets::directional(),
					'eanm_direction!' => 'none',
				),
			),
			'eanm_duration'  => array(
				'label'      => esc_html__( 'Duration', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 100,
						'max'  => 3000,
						'step' => 50,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 800,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--eanm-duration: {{SIZE}}ms;' ),
				'condition'  => $active,
			),
			'eanm_stagger'   => array(
				'label'       => esc_html__( 'Stagger', 'numbered-accordion' ),
				'description' => esc_html__( 'The gap between one word or letter starting and the next.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( 'ms' ),
				'range'       => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 300,
						'step' => 5,
					),
				),
				'default'     => array(
					'unit' => 'ms',
					'size' => 60,
				),
				'selectors'   => array( '{{WRAPPER}}' => '--eanm-stagger: {{SIZE}}ms;' ),
				'condition'   => $active,
			),
			'eanm_delay'     => array(
				'label'      => esc_html__( 'Delay', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 3000,
						'step' => 50,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 0,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--eanm-delay: {{SIZE}}ms;' ),
				'condition'  => $active,
			),
			'eanm_ease'      => array(
				'label'        => esc_html__( 'Easing', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'out-expo',
				'options'      => Motion_Presets::easings(),
				'prefix_class' => 'eanm-ease-',
				'condition'    => $active,
			),
			'eanm_threshold' => array(
				'label'       => esc_html__( 'Starts at', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the text has to be on screen before it animates.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 20,
				),
				// Read back by the script, not used by the stylesheet. A custom
				// property is how the other modules pass numbers to their JS.
				'selectors'   => array( '{{WRAPPER}}' => '--eanm-threshold: {{SIZE}};' ),
				'condition'   => $on_scroll,
			),
			'eanm_replay'    => array(
				'label'        => esc_html__( 'Replay every time', 'numbered-accordion' ),
				'description'  => esc_html__( 'Animate again whenever the text scrolls back into view.', 'numbered-accordion' ),
				'type'         => 'switcher',
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'eanm-replay-',
				'condition'    => $on_scroll,
			),
		);
	}

	/**
	 * Should this element get the section now?
	 *
	 * Pure, so the guard that 2.3.0 got wrong is covered by the test suite.
	 *
	 * @param mixed $name Widget name, or whatever get_name() returned.
	 * @param bool  $done Has this element already been given the section?
	 * @return bool
	 */
	public static function should_inject( $name, $done ) {
		if ( $done ) {
			return false;
		}

		return self::is_supported( $name );
	}

	/**
	 * Register the section on a widget.
	 *
	 * Hooked to elementor/element/after_section_end, which fires on the real
	 * widget for every section it closes. See the note on this class for why
	 * the common hook cannot be used here.
	 *
	 * @param mixed  $element    Element being built.
	 * @param string $section_id Section that just ended.
	 * @param array  $args       Section arguments.
	 */
	public function inject( $element, $section_id = '', $args = array() ) {
		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		$key = spl_object_id( $element );

		if ( ! self::should_inject( $element->get_name(), isset( self::$injected[ $key ] ) ) ) {
			return;
		}

		self::$injected[ $key ] = true;

		$element->start_controls_section(
			self::SECTION_ID,
			array(
				'label' => esc_html__( 'Eruda Text Animation', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		foreach ( self::control_definitions() as $id => $definition ) {
			$definition['type'] = self::control_type( $definition['type'] );
			$element->add_control( $id, $definition );
		}

		$element->end_controls_section();
	}

	/**
	 * Token to Elementor control constant.
	 *
	 * @param string $token One of select|slider|switcher.
	 * @return string
	 */
	private static function control_type( $token ) {
		switch ( $token ) {
			case 'select':
				return Controls_Manager::SELECT;
			case 'slider':
				return Controls_Manager::SLIDER;
			case 'switcher':
				return Controls_Manager::SWITCHER;
			case 'button':
				return Controls_Manager::BUTTON;
		}

		return Controls_Manager::TEXT;
	}
}
