<?php
/**
 * Impact Grid widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Impact\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Impact\Impact_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A grid of numbered impact cards: big figures, or checklists.
 */
class Impact_Grid_Widget extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * Never change this. Live pages carry it inside their saved Elementor
	 * JSON, and a renamed widget renders those pages empty.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eimp-impact-grid';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Impact Grid', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-number-field';
	}

	/**
	 * Panel categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'impact', 'stats', 'counter', 'grid', 'cards', 'infographic', 'numbers', 'results' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Impact\Impact_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Impact\Impact_Module::SCRIPT_HANDLE );
	}

	/**
	 * Allowed wrapper tags for card titles.
	 *
	 * @return array
	 */
	private function get_title_tags() {
		return array(
			'h2'  => 'H2',
			'h3'  => 'H3',
			'h4'  => 'H4',
			'h5'  => 'H5',
			'h6'  => 'H6',
			'div' => 'div',
		);
	}

	/**
	 * Register all controls.
	 */
	protected function register_controls() {
		$this->register_cards_controls();
		$this->register_settings_controls();
		$this->register_layout_style_controls();
		$this->register_card_style_controls();
		$this->register_accent_style_controls();
		$this->register_badge_style_controls();
		$this->register_title_style_controls();
		$this->register_icon_style_controls();
		$this->register_figure_style_controls();
		$this->register_checklist_style_controls();
		$this->register_motion_controls();
	}

	/**
	 * Content tab: the repeater of cards.
	 */
	private function register_cards_controls() {
		$this->start_controls_section(
			'section_cards',
			array(
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'card_type',
			array(
				'label'   => esc_html__( 'Card type', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'stat' => esc_html__( 'Figure', 'numbered-accordion' ),
					'list' => esc_html__( 'Checklist', 'numbered-accordion' ),
				),
				'default' => 'stat',
			)
		);

		$repeater->add_control(
			'card_accent',
			array(
				'label'       => esc_html__( 'Accent', 'numbered-accordion' ),
				'description' => esc_html__( 'Both colours are set once, under Style → Accents.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'primary'   => esc_html__( 'Primary', 'numbered-accordion' ),
					'secondary' => esc_html__( 'Secondary', 'numbered-accordion' ),
				),
				'default'     => 'primary',
			)
		);

		$repeater->add_control(
			'card_title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Card title', 'numbered-accordion' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_image',
			array(
				'label'   => esc_html__( 'Icon', 'numbered-accordion' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		/* ---------------------------------------------------- figure --- */

		$repeater->add_control(
			'stat_value',
			array(
				'label'       => esc_html__( 'Figure', 'numbered-accordion' ),
				'description' => esc_html__( 'A plain number counts up from zero. Anything else — a range, a plus sign, a percentage — is shown exactly as typed.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'stat' ),
			)
		);

		$repeater->add_control(
			'stat_unit',
			array(
				'label'       => esc_html__( 'Unit', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'stat' ),
			)
		);

		$repeater->add_control(
			'stat_sub_value',
			array(
				'label'       => esc_html__( 'Second figure', 'numbered-accordion' ),
				'description' => esc_html__( 'Shown below the rule. Leave empty to hide.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'stat' ),
			)
		);

		$repeater->add_control(
			'stat_sub_unit',
			array(
				'label'       => esc_html__( 'Second unit', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'stat' ),
			)
		);

		$repeater->add_control(
			'stat_caption',
			array(
				'label'       => esc_html__( 'Caption', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'stat' ),
			)
		);

		/* ------------------------------------------------- checklist --- */

		$repeater->add_control(
			'list_items',
			array(
				'label'       => esc_html__( 'Checklist', 'numbered-accordion' ),
				'description' => esc_html__( 'One item per line. Start a line with a dash to nest it as a plain bullet under the item above.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'card_type' => 'list' ),
			)
		);

		$this->add_control(
			'cards',
			array(
				'label'       => esc_html__( 'Cards', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ card_title }}}',
				'default'     => $this->default_cards(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The six cards the widget ships with.
	 *
	 * @return array
	 */
	private function default_cards() {
		return array(
			array(
				'card_type'      => 'stat',
				'card_accent'    => 'primary',
				'card_title'     => __( 'Total Energy Savings', 'numbered-accordion' ),
				'stat_value'     => '165,000,000',
				'stat_unit'      => __( 'kWh', 'numbered-accordion' ),
				'stat_sub_value' => '5,631,400',
				'stat_sub_unit'  => __( 'therms', 'numbered-accordion' ),
				'stat_caption'   => __( 'Total energy saved', 'numbered-accordion' ),
			),
			array(
				'card_type'      => 'stat',
				'card_accent'    => 'secondary',
				'card_title'     => __( 'CO₂ Reduction', 'numbered-accordion' ),
				'stat_value'     => '26,015',
				'stat_unit'      => __( 'metric tons', 'numbered-accordion' ),
				'stat_sub_value' => '57,352,423',
				'stat_sub_unit'  => __( 'lbs CO₂', 'numbered-accordion' ),
				'stat_caption'   => __( 'Estimated 5-year CO₂ reduction', 'numbered-accordion' ),
			),
			array(
				'card_type'    => 'stat',
				'card_accent'  => 'primary',
				'card_title'   => __( 'Environmental Equivalent', 'numbered-accordion' ),
				'stat_value'   => '30,400',
				'stat_unit'    => __( 'acres of forest', 'numbered-accordion' ),
				'stat_caption' => __( '5-year CO₂ reduction = one year of CO₂ sequestration', 'numbered-accordion' ),
			),
			array(
				'card_type'   => 'list',
				'card_accent' => 'primary',
				'card_title'  => __( 'Continuous Energy Recovery', 'numbered-accordion' ),
				'list_items'  => __( "Operates independent of weather conditions\nMore exhaust heat = more recoverable energy", 'numbered-accordion' ),
			),
			array(
				'card_type'   => 'list',
				'card_accent' => 'primary',
				'card_title'  => __( 'Low Deployment Risk', 'numbered-accordion' ),
				'list_items'  => __( "Minimal permitting complexity\nDesigned for retrofit and new-build applications", 'numbered-accordion' ),
			),
			array(
				'card_type'   => 'list',
				'card_accent' => 'secondary',
				'card_title'  => __( 'Measurable & Verified', 'numbered-accordion' ),
				'list_items'  => __( "Cloud-connected ThermStar Power Intelligence™\n- Real-time performance monitoring\n- Decarbonization reporting", 'numbered-accordion' ),
			),
		);
	}

	/**
	 * Content tab: numbering and markup settings.
	 */
	private function register_settings_controls() {
		$this->start_controls_section(
			'section_settings',
			array(
				'label' => esc_html__( 'Settings', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title HTML tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_title_tags(),
				'default' => 'h3',
			)
		);

		$this->add_control(
			'number_format',
			array(
				'label'   => esc_html__( 'Number format', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'plain' => esc_html__( 'Plain (1, 2)', 'numbered-accordion' ),
					'pad'   => esc_html__( 'Padded (01, 02)', 'numbered-accordion' ),
					'none'  => esc_html__( 'Hidden', 'numbered-accordion' ),
				),
				'default' => 'plain',
			)
		);

		$this->add_control(
			'number_start',
			array(
				'label'     => esc_html__( 'Start counting at', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 99,
				'step'      => 1,
				'default'   => 1,
				'condition' => array( 'number_format!' => 'none' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the grid itself.
	 */
	private function register_layout_style_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => esc_html__( 'Layout', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'numbered-accordion' ),
				'type'           => Controls_Manager::SELECT,
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'selectors'      => array(
					'{{WRAPPER}} .eimp' => '--eimp-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Gap', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'numbered-accordion' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'numbered-accordion' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Centre', 'numbered-accordion' ),
						'icon'  => 'eicon-text-align-center',
					),
				),
				'default'   => 'center',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-align: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the card shell.
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FBFBF8',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-card-bg: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => '28',
					'right'    => '26',
					'bottom'   => '30',
					'left'     => '26',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .eimp-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 14,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'card_border_width',
			array(
				'label'      => esc_html__( 'Border thickness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 2,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-border-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'card_border_opacity',
			array(
				'label'       => esc_html__( 'Border strength', 'numbered-accordion' ),
				'description' => esc_html__( 'How much of the card accent the border shows.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
				'default'     => array(
					'unit' => 'px',
					'size' => 0.55,
				),
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-border-alpha: {{SIZE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .eimp-card',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the two accent colours every card picks from.
	 */
	private function register_accent_style_controls() {
		$this->start_controls_section(
			'section_style_accents',
			array(
				'label' => esc_html__( 'Accents', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_primary',
			array(
				'label'     => esc_html__( 'Primary', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2F6B33',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-primary: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent_secondary',
			array(
				'label'     => esc_html__( 'Secondary', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1C3F94',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-secondary: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the numbered badge.
	 */
	private function register_badge_style_controls() {
		$this->start_controls_section(
			'section_style_badge',
			array(
				'label'     => esc_html__( 'Number badge', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'number_format!' => 'none' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'badge_typography',
				'selector'       => '{{WRAPPER}} .eimp-card__badge',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 20 ) ),
					'font_weight' => array( 'default' => '700' ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1 ) ),
				),
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Number colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-badge-fg: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'badge_size',
			array(
				'label'      => esc_html__( 'Diameter', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 96 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 46,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-badge-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: card titles.
	 */
	private function register_title_style_controls() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Title', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'title_typography',
				'selector'       => '{{WRAPPER}} .eimp-card__title',
				'fields_options' => array(
					'font_size'      => array( 'default' => array( 'unit' => 'px', 'size' => 21 ) ),
					'font_weight'    => array( 'default' => '800' ),
					'line_height'    => array( 'default' => array( 'unit' => 'em', 'size' => 1.15 ) ),
					'text_transform' => array( 'default' => 'uppercase' ),
					'letter_spacing' => array( 'default' => array( 'unit' => 'px', 'size' => 0.2 ) ),
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'       => esc_html__( 'Colour', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave empty to use each card\'s accent.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-title: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the uploaded card icon.
	 */
	private function register_icon_style_controls() {
		$this->start_controls_section(
			'section_style_icon',
			array(
				'label' => esc_html__( 'Icon', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Maximum width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 40, 'max' => 320 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 150,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-icon-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'icon_spacing',
			array(
				'label'      => esc_html__( 'Space around', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 18,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-icon-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: figures, units, rule and caption.
	 */
	private function register_figure_style_controls() {
		$this->start_controls_section(
			'section_style_figure',
			array(
				'label' => esc_html__( 'Figures', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'figure_typography',
				'selector'       => '{{WRAPPER}} .eimp-figure--lead .eimp-figure__value',
				'fields_options' => array(
					'font_size'      => array( 'default' => array( 'unit' => 'px', 'size' => 46 ) ),
					'font_weight'    => array( 'default' => '800' ),
					'line_height'    => array( 'default' => array( 'unit' => 'em', 'size' => 1 ) ),
					'letter_spacing' => array( 'default' => array( 'unit' => 'px', 'size' => -1 ) ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'figure_unit_typography',
				'label'          => esc_html__( 'Unit typography', 'numbered-accordion' ),
				'selector'       => '{{WRAPPER}} .eimp-figure--lead .eimp-figure__unit',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 20 ) ),
					'font_weight' => array( 'default' => '700' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'figure_sub_typography',
				'label'          => esc_html__( 'Second figure typography', 'numbered-accordion' ),
				'selector'       => '{{WRAPPER}} .eimp-figure--sub',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 30 ) ),
					'font_weight' => array( 'default' => '800' ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1.1 ) ),
				),
			)
		);

		$this->add_control(
			'figure_color',
			array(
				'label'       => esc_html__( 'Colour', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave empty to use each card\'s accent.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-figure: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'rule_heading',
			array(
				'label'     => esc_html__( 'Rule', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'rule_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#C9CFC6',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-rule: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'rule_width',
			array(
				'label'      => esc_html__( 'Thickness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 6 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-rule-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'caption_heading',
			array(
				'label'     => esc_html__( 'Caption', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'caption_typography',
				'selector'       => '{{WRAPPER}} .eimp-card__caption',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 16 ) ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1.45 ) ),
				),
			)
		);

		$this->add_control(
			'caption_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2C3A44',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-caption: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: checklist cards.
	 */
	private function register_checklist_style_controls() {
		$this->start_controls_section(
			'section_style_checks',
			array(
				'label' => esc_html__( 'Checklist', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'check_typography',
				'selector'       => '{{WRAPPER}} .eimp-check__text',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 17 ) ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1.45 ) ),
				),
			)
		);

		$this->add_control(
			'check_color',
			array(
				'label'     => esc_html__( 'Text colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2C3A44',
				'selectors' => array( '{{WRAPPER}} .eimp' => '--eimp-check-text: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'tick_color',
			array(
				'label'       => esc_html__( 'Tick colour', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave empty to use each card\'s accent.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-tick: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'check_gap',
			array(
				'label'      => esc_html__( 'Space between items', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 14,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-check-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: motion.
	 */
	private function register_motion_controls() {
		$this->start_controls_section(
			'section_style_motion',
			array(
				'label' => esc_html__( 'Motion', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'motion_duration',
			array(
				'label'      => esc_html__( 'Reveal duration (ms)', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 2000, 'step' => 25 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 800,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-duration: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'motion_stagger',
			array(
				'label'       => esc_html__( 'Stagger between cards (ms)', 'numbered-accordion' ),
				'description' => esc_html__( 'Each card waits this much longer than the one before it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 400, 'step' => 10 ) ),
				'default'     => array(
					'unit' => 'px',
					'size' => 90,
				),
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-stagger: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'motion_rise',
			array(
				'label'      => esc_html__( 'Rise distance', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-rise: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'motion_count',
			array(
				'label'       => esc_html__( 'Count-up duration (ms)', 'numbered-accordion' ),
				'description' => esc_html__( 'Set to zero to print figures without counting.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 4000, 'step' => 50 ) ),
				'default'     => array(
					'unit' => 'px',
					'size' => 1600,
				),
				'selectors'   => array( '{{WRAPPER}} .eimp' => '--eimp-count-duration: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'motion_lift',
			array(
				'label'      => esc_html__( 'Hover lift', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors'  => array( '{{WRAPPER}} .eimp' => '--eimp-lift: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'motion_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Motion is automatically disabled for visitors who have "reduce motion" enabled in their operating system: cards appear in place and figures print their final value.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Does a card carry anything worth rendering?
	 *
	 * A card is skipped only when it is genuinely empty. Skipping on a blank
	 * title alone would drop a card that is nothing but a figure.
	 *
	 * @param array $card Repeater row.
	 * @return bool
	 */
	private function card_has_content( $card ) {
		foreach ( array( 'card_title', 'stat_value', 'stat_sub_value', 'stat_caption', 'list_items' ) as $key ) {
			if ( isset( $card[ $key ] ) && '' !== trim( (string) $card[ $key ] ) ) {
				return true;
			}
		}

		return isset( $card['card_image']['url'] ) && '' !== $card['card_image']['url'];
	}

	/**
	 * Render one figure line.
	 *
	 * @param string $value    Figure as typed.
	 * @param string $unit     Unit label.
	 * @param string $modifier Either 'lead' or 'sub'.
	 */
	private function render_figure( $value, $unit, $modifier ) {
		if ( '' === $value && '' === $unit ) {
			return;
		}

		$countable = Impact_Content::is_countable( $value );
		?>
		<p class="eimp-figure eimp-figure--<?php echo esc_attr( $modifier ); ?>">
			<?php if ( '' !== $value ) : ?>
				<?php if ( $countable ) : ?>
					<?php
					/*
					 * While the count-up runs, this element's text is a number
					 * on its way to the real one. Hiding it from assistive
					 * tech and carrying the true value in a visually hidden
					 * sibling means a screen reader never reads a figure that
					 * was only ever a frame of an animation.
					 */
					?>
					<span class="eimp-figure__value"
						data-eimp-count="<?php echo esc_attr( str_replace( ',', '', trim( $value ) ) ); ?>"
						aria-hidden="true"><?php echo esc_html( $value ); ?></span>
					<span class="eimp-sr-only"><?php echo esc_html( $value ); ?></span>
				<?php else : ?>
					<span class="eimp-figure__value"><?php echo esc_html( $value ); ?></span>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( '' !== $unit ) : ?>
				<span class="eimp-figure__unit"><?php echo esc_html( $unit ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Render a card's uploaded icon.
	 *
	 * @param array $card Repeater row.
	 */
	private function render_media( $card ) {
		$image = isset( $card['card_image'] ) && is_array( $card['card_image'] ) ? $card['card_image'] : array();
		$url   = isset( $image['url'] ) ? $image['url'] : '';
		$id    = isset( $image['id'] ) ? (int) $image['id'] : 0;

		if ( '' === $url ) {
			return;
		}
		?>
		<div class="eimp-card__media">
			<?php
			if ( $id > 0 ) {
				// Goes through the media library, so WordPress supplies both
				// the alt text and a srcset.
				echo wp_get_attachment_image(
					$id,
					'medium',
					false,
					array(
						'class'   => 'eimp-card__img',
						'loading' => 'lazy',
					)
				);
			} else {
				?>
				<img class="eimp-card__img" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy" />
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$cards = isset( $settings['cards'] ) && is_array( $settings['cards'] ) ? $settings['cards'] : array();

		if ( empty( $cards ) ) {
			return;
		}

		$tags      = $this->get_title_tags();
		$title_tag = isset( $settings['title_tag'] ) && isset( $tags[ $settings['title_tag'] ] ) ? $settings['title_tag'] : 'h3';

		$format = isset( $settings['number_format'] ) ? $settings['number_format'] : 'plain';
		$start  = ( isset( $settings['number_start'] ) && '' !== $settings['number_start'] ) ? (int) $settings['number_start'] : 1;

		/*
		 * Counts rendered cards, not repeater rows. An empty row left behind
		 * in the panel must not put a gap in the 1..6 sequence, nor shift the
		 * reveal stagger of everything after it.
		 */
		$position = 0;
		?>
		<div class="eimp">
			<?php
			foreach ( $cards as $card ) {
				if ( ! $this->card_has_content( $card ) ) {
					continue;
				}

				$type   = ( isset( $card['card_type'] ) && 'list' === $card['card_type'] ) ? 'list' : 'stat';
				$accent = ( isset( $card['card_accent'] ) && 'secondary' === $card['card_accent'] ) ? 'secondary' : 'primary';
				$title  = isset( $card['card_title'] ) ? trim( $card['card_title'] ) : '';
				$number = Impact_Content::format_number( $position, $format, $start );

				$classes = sprintf( 'eimp-card eimp-card--%s eimp-card--%s', $accent, $type );

				// Drives the reveal stagger from CSS, so the script never has
				// to write inline styles card by card.
				$style = '--eimp-i:' . $position;

				++$position;
				?>
				<article class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>">
					<?php if ( '' !== $number || '' !== $title ) : ?>
						<div class="eimp-card__head">
							<?php if ( '' !== $number ) : ?>
								<span class="eimp-card__badge" aria-hidden="true"><?php echo esc_html( $number ); ?></span>
							<?php endif; ?>

							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_html( $title_tag ); ?> class="eimp-card__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $title_tag ); ?>>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php $this->render_media( $card ); ?>

					<?php if ( 'stat' === $type ) : ?>
						<?php
						$value     = isset( $card['stat_value'] ) ? trim( $card['stat_value'] ) : '';
						$unit      = isset( $card['stat_unit'] ) ? trim( $card['stat_unit'] ) : '';
						$sub_value = isset( $card['stat_sub_value'] ) ? trim( $card['stat_sub_value'] ) : '';
						$sub_unit  = isset( $card['stat_sub_unit'] ) ? trim( $card['stat_sub_unit'] ) : '';
						$caption   = isset( $card['stat_caption'] ) ? trim( $card['stat_caption'] ) : '';

						$has_tail = ( '' !== $sub_value || '' !== $sub_unit || '' !== $caption );
						?>
						<div class="eimp-card__body">
							<?php $this->render_figure( $value, $unit, 'lead' ); ?>

							<?php if ( $has_tail ) : ?>
								<span class="eimp-card__rule" aria-hidden="true"></span>
							<?php endif; ?>

							<?php $this->render_figure( $sub_value, $sub_unit, 'sub' ); ?>

							<?php if ( '' !== $caption ) : ?>
								<p class="eimp-card__caption"><?php echo esc_html( $caption ); ?></p>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<?php
						$items = Impact_Content::parse_list( isset( $card['list_items'] ) ? $card['list_items'] : '' );

						if ( ! empty( $items ) ) :
							// One running counter across items and their
							// bullets, so every line joins the same cascade.
							$row = 0;
							?>
							<div class="eimp-card__body">
								<ul class="eimp-checks">
									<?php foreach ( $items as $item ) : ?>
										<li class="eimp-check" style="<?php echo esc_attr( '--eimp-j:' . $row ); ?>">
											<?php ++$row; ?>
											<span class="eimp-check__tick" aria-hidden="true">
												<svg viewBox="0 0 24 24" focusable="false">
													<circle class="eimp-tick__ring" cx="12" cy="12" r="10" />
													<path class="eimp-tick__mark" d="M7.5 12.4l3.1 3.1 6-6.4" />
												</svg>
											</span>
											<span class="eimp-check__text"><?php echo esc_html( $item['text'] ); ?></span>

											<?php if ( ! empty( $item['sub'] ) ) : ?>
												<ul class="eimp-check__sub">
													<?php foreach ( $item['sub'] as $sub ) : ?>
														<li style="<?php echo esc_attr( '--eimp-j:' . $row ); ?>"><?php echo esc_html( $sub ); ?></li>
														<?php ++$row; ?>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</article>
				<?php
			}
			?>
		</div>
		<?php
	}
}
