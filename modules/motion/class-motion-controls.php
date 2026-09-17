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
 * Adds an "Eruda Text Animation" section to the Advanced tab of the widgets
 * whose content is a single block of plain text.
 *
 * The module writes no markup and no inline styles. Every value reaches the
 * DOM through Elementor's own prefix_class and selectors, which Elementor
 * applies live in the editor as well as on the front end -- a render-time
 * attribute would never appear on a widget with a content_template(), and the
 * native Heading has one.
 */
final class Motion_Controls {

	const SECTION_ID = 'eanm_section';

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
	 * Register the section on a widget.
	 *
	 * Hooked to elementor/element/common/_section_style/after_section_end,
	 * which fires for every widget, so the guard does the selecting.
	 *
	 * @param mixed $element Element being built.
	 * @param array $args    Section arguments.
	 */
	public function inject( $element, $args ) {
		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! self::is_supported( $element->get_name() ) ) {
			return;
		}

		$element->start_controls_section(
			self::SECTION_ID,
			array(
				'label' => esc_html__( 'Eruda Text Animation', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
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
		}

		return Controls_Manager::TEXT;
	}
}
