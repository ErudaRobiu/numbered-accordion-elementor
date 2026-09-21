<?php
/**
 * Comparison Ledger widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Explainer\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Explainer\Explainer_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prose turned into a comparison: a row per point, the old way against the
 * new one, with the winning column tinted and marked.
 *
 * Three columns and repeatable rows, so it carries any comparison rather than
 * the one it was drawn for.
 */
class Comparison_Ledger_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eexp-comparison-ledger';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Comparison Ledger', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-table';
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
		return array( 'comparison', 'ledger', 'table', 'versus', 'matrix', 'against', 'rows', 'specification' );
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
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * The headings and the rows.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_ledger',
			array( 'label' => esc_html__( 'Ledger', 'numbered-accordion' ) )
		);

		$this->add_control(
			'head_key',
			array(
				'label'       => esc_html__( 'Column 1 heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Across the same dirty airstream', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'head_old',
			array(
				'label'       => esc_html__( 'Column 2 heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Conventional finned exchanger', 'numbered-accordion' ),
				'description' => esc_html__( 'On a phone the columns stack and the header row is hidden, so this heading labels the middle value inline instead.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'head_new',
			array(
				'label'       => esc_html__( 'Column 3 heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Lepido with PRG', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'show_head',
			array(
				'label'        => esc_html__( 'Show the header row', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$rows = new Repeater();

		$rows->add_control(
			'key',
			array(
				'label'   => esc_html__( 'Row', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Row', 'numbered-accordion' ),
			)
		);

		$rows->add_control(
			'old',
			array(
				'label'       => esc_html__( 'Column 2', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
			)
		);

		$rows->add_control(
			'new',
			array(
				'label'       => esc_html__( 'Column 3', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
			)
		);

		$this->add_control(
			'rows',
			array(
				'label'       => esc_html__( 'Rows', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rows->get_controls(),
				'title_field' => '{{{ key }}}',
				'default'     => $this->default_rows(),
			)
		);

		$this->add_control(
			'mark_winner',
			array(
				'label'        => esc_html__( 'Mark the third column', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'The tint and the dot that say which side won. Switch it off for a comparison with no winner.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Colours and proportions.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style_ledger',
			array(
				'label' => esc_html__( 'Ledger', 'numbered-accordion' ),
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
			'ledger_background',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-ledger' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'ledger_rule',
			array(
				'label'     => esc_html__( 'Rules and borders', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-lrow'   => 'border-top-color: {{VALUE}};',
					'{{WRAPPER}} .eexp-ledger' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ledger_radius',
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
					'{{WRAPPER}} .eexp-ledger' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'key_colour',
			array(
				'label'     => esc_html__( 'Row label', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-lkey' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'old_colour',
			array(
				'label'     => esc_html__( 'Column 2 text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-lold' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'new_colour',
			array(
				'label'     => esc_html__( 'Column 3 text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-lnew' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'selector' => '{{WRAPPER}} .eexp-lold, {{WRAPPER}} .eexp-lnew',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The rows the widget ships with.
	 *
	 * @return array
	 */
	private function default_rows() {
		return array(
			array(
				'key' => esc_html__( 'Coil surface', 'numbered-accordion' ),
				'old' => esc_html__( 'Tightly spaced fins, narrow passages', 'numbered-accordion' ),
				'new' => esc_html__( 'Finless, open coil with wide spacing', 'numbered-accordion' ),
			),
			array(
				'key' => esc_html__( 'Particle path', 'numbered-accordion' ),
				'old' => esc_html__( 'Grease and soot collect and stick', 'numbered-accordion' ),
				'new' => esc_html__( 'Particles pass through without clogging', 'numbered-accordion' ),
			),
			array(
				'key' => esc_html__( 'Pressure drop', 'numbered-accordion' ),
				'old' => esc_html__( 'Climbs as the unit fouls', 'numbered-accordion' ),
				'new' => esc_html__( 'Stays close to where it started', 'numbered-accordion' ),
			),
			array(
				'key' => esc_html__( 'Maintenance', 'numbered-accordion' ),
				'old' => esc_html__( 'Frequent cleaning to hold output', 'numbered-accordion' ),
				'new' => esc_html__( 'Drastically reduced intervention', 'numbered-accordion' ),
			),
			array(
				'key' => esc_html__( 'Heat transfer', 'numbered-accordion' ),
				'old' => esc_html__( 'Depends on fin area staying clean', 'numbered-accordion' ),
				'new' => esc_html__( 'Same surface area, held by counter-current flow', 'numbered-accordion' ),
			),
		);
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$rows = Explainer_Content::rows( $settings, 'rows' );

		if ( empty( $rows ) ) {
			return;
		}

		$old_label = Explainer_Content::text( $settings, 'head_old' );
		$show_head = 'yes' === Explainer_Content::text( $settings, 'show_head' );
		$win_class = 'yes' === Explainer_Content::text( $settings, 'mark_winner' ) ? 'eexp-lnew' : 'eexp-lnew eexp-lnew--plain';
		?>
		<div class="eexp eexp-no-js">
			<div class="eexp-ledger eexp-rise">
				<?php if ( $show_head ) : ?>
					<div class="eexp-lrow eexp-lhead">
						<span><?php echo esc_html( Explainer_Content::text( $settings, 'head_key' ) ); ?></span>
						<span><?php echo esc_html( $old_label ); ?></span>
						<span class="eexp-win"><?php echo esc_html( Explainer_Content::text( $settings, 'head_new' ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php foreach ( $rows as $row ) : ?>
					<div class="eexp-lrow">
						<div class="eexp-lkey eexp-label"><?php echo esc_html( Explainer_Content::text( $row, 'key' ) ); ?></div>
						<div class="eexp-lold" data-eexp-label="<?php echo esc_attr( $old_label ); ?>"><?php echo esc_html( Explainer_Content::text( $row, 'old' ) ); ?></div>
						<div class="<?php echo esc_attr( $win_class ); ?>"><?php echo esc_html( Explainer_Content::text( $row, 'new' ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
