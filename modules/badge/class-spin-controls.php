<?php
/**
 * The injected spin control section.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Badge;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "Eruda Spin" section to widgets that hold a single thing worth
 * turning -- a picture, an icon, a button.
 *
 * It writes no markup. Everything reaches the DOM through Elementor's own
 * prefix_class and selectors, which Elementor applies live in the editor as
 * well as on the front end; a render-time attribute would never appear on a
 * widget with a content_template(), and the native Image has one.
 *
 * The hook is the generic per-section one, for the same reason the motion
 * module uses it: elementor/element/common/... never fires per widget, because
 * Elementor registers the common controls once on a shared stack whose name is
 * 'common' and merges them in afterwards. See Motion_Controls for the full
 * account of that.
 */
final class Spin_Controls {

	const SECTION_ID = 'espin_section';

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
	 * Things that are one object on the page, where "turn it" means something
	 * obvious. A widget holding a heading, a paragraph and a button raises a
	 * question this has no good answer to: which of the three is spinning.
	 *
	 * Everything downstream is widget-agnostic, so a site that wants the
	 * controls elsewhere adds the widget name here and nothing else changes.
	 *
	 * @return string[]
	 */
	public static function supported_widgets() {
		$widgets = apply_filters(
			'eruda_spin_supported_widgets',
			array( 'image', 'theme-site-logo', 'icon', 'button', 'image-box' )
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
		$on = array( 'espin_on' => 'yes' );

		return array(
			'espin_on'        => array(
				'label'        => esc_html__( 'Turn it', 'numbered-accordion' ),
				'description'  => esc_html__( 'Rotates whatever this widget holds, forever, and slows it to a stop when a pointer is over it.', 'numbered-accordion' ),
				'type'         => 'switcher',
				'default'      => '',
				'return_value' => 'yes',
				'prefix_class' => 'espin-',
				'render_type'  => 'template',
			),
			'espin_target'    => array(
				'label'        => esc_html__( 'What turns', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'image',
				'options'      => array(
					'image' => esc_html__( 'The picture inside it', 'numbered-accordion' ),
					'self'  => esc_html__( 'The whole widget', 'numbered-accordion' ),
				),
				'prefix_class' => 'espin-target-',
				'render_type'  => 'template',
				'condition'    => $on,
			),
			'espin_speed'     => array(
				'label'       => esc_html__( 'One turn takes', 'numbered-accordion' ),
				'description' => esc_html__( 'Slower than feels right while you are watching it. Nobody is watching it.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( 's' ),
				'range'       => array(
					's' => array(
						'min'  => 2,
						'max'  => 90,
						'step' => 0.5,
					),
				),
				'default'     => array(
					'unit' => 's',
					'size' => 18,
				),
				'selectors'   => array( '{{WRAPPER}}' => '--espin-speed: {{SIZE}}s;' ),
				'condition'   => $on,
			),
			'espin_direction' => array(
				'label'        => esc_html__( 'Direction', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'forward',
				'options'      => array(
					'forward' => esc_html__( 'Clockwise', 'numbered-accordion' ),
					'reverse' => esc_html__( 'Anticlockwise', 'numbered-accordion' ),
				),
				'prefix_class' => 'espin-dir-',
				'render_type'  => 'template',
				'condition'    => $on,
			),
			'espin_hover'     => array(
				'label'        => esc_html__( 'Under the pointer', 'numbered-accordion' ),
				'type'         => 'select',
				'default'      => 'stop',
				'options'      => array(
					'stop' => esc_html__( 'Slow to a stop', 'numbered-accordion' ),
					'keep' => esc_html__( 'Keep turning', 'numbered-accordion' ),
				),
				'prefix_class' => 'espin-hover-',
				'render_type'  => 'template',
				'condition'    => $on,
			),
			'espin_ramp'      => array(
				'label'       => esc_html__( 'Takes this long to stop', 'numbered-accordion' ),
				'description' => esc_html__( 'It slows evenly to a halt rather than stopping on the spot, which is the difference between a wheel stopping and a wheel jamming.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( 'ms' ),
				'range'       => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 2500,
						'step' => 50,
					),
				),
				'default'     => array(
					'unit' => 'ms',
					'size' => 700,
				),
				'selectors'   => array( '{{WRAPPER}}' => '--espin-ramp: {{SIZE}};' ),
				'condition'   => array(
					'espin_on'    => 'yes',
					'espin_hover' => 'stop',
				),
			),
			'espin_lift'      => array(
				'label'       => esc_html__( 'Lift on hover', 'numbered-accordion' ),
				'description' => esc_html__( 'How far it rises, which along with the slowing is what says it can be clicked.', 'numbered-accordion' ),
				'type'        => 'slider',
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'   => array( '{{WRAPPER}}' => '--espin-lift: {{SIZE}}px;' ),
				'condition'   => $on,
				'separator'   => 'before',
			),
			'espin_lift_ms'   => array(
				'label'      => esc_html__( 'Lift length', 'numbered-accordion' ),
				'type'       => 'slider',
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 80,
						'max'  => 1200,
						'step' => 20,
					),
				),
				'default'    => array(
					'unit' => 'ms',
					'size' => 520,
				),
				'selectors'  => array( '{{WRAPPER}}' => '--espin-lift-ms: {{SIZE}}ms;' ),
				'condition'  => $on,
			),
		);
	}

	/**
	 * Swap the pure tokens for Elementor's own constants.
	 *
	 * @param string $token Token from control_definitions().
	 * @return string
	 */
	private static function control_type( $token ) {
		switch ( $token ) {
			case 'switcher':
				return Controls_Manager::SWITCHER;
			case 'select':
				return Controls_Manager::SELECT;
			case 'slider':
				return Controls_Manager::SLIDER;
			default:
				return Controls_Manager::TEXT;
		}
	}

	/**
	 * Add the section, once, to a widget that should have it.
	 *
	 * @param \Elementor\Controls_Stack $element   The element being built.
	 * @param string                    $section   Section that just closed.
	 * @param array                     $arguments Section arguments.
	 */
	public function inject( $element, $section, $arguments ) {
		unset( $section, $arguments );

		if ( ! $element instanceof \Elementor\Widget_Base ) {
			return;
		}

		if ( ! self::is_supported( $element->get_name() ) ) {
			return;
		}

		$key = spl_object_id( $element );

		if ( isset( self::$injected[ $key ] ) ) {
			return;
		}

		self::$injected[ $key ] = true;

		$element->start_controls_section(
			self::SECTION_ID,
			array(
				'label' => esc_html__( 'Eruda Spin', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		foreach ( self::control_definitions() as $id => $definition ) {
			$definition['type'] = self::control_type( $definition['type'] );
			$element->add_control( $id, $definition );
		}

		$element->end_controls_section();
	}
}
