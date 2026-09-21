<?php
/**
 * Flow Schematic widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Schematic\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Schematic\Schematic_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A process diagram: stages in a row, connectors between them, and the three
 * bands a real system drawing needs -- a return path under the middle, a
 * monitoring bar over it, and a boundary line through it.
 *
 * Drawn in CSS rather than exported as a picture, so the labels stay text.
 */
class Flow_Schematic_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'efs-flow-schematic';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Flow Schematic', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-flow';
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
		return array( 'schematic', 'diagram', 'flow', 'process', 'system', 'loop', 'architecture', 'stages', 'chart' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Schematic\Schematic_Module::STYLE_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_stage_controls();
		$this->register_source_controls();
		$this->register_output_controls();
		$this->register_band_controls();
		$this->register_canvas_style_controls();
		$this->register_node_style_controls();
		$this->register_line_style_controls();
		$this->register_type_style_controls();
	}

	/**
	 * The stages themselves.
	 */
	private function register_stage_controls() {
		$this->start_controls_section(
			'section_stages',
			array( 'label' => esc_html__( 'Stages', 'numbered-accordion' ) )
		);

		$stage = new Repeater();

		$stage->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Stage', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$stage->add_control(
			'eyebrow',
			array(
				'label'       => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$stage->add_control(
			'meta',
			array(
				'label'       => esc_html__( 'Detail line', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$stage->add_control(
			'num',
			array(
				'label'       => esc_html__( 'Pin number', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Leave empty for no pin.', 'numbered-accordion' ),
			)
		);

		$stage->add_control(
			'variant',
			array(
				'label'   => esc_html__( 'Treatment', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'standard',
				'options' => array(
					'standard' => esc_html__( 'Standard', 'numbered-accordion' ),
					'accent'   => esc_html__( 'Accent', 'numbered-accordion' ),
					'quiet'    => esc_html__( 'Quiet — no fill', 'numbered-accordion' ),
					'dashed'   => esc_html__( 'Dashed outline', 'numbered-accordion' ),
				),
			)
		);

		$stage->add_control(
			'link',
			array(
				'label' => esc_html__( 'Link', 'numbered-accordion' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$stage->add_control(
			'flow_label',
			array(
				'label'       => esc_html__( 'Label on the connector after it', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$stage->add_control(
			'flow_style',
			array(
				'label'   => esc_html__( 'Label sits', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'over',
				'options' => array(
					'over' => esc_html__( 'Above the line', 'numbered-accordion' ),
					'chip' => esc_html__( 'On the line', 'numbered-accordion' ),
				),
			)
		);

		$stage->add_control(
			'flow_tone',
			array(
				'label'   => esc_html__( 'Connector', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'flow',
				'options' => array(
					'flow'  => esc_html__( 'Accent', 'numbered-accordion' ),
					'quiet' => esc_html__( 'Quiet', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'stages',
			array(
				'label'       => esc_html__( 'Stages', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $stage->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'title'      => esc_html__( 'Source unit', 'numbered-accordion' ),
						'eyebrow'    => esc_html__( 'Recovery', 'numbered-accordion' ),
						'meta'       => esc_html__( 'air → water', 'numbered-accordion' ),
						'num'        => '01',
						'variant'    => 'accent',
						'flow_label' => esc_html__( 'heat transfer fluid, warmed', 'numbered-accordion' ),
						'flow_style' => 'over',
						'flow_tone'  => 'flow',
					),
					array(
						'title'      => esc_html__( 'Delivery unit', 'numbered-accordion' ),
						'eyebrow'    => esc_html__( 'Delivery', 'numbered-accordion' ),
						'meta'       => esc_html__( 'one — or several', 'numbered-accordion' ),
						'num'        => '02',
						'variant'    => 'standard',
						'flow_style' => 'over',
						'flow_tone'  => 'flow',
					),
				),
				'description' => sprintf(
					/* translators: %d: the largest number of stages the row can carry */
					esc_html__( 'Up to %d. Past that the row stops reading and the stacked layout is the better answer.', 'numbered-accordion' ),
					Schematic_Content::MAX_STAGES
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The block on the left, and what arrives from it.
	 */
	private function register_source_controls() {
		$this->start_controls_section(
			'section_source',
			array( 'label' => esc_html__( 'Source block', 'numbered-accordion' ) )
		);

		$this->add_control(
			'source_show',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'source_title',
			array(
				'label'       => esc_html__( 'Name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Process', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'source_show' => 'yes' ),
			)
		);

		$this->add_control(
			'source_meta',
			array(
				'label'       => esc_html__( 'Detail line', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'oven · washer · fryer', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'source_show' => 'yes' ),
			)
		);

		$this->add_control(
			'source_label',
			array(
				'label'       => esc_html__( 'Label on the connector', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'grease · lint · soot', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'source_show' => 'yes' ),
			)
		);

		$this->add_control(
			'source_style',
			array(
				'label'     => esc_html__( 'Label sits', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'chip',
				'options'   => array(
					'chip' => esc_html__( 'On the line', 'numbered-accordion' ),
					'over' => esc_html__( 'Above the line', 'numbered-accordion' ),
				),
				'condition' => array( 'source_show' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The group on the right.
	 */
	private function register_output_controls() {
		$this->start_controls_section(
			'section_outputs',
			array( 'label' => esc_html__( 'Targets', 'numbered-accordion' ) )
		);

		$this->add_control(
			'outs_show',
			array(
				'label'        => esc_html__( 'Show them', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$outs = new Repeater();

		$outs->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Target', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'outs',
			array(
				'label'       => esc_html__( 'Targets', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $outs->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array( 'text' => esc_html__( 'Process / makeup air', 'numbered-accordion' ) ),
					array( 'text' => esc_html__( 'Process water', 'numbered-accordion' ) ),
					array( 'text' => esc_html__( 'Boiler feedwater', 'numbered-accordion' ) ),
				),
				'condition'   => array( 'outs_show' => 'yes' ),
			)
		);

		$this->add_control(
			'outs_foot',
			array(
				'label'       => esc_html__( 'Footnote', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'condition'   => array( 'outs_show' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The return path, the monitoring bar and the boundary.
	 */
	private function register_band_controls() {
		$this->start_controls_section(
			'section_bands',
			array( 'label' => esc_html__( 'Return, monitoring, boundary', 'numbered-accordion' ) )
		);

		$this->add_control(
			'ret_heading',
			array(
				'label' => esc_html__( 'Return path', 'numbered-accordion' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'ret_show',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'ret_label',
			array(
				'label'       => esc_html__( 'Label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'returned to the source unit, cooled', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'ret_show' => 'yes' ),
			)
		);

		$this->add_control(
			'ret_markers',
			array(
				'label'       => esc_html__( 'Inline markers', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'pump-box',
				'options'     => array(
					''         => esc_html__( 'None', 'numbered-accordion' ),
					'pump'     => esc_html__( 'One — a pump', 'numbered-accordion' ),
					'pump-box' => esc_html__( 'Two — a pump and a vessel', 'numbered-accordion' ),
				),
				'description' => esc_html__( 'Small devices sitting on the return line.', 'numbered-accordion' ),
				'condition'   => array( 'ret_show' => 'yes' ),
			)
		);

		$this->add_control(
			'ret_from',
			array(
				'label'     => esc_html__( 'From stage', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => Schematic_Content::MAX_STAGES,
				'default'   => 1,
				'condition' => array( 'ret_show' => 'yes' ),
			)
		);

		$this->add_control(
			'ret_to',
			array(
				'label'     => esc_html__( 'To stage', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => Schematic_Content::MAX_STAGES,
				'default'   => 2,
				'condition' => array( 'ret_show' => 'yes' ),
			)
		);

		$this->add_control(
			'mon_heading',
			array(
				'label'     => esc_html__( 'Monitoring bar', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'mon_show',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'mon_label',
			array(
				'label'       => esc_html__( 'Label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Monitoring', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'mon_show' => 'yes' ),
			)
		);

		$this->add_control(
			'mon_from',
			array(
				'label'     => esc_html__( 'From stage', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => Schematic_Content::MAX_STAGES,
				'default'   => 1,
				'condition' => array( 'mon_show' => 'yes' ),
			)
		);

		$this->add_control(
			'mon_to',
			array(
				'label'     => esc_html__( 'To stage', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => Schematic_Content::MAX_STAGES,
				'default'   => 2,
				'condition' => array( 'mon_show' => 'yes' ),
			)
		);

		$this->add_control(
			'div_heading',
			array(
				'label'     => esc_html__( 'Boundary', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'div_show',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'div_label',
			array(
				'label'       => esc_html__( 'Label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'The two fluids never mix', 'numbered-accordion' ),
				'label_block' => true,
				'condition'   => array( 'div_show' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'div_at',
			array(
				'label'      => esc_html__( 'Where it stands', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 46,
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs__divide' => '--efs-at: {{SIZE}}%;',
				),
				'condition'  => array( 'div_show' => 'yes' ),
			)
		);

		$this->add_control(
			'zone_left',
			array(
				'label'       => esc_html__( 'Caption, left', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Contaminated side', 'numbered-accordion' ),
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'zone_right',
			array(
				'label'       => esc_html__( 'Caption, right', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Sealed circuit — clean side', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The canvas the diagram is drawn on.
	 */
	private function register_canvas_style_controls() {
		$this->start_controls_section(
			'section_style_canvas',
			array(
				'label' => esc_html__( 'Canvas', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'frame',
			array(
				'label'        => esc_html__( 'Draw the card around it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Off, the diagram sits straight on the section behind it.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'surface',
			array(
				'label'       => esc_html__( 'Surface', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'description' => esc_html__( 'Also painted behind every label that cuts a line, so it has to match what is actually behind the diagram.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .efs' => '--efs-surface: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'pad',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 90,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-pad-y: {{SIZE}}{{UNIT}}; --efs-pad-x: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'frame' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The boxes.
	 */
	private function register_node_style_controls() {
		$this->start_controls_section(
			'section_style_nodes',
			array(
				'label' => esc_html__( 'Nodes', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'node_bg',
			array(
				'label'     => esc_html__( 'Fill', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-node: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'node_line',
			array(
				'label'     => esc_html__( 'Outline', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-line: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'accent_fill',
			array(
				'label'     => esc_html__( 'Accent node fill', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-accent-fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'accent_line',
			array(
				'label'     => esc_html__( 'Accent node outline', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-accent-line: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'node_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'node_min',
			array(
				'label'      => esc_html__( 'Node height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 80,
						'max' => 260,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-node-min: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The lines between them.
	 */
	private function register_line_style_controls() {
		$this->start_controls_section(
			'section_style_lines',
			array(
				'label' => esc_html__( 'Connectors', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'flow_colour',
			array(
				'label'     => esc_html__( 'Accent line', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-flow: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'quiet_colour',
			array(
				'label'     => esc_html__( 'Quiet line', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-quiet: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'stroke',
			array(
				'label'      => esc_html__( 'Line weight', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 4,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-stroke: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'spread',
			array(
				'label'       => esc_html__( 'Run between stages', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array(
					'px' => array(
						'min'  => 0.4,
						'max'  => 4,
						'step' => 0.1,
					),
				),
				'default'     => array( 'size' => 1.4 ),
				'description' => esc_html__( 'How much of the width the connectors take against the nodes.', 'numbered-accordion' ),
			)
		);

		$this->add_responsive_control(
			'return_depth',
			array(
				'label'      => esc_html__( 'Return path depth', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 160,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-return: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'ret_show' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'monitor_depth',
			array(
				'label'      => esc_html__( 'Monitoring bar depth', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 30,
						'max' => 140,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .efs' => '--efs-monitor: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'mon_show' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Colours and faces for the words.
	 */
	private function register_type_style_controls() {
		$this->start_controls_section(
			'section_style_type',
			array(
				'label' => esc_html__( 'Text', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'muted_colour',
			array(
				'label'     => esc_html__( 'Secondary text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-muted: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'accent_colour',
			array(
				'label'     => esc_html__( 'Accent', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .efs' => '--efs-accent: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'type_title',
				'label'    => esc_html__( 'Node name', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .efs__t',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'type_eyebrow',
				'label'    => esc_html__( 'Eyebrow', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .efs__s',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'type_meta',
				'label'    => esc_html__( 'Detail line', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .efs__m',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'type_chip',
				'label'    => esc_html__( 'Connector labels', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .efs__chip, {{WRAPPER}} .efs__over, {{WRAPPER}} .efs__ret span',
			)
		);

		$this->end_controls_section();
	}

	/* ── rendering ──────────────────────────────────────────────────── */

	/**
	 * One connector cell.
	 *
	 * @param int    $column Grid column.
	 * @param string $label  Label, or an empty string.
	 * @param string $style  'chip' or 'over'.
	 * @param string $tone   'flow' or 'quiet'.
	 */
	private function render_link( $column, $label, $style, $tone ) {
		$classes = 'efs__link' . ( 'quiet' === $tone ? ' efs__link--quiet' : '' );
		$chip    = '' !== $label && 'chip' === $style;
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" style="grid-column:<?php echo (int) $column; ?>">
			<?php if ( '' !== $label && 'over' === $style ) : ?>
				<span class="efs__over"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<i class="efs__line"></i>
			<?php if ( $chip ) : ?>
				<span class="efs__chip efs__chip--ghost"><?php echo esc_html( $label ); ?></span>
				<i class="efs__line"></i>
			<?php endif; ?>
			<i class="efs__arrow"></i>
		</div>
		<?php
	}

	/**
	 * One stage.
	 *
	 * @param array $stage  Repeater row.
	 * @param int   $column Grid column.
	 * @param int   $index  Row index, for the link attribute key.
	 */
	private function render_stage( $stage, $column, $index ) {
		$variant = isset( $stage['variant'] ) ? (string) $stage['variant'] : 'standard';
		$classes = 'efs__node';

		if ( in_array( $variant, array( 'accent', 'quiet', 'dashed' ), true ) ) {
			$classes .= ' efs__node--' . $variant;
		}

		$url = isset( $stage['link']['url'] ) ? $stage['link']['url'] : '';
		$key = 'stage_link_' . $index;

		if ( '' !== $url ) {
			$this->add_link_attributes( $key, $stage['link'] );
		}

		$tag = '' !== $url ? 'a' : 'div';
		?>
		<div class="efs__cell" style="grid-column:<?php echo (int) $column; ?>">
			<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( $classes ); ?>"
				<?php
				if ( '' !== $url ) {
					$this->print_render_attribute_string( $key );
				}
				?>
			>
				<?php if ( ! empty( $stage['num'] ) ) : ?>
					<span class="efs__num"><?php echo esc_html( $stage['num'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $stage['title'] ) ) : ?>
					<span class="efs__t"><?php echo esc_html( $stage['title'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $stage['eyebrow'] ) ) : ?>
					<span class="efs__s"><?php echo esc_html( $stage['eyebrow'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $stage['meta'] ) ) : ?>
					<span class="efs__m"><?php echo esc_html( $stage['meta'] ); ?></span>
				<?php endif; ?>
			</<?php echo esc_attr( $tag ); ?>>
		</div>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$stages = isset( $settings['stages'] ) && is_array( $settings['stages'] ) ? $settings['stages'] : array();
		$stages = array_slice( $stages, 0, Schematic_Content::MAX_STAGES );
		$count  = count( $stages );

		if ( 0 === $count ) {
			return;
		}

		$outs    = isset( $settings['outs'] ) && is_array( $settings['outs'] ) ? $settings['outs'] : array();
		$source  = 'yes' === ( isset( $settings['source_show'] ) ? $settings['source_show'] : '' );
		$targets = 'yes' === ( isset( $settings['outs_show'] ) ? $settings['outs_show'] : '' ) && ! empty( $outs );
		$ret     = 'yes' === ( isset( $settings['ret_show'] ) ? $settings['ret_show'] : '' ) && $count > 1;
		$mon     = 'yes' === ( isset( $settings['mon_show'] ) ? $settings['mon_show'] : '' );
		$divide  = 'yes' === ( isset( $settings['div_show'] ) ? $settings['div_show'] : '' );

		$cols = Schematic_Content::columns(
			$count,
			$source,
			$targets,
			isset( $settings['spread'] ) ? $settings['spread'] : 1.4
		);

		$root = 'efs';
		$root .= 'yes' === ( isset( $settings['frame'] ) ? $settings['frame'] : 'yes' ) ? '' : ' efs--plain';
		$root .= $mon ? ' efs--mon' : '';
		$root .= $ret ? ' efs--ret' : '';

		$zone_left  = isset( $settings['zone_left'] ) ? (string) $settings['zone_left'] : '';
		$zone_right = isset( $settings['zone_right'] ) ? (string) $settings['zone_right'] : '';
		?>
		<div class="<?php echo esc_attr( $root ); ?>">

			<?php if ( '' !== $zone_left || '' !== $zone_right ) : ?>
				<div class="efs__zones">
					<span class="efs__zone"><?php echo esc_html( $zone_left ); ?></span>
					<span class="efs__zone"><?php echo esc_html( $zone_right ); ?></span>
				</div>
			<?php endif; ?>

			<div class="efs__grid" style="--efs-cols:<?php echo esc_attr( $cols ); ?>">

				<?php
				if ( $mon ) {
					list( $mon_start, $mon_end ) = Schematic_Content::span(
						isset( $settings['mon_from'] ) ? $settings['mon_from'] : 1,
						isset( $settings['mon_to'] ) ? $settings['mon_to'] : $count,
						$count,
						$source
					);
					?>
					<div class="efs__mon" style="grid-column:<?php echo (int) $mon_start; ?>/<?php echo (int) $mon_end; ?>">
						<?php if ( ! empty( $settings['mon_label'] ) ) : ?>
							<span class="efs__chip efs__chip--accent"><?php echo esc_html( $settings['mon_label'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php
					// A drop onto each stage the bar reads.
					for ( $i = 1; $i <= $count; $i++ ) {
						$column = Schematic_Content::stage_column( $i, $source );

						if ( $column < $mon_start || $column >= $mon_end ) {
							continue;
						}
						?>
						<div class="efs__tap" style="grid-column:<?php echo (int) $column; ?>"></div>
						<?php
					}
				}
				?>

				<?php if ( $source ) : ?>
					<div class="efs__cell" style="grid-column:1">
						<div class="efs__node efs__node--quiet">
							<?php if ( ! empty( $settings['source_title'] ) ) : ?>
								<span class="efs__t"><?php echo esc_html( $settings['source_title'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $settings['source_meta'] ) ) : ?>
								<span class="efs__m"><?php echo esc_html( $settings['source_meta'] ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<?php
					$this->render_link(
						2,
						isset( $settings['source_label'] ) ? (string) $settings['source_label'] : '',
						isset( $settings['source_style'] ) ? (string) $settings['source_style'] : 'chip',
						'quiet'
					);
					?>
				<?php endif; ?>

				<?php
				foreach ( $stages as $index => $stage ) {
					$number = $index + 1;

					$this->render_stage( $stage, Schematic_Content::stage_column( $number, $source ), $index );

					$last = ( $number === $count );

					// The connector after a stage exists between stages, and
					// once more on the way out to the targets.
					if ( ! $last || $targets ) {
						$this->render_link(
							Schematic_Content::link_column( $number, $source ),
							isset( $stage['flow_label'] ) ? (string) $stage['flow_label'] : '',
							isset( $stage['flow_style'] ) ? (string) $stage['flow_style'] : 'over',
							isset( $stage['flow_tone'] ) ? (string) $stage['flow_tone'] : 'flow'
						);
					}
				}
				?>

				<?php if ( $targets ) : ?>
					<div class="efs__cell" style="grid-column:<?php echo (int) Schematic_Content::outputs_column( $count, $source ); ?>">
						<div class="efs__outs">
							<?php foreach ( $outs as $out ) : ?>
								<?php if ( empty( $out['text'] ) ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<div class="efs__out"><?php echo esc_html( $out['text'] ); ?></div>
							<?php endforeach; ?>
							<?php if ( ! empty( $settings['outs_foot'] ) ) : ?>
								<p class="efs__foot"><?php echo esc_html( $settings['outs_foot'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				if ( $ret ) {
					list( $ret_start, $ret_end ) = Schematic_Content::span(
						isset( $settings['ret_from'] ) ? $settings['ret_from'] : 1,
						isset( $settings['ret_to'] ) ? $settings['ret_to'] : $count,
						$count,
						$source
					);

					$markers = isset( $settings['ret_markers'] ) ? (string) $settings['ret_markers'] : '';
					?>
					<div class="efs__return" style="grid-column:<?php echo (int) $ret_start; ?>/<?php echo (int) $ret_end; ?>">
						<div class="efs__ret">
							<?php if ( ! empty( $settings['ret_label'] ) ) : ?>
								<span><?php echo esc_html( $settings['ret_label'] ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $markers ) : ?>
								<i></i>
							<?php endif; ?>
							<?php if ( 'pump-box' === $markers ) : ?>
								<i class="is-box"></i>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}
				?>

				<?php if ( $divide ) : ?>
					<div class="efs__divide">
						<?php if ( ! empty( $settings['div_label'] ) ) : ?>
							<span><?php echo esc_html( $settings['div_label'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}
}
