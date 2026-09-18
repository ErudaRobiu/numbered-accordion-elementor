<?php
/**
 * Scroll Rail widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Rail\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A row of linked cards that travels sideways as the section is scrolled past.
 */
class Scroll_Rail_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-scroll-rail';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Scroll Rail', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-post-slider';
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
		return array( 'carousel', 'horizontal', 'scroll', 'rail', 'slider', 'cards', 'sideways' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Rail\Rail_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Rail\Rail_Module::SCRIPT_HANDLE );
	}

	/**
	 * The arrow marks, as inline SVG.
	 *
	 * Inline because the cue is one glyph on every card and an icon font or a
	 * sprite request for it would cost more than the markup does.
	 *
	 * @return array<string, string>
	 */
	private function icons() {
		return array(
			'diagonal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>',
			'right'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
			'plus'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
		);
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_card_controls();
		$this->register_text_controls();
		$this->register_cue_controls();
		$this->register_motion_controls();
	}

	/**
	 * The cards.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_cards',
			array( 'label' => esc_html__( 'Cards', 'numbered-accordion' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			array(
				'label' => esc_html__( 'Image', 'numbered-accordion' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Industrial Laundry & Linen', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'copy',
			array(
				'label'   => esc_html__( 'Description', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Tunnel washers and dryers running long shifts, venting lint-laden air continuously.', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'numbered-accordion' ),
				'description' => esc_html__( 'A card with no link is still a card. It just does not show the cue or behave as though it opens something.', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'cards',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'title' => esc_html__( 'Industrial Laundry &amp; Linen', 'numbered-accordion' ),
						'copy'  => esc_html__( 'Tunnel washers and dryers running long shifts, venting lint-laden air continuously.', 'numbered-accordion' ),
					),
					array(
						'title' => esc_html__( 'Food &amp; Beverage Processing', 'numbered-accordion' ),
						'copy'  => esc_html__( 'Wash-down areas and cook lines that load extraction hard, every shift.', 'numbered-accordion' ),
					),
					array(
						'title' => esc_html__( 'Coating &amp; Finishing', 'numbered-accordion' ),
						'copy'  => esc_html__( 'Solvent-laden exhaust that has to be moved, treated and accounted for.', 'numbered-accordion' ),
					),
					array(
						'title' => esc_html__( 'Warehousing &amp; Logistics', 'numbered-accordion' ),
						'copy'  => esc_html__( 'Tall spaces, wide doors and a heat load that moves with the season.', 'numbered-accordion' ),
					),
				),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title tag', 'numbered-accordion' ),
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
	 * How the row sits and how big the cards are.
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
			'card_width',
			array(
				'label'      => esc_html__( 'Card width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 220, 'max' => 900 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 420 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-card: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'card_ratio',
			array(
				'label'       => esc_html__( 'Card shape', 'numbered-accordion' ),
				'description' => esc_html__( 'Width divided by height. Below 1 is a portrait card.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0.5, 'max' => 1.8, 'step' => 0.01 ) ),
				'default'     => array( 'size' => 0.81 ),
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-ratio: {{SIZE}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Space between cards', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'height_mode',
			array(
				'label'       => esc_html__( 'Section height', 'numbered-accordion' ),
				'description' => esc_html__( 'A height in screen-heights has no idea how tall a card is, so it leaves dead space above and below the row. Fitting the cards is almost always what you want.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'fit',
				'options'     => array(
					'fit'   => esc_html__( 'Fit the cards', 'numbered-accordion' ),
					'fixed' => esc_html__( 'A fixed share of the screen', 'numbered-accordion' ),
				),
			)
		);

		$this->add_responsive_control(
			'stage_height',
			array(
				'label'      => esc_html__( 'How much of the screen', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh' ),
				'range'      => array( 'vh' => array( 'min' => 40, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'vh', 'size' => 78 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-height: {{SIZE}}vh;' ),
				'condition'  => array( 'height_mode' => 'fixed' ),
			)
		);

		$this->add_responsive_control(
			'pad_y',
			array(
				'label'       => esc_html__( 'Space above and below', 'numbered-accordion' ),
				'description' => esc_html__( 'Room for a card\'s shadow and its hover lift, which the row would otherwise clip.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-pad-y: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'lead',
			array(
				'label'       => esc_html__( 'Lead-in', 'numbered-accordion' ),
				'description' => esc_html__( 'Space before the first card, so the row starts where your text does rather than at the screen edge.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 400 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-lead: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The card itself: its edge, its picture, its scrim.
	 */
	private function register_card_controls() {
		$this->start_controls_section(
			'section_card',
			array(
				'label' => esc_html__( 'Card', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => esc_html__( 'Card background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0C1116',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-card-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-radius: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'card_border_w',
			array(
				'label'      => esc_html__( 'Border width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 6 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 1 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-border-w: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'card_border',
			array(
				'label'     => esc_html__( 'Border colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.1)',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'card_border_hover',
			array(
				'label'     => esc_html__( 'Border colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.28)',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-border-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'scrim',
			array(
				'label'       => esc_html__( 'Scrim', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour the picture darkens into behind the words. Usually the card background.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#0C1116',
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-scrim: {{VALUE}};' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'obj_pos',
			array(
				'label'       => esc_html__( 'Focal point', 'numbered-accordion' ),
				'description' => esc_html__( 'Pictures always fill the card. This is which part of one survives the crop.', 'numbered-accordion' ),
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
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-obj-pos: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'grey',
			array(
				'label'       => esc_html__( 'Desaturate', 'numbered-accordion' ),
				'description' => esc_html__( 'How grey a picture sits at rest. It returns to full colour on hover.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 0.85 ),
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-grey: {{SIZE}};' ),
			)
		);

		$this->add_control(
			'bright',
			array(
				'label'      => esc_html__( 'Brightness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0.4, 'max' => 1.4, 'step' => 0.05 ) ),
				'default'    => array( 'size' => 0.9 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-bright: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The words on the card.
	 */
	private function register_text_controls() {
		$this->start_controls_section(
			'section_text',
			array(
				'label' => esc_html__( 'Text', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'text_pad',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 32 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-text-pad: {{SIZE}}px;' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .erail__title',
			)
		);

		$this->add_control(
			'title_colour',
			array(
				'label'     => esc_html__( 'Title colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-title: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'copy_typography',
				'selector'  => '{{WRAPPER}} .erail__copy',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'copy_colour',
			array(
				'label'     => esc_html__( 'Description colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#C3CCD6',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-copy: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The mark that says a card opens something.
	 */
	private function register_cue_controls() {
		$this->start_controls_section(
			'section_cue',
			array(
				'label' => esc_html__( 'Click cue', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'cue',
			array(
				'label'        => esc_html__( 'Show the cue', 'numbered-accordion' ),
				'description'  => esc_html__( 'A mark on the picture that says the card opens something. It is visible before you touch the card and fills in on hover, because a hover-only hint arrives after you have already guessed.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'cue_icon',
			array(
				'label'     => esc_html__( 'Mark', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'diagonal',
				'options'   => array(
					'diagonal' => esc_html__( 'Arrow, out of the corner', 'numbered-accordion' ),
					'right'    => esc_html__( 'Arrow, straight on', 'numbered-accordion' ),
					'plus'     => esc_html__( 'Plus', 'numbered-accordion' ),
				),
				'condition' => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_side',
			array(
				'label'     => esc_html__( 'Corner', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => array(
					'right' => esc_html__( 'Top right', 'numbered-accordion' ),
					'left'  => esc_html__( 'Top left', 'numbered-accordion' ),
				),
				'condition' => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_size',
			array(
				'label'      => esc_html__( 'Size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 88 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 44 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-cue-size: {{SIZE}}px;' ),
				'condition'  => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_inset',
			array(
				'label'      => esc_html__( 'Distance from the corner', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-cue-inset: {{SIZE}}px;' ),
				'condition'  => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_idle',
			array(
				'label'       => esc_html__( 'Resting strength', 'numbered-accordion' ),
				'description' => esc_html__( 'How visible the cue is before anyone hovers. Zero hides it until they do.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 0.55 ),
				'selectors'   => array( '{{WRAPPER}} .erail' => '--erail-cue-idle: {{SIZE}};' ),
				'condition'   => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_fg',
			array(
				'label'     => esc_html__( 'Mark colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-cue-fg: {{VALUE}};' ),
				'condition' => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_bg_hover',
			array(
				'label'     => esc_html__( 'Fill on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-cue-bg-hover: {{VALUE}};' ),
				'condition' => array( 'cue' => 'yes' ),
			)
		);

		$this->add_control(
			'cue_fg_hover',
			array(
				'label'     => esc_html__( 'Mark colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0C1116',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-cue-fg-hover: {{VALUE}};' ),
				'condition' => array( 'cue' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Pace, hover and the progress bar.
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
			'mode',
			array(
				'label'       => esc_html__( 'How it travels', 'numbered-accordion' ),
				'description' => esc_html__( 'Pinned is the usual one: the page stops, the row crosses, it pauses, and then the page carries on. The scrolling it crosses in has to come from somewhere, so the section is made taller by that much and everything after it sits further down the page. That is not a side effect, it is the mechanism. Flow spends the section\'s own journey across the screen instead and adds nothing, at the cost of a quicker crossing.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'pinned',
				'options'     => array(
					'pinned' => esc_html__( 'Pinned - the page holds still while the row crosses', 'numbered-accordion' ),
					'flow'   => esc_html__( 'Flow - travels as the section crosses the screen, adds no height', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'pace',
			array(
				'label'       => esc_html__( 'Scroll distance', 'numbered-accordion' ),
				'description' => esc_html__( 'How much scrolling the crossing itself costs, against how wide the row is. One is a pixel of scroll per pixel of row; higher is slower.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0.4, 'max' => 2.5, 'step' => 0.1 ) ),
				'default'     => array( 'size' => 1 ),
				'condition'   => array( 'mode' => 'pinned' ),
			)
		);

		$this->add_control(
			'pause_in',
			array(
				'label'       => esc_html__( 'Pause before it sets off', 'numbered-accordion' ),
				'description' => esc_html__( 'The page is already held and the row has not moved yet, so the cards arrive, settle in the middle of the screen and can be read before anything travels. In screen-heights of scrolling.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 150, 'step' => 5 ) ),
				'default'     => array( 'unit' => '%', 'size' => 30 ),
				'condition'   => array( 'mode' => 'pinned' ),
			)
		);

		$this->add_control(
			'pause_out',
			array(
				'label'       => esc_html__( 'Pause before it lets go', 'numbered-accordion' ),
				'description' => esc_html__( 'The row has finished but the page is still held, so the last cards are read before scrolling carries on down the page.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 150, 'step' => 5 ) ),
				'default'     => array( 'unit' => '%', 'size' => 30 ),
				'condition'   => array( 'mode' => 'pinned' ),
			)
		);

		$this->add_control(
			'travel_start',
			array(
				'label'       => esc_html__( 'Starts once this much is on screen', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the section has to be in view before the row sets off. At 100 it waits until the whole section is showing, so nothing moves before you can see it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 80 ),
				'condition'   => array( 'mode' => 'flow' ),
			)
		);

		$this->add_control(
			'travel_finish',
			array(
				'label'       => esc_html__( 'Finishes while this much is still on screen', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the section is still showing when the row reaches the end. At 100 it is done before the section starts to leave, so the last cards are read rather than glimpsed.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 80 ),
				'condition'   => array( 'mode' => 'flow' ),
			)
		);

		$this->add_control(
			'extra',
			array(
				'label'       => esc_html__( 'Extra scroll', 'numbered-accordion' ),
				'description' => esc_html__( 'Flow can only spend the scrolling the section already has, so a very wide row crosses quickly. This buys more, in screen-heights, and is the one thing here that does push what follows further down the page. Zero adds nothing.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'vh' ),
				'range'       => array( 'vh' => array( 'min' => 0, 'max' => 200, 'step' => 10 ) ),
				'default'     => array( 'unit' => 'vh', 'size' => 0 ),
				'condition'   => array( 'mode' => 'flow' ),
			)
		);

		$this->add_control(
			'hover_ms',
			array(
				'label'      => esc_html__( 'Hover length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 100, 'max' => 1200, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 500 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-hover-ms: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'lift',
			array(
				'label'      => esc_html__( 'Hover lift', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array( '{{WRAPPER}} .erail' => '--erail-lift: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'progress',
			array(
				'label'        => esc_html__( 'Progress bar', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'bar_track',
			array(
				'label'     => esc_html__( 'Bar track', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.12)',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-bar-track: {{VALUE}};' ),
				'condition' => array( 'progress' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_fill',
			array(
				'label'     => esc_html__( 'Bar fill', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.75)',
				'selectors' => array( '{{WRAPPER}} .erail' => '--erail-bar-fill: {{VALUE}};' ),
				'condition' => array( 'progress' => 'yes' ),
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
	 * Complete without the script: the row is a real horizontal scroller with
	 * snap points until the script marks it ready, which is also what it stays
	 * as on a narrow screen and under reduced motion.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$cards    = isset( $settings['cards'] ) && is_array( $settings['cards'] ) ? $settings['cards'] : array();

		if ( empty( $cards ) ) {
			return;
		}

		$icons = $this->icons();
		$cue   = ! isset( $settings['cue'] ) || 'yes' === $settings['cue'];
		$icon  = isset( $settings['cue_icon'] ) && isset( $icons[ $settings['cue_icon'] ] ) ? $settings['cue_icon'] : 'diagonal';
		$side  = isset( $settings['cue_side'] ) && 'left' === $settings['cue_side'] ? 'left' : 'right';
		$bar   = ! isset( $settings['progress'] ) || 'yes' === $settings['progress'];

		$tag = isset( $settings['title_tag'] ) ? $settings['title_tag'] : 'h3';
		$tag = in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'div', 'span' ), true ) ? $tag : 'h3';

		$pace     = $this->slider( $settings, 'pace', 1, 0.4, 2.5 );
		$pauseIn  = $this->slider( $settings, 'pause_in', 30, 0, 150 ) / 100;
		$pauseOut = $this->slider( $settings, 'pause_out', 30, 0, 150 ) / 100;
		$mode     = isset( $settings['mode'] ) && 'flow' === $settings['mode'] ? 'flow' : 'pinned';
		$start  = $this->slider( $settings, 'travel_start', 80, 0, 100 ) / 100;
		$finish = $this->slider( $settings, 'travel_finish', 80, 0, 100 ) / 100;
		$extra  = $this->slider( $settings, 'extra', 0, 0, 200 ) / 100;
		?>
		<div
			class="erail"
			data-erail-mode="<?php echo esc_attr( $mode ); ?>"
			data-erail-pace="<?php echo esc_attr( (string) $pace ); ?>"
			data-erail-pause-in="<?php echo esc_attr( (string) $pauseIn ); ?>"
			data-erail-pause-out="<?php echo esc_attr( (string) $pauseOut ); ?>"
			data-erail-start="<?php echo esc_attr( (string) $start ); ?>"
			data-erail-finish="<?php echo esc_attr( (string) $finish ); ?>"
			data-erail-extra="<?php echo esc_attr( (string) $extra ); ?>"
		>
			<div class="erail__stage">
				<div class="erail__viewport">
					<div class="erail__track">
						<?php
						foreach ( $cards as $card ) :
							$url      = isset( $card['link']['url'] ) ? $card['link']['url'] : '';
							$element  = '' !== $url ? 'a' : 'div';
							$title    = isset( $card['title'] ) ? $card['title'] : '';
							$external = ! empty( $card['link']['is_external'] );
							$nofollow = ! empty( $card['link']['nofollow'] );
							?>
							<<?php echo esc_html( $element ); ?>
								class="erail__card"
								<?php if ( '' !== $url ) : ?>
									href="<?php echo esc_url( $url ); ?>"
									<?php echo $external ? ' target="_blank"' : ''; ?>
									<?php echo $external || $nofollow ? ' rel="' . esc_attr( trim( ( $nofollow ? 'nofollow ' : '' ) . ( $external ? 'noopener noreferrer' : '' ) ) ) . '"' : ''; ?>
								<?php endif; ?>
							>
								<div class="erail__media">
									<?php if ( ! empty( $card['image']['url'] ) ) : ?>
										<img
											class="erail__img"
											src="<?php echo esc_url( $card['image']['url'] ); ?>"
											alt="<?php echo esc_attr( $title ); ?>"
											loading="lazy"
											decoding="async"
										/>
									<?php endif; ?>
								</div>

								<div class="erail__scrim" aria-hidden="true"></div>

								<?php if ( $cue && '' !== $url ) : ?>
									<span
										class="erail__cue"
										data-erail-cue="<?php echo esc_attr( $side ); ?>"
										data-erail-icon="<?php echo esc_attr( $icon ); ?>"
										aria-hidden="true"
									><?php echo $icons[ $icon ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a fixed inline SVG from icons(), not user input. ?></span>
								<?php endif; ?>

								<div class="erail__text">
									<?php if ( '' !== $title ) : ?>
										<<?php echo esc_html( $tag ); ?> class="erail__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $tag ); ?>>
									<?php endif; ?>

									<?php if ( ! empty( $card['copy'] ) ) : ?>
										<p class="erail__copy"><?php echo esc_html( $card['copy'] ); ?></p>
									<?php endif; ?>
								</div>
							</<?php echo esc_html( $element ); ?>>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( $bar ) : ?>
					<div class="erail__progress" aria-hidden="true"><span></span></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
