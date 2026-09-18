<?php
/**
 * Scroll Story widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Story\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A column of text beside a pinned panel that follows what you are reading.
 */
class Scroll_Story_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-scroll-story';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Scroll Story', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slides';
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
		return array( 'scroll', 'story', 'sticky', 'pinned', 'steps', 'features', 'showcase', 'highlight' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Story\Story_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Story\Story_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_eyebrow_controls();
		$this->register_heading_controls();
		$this->register_description_controls();
		$this->register_media_controls();
		$this->register_motion_controls();
	}

	/**
	 * The items themselves.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Items', 'numbered-accordion' ) )
		);

		$this->add_control(
			'label_mode',
			array(
				'label'   => esc_html__( 'Item label', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'eyebrow',
				'options' => array(
					'eyebrow' => esc_html__( 'Eyebrow pill', 'numbered-accordion' ),
					'number'  => esc_html__( 'Number', 'numbered-accordion' ),
					'none'    => esc_html__( 'None', 'numbered-accordion' ),
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'description' => esc_html__( 'The short line inside the pill. Leave it empty and the item has no pill.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Global installed customer base', 'numbered-accordion' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Orchestrate Yard Execution', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'body',
			array(
				'label'   => esc_html__( 'Description', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Computer vision automates check-in, location, and validation gate to dock.', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'media_type',
			array(
				'label'       => esc_html__( 'Media', 'numbered-accordion' ),
				'description' => esc_html__( 'An item with no media keeps showing the one above it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'image',
				'options'     => array(
					'image' => esc_html__( 'Image', 'numbered-accordion' ),
					'video' => esc_html__( 'Video', 'numbered-accordion' ),
				),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'     => esc_html__( 'Image', 'numbered-accordion' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'media_type' => 'image' ),
			)
		);

		$repeater->add_control(
			'video',
			array(
				'label'       => esc_html__( 'Video file', 'numbered-accordion' ),
				'description' => esc_html__( 'Plays muted and looped while its item is the one being read.', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'condition'   => array( 'media_type' => 'video' ),
			)
		);

		$repeater->add_control(
			'poster',
			array(
				'label'       => esc_html__( 'Video poster', 'numbered-accordion' ),
				'description' => esc_html__( 'Shown until the video has enough to play.', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'condition'   => array( 'media_type' => 'video' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'eyebrow' => esc_html__( 'Yard execution', 'numbered-accordion' ),
						'title'   => esc_html__( 'Orchestrate Yard Execution', 'numbered-accordion' ),
						'body'    => esc_html__( 'Computer vision automates check-in, location, and validation gate to dock.', 'numbered-accordion' ),
					),
					array(
						'eyebrow' => esc_html__( 'Modular by design', 'numbered-accordion' ),
						'title'   => esc_html__( 'Build the System You Need', 'numbered-accordion' ),
						'body'    => esc_html__( 'Start with the applications you need most, then expand as your operations grow.', 'numbered-accordion' ),
					),
					array(
						'eyebrow' => esc_html__( 'Measurable return', 'numbered-accordion' ),
						'title'   => esc_html__( 'Fast Payback', 'numbered-accordion' ),
						'body'    => esc_html__( 'All-inclusive, priced as a service. Measurable payback in months.', 'numbered-accordion' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Proportions and pinning.
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'text_width',
			array(
				'label'      => esc_html__( 'Text column', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'fr' ),
				'range'      => array( 'fr' => array( 'min' => 0.4, 'max' => 2, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'fr', 'size' => 1 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-text: {{SIZE}}fr;' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap between columns', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 48 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'sticky_top',
			array(
				'label'       => esc_html__( 'Vertical nudge', 'numbered-accordion' ),
				'description' => esc_html__( 'The panel is centred on the screen whatever its height. This shifts it off centre, which is what a tall sticky site header needs.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => -160, 'max' => 160 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-top: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'media_height',
			array(
				'label'      => esc_html__( 'Panel height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh' ),
				'range'      => array( 'vh' => array( 'min' => 40, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'vh', 'size' => 88 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-height: {{SIZE}}vh;' ),
			)
		);

		$this->add_responsive_control(
			'item_gap',
			array(
				'label'       => esc_html__( 'Space between items', 'numbered-accordion' ),
				'description' => esc_html__( 'How far you scroll from one item to the next.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'vh' ),
				'range'       => array( 'vh' => array( 'min' => 10, 'max' => 90 ) ),
				'default'     => array( 'unit' => 'vh', 'size' => 45 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-item-gap: {{SIZE}}vh;' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The pill above each heading.
	 */
	private function register_eyebrow_controls() {
		$this->start_controls_section(
			'section_eyebrow',
			array(
				'label'     => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'label_mode' => 'eyebrow' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'eyebrow_typography',
				'selector' => '{{WRAPPER}} .estry__eyebrow',
			)
		);

		$this->add_control(
			'eyebrow_colour',
			array(
				'label'     => esc_html__( 'Text colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2F7D4F',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-eb-colour: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'eyebrow_bg',
			array(
				'label'     => esc_html__( 'Pill background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-eb-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'eyebrow_border_colour',
			array(
				'label'     => esc_html__( 'Pill border colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#D5E3D9',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-eb-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'eyebrow_border_width',
			array(
				'label'      => esc_html__( 'Pill border width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 6 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 1 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-eb-bw: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'eyebrow_radius',
			array(
				'label'      => esc_html__( 'Pill corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 999 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 999 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-eb-radius: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'eyebrow_padding',
			array(
				'label'      => esc_html__( 'Pill padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => 10,
					'right'    => 20,
					'bottom'   => 10,
					'left'     => 20,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .estry__eyebrow' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'eyebrow_space',
			array(
				'label'      => esc_html__( 'Space below pill', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-eb-space: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'eyebrow_dot',
			array(
				'label'        => esc_html__( 'Show the dot', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'eyebrow_dot_colour',
			array(
				'label'     => esc_html__( 'Dot colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2F7D4F',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-eb-dot: {{VALUE}};' ),
				'condition' => array( 'eyebrow_dot' => 'yes' ),
			)
		);

		$this->add_control(
			'eyebrow_dot_size',
			array(
				'label'      => esc_html__( 'Dot size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 2, 'max' => 28 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-eb-dot-size: {{SIZE}}px;' ),
				'condition'  => array( 'eyebrow_dot' => 'yes' ),
			)
		);

		$this->add_control(
			'eyebrow_gap',
			array(
				'label'      => esc_html__( 'Space after dot', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-eb-gap: {{SIZE}}px;' ),
				'condition'  => array( 'eyebrow_dot' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The heading, and the three colours its sweep passes through.
	 */
	private function register_heading_controls() {
		$this->start_controls_section(
			'section_heading',
			array(
				'label' => esc_html__( 'Heading', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .estry__title',
			)
		);

		$this->add_control(
			'heading_dim',
			array(
				'label'       => esc_html__( 'Before it is read', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour a heading waits in before the sweep reaches it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#DDDDDD',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-h-dim: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'heading_flash',
			array(
				'label'       => esc_html__( 'Flash', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour each letter passes through on its way in.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#ABFF04',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-h-flash: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'heading_lit',
			array(
				'label'     => esc_html__( 'After it is read', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#052424',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-h-lit: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'title_space',
			array(
				'label'      => esc_html__( 'Space below heading', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-title-space: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'HTML tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'div'  => 'div',
					'span' => 'span',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The description, styled entirely separately from the heading.
	 */
	private function register_description_controls() {
		$this->start_controls_section(
			'section_description',
			array(
				'label' => esc_html__( 'Description', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'selector' => '{{WRAPPER}} .estry__body',
			)
		);

		$this->add_control(
			'body_dim',
			array(
				'label'       => esc_html__( 'Before it is read', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour a description waits in before the sweep reaches it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#DDDDDD',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-b-dim: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'body_flash',
			array(
				'label'       => esc_html__( 'Flash', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour each letter passes through on its way in.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#ABFF04',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-b-flash: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'body_lit',
			array(
				'label'     => esc_html__( 'After it is read', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#052424',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-b-lit: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'colour_num',
			array(
				'label'     => esc_html__( 'Number colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#9AA4A4',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-num: {{VALUE}};' ),
				'condition' => array( 'label_mode' => 'number' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The panel: its edge, its border, how the picture sits in it.
	 */
	private function register_media_controls() {
		$this->start_controls_section(
			'section_media',
			array(
				'label' => esc_html__( 'Media panel', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'object_position',
			array(
				'label'       => esc_html__( 'Focal point', 'numbered-accordion' ),
				'description' => esc_html__( 'Media always fills the panel. This is which part of it survives the crop.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '50% 50%',
				'options'     => array(
					'50% 0%'   => esc_html__( 'Top', 'numbered-accordion' ),
					'50% 25%'  => esc_html__( 'Upper middle', 'numbered-accordion' ),
					'50% 50%'  => esc_html__( 'Centre', 'numbered-accordion' ),
					'50% 75%'  => esc_html__( 'Lower middle', 'numbered-accordion' ),
					'50% 100%' => esc_html__( 'Bottom', 'numbered-accordion' ),
					'0% 50%'   => esc_html__( 'Left', 'numbered-accordion' ),
					'100% 50%' => esc_html__( 'Right', 'numbered-accordion' ),
				),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-obj-pos: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'media_border_width',
			array(
				'label'       => esc_html__( 'Border width', 'numbered-accordion' ),
				'description' => esc_html__( 'Drawn on the panel itself, and it follows the notch when the notch is on.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-media-bw: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'media_border_colour',
			array(
				'label'     => esc_html__( 'Border colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#052424',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-media-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'frame_bg',
			array(
				'label'       => esc_html__( 'Panel background', 'numbered-accordion' ),
				'description' => esc_html__( 'Only ever seen where a picture does not reach, which should be nowhere.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-frame-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'notch',
			array(
				'label'        => esc_html__( 'Notched edge', 'numbered-accordion' ),
				'description'  => esc_html__( 'Cuts a rounded step into the panel\'s left edge, travelling as you scroll. Replaces the corner radius.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'notch_depth',
			array(
				'label'      => esc_html__( 'Notch depth', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 90 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 30 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-notch: {{SIZE}}px;' ),
				'condition'  => array( 'notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_size',
			array(
				'label'      => esc_html__( 'Notch length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 40, 'max' => 600 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 280 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-band-size: {{SIZE}}px;' ),
				'condition'  => array( 'notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_travel',
			array(
				'label'       => esc_html__( 'Notch travel', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the panel the notch crosses as you scroll. The rest is split evenly above and below, so the notch always stops short of both corners.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 40 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-notch-travel: {{SIZE}};' ),
				'condition'   => array( 'notch' => 'yes' ),
			)
		);

		$this->add_control(
			'radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-radius: {{SIZE}}px;' ),
				'condition'  => array( 'notch!' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The sweep and the panel change.
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
			'sweep_heading',
			array(
				'label' => esc_html__( 'Scroll highlight', 'numbered-accordion' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'sweep_start',
			array(
				'label'       => esc_html__( 'Highlight starts at', 'numbered-accordion' ),
				'description' => esc_html__( 'How far down the screen an item has to reach before its first letter lights. 100 is the bottom edge.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 30, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 85 ),
			)
		);

		$this->add_control(
			'sweep_end',
			array(
				'label'       => esc_html__( 'Highlight finishes at', 'numbered-accordion' ),
				'description' => esc_html__( 'Where the item\'s last line has to reach for the sweep to be complete. Must be above the start.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 90 ) ),
				'default'     => array( 'unit' => '%', 'size' => 45 ),
			)
		);

		$this->add_control(
			'sweep_duration',
			array(
				'label'       => esc_html__( 'Letter fade in', 'numbered-accordion' ),
				'description' => esc_html__( 'How long one letter takes to travel dim, flash, lit. The stagger between letters is your scroll speed.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 500 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-sweep: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'unsweep_duration',
			array(
				'label'       => esc_html__( 'Letter fade out', 'numbered-accordion' ),
				'description' => esc_html__( 'How long a letter takes to go back out when you scroll up past it. No flash on the way out.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 1500, 'step' => 50 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 400 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-unsweep: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'panel_heading',
			array(
				'label'     => esc_html__( 'Panel change', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'transition',
			array(
				'label'   => esc_html__( 'Transition', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'wipe',
				'options' => array(
					'wipe'     => esc_html__( 'Wipe - a hard edge travels with the scroll', 'numbered-accordion' ),
					'zoom'     => esc_html__( 'Zoom - arrives oversized and out of focus', 'numbered-accordion' ),
					'push'     => esc_html__( 'Push - slides in over the one below', 'numbered-accordion' ),
					'dissolve' => esc_html__( 'Dissolve - a plain cross-fade', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'fade',
			array(
				'label'      => esc_html__( 'Transition length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 100, 'max' => 2500, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 900 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-fade: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'ease',
			array(
				'label'       => esc_html__( 'Easing', 'numbered-accordion' ),
				'description' => esc_html__( 'How the transition is paced. The default covers most of the distance at once and spends the rest settling.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'cubic-bezier(0.16, 1, 0.3, 1)',
				'options'     => array(
					'cubic-bezier(0.16, 1, 0.3, 1)'    => esc_html__( 'Settle (hard out)', 'numbered-accordion' ),
					'cubic-bezier(0.22, 1, 0.36, 1)'   => esc_html__( 'Glide', 'numbered-accordion' ),
					'cubic-bezier(0.83, 0, 0.17, 1)'   => esc_html__( 'Swoop (slow both ends)', 'numbered-accordion' ),
					'cubic-bezier(0.65, 0, 0.35, 1)'   => esc_html__( 'Even', 'numbered-accordion' ),
					'ease'                             => esc_html__( 'Plain', 'numbered-accordion' ),
				),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-ease: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'switch_at',
			array(
				'label'       => esc_html__( 'Panel changes at', 'numbered-accordion' ),
				'description' => esc_html__( 'How far into an item\'s highlight the panel swaps to that item\'s media.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 90 ) ),
				'default'     => array( 'unit' => '%', 'size' => 15 ),
			)
		);

		$this->add_control(
			'drift',
			array(
				'label'        => esc_html__( 'Drift', 'numbered-accordion' ),
				'description'  => esc_html__( 'A slow push into whatever is showing, so a held panel is never quite still.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'drift_amount',
			array(
				'label'      => esc_html__( 'Drift distance', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 1, 'max' => 20 ) ),
				'default'    => array( 'unit' => '%', 'size' => 6 ),
				'condition'  => array( 'drift' => 'yes' ),
			)
		);

		$this->add_control(
			'drift_time',
			array(
				'label'      => esc_html__( 'Drift length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array( 's' => array( 'min' => 4, 'max' => 40 ) ),
				'default'    => array( 'unit' => 's', 'size' => 14 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-drift: {{SIZE}}s;' ),
				'condition'  => array( 'drift' => 'yes' ),
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
	 * Render.
	 *
	 * The markup is complete and readable on its own: every item's text is in
	 * its lit colour, and the media slides simply stack so the first one shows.
	 * The script dims, scrubs and reorders only once it has marked the section
	 * ready.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$lead = $this->slider( $settings, 'sweep_start', 85, 30, 100 ) / 100;
		$tail = $this->slider( $settings, 'sweep_end', 45, 0, 90 ) / 100;

		// A tail at or above the lead would divide by zero in the script, or
		// run the sweep backwards. Fall back rather than render something that
		// cannot work.
		if ( $tail >= $lead ) {
			$lead = 0.85;
			$tail = 0.45;
		}

		$switch = $this->slider( $settings, 'switch_at', 15, 0, 90 ) / 100;

		$mode       = isset( $settings['label_mode'] ) ? $settings['label_mode'] : 'eyebrow';
		$dot        = ! isset( $settings['eyebrow_dot'] ) || 'yes' === $settings['eyebrow_dot'];
		$notched    = isset( $settings['notch'] ) && 'yes' === $settings['notch'];
		$transition = isset( $settings['transition'] ) ? $settings['transition'] : 'wipe';
		$tag        = isset( $settings['title_tag'] ) ? $settings['title_tag'] : 'h3';
		$tag        = in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'div', 'span' ), true ) ? $tag : 'h3';

		$drift = isset( $settings['drift'] ) && 'yes' === $settings['drift']
			? 1 + ( $this->slider( $settings, 'drift_amount', 6, 1, 20 ) / 100 )
			: 1;
		?>
		<div
			class="estry"
			data-estry-lead="<?php echo esc_attr( (string) $lead ); ?>"
			data-estry-tail="<?php echo esc_attr( (string) $tail ); ?>"
			data-estry-switch="<?php echo esc_attr( (string) $switch ); ?>"
			data-estry-transition="<?php echo esc_attr( $transition ); ?>"
			style="--estry-drift-to: <?php echo esc_attr( (string) $drift ); ?>;"
		>
			<div class="estry__items">
				<?php foreach ( $items as $index => $item ) : ?>
					<div class="estry__item">
						<?php if ( 'number' === $mode ) : ?>
							<span class="estry__num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
						<?php elseif ( 'eyebrow' === $mode && ! empty( $item['eyebrow'] ) ) : ?>
							<span class="estry__eyebrow">
								<?php if ( $dot ) : ?>
									<span class="estry__dot" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="estry__eyebrow-text"><?php echo esc_html( $item['eyebrow'] ); ?></span>
							</span>
						<?php endif; ?>

						<?php if ( ! empty( $item['title'] ) ) : ?>
							<<?php echo esc_html( $tag ); ?> class="estry__title"><?php echo esc_html( $item['title'] ); ?></<?php echo esc_html( $tag ); ?>>
						<?php endif; ?>

						<?php if ( ! empty( $item['body'] ) ) : ?>
							<p class="estry__body"><?php echo esc_html( $item['body'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="estry__media">
				<div class="estry__frame"<?php echo $notched ? ' data-estry-notch' : ''; ?>>
					<?php
					foreach ( $items as $index => $item ) :
						$video = 'video' === ( isset( $item['media_type'] ) ? $item['media_type'] : 'image' );
						$src   = $video
							? ( isset( $item['video']['url'] ) ? $item['video']['url'] : '' )
							: ( isset( $item['image']['url'] ) ? $item['image']['url'] : '' );

						// No media means no slide. The script maps items onto
						// the slides that do exist, so such an item simply
						// keeps showing whatever the item above it set.
						if ( '' === $src ) {
							continue;
						}

						$alt = ! empty( $item['title'] ) ? $item['title'] : '';
						?>
						<div class="estry__slide" data-estry-for="<?php echo esc_attr( (string) $index ); ?>">
							<?php if ( $video ) : ?>
								<video
									class="estry__vid"
									src="<?php echo esc_url( $src ); ?>"
									<?php if ( ! empty( $item['poster']['url'] ) ) : ?>
										poster="<?php echo esc_url( $item['poster']['url'] ); ?>"
									<?php endif; ?>
									muted
									loop
									playsinline
									preload="metadata"
									aria-label="<?php echo esc_attr( $alt ); ?>"
								></video>
							<?php else : ?>
								<img
									class="estry__img"
									src="<?php echo esc_url( $src ); ?>"
									alt="<?php echo esc_attr( $alt ); ?>"
									loading="lazy"
									decoding="async"
								/>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
