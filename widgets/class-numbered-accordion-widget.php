<?php
/**
 * Numbered Accordion widget.
 *
 * @package NumberedAccordion
 */

namespace NumberedAccordion\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A numbered, animated accordion.
 */
class Numbered_Accordion_Widget extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'nacc-numbered-accordion';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Numbered Accordion', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-accordion';
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
		return array( 'accordion', 'toggle', 'numbered', 'list', 'faq', 'features' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \NumberedAccordion\Plugin::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \NumberedAccordion\Plugin::SCRIPT_HANDLE );
	}

	/**
	 * Allowed wrapper tags for item titles.
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
		$this->register_content_controls();
		$this->register_settings_controls();
		$this->register_item_style_controls();
		$this->register_number_style_controls();
		$this->register_eyebrow_style_controls();
		$this->register_title_style_controls();
		$this->register_description_style_controls();
		$this->register_motion_controls();
	}

	/**
	 * Content tab: the repeater of accordion items.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_items',
			array(
				'label' => esc_html__( 'Items', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'item_title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Accordion item', 'numbered-accordion' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'description' => esc_html__( 'Small label shown above the title while the item is open. Leave empty to hide.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_desc',
			array(
				'label'       => esc_html__( 'Description', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => '',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_open',
			array(
				'label'        => esc_html__( 'Open by default', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'numbered-accordion' ),
				'label_off'    => esc_html__( 'No', 'numbered-accordion' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Accordion items', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ item_title }}}',
				'default'     => array(
					array(
						'item_title'   => __( 'Lepido™ Heat Recovery Unit', 'numbered-accordion' ),
						'item_eyebrow' => __( 'Recover', 'numbered-accordion' ),
						'item_desc'    => __( 'Captures waste heat from the exhaust stream before it leaves the building, then hands it back to the system that needs it most.', 'numbered-accordion' ),
						'item_open'    => '',
					),
					array(
						'item_title'   => __( 'Closed-Loop Thermal Transfer', 'numbered-accordion' ),
						'item_eyebrow' => __( 'Deliver', 'numbered-accordion' ),
						'item_desc'    => __( 'A sealed glycol circuit carries recovered energy from the exhaust to wherever the heat is needed. Contaminated exhaust and clean building air never meet. Designed for rooftop or in-building installation.', 'numbered-accordion' ),
						'item_open'    => 'yes',
					),
					array(
						'item_title'   => __( 'HeatCore HX™ Coil', 'numbered-accordion' ),
						'item_eyebrow' => __( 'Transfer', 'numbered-accordion' ),
						'item_desc'    => __( 'A high-surface-area exchanger built for grease-laden and particulate-heavy air, sized to keep pressure drop low across the run.', 'numbered-accordion' ),
						'item_open'    => '',
					),
					array(
						'item_title'   => __( 'ThermStar Power Intelligence™', 'numbered-accordion' ),
						'item_eyebrow' => __( 'Control', 'numbered-accordion' ),
						'item_desc'    => __( 'Continuous monitoring balances recovery against demand in real time and reports verified savings back to the building management system.', 'numbered-accordion' ),
						'item_open'    => '',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: behaviour settings.
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
					'pad'   => esc_html__( 'Padded (01, 02)', 'numbered-accordion' ),
					'plain' => esc_html__( 'Plain (1, 2)', 'numbered-accordion' ),
					'none'  => esc_html__( 'Hidden', 'numbered-accordion' ),
				),
				'default' => 'pad',
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

		$this->add_control(
			'allow_multiple',
			array(
				'label'        => esc_html__( 'Allow multiple open', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'allow_collapse_all',
			array(
				'label'        => esc_html__( 'Allow closing every item', 'numbered-accordion' ),
				'description'  => esc_html__( 'When off, one item always stays open.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: item rows and dividers.
	 */
	private function register_item_style_controls() {
		$this->start_controls_section(
			'section_style_item',
			array(
				'label' => esc_html__( 'Item', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => esc_html__( 'Row padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => '26',
					'right'    => '0',
					'bottom'   => '26',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .nacc-item__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'panel_padding',
			array(
				'label'      => esc_html__( 'Open panel padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '30',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .nacc-item__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'divider_heading',
			array(
				'label'     => esc_html__( 'Divider', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'divider_width',
			array(
				'label'      => esc_html__( 'Thickness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .nacc' => '--nacc-divider-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#E3E7EA',
				'selectors' => array(
					'{{WRAPPER}} .nacc' => '--nacc-divider: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'divider_color_active',
			array(
				'label'       => esc_html__( 'Colour when open', 'numbered-accordion' ),
				'description' => esc_html__( 'Sweeps in from the left as the item opens.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#2F7A4E',
				'selectors'   => array(
					'{{WRAPPER}} .nacc' => '--nacc-divider-active: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'first_divider',
			array(
				'label'        => esc_html__( 'Line above first item', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the numbers.
	 */
	private function register_number_style_controls() {
		$this->start_controls_section(
			'section_style_number',
			array(
				'label'     => esc_html__( 'Number', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'number_format!' => 'none' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'number_typography',
				'selector' => '{{WRAPPER}} .nacc-item__number',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 30 ) ),
					'font_weight' => array( 'default' => '700' ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1 ) ),
				),
			)
		);

		$this->add_control(
			'number_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#7FA98C',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-number: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'number_color_hover',
			array(
				'label'     => esc_html__( 'Colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4E9670',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-number-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'number_color_active',
			array(
				'label'     => esc_html__( 'Colour when open', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2F7A4E',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-number-active: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'number_gap',
			array(
				'label'      => esc_html__( 'Gap from title', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .nacc-item__trigger' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the eyebrow label.
	 */
	private function register_eyebrow_style_controls() {
		$this->start_controls_section(
			'section_style_eyebrow',
			array(
				'label' => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'eyebrow_typography',
				'selector'       => '{{WRAPPER}} .nacc-item__eyebrow',
				'fields_options' => array(
					'font_size'      => array( 'default' => array( 'unit' => 'px', 'size' => 12 ) ),
					'font_weight'    => array( 'default' => '700' ),
					'text_transform' => array( 'default' => 'uppercase' ),
					'letter_spacing' => array( 'default' => array( 'unit' => 'px', 'size' => 1.2 ) ),
				),
			)
		);

		$this->add_control(
			'eyebrow_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2F7A4E',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-eyebrow: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'eyebrow_spacing',
			array(
				'label'      => esc_html__( 'Space below', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .nacc-item__eyebrow' => 'padding-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the titles.
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
				'selector'       => '{{WRAPPER}} .nacc-item__title',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 28 ) ),
					'font_weight' => array( 'default' => '600' ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1.25 ) ),
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5B7186',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-title: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'title_color_hover',
			array(
				'label'     => esc_html__( 'Colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#33536F',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-title-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'title_color_active',
			array(
				'label'     => esc_html__( 'Colour when open', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#22405C',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-title-active: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: the description text.
	 */
	private function register_description_style_controls() {
		$this->start_controls_section(
			'section_style_desc',
			array(
				'label' => esc_html__( 'Description', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'desc_typography',
				'selector'       => '{{WRAPPER}} .nacc-item__desc',
				'fields_options' => array(
					'font_size'   => array( 'default' => array( 'unit' => 'px', 'size' => 17 ) ),
					'line_height' => array( 'default' => array( 'unit' => 'em', 'size' => 1.7 ) ),
				),
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3E5A70',
				'selectors' => array( '{{WRAPPER}} .nacc' => '--nacc-desc: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'desc_max_width',
			array(
				'label'      => esc_html__( 'Maximum width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 200, 'max' => 1200 ),
					'%'  => array( 'min' => 20, 'max' => 100 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 100 ),
				'selectors'  => array( '{{WRAPPER}} .nacc-item__desc' => 'max-width: {{SIZE}}{{UNIT}};' ),
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
				'label'      => esc_html__( 'Duration (ms)', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 1200, 'step' => 25 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 450 ),
				'selectors'  => array( '{{WRAPPER}} .nacc' => '--nacc-duration: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'motion_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Motion is automatically disabled for visitors who have "reduce motion" enabled in their operating system.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Format an item number.
	 *
	 * @param int    $index  Zero-based item index.
	 * @param array  $settings Widget settings.
	 * @return string Empty string when numbering is off.
	 */
	private function format_number( $index, $settings ) {
		$format = isset( $settings['number_format'] ) ? $settings['number_format'] : 'pad';

		if ( 'none' === $format ) {
			return '';
		}

		$start = isset( $settings['number_start'] ) && '' !== $settings['number_start'] ? (int) $settings['number_start'] : 1;
		$value = $start + $index;

		if ( 'pad' === $format ) {
			return str_pad( (string) $value, 2, '0', STR_PAD_LEFT );
		}

		return (string) $value;
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$items = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$tags      = $this->get_title_tags();
		$title_tag = isset( $settings['title_tag'] ) && isset( $tags[ $settings['title_tag'] ] ) ? $settings['title_tag'] : 'h3';

		$multiple     = ( isset( $settings['allow_multiple'] ) && 'yes' === $settings['allow_multiple'] ) ? 'yes' : 'no';
		$collapse_all = ( isset( $settings['allow_collapse_all'] ) && 'yes' === $settings['allow_collapse_all'] ) ? 'yes' : 'no';
		$first_rule   = ( isset( $settings['first_divider'] ) && 'yes' === $settings['first_divider'] ) ? ' nacc--top-rule' : '';

		$widget_id = $this->get_id();

		// If closing everything is disallowed, make sure at least one item starts open.
		$has_open = false;
		foreach ( $items as $item ) {
			if ( isset( $item['item_open'] ) && 'yes' === $item['item_open'] ) {
				$has_open = true;
				break;
			}
		}
		$force_first_open = ( ! $has_open && 'no' === $collapse_all );

		?>
		<div class="nacc<?php echo esc_attr( $first_rule ); ?>"
			data-multiple="<?php echo esc_attr( $multiple ); ?>"
			data-collapse-all="<?php echo esc_attr( $collapse_all ); ?>">
			<?php
			foreach ( array_values( $items ) as $index => $item ) {
				$title = isset( $item['item_title'] ) ? trim( $item['item_title'] ) : '';

				if ( '' === $title ) {
					continue;
				}

				$eyebrow = isset( $item['item_eyebrow'] ) ? trim( $item['item_eyebrow'] ) : '';
				$desc    = isset( $item['item_desc'] ) ? trim( $item['item_desc'] ) : '';
				$number  = $this->format_number( $index, $settings );

				$is_open = ( isset( $item['item_open'] ) && 'yes' === $item['item_open'] ) || ( $force_first_open && 0 === $index );

				$uid          = $widget_id . '-' . ( isset( $item['_id'] ) ? $item['_id'] : (string) $index );
				$trigger_id   = 'nacc-trigger-' . $uid;
				$panel_id     = 'nacc-panel-' . $uid;
				$has_panel    = ( '' !== $desc );
				$item_classes = 'nacc-item' . ( $is_open ? ' is-open' : '' );
				?>
				<div class="<?php echo esc_attr( $item_classes ); ?>">
					<<?php echo esc_html( $title_tag ); ?> class="nacc-item__head">
						<button type="button"
							class="nacc-item__trigger"
							id="<?php echo esc_attr( $trigger_id ); ?>"
							aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
							<?php if ( $has_panel ) : ?>
								aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							<?php endif; ?>>
							<span class="nacc-item__label">
								<?php if ( '' !== $eyebrow ) : ?>
									<span class="nacc-item__eyebrow-wrap nacc-collapse">
										<span class="nacc-collapse__inner">
											<span class="nacc-item__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
										</span>
									</span>
								<?php endif; ?>
								<span class="nacc-item__title"><?php echo esc_html( $title ); ?></span>
							</span>
							<?php if ( '' !== $number ) : ?>
								<span class="nacc-item__number" aria-hidden="true"><?php echo esc_html( $number ); ?></span>
							<?php endif; ?>
						</button>
					</<?php echo esc_html( $title_tag ); ?>>

					<?php if ( $has_panel ) : ?>
						<div class="nacc-item__panel nacc-collapse"
							id="<?php echo esc_attr( $panel_id ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>">
							<div class="nacc-collapse__inner">
								<div class="nacc-item__body">
									<p class="nacc-item__desc"><?php echo wp_kses_post( nl2br( $desc ) ); ?></p>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
