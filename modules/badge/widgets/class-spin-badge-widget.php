<?php
/**
 * Spin Badge widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Badge\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A round link whose text turns around its edge and slows to a stop under the
 * pointer.
 */
class Spin_Badge_Widget extends Widget_Base {

	/**
	 * The circle the ring text is set on, in viewBox units.
	 *
	 * The viewBox is 200 square whatever size the badge is drawn at, so every
	 * measurement in it is effectively a percentage of the badge and scales
	 * with it for free.
	 */
	const BOX = 200;

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-spin-badge';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Spin Badge', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-animation';
	}

	/**
	 * Panel categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \ErudaToolkit\Panel_Category::SLUG );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'badge', 'spin', 'rotate', 'circular', 'text', 'sticker', 'button', 'cta' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Badge\Badge_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Badge\Badge_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_shape_controls();
		$this->register_text_controls();
		$this->register_icon_controls();
		$this->register_motion_controls();
	}

	/**
	 * What it says and where it goes.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'Badge', 'numbered-accordion' ) )
		);

		$this->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text around the edge', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Calculate Savings', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'separator_text',
			array(
				'label'       => esc_html__( 'Between each one', 'numbered-accordion' ),
				'description' => esc_html__( 'The mark that sits between one reading and the next. Leave it empty for a plain gap.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '•',
			)
		);

		$this->add_control(
			'repeat',
			array(
				'label'       => esc_html__( 'How many times round', 'numbered-accordion' ),
				'description' => esc_html__( 'However many you choose, the text is stretched to meet itself exactly once around the circle, so it never laps or falls short.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 1, 'max' => 6, 'step' => 1 ) ),
				'default'     => array( 'size' => 2 ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'aria',
			array(
				'label'       => esc_html__( 'Spoken label', 'numbered-accordion' ),
				'description' => esc_html__( 'What a screen reader announces. The ring text is repeated and stretched, so it is hidden from them and this is read instead. Left empty, the text above is used once.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The disc itself.
	 */
	private function register_shape_controls() {
		$this->start_controls_section(
			'section_shape',
			array(
				'label' => esc_html__( 'Shape', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'size',
			array(
				'label'      => esc_html__( 'Size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 80, 'max' => 520 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 220 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-size: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'bg_image',
			array(
				'label'       => esc_html__( 'Background picture', 'numbered-accordion' ),
				'description' => esc_html__( 'Optional, and it sits under the fill rather than instead of it. Turn the fill\'s strength down to let it through.', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'bg_position',
			array(
				'label'     => esc_html__( 'Which part of it shows', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '50% 50%',
				'options'   => array(
					'50% 0%'   => esc_html__( 'Top', 'numbered-accordion' ),
					'50% 25%'  => esc_html__( 'Upper middle', 'numbered-accordion' ),
					'50% 50%'  => esc_html__( 'Centre', 'numbered-accordion' ),
					'50% 75%'  => esc_html__( 'Lower middle', 'numbered-accordion' ),
					'50% 100%' => esc_html__( 'Bottom', 'numbered-accordion' ),
					'0% 50%'   => esc_html__( 'Left', 'numbered-accordion' ),
					'100% 50%' => esc_html__( 'Right', 'numbered-accordion' ),
				),
				'selectors' => array( '{{WRAPPER}} .ebdg' => '--ebdg-image-pos: {{VALUE}};' ),
				'condition' => array( 'bg_image[url]!' => '' ),
			)
		);

		$this->add_control(
			'overlay',
			array(
				'label'       => esc_html__( 'Fill strength', 'numbered-accordion' ),
				'description' => esc_html__( 'How strongly the colour below covers the picture. It is a layer of its own, so turning it down lets the photograph through without taking the ring text with it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 100 ),
				'selectors'   => array( '{{WRAPPER}} .ebdg' => '--ebdg-overlay: calc({{SIZE}} / 100);' ),
			)
		);

		$this->add_control(
			'fill_type',
			array(
				'label'   => esc_html__( 'Fill', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'gradient',
				'options' => array(
					'gradient' => esc_html__( 'Gradient', 'numbered-accordion' ),
					'solid'    => esc_html__( 'One colour', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'fill_a',
			array(
				'label'   => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#3F8A5C',
			)
		);

		$this->add_control(
			'fill_b',
			array(
				'label'     => esc_html__( 'Fading to', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1F5236',
				'condition' => array( 'fill_type' => 'gradient' ),
			)
		);

		$this->add_control(
			'fill_shape',
			array(
				'label'     => esc_html__( 'Gradient shape', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'radial',
				'options'   => array(
					'radial' => esc_html__( 'From a point inside it', 'numbered-accordion' ),
					'linear' => esc_html__( 'Across it', 'numbered-accordion' ),
				),
				'condition' => array( 'fill_type' => 'gradient' ),
			)
		);

		$this->add_control(
			'fill_angle',
			array(
				'label'      => esc_html__( 'Angle', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'range'      => array( 'deg' => array( 'min' => 0, 'max' => 360 ) ),
				'default'    => array( 'unit' => 'deg', 'size' => 160 ),
				'condition'  => array( 'fill_type' => 'gradient', 'fill_shape' => 'linear' ),
			)
		);

		$this->add_control(
			'ring_colour',
			array(
				'label'     => esc_html__( 'Outer ring', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .ebdg' => '--ebdg-ring: {{VALUE}};' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'ring_width',
			array(
				'label'      => esc_html__( 'Ring width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-ring-w: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'shadow_strength',
			array(
				'label'      => esc_html__( 'Shadow', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.02 ) ),
				'default'    => array( 'size' => 0.28 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-shadow: {{SIZE}};' ),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'shadow_blur',
			array(
				'label'      => esc_html__( 'Shadow softness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-shadow-blur: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'inner_heading',
			array(
				'label'       => esc_html__( 'Inner shadow', 'numbered-accordion' ),
				'description' => esc_html__( 'Cast inside the disc rather than under it, which is what gives the edge a lip and makes the badge read as a pressed object instead of a flat circle.', 'numbered-accordion' ),
				'type'        => Controls_Manager::HEADING,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'inner_colour',
			array(
				'label'   => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'    => Controls_Manager::COLOR,
				'default' => 'rgba(0,0,0,0.15)',
			)
		);

		$this->add_control(
			'inner_x',
			array(
				'label'      => esc_html__( 'Across', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -40, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 2 ),
			)
		);

		$this->add_control(
			'inner_y',
			array(
				'label'      => esc_html__( 'Down', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -40, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => -4 ),
			)
		);

		$this->add_control(
			'inner_blur',
			array(
				'label'      => esc_html__( 'Blur', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
			)
		);

		$this->add_control(
			'inner_spread',
			array(
				'label'      => esc_html__( 'Spread', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -40, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The ring of words.
	 */
	private function register_text_controls() {
		$this->start_controls_section(
			'section_text',
			array(
				'label' => esc_html__( 'Ring text', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#EAF4EE',
				'selectors' => array( '{{WRAPPER}} .ebdg' => '--ebdg-text: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_size',
			array(
				'label'       => esc_html__( 'Size', 'numbered-accordion' ),
				'description' => esc_html__( 'A share of the badge, not a fixed number of pixels, so it keeps its proportions at every size the badge is used at.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 2, 'max' => 14, 'step' => 0.25 ) ),
				'default'     => array( 'unit' => '%', 'size' => 7.5 ),
			)
		);

		$this->add_control(
			'tracking',
			array(
				'label'      => esc_html__( 'Letter spacing', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'em' ),
				'range'      => array( 'em' => array( 'min' => 0, 'max' => 0.6, 'step' => 0.01 ) ),
				'default'    => array( 'unit' => 'em', 'size' => 0.22 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-tracking: {{SIZE}}em;' ),
			)
		);

		$this->add_control(
			'text_radius',
			array(
				'label'       => esc_html__( 'Distance from the middle', 'numbered-accordion' ),
				'description' => esc_html__( 'How far out the circle of text sits. A hundred is the very edge of the disc.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 40, 'max' => 95 ) ),
				'default'     => array( 'unit' => '%', 'size' => 78 ),
			)
		);

		$this->add_control(
			'text_start',
			array(
				'label'       => esc_html__( 'Starts at', 'numbered-accordion' ),
				'description' => esc_html__( 'Where on the circle the first letter sits. Nought is the top.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 0 ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'text_typography',
				'selector'  => '{{WRAPPER}} .ebdg__ring text',
				// Size, spacing and case have their own controls above, in the
				// units this actually needs.
				'exclude'   => array( 'font_size', 'letter_spacing', 'text_transform', 'line_height' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * What sits in the middle.
	 */
	private function register_icon_controls() {
		$this->start_controls_section(
			'section_icon',
			array(
				'label' => esc_html__( 'Middle', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'       => esc_html__( 'Icon', 'numbered-accordion' ),
				'description' => esc_html__( 'Left empty, a drawn arrow is used, which stays sharp at any size.', 'numbered-accordion' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array(),
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 200 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 64 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-icon: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'icon_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#EAF4EE',
				'selectors' => array( '{{WRAPPER}} .ebdg' => '--ebdg-icon-colour: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'icon_weight',
			array(
				'label'      => esc_html__( 'Line weight', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0.5, 'max' => 4, 'step' => 0.1 ) ),
				'default'    => array( 'size' => 1.5 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-icon-weight: {{SIZE}};' ),
				'condition'  => array( 'icon[value]' => '' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Turning, slowing and lifting.
	 */
	private function register_motion_controls() {
		$this->start_controls_section(
			'section_motion',
			array(
				'label' => esc_html__( 'Motion', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'       => esc_html__( 'One turn takes', 'numbered-accordion' ),
				'description' => esc_html__( 'Slower than feels right while you are watching it. Nobody is watching it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 's' ),
				'range'       => array( 's' => array( 'min' => 3, 'max' => 60 ) ),
				'default'     => array( 'unit' => 's', 'size' => 18 ),
				'selectors'   => array( '{{WRAPPER}} .ebdg' => '--ebdg-speed: {{SIZE}}s;' ),
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => esc_html__( 'Direction', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'forward',
				'options' => array(
					'forward' => esc_html__( 'Clockwise', 'numbered-accordion' ),
					'reverse' => esc_html__( 'Anticlockwise', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'ramp',
			array(
				'label'       => esc_html__( 'Takes this long to stop', 'numbered-accordion' ),
				'description' => esc_html__( 'Under the pointer the badge slows evenly to a halt rather than stopping on the spot, which is the difference between a wheel stopping and a wheel jamming.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 2500, 'step' => 50 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 700 ),
			)
		);

		$this->add_control(
			'lift',
			array(
				'label'       => esc_html__( 'Lift on hover', 'numbered-accordion' ),
				'description' => esc_html__( 'How far it rises, which along with the stopping is what says it can be clicked.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'   => array( '{{WRAPPER}} .ebdg' => '--ebdg-lift: {{SIZE}}px;' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'lift_ms',
			array(
				'label'      => esc_html__( 'Lift length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 80, 'max' => 1200, 'step' => 20 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 520 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-lift-ms: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'icon_grow',
			array(
				'label'      => esc_html__( 'The middle grows to', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 1.4, 'step' => 0.01 ) ),
				'default'    => array( 'size' => 1.08 ),
				'selectors'  => array( '{{WRAPPER}} .ebdg' => '--ebdg-icon-grow: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Read a slider's size, with a fallback and a clamp.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Control id.
	 * @param float  $fallback Value when unset.
	 * @param float  $min      Lowest allowed.
	 * @param float  $max      Highest allowed.
	 * @return float
	 */
	private function slider( $settings, $key, $fallback, $min, $max ) {
		$size = isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size']
			? (float) $settings[ $key ]['size']
			: $fallback;

		return max( $min, min( $size, $max ) );
	}

	/**
	 * The circle the text is set on.
	 *
	 * Two half-circle arcs rather than a <circle>, because only a path can
	 * carry a textPath. It starts at the top and runs clockwise.
	 *
	 * @param float $radius Radius in viewBox units.
	 * @return string
	 */
	private function circle( $radius ) {
		$centre = self::BOX / 2;

		return sprintf(
			'M %1$s,%2$s a %3$s,%3$s 0 1,1 0,%4$s a %3$s,%3$s 0 1,1 0,-%4$s',
			$centre,
			$centre - $radius,
			$radius,
			$radius * 2
		);
	}

	/**
	 * The arrow used when no icon is chosen.
	 *
	 * Drawn rather than fetched: one shape, always sharp, no request.
	 *
	 * @return string
	 */
	private function arrow() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 5 6 18"/><path d="M6 9v9h9"/></svg>';
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$text     = isset( $settings['text'] ) ? trim( (string) $settings['text'] ) : '';

		if ( '' === $text ) {
			return;
		}

		$mark   = isset( $settings['separator_text'] ) ? (string) $settings['separator_text'] : '';
		$repeat = (int) $this->slider( $settings, 'repeat', 2, 1, 6 );

		// A trailing separator on every reading is what keeps the join between
		// the last and the first looking like all the others.
		$unit = $text . ( '' === $mark ? ' ' : ' ' . $mark . ' ' );
		$ring = str_repeat( $unit, $repeat );

		$radius = $this->slider( $settings, 'text_radius', 78, 40, 95 ) / 100 * ( self::BOX / 2 );
		$start  = $this->slider( $settings, 'text_start', 0, 0, 100 );
		$size   = $this->slider( $settings, 'text_size', 7.5, 2, 14 ) / 100 * self::BOX;

		$speed   = $this->slider( $settings, 'speed', 18, 3, 60 );
		$ramp    = $this->slider( $settings, 'ramp', 700, 0, 2500 );
		$reverse = isset( $settings['direction'] ) && 'reverse' === $settings['direction'];

		$fill  = $this->fill( $settings );
		$inner = $this->inner( $settings );
		$image = isset( $settings['bg_image']['url'] ) && '' !== $settings['bg_image']['url']
			? sprintf( "url('%s')", esc_url( $settings['bg_image']['url'] ) )
			: 'none';

		$url      = isset( $settings['link']['url'] ) ? $settings['link']['url'] : '';
		$element  = '' !== $url ? 'a' : 'span';
		$external = ! empty( $settings['link']['is_external'] );
		$nofollow = ! empty( $settings['link']['nofollow'] );

		$spoken = isset( $settings['aria'] ) && '' !== trim( (string) $settings['aria'] )
			? trim( (string) $settings['aria'] )
			: $text;

		// Two badges on one page must not share a path id, or the second one's
		// text is set on the first one's circle.
		$path_id = 'ebdg-path-' . $this->get_id();
		?>
		<<?php echo esc_html( $element ); ?>
			class="ebdg"
			data-ebdg-speed="<?php echo esc_attr( (string) $speed ); ?>"
			data-ebdg-ramp="<?php echo esc_attr( (string) $ramp ); ?>"
			<?php echo $reverse ? ' data-ebdg-reverse' : ''; ?>
			style="--ebdg-bg: <?php echo esc_attr( $fill ); ?>; --ebdg-image: <?php echo esc_attr( $image ); ?>; --ebdg-inner: <?php echo esc_attr( $inner ); ?>; --ebdg-text-size: <?php echo esc_attr( (string) round( $size, 2 ) ); ?>px;"
			<?php if ( '' !== $url ) : ?>
				href="<?php echo esc_url( $url ); ?>"
				aria-label="<?php echo esc_attr( $spoken ); ?>"
				<?php echo $external ? ' target="_blank"' : ''; ?>
				<?php echo $external || $nofollow ? ' rel="' . esc_attr( trim( ( $nofollow ? 'nofollow ' : '' ) . ( $external ? 'noopener noreferrer' : '' ) ) ) . '"' : ''; ?>
			<?php else : ?>
				role="img"
				aria-label="<?php echo esc_attr( $spoken ); ?>"
			<?php endif; ?>
		>
			<span class="ebdg__disc">
				<?php
				/*
				 * aria-hidden because the ring says the same thing several
				 * times over and is stretched letter by letter to fit the
				 * circle. The link's own label is what gets read.
				 */
				?>
				<svg
					class="ebdg__ring"
					viewBox="0 0 <?php echo esc_attr( (string) self::BOX ); ?> <?php echo esc_attr( (string) self::BOX ); ?>"
					aria-hidden="true"
					focusable="false"
				>
					<defs>
						<path id="<?php echo esc_attr( $path_id ); ?>" fill="none" d="<?php echo esc_attr( $this->circle( $radius ) ); ?>" />
					</defs>
					<text>
						<textPath
							href="#<?php echo esc_attr( $path_id ); ?>"
							xlink:href="#<?php echo esc_attr( $path_id ); ?>"
							startOffset="<?php echo esc_attr( (string) $start ); ?>%"
						><?php echo esc_html( $ring ); ?></textPath>
					</text>
				</svg>

				<span class="ebdg__centre">
					<?php if ( ! empty( $settings['icon']['value'] ) ) : ?>
						<span class="ebdg__icon-lib">
							<?php Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
						</span>
					<?php else : ?>
						<?php echo $this->arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a fixed inline SVG from arrow(), not user input. ?>
					<?php endif; ?>
				</span>
			</span>
		</<?php echo esc_html( $element ); ?>>
		<?php
	}

	/**
	 * Build the inner shadow from its four numbers and a colour.
	 *
	 * Composed here rather than left to Elementor's box-shadow group, because
	 * that writes the whole `box-shadow` property and this disc already has
	 * two shadows on it -- the ring and the one it casts. Overwriting the list
	 * would take both with it, so the inner one is a value the stylesheet
	 * slots into the front of its own list.
	 *
	 * @param array $settings Widget settings.
	 * @return string A CSS shadow, or a transparent one that costs nothing.
	 */
	private function inner( $settings ) {
		$colour = isset( $settings['inner_colour'] ) ? trim( (string) $settings['inner_colour'] ) : '';

		if ( '' === $colour ) {
			return 'inset 0 0 0 0 transparent';
		}

		return sprintf(
			'inset %spx %spx %spx %spx %s',
			$this->slider( $settings, 'inner_x', 2, -40, 40 ),
			$this->slider( $settings, 'inner_y', -4, -40, 40 ),
			$this->slider( $settings, 'inner_blur', 4, 0, 80 ),
			$this->slider( $settings, 'inner_spread', 0, -40, 40 ),
			$colour
		);
	}

	/**
	 * Build the disc's background from the fill controls.
	 *
	 * Assembled here rather than left to Elementor's background group, because
	 * that writes `background-image` and `background-color` separately and
	 * this stylesheet sets the `background` shorthand: whichever landed last
	 * would win, which is not a thing to leave to chance.
	 *
	 * @param array $settings Widget settings.
	 * @return string A CSS colour or gradient.
	 */
	private function fill( $settings ) {
		$a = isset( $settings['fill_a'] ) && '' !== $settings['fill_a'] ? $settings['fill_a'] : '#3F8A5C';

		if ( ! isset( $settings['fill_type'] ) || 'gradient' !== $settings['fill_type'] ) {
			return $a;
		}

		$b = isset( $settings['fill_b'] ) && '' !== $settings['fill_b'] ? $settings['fill_b'] : '#1F5236';

		if ( isset( $settings['fill_shape'] ) && 'linear' === $settings['fill_shape'] ) {
			$angle = $this->slider( $settings, 'fill_angle', 160, 0, 360 );

			return sprintf( 'linear-gradient(%sdeg, %s 0%%, %s 100%%)', $angle, $a, $b );
		}

		return sprintf( 'radial-gradient(circle at 50%% 35%%, %s 0%%, %s 100%%)', $a, $b );
	}
}
