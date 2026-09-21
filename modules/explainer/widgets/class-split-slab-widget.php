<?php
/**
 * Split Slab widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Explainer\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Explainer\Explainer_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One rounded object split down the middle by a coloured bar: a light panel
 * that states the problem and an ink panel that answers it.
 *
 * Two fixed panels rather than a repeater. The whole point of the shape is
 * that there are exactly two sides to the argument, and a third would break
 * both the grid and the reading.
 */
class Split_Slab_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eexp-split-slab';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Split Slab', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-before-after';
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
		return array( 'slab', 'split', 'panels', 'steps', 'problem', 'solution', 'two', 'compare' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Explainer\Explainer_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Explainer\Explainer_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_panel_one_controls();
		$this->register_panel_two_controls();

		$this->register_slab_style_controls();
		$this->register_spacing_controls();
		$this->register_typography_controls();
		$this->register_picture_controls();
		$this->register_pill_style_controls();
		$this->register_mote_controls();
		$this->register_fill_controls();
		$this->register_panel_one_style_controls();
		$this->register_panel_two_style_controls();
	}

	/**
	 * Step controls, the same shape on both panels.
	 *
	 * @param string $prefix  Control prefix, p1 or p2.
	 * @param string $default Default label.
	 * @param string $number  Default number.
	 */
	private function add_step_controls( $prefix, $default, $number = '' ) {
		$this->add_control(
			$prefix . '_step',
			array(
				'label'       => esc_html__( 'Step number', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $number,
				'placeholder' => esc_html__( 'e.g. 01', 'numbered-accordion' ),
				'description' => esc_html__( 'Empty hides the number and closes the gap before the label.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			$prefix . '_label',
			array(
				'label'       => esc_html__( 'Step label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => $default,
			)
		);
	}

	/**
	 * The light panel.
	 */
	private function register_panel_one_controls() {
		$this->start_controls_section(
			'section_panel_one',
			array( 'label' => esc_html__( 'Panel 1 (light)', 'numbered-accordion' ) )
		);

		$this->add_step_controls( 'p1', esc_html__( 'What the air carries', 'numbered-accordion' ), '01' );

		$this->add_control(
			'p1_title',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Exhaust that fouls everything else', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_title_tag',
			array(
				'label'   => esc_html__( 'Heading tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'p1_body',
			array(
				'label'   => esc_html__( 'Body', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Grease, soot, moisture, lint and fiber arrive with the airstream — the load that closes a finned exchanger down.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_image',
			array(
				'label'       => esc_html__( 'Image', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'This panel stays light, so a cut-out render with dark labels baked into it reads correctly.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_loads_label',
			array(
				'label'       => esc_html__( 'Footer label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Carried in, carried straight back out', 'numbered-accordion' ),
			)
		);

		$pills = new Repeater();

		$pills->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Item', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_pill_style',
			array(
				'label'   => esc_html__( 'Pill style', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dot',
				'options' => array(
					'dot'   => esc_html__( 'Lit dot', 'numbered-accordion' ),
					'solid' => esc_html__( 'Filled', 'numbered-accordion' ),
					'glass' => esc_html__( 'Glass', 'numbered-accordion' ),
					'plain' => esc_html__( 'Plain', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'p1_pills',
			array(
				'label'       => esc_html__( 'Footer pills', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $pills->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => $this->default_pills(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The ink panel.
	 */
	private function register_panel_two_controls() {
		$this->start_controls_section(
			'section_panel_two',
			array( 'label' => esc_html__( 'Panel 2 (ink)', 'numbered-accordion' ) )
		);

		$this->add_step_controls( 'p2', esc_html__( 'What the geometry does', 'numbered-accordion' ), '02' );

		$this->add_control(
			'p2_title',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Particles are repelled, not trapped', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_title_tag',
			array(
				'label'   => esc_html__( 'Heading tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'p2_body',
			array(
				'label'   => esc_html__( 'Body', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Ample spacing between the coils creates an airflow pattern that carries particles straight through the unit instead of onto its surfaces.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_image',
			array(
				'label'       => esc_html__( 'Diagram', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Artwork drawn on black. It is screened onto the plate, so the black falls away and only the drawing remains.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_plate',
			array(
				'label'        => esc_html__( 'Put it on a plate', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'The bordered black panel the diagram is screened onto. Switch it off for artwork that already has a background of its own.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_caption_left',
			array(
				'label'       => esc_html__( 'Caption, left', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Hot contaminated exhaust in', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_caption_right',
			array(
				'label'       => esc_html__( 'Caption, right', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Cooled air out →', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_motes',
			array(
				'label'       => esc_html__( 'Drifting motes', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 7,
				'step'        => 1,
				'default'     => 5,
				'description' => esc_html__( 'Specks that drift across the diagram. Zero switches them off, and they never run for a visitor who asked for reduced motion.', 'numbered-accordion' ),
			)
		);

		$specs = new Repeater();

		$specs->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Label', 'numbered-accordion' ),
			)
		);

		$specs->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Value', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Value', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_specs',
			array(
				'label'       => esc_html__( 'Footer specs', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $specs->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => $this->default_specs(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The slab's own shape.
	 */
	private function register_slab_style_controls() {
		$this->start_controls_section(
			'section_style_slab',
			array(
				'label' => esc_html__( 'Slab', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_colour',
			array(
				'label'     => esc_html__( 'Accent', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-green-light: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bar_colour',
			array(
				'label'     => esc_html__( 'Divider bar', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-green: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_width',
			array(
				'label'      => esc_html__( 'Divider width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 24,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-bar: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'slab_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'slab_split',
			array(
				'label'       => esc_html__( 'Weight of the light panel', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 30,
						'max' => 70,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 56,
				),
				'description' => esc_html__( 'How much of the width the light panel takes. The ink panel takes the rest.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp-slab' => 'grid-template-columns: {{SIZE}}% var(--eexp-bar) 1fr;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The light panel's colours.
	 */
	private function register_panel_one_style_controls() {
		$this->start_controls_section(
			'section_style_panel_one',
			array(
				'label' => esc_html__( 'Panel 1 (light)', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'p1_background',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_title_colour',
			array(
				'label'     => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light .eexp-panel__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_text_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_pill_colour',
			array(
				'label'     => esc_html__( 'Pill text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_pill_border',
			array(
				'label'     => esc_html__( 'Pill border', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The ink panel: a background group over the colour, plus the veil that
	 * keeps the text legible on top of whatever gets uploaded.
	 */
	private function register_panel_two_style_controls() {
		$this->start_controls_section(
			'section_style_panel_two',
			array(
				'label' => esc_html__( 'Panel 2 (ink)', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'p2_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .eexp-panel--ink',
			)
		);

		$this->add_control(
			'p2_dim',
			array(
				'label'       => esc_html__( 'Darken behind the text', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 0,
				),
				'description' => esc_html__( 'A black veil between the background and the words. Raise it until the white text and the accent hold their contrast over a picture.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-dim: calc({{SIZE}} / 100);',
				),
			)
		);

		$this->add_control(
			'p2_title_colour',
			array(
				'label'     => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--ink .eexp-panel__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p2_text_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--ink' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'caption_colour',
			array(
				'label'       => esc_html__( 'Caption', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'description' => esc_html__( 'The line under the diagram. It sits on the plate rather than on the panel, so it needs its own colour to stay readable.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-caption-colour: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'caption_accent',
			array(
				'label'     => esc_html__( 'Caption, right half', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-caption-accent: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p2_plate_colour',
			array(
				'label'       => esc_html__( 'Diagram plate', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'condition'   => array( 'p2_plate' => 'yes' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp-flow' => 'background-color: {{VALUE}};',
				),
				'description' => esc_html__( 'The artwork is screened over this, so a near-black plate keeps the drawing clean.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * How the panel headings are filled.
	 */
	private function register_fill_controls() {
		$this->start_controls_section(
			'section_style_fill',
			array(
				'label' => esc_html__( 'Heading fill', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_fill',
			array(
				'label'   => esc_html__( 'Fill', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => array(
					'solid'    => esc_html__( 'Solid colour', 'numbered-accordion' ),
					'gradient' => esc_html__( 'Gradient through the letters', 'numbered-accordion' ),
					'reveal'   => esc_html__( 'Fills in a word at a time as you reach it', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'fill_from',
			array(
				'label'     => esc_html__( 'From', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'title_fill' => 'gradient' ),
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-fill-from: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'fill_to',
			array(
				'label'     => esc_html__( 'To', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'title_fill' => 'gradient' ),
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-fill-to: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'fill_angle',
			array(
				'label'      => esc_html__( 'Angle', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'range'      => array(
					'deg' => array(
						'min' => 0,
						'max' => 360,
					),
				),
				'default'    => array(
					'unit' => 'deg',
					'size' => 95,
				),
				'condition'  => array( 'title_fill' => 'gradient' ),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-fill-angle: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'fill_dim',
			array(
				'label'       => esc_html__( 'Before it fills', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'condition'   => array( 'title_fill' => 'reveal' ),
				'description' => esc_html__( 'The colour the words wait in. It has to be readable on its own -- anyone who scrolls past quickly reads it in this state.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-fill-dim: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The drifting motes.
	 */
	private function register_mote_controls() {
		$this->start_controls_section(
			'section_style_motes',
			array(
				'label'     => esc_html__( 'Motes', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'p2_motes!' => array( '', '0', 0 ) ),
			)
		);

		$this->add_control(
			'mote_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-mote-colour: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'mote_scale',
			array(
				'label'       => esc_html__( 'Size', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array(
					'px' => array(
						'min'  => 0.4,
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'default'     => array( 'size' => 1 ),
				'description' => esc_html__( 'Scales all of them at once. They keep their own sizes relative to each other, which is what gives the depth.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-mote-scale: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'mote_speed',
			array(
				'label'     => esc_html__( 'Speed', 'numbered-accordion' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0.2,
						'max'  => 4,
						'step' => 0.1,
					),
				),
				'default'   => array( 'size' => 1 ),
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-mote-speed: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'mote_trails',
			array(
				'label'        => esc_html__( 'Trails', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'The streak behind each mote, which is what makes the direction of travel readable.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * One spacing slider, since there are a lot of them and they are all the
	 * same shape.
	 *
	 * @param string $id       Control id.
	 * @param string $label    Label.
	 * @param string $property Custom property to drive.
	 * @param int    $max      Top of the range.
	 * @param string $selector Selector to set it on.
	 */
	private function add_space_control( $id, $label, $property, $max = 120, $selector = '.eexp' ) {
		$this->add_responsive_control(
			$id,
			array(
				'label'      => $label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => $max,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * Space between everything.
	 */
	private function register_spacing_controls() {
		$this->start_controls_section(
			'section_style_spacing',
			array(
				'label' => esc_html__( 'Spacing', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_padding',
			array(
				'label' => esc_html__( 'Panel padding', 'numbered-accordion' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_space_control( 'pad_y', esc_html__( 'Top and bottom', 'numbered-accordion' ), '--eexp-pad-y', 160 );
		$this->add_space_control( 'pad_x', esc_html__( 'Left and right', 'numbered-accordion' ), '--eexp-pad-x', 160 );

		$this->add_control(
			'heading_stack',
			array(
				'label'     => esc_html__( 'Between the elements', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_space_control( 'gap_title', esc_html__( 'Step line to heading', 'numbered-accordion' ), '--eexp-gap-title', 80 );
		$this->add_space_control( 'gap_body', esc_html__( 'Heading to body', 'numbered-accordion' ), '--eexp-gap-body', 80 );
		$this->add_space_control( 'gap_stage', esc_html__( 'Body to picture', 'numbered-accordion' ), '--eexp-gap-stage', 120 );
		$this->add_space_control( 'gap_footer', esc_html__( 'Picture to footer', 'numbered-accordion' ), '--eexp-gap-footer', 120 );
		$this->add_space_control( 'gap_label', esc_html__( 'Footer label to pills', 'numbered-accordion' ), '--eexp-gap-label', 60 );
		$this->add_space_control( 'gap_caption', esc_html__( 'Diagram to its caption', 'numbered-accordion' ), '--eexp-gap-caption', 60 );

		$this->add_control(
			'heading_measure',
			array(
				'label'     => esc_html__( 'Line length', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'title_measure',
			array(
				'label'       => esc_html__( 'Heading width', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ch', 'px', '%' ),
				'range'       => array(
					'ch' => array(
						'min' => 6,
						'max' => 40,
					),
					'px' => array(
						'min' => 80,
						'max' => 900,
					),
					'%'  => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'description' => esc_html__( 'Where the heading wraps. In ch it is measured in characters, which is how a line length is usually judged.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-title-measure: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'body_measure',
			array(
				'label'      => esc_html__( 'Body width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ch', 'px', '%' ),
				'range'      => array(
					'ch' => array(
						'min' => 20,
						'max' => 90,
					),
					'px' => array(
						'min' => 160,
						'max' => 900,
					),
					'%'  => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-body-measure: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_specs_space',
			array(
				'label'     => esc_html__( 'Spec rows', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_space_control( 'spec_pad_y', esc_html__( 'Row height', 'numbered-accordion' ), '--eexp-spec-pad-y', 60 );
		$this->add_space_control( 'spec_pad_x', esc_html__( 'Row padding', 'numbered-accordion' ), '--eexp-spec-pad-x', 60 );
		$this->add_space_control( 'spec_radius', esc_html__( 'Corner radius', 'numbered-accordion' ), '--eexp-spec-radius', 40 );

		$this->end_controls_section();
	}

	/**
	 * One typography group per text role.
	 */
	private function register_typography_controls() {
		$this->start_controls_section(
			'section_style_type',
			array(
				'label' => esc_html__( 'Type', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$roles = array(
			'type_step_num'  => array( esc_html__( 'Step number', 'numbered-accordion' ), '{{WRAPPER}} .eexp-step__num' ),
			'type_step'      => array( esc_html__( 'Step label', 'numbered-accordion' ), '{{WRAPPER}} .eexp-step span' ),
			'type_title'     => array( esc_html__( 'Panel headings', 'numbered-accordion' ), '{{WRAPPER}} .eexp-panel__title' ),
			'type_body'      => array( esc_html__( 'Body', 'numbered-accordion' ), '{{WRAPPER}} .eexp-panel__body' ),
			'type_loads'     => array( esc_html__( 'Footer label', 'numbered-accordion' ), '{{WRAPPER}} .eexp-loads__label' ),
			'type_pills'     => array( esc_html__( 'Pills', 'numbered-accordion' ), '{{WRAPPER}} .eexp-loads li' ),
			'type_spec_key'  => array( esc_html__( 'Spec label', 'numbered-accordion' ), '{{WRAPPER}} .eexp-specs b' ),
			'type_spec_val'  => array( esc_html__( 'Spec value', 'numbered-accordion' ), '{{WRAPPER}} .eexp-specs li span' ),
			'type_caption'   => array( esc_html__( 'Diagram caption', 'numbered-accordion' ), '{{WRAPPER}} .eexp-flow__caption' ),
		);

		foreach ( $roles as $name => $role ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $name,
					'label'    => $role[0],
					'selector' => $role[1],
				)
			);
		}

		$this->add_control(
			'type_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Letter spacing must be set in em. Elementor writes a percentage as "2%", which browsers ignore, so a percentage here does nothing at all.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The picture in the light panel.
	 */
	private function register_picture_controls() {
		$this->start_controls_section(
			'section_style_picture',
			array(
				'label' => esc_html__( 'Picture', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'img_max',
			array(
				'label'      => esc_html__( 'Largest it gets', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 120,
						'max' => 1200,
					),
					'%'  => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-img-max: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'img_align',
			array(
				'label'     => esc_html__( 'Alignment', 'numbered-accordion' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'numbered-accordion' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Centre', 'numbered-accordion' ),
						'icon'  => 'eicon-h-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'numbered-accordion' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .eexp-stage__img' => 'margin-inline: {{VALUE}};',
				),
				'selectors_dictionary' => array(
					'left'   => '0 auto 0 0',
					'center' => 'auto',
					'right'  => '0 0 0 auto',
				),
			)
		);

		$this->add_responsive_control(
			'img_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp-stage__img' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The pills, beyond their two colours.
	 */
	private function register_pill_style_controls() {
		$this->start_controls_section(
			'section_style_pills',
			array(
				'label' => esc_html__( 'Pills', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'pill_background',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pill_dot',
			array(
				'label'     => esc_html__( 'Dot', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'p1_pill_style' => array( 'dot', 'solid', 'glass' ) ),
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li::before' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pill_hover_border',
			array(
				'label'     => esc_html__( 'Border on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_space_control( 'pill_gap', esc_html__( 'Gap between pills', 'numbered-accordion' ), '--eexp-pill-gap', 40 );
		$this->add_space_control( 'pill_pad_y', esc_html__( 'Height', 'numbered-accordion' ), '--eexp-pill-pad-y', 40 );
		$this->add_space_control( 'pill_pad_x', esc_html__( 'Width', 'numbered-accordion' ), '--eexp-pill-pad-x', 60 );
		$this->add_space_control( 'pill_radius', esc_html__( 'Corner radius', 'numbered-accordion' ), '--eexp-pill-radius', 2000 );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ *
	 * Defaults
	 * ------------------------------------------------------------------ */

	/**
	 * The pills the light panel ships with.
	 *
	 * @return array
	 */
	private function default_pills() {
		$rows = array();

		$items = array(
			esc_html__( 'Grease', 'numbered-accordion' ),
			esc_html__( 'Soot', 'numbered-accordion' ),
			esc_html__( 'Moisture', 'numbered-accordion' ),
			esc_html__( 'Lint', 'numbered-accordion' ),
			esc_html__( 'Fibre', 'numbered-accordion' ),
			esc_html__( 'Fumes', 'numbered-accordion' ),
		);

		foreach ( $items as $item ) {
			$rows[] = array( 'text' => $item );
		}

		return $rows;
	}

	/**
	 * The spec rows the ink panel ships with.
	 *
	 * @return array
	 */
	private function default_specs() {
		return array(
			array(
				'label' => esc_html__( 'Flow', 'numbered-accordion' ),
				'value' => esc_html__( '100% counter-current', 'numbered-accordion' ),
			),
			array(
				'label' => esc_html__( 'Circuiting', 'numbered-accordion' ),
				'value' => esc_html__( 'Multiple fluid patterns', 'numbered-accordion' ),
			),
			array(
				'label' => esc_html__( 'Transfer area', 'numbered-accordion' ),
				'value' => esc_html__( 'Matched to a conventional coil', 'numbered-accordion' ),
			),
		);
	}

	/* ------------------------------------------------------------------ *
	 * Render
	 * ------------------------------------------------------------------ */

	/**
	 * One of the four heading tags, or the default.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Control key.
	 * @return string
	 */
	private function heading_tag( $settings, $key ) {
		$tag = Explainer_Content::text( $settings, $key );

		return in_array( $tag, array( 'h2', 'h3', 'h4', 'div' ), true ) ? $tag : 'h3';
	}

	/**
	 * Print a panel heading, in word spans when the fill arrives a word at a
	 * time and as plain text otherwise.
	 *
	 * @param string $title Heading text.
	 * @param string $fill  Fill mode.
	 */
	private function print_title( $title, $fill ) {
		if ( 'reveal' !== $fill ) {
			echo esc_html( $title );

			return;
		}

		// Each span is built and escaped by Explainer_Content.
		echo implode( ' ', Explainer_Content::heading_words( $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Print one panel's step line.
	 *
	 * @param array  $settings Settings.
	 * @param string $prefix   Control prefix.
	 */
	private function render_step( $settings, $prefix ) {
		$number = Explainer_Content::text( $settings, $prefix . '_step' );
		$label  = Explainer_Content::text( $settings, $prefix . '_label' );

		if ( '' === $number && '' === $label ) {
			return;
		}
		?>
		<p class="<?php echo esc_attr( Explainer_Content::step_classes( $number ) ); ?> eexp-label">
			<?php if ( Explainer_Content::has_step( $number ) ) : ?>
				<b class="eexp-step__num"><?php echo esc_html( Explainer_Content::step_text( $number ) ); ?></b>
			<?php endif; ?>
			<?php if ( '' !== $label ) : ?>
				<span><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Print a panel image.
	 *
	 * Through the media library where possible, so WordPress supplies the
	 * dimensions, the alt text and a srcset of its own.
	 *
	 * @param array  $image Media control value.
	 * @param string $class Class for the image.
	 */
	private function render_image( $image, $class ) {
		$url = isset( $image['url'] ) ? $image['url'] : '';
		$id  = isset( $image['id'] ) ? (int) $image['id'] : 0;

		if ( '' === $url ) {
			return;
		}

		if ( $id > 0 ) {
			echo wp_get_attachment_image(
				$id,
				'full',
				false,
				array(
					'class'   => $class,
					'loading' => 'lazy',
					'sizes'   => Explainer_Content::panel_sizes_attr(),
				)
			);

			return;
		}
		?>
		<img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy" />
		<?php
	}

	/**
	 * Print the light panel.
	 *
	 * @param array $settings Settings.
	 */
	private function render_panel_one( $settings ) {
		$tag   = $this->heading_tag( $settings, 'p1_title_tag' );
		$fill  = Explainer_Content::text( $settings, 'title_fill' );
		$title = Explainer_Content::text( $settings, 'p1_title' );
		$body  = Explainer_Content::text( $settings, 'p1_body' );
		$label = Explainer_Content::text( $settings, 'p1_loads_label' );
		$pills = Explainer_Content::rows( $settings, 'p1_pills' );
		$style = Explainer_Content::pill_classes( Explainer_Content::text( $settings, 'p1_pill_style' ) );
		?>
		<div class="eexp-panel eexp-panel--light">
			<?php $this->render_step( $settings, 'p1' ); ?>

			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( Explainer_Content::title_classes( $fill ) ); ?>"><?php $this->print_title( $title, $fill ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== $body ) : ?>
				<p class="eexp-panel__body"><?php echo esc_html( $body ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['p1_image']['url'] ) ) : ?>
				<div class="eexp-stage">
					<?php $this->render_image( $settings['p1_image'], 'eexp-stage__img' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $pills ) || '' !== $label ) : ?>
				<div class="<?php echo esc_attr( $style ); ?>">
					<?php if ( '' !== $label ) : ?>
						<p class="eexp-loads__label"><?php echo esc_html( $label ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $pills ) ) : ?>
						<ul>
							<?php foreach ( $pills as $pill ) : ?>
								<?php
								$text = Explainer_Content::text( $pill, 'text' );

								if ( '' === $text ) {
									continue;
								}
								?>
								<li><?php echo esc_html( $text ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Print the ink panel.
	 *
	 * @param array $settings Settings.
	 */
	private function render_panel_two( $settings ) {
		$tag     = $this->heading_tag( $settings, 'p2_title_tag' );
		$fill    = Explainer_Content::text( $settings, 'title_fill' );
		$title   = Explainer_Content::text( $settings, 'p2_title' );
		$body    = Explainer_Content::text( $settings, 'p2_body' );
		$specs   = Explainer_Content::rows( $settings, 'p2_specs' );
		$plate   = 'yes' === Explainer_Content::text( $settings, 'p2_plate' );
		$left    = Explainer_Content::text( $settings, 'p2_caption_left' );
		$right   = Explainer_Content::text( $settings, 'p2_caption_right' );
		$motes   = Explainer_Content::mote_count( isset( $settings['p2_motes'] ) ? $settings['p2_motes'] : 0 );
		$classes = $plate ? 'eexp-flow' : 'eexp-flow eexp-flow--bare';

		if ( 'yes' !== Explainer_Content::text( $settings, 'mote_trails' ) ) {
			$classes .= ' eexp-flow--no-trails';
		}
		?>
		<div class="eexp-panel eexp-panel--ink">
			<?php $this->render_step( $settings, 'p2' ); ?>

			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( Explainer_Content::title_classes( $fill ) ); ?>"><?php $this->print_title( $title, $fill ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== $body ) : ?>
				<p class="eexp-panel__body"><?php echo esc_html( $body ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['p2_image']['url'] ) ) : ?>
				<div class="eexp-stage">
					<figure class="<?php echo esc_attr( $classes ); ?>">
						<?php $this->render_image( $settings['p2_image'], 'eexp-flow__img' ); ?>

						<?php for ( $i = 0; $i < $motes; $i++ ) : ?>
							<span class="eexp-mote" style="<?php echo esc_attr( Explainer_Content::mote_style( $i, $motes ) ); ?>"></span>
						<?php endfor; ?>

						<?php if ( '' !== $left || '' !== $right ) : ?>
							<figcaption class="eexp-flow__caption">
								<span><?php echo esc_html( $left ); ?></span>
								<i><?php echo esc_html( $right ); ?></i>
							</figcaption>
						<?php endif; ?>
					</figure>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $specs ) ) : ?>
				<ul class="eexp-specs">
					<?php foreach ( $specs as $spec ) : ?>
						<?php
						$spec_label = Explainer_Content::text( $spec, 'label' );
						$spec_value = Explainer_Content::text( $spec, 'value' );

						if ( '' === $spec_label && '' === $spec_value ) {
							continue;
						}
						?>
						<li>
							<b><?php echo esc_html( $spec_label ); ?></b>
							<span><?php echo esc_html( $spec_value ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="eexp eexp-no-js">
			<div class="eexp-slab eexp-rise">
				<?php $this->render_panel_one( $settings ); ?>
				<div class="eexp-bar" role="presentation"></div>
				<?php $this->render_panel_two( $settings ); ?>
			</div>
		</div>
		<?php
	}
}
