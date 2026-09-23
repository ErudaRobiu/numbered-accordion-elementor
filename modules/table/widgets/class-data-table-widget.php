<?php
/**
 * Data Table widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Table\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Table\Table_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A reference table: a filled header row, banded rows under it, and a last
 * column that can be set apart from the two carrying the argument.
 *
 * A real <table> rather than a grid of divs, which is not pedantry. A screen
 * reader announces "Source of the value, Field sensors" only if the heading
 * and the cell are joined by the markup, and someone copying the table into a
 * spreadsheet gets columns rather than one run of text. The grid that does the
 * layout is laid over the table rather than replacing it.
 */
class Data_Table_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'etbl-data-table';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Data Table', 'numbered-accordion' );
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
		return array( 'table', 'data', 'rows', 'columns', 'specification', 'spec', 'reference', 'matrix', 'grid' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Table\Table_Module::STYLE_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_table_style_controls();
		$this->register_head_style_controls();
		$this->register_body_style_controls();
	}

	/**
	 * The headings and the rows.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_table',
			array( 'label' => esc_html__( 'Table', 'numbered-accordion' ) )
		);

		$this->add_control(
			'head_1',
			array(
				'label'   => esc_html__( 'Column 1 heading', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Measured', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'head_2',
			array(
				'label'   => esc_html__( 'Column 2 heading', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'What it tells you', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'head_3',
			array(
				'label'       => esc_html__( 'Column 3 heading', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave it empty for a two-column table. The columns and the phone layout follow it on their own.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Source of the value', 'numbered-accordion' ),
			)
		);

		$rows = new Repeater();

		$rows->add_control(
			'cell_1',
			array(
				'label'       => esc_html__( 'Column 1', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$rows->add_control(
			'cell_2',
			array(
				'label'       => esc_html__( 'Column 2', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$rows->add_control(
			'cell_3',
			array(
				'label'       => esc_html__( 'Column 3', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'rows',
			array(
				'label'       => esc_html__( 'Rows', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rows->get_controls(),
				'default'     => $this->default_rows(),
				'title_field' => '{{{ cell_1 }}}',
			)
		);

		$this->add_control(
			'show_head',
			array(
				'label'       => esc_html__( 'Show the header row', 'numbered-accordion' ),
				'description' => esc_html__( 'Switched off, the headings are still used as the labels beside each value on a phone.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'caption',
			array(
				'label'       => esc_html__( 'Caption', 'numbered-accordion' ),
				'description' => esc_html__( 'Sits under the table and tells a screen reader what it is looking at before it starts reading cells.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The shape of the table itself.
	 */
	private function register_table_style_controls() {
		$this->start_controls_section(
			'section_table_style',
			array(
				'label' => esc_html__( 'Table', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'       => esc_html__( 'Column widths', 'numbered-accordion' ),
				'description' => esc_html__( 'Any valid grid-template-columns value, one part per column. Left empty it gives the first two columns the room and lets the last take what is left. It is responsive, so a table that needs a different shape on a tablet can have one; below 768px the columns fold into blocks regardless.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'selectors'   => array( '{{WRAPPER}} .etbl' => '--etbl-cols: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'pad_y',
			array(
				'label'      => esc_html__( 'Row height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 6, 'max' => 60 ), 'em' => array( 'min' => 0, 'max' => 5, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 5, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 22 ),
				'selectors'  => array( '{{WRAPPER}} .etbl' => '--etbl-pad-y: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pad_x',
			array(
				'label'      => esc_html__( 'Side padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ), 'em' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array( '{{WRAPPER}} .etbl' => '--etbl-pad-x: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'       => esc_html__( 'Gap between columns', 'numbered-accordion' ),
				'description' => esc_html__( 'On top of the side padding. A wide table with short values needs it; a dense one does not.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 80 ), 'em' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 6, 'step' => 0.1 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'   => array( '{{WRAPPER}} .etbl' => '--etbl-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ), 'rem' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array( '{{WRAPPER}} .etbl' => '--etbl-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'outline',
			array(
				'label'     => esc_html__( 'Outer border', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-outline: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'rule',
			array(
				'label'     => esc_html__( 'Line between rows', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#E7ECF1',
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-rule: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The header row.
	 */
	private function register_head_style_controls() {
		$this->start_controls_section(
			'section_head_style',
			array(
				'label'     => esc_html__( 'Header row', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_head' => 'yes' ),
			)
		);

		$this->add_control(
			'head_fill',
			array(
				'label'     => esc_html__( 'Fill', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1B3A61',
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-head-fill: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'head_ink',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-head-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'head_case',
			array(
				'label'   => esc_html__( 'Letter case', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'uppercase',
				'options' => array(
					'none'      => esc_html__( 'As typed', 'numbered-accordion' ),
					'uppercase' => esc_html__( 'Capitals', 'numbered-accordion' ),
				),
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-head-case: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'head_track',
			array(
				'label'       => esc_html__( 'Letter spacing', 'numbered-accordion' ),
				'description' => esc_html__( 'In em, not per cent. Capitals need the extra room; set in per cent the browser drops it entirely.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'em' ),
				'range'       => array( 'em' => array( 'min' => 0, 'max' => 0.3, 'step' => 0.005 ) ),
				'default'     => array( 'unit' => 'em', 'size' => 0.06 ),
				'selectors'   => array( '{{WRAPPER}} .etbl' => '--etbl-head-track: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'head_typography',
				'selector' => '{{WRAPPER}} .etbl__th',
				'exclude'  => array( 'text_transform', 'letter_spacing' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The rows under it.
	 */
	private function register_body_style_controls() {
		$this->start_controls_section(
			'section_body_style',
			array(
				'label' => esc_html__( 'Rows', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'band',
			array(
				'label'   => esc_html__( 'Band every other row', 'numbered-accordion' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'band_fill',
			array(
				'label'     => esc_html__( 'Band', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#F5F7F9',
				'condition' => array( 'band' => 'yes' ),
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-band: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'row_fill',
			array(
				'label'     => esc_html__( 'The rows between', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-row: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ink',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1B3A61',
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'last_ink',
			array(
				'label'       => esc_html__( 'Last column text', 'numbered-accordion' ),
				'description' => esc_html__( 'A column holding a source, a status or a note is not making the argument; setting it back a shade stops it competing with the two that are.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#6E88A6',
				'selectors'   => array( '{{WRAPPER}} .etbl' => '--etbl-last-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'last_weight',
			array(
				'label'     => esc_html__( 'Last column weight', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '600',
				'options'   => array(
					'inherit' => esc_html__( 'Same as the rest', 'numbered-accordion' ),
					'500'     => esc_html__( 'Medium', 'numbered-accordion' ),
					'600'     => esc_html__( 'Semi-bold', 'numbered-accordion' ),
					'700'     => esc_html__( 'Bold', 'numbered-accordion' ),
				),
				'selectors' => array( '{{WRAPPER}} .etbl' => '--etbl-last-weight: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'selector' => '{{WRAPPER}} .etbl__td',
			)
		);

		$this->add_control(
			'label_ink',
			array(
				'label'       => esc_html__( 'Heading beside a value, on a phone', 'numbered-accordion' ),
				'description' => esc_html__( 'Once the columns fold, each value carries its own heading. This is that heading.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#8CA3BC',
				'separator'   => 'before',
				'selectors'   => array( '{{WRAPPER}} .etbl' => '--etbl-label-ink: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The rows the widget ships with.
	 *
	 * The measurement table from a heat-recovery page, because a table of
	 * lorem is no help at all in judging whether the column widths are right.
	 *
	 * @return array
	 */
	private function default_rows() {
		$rows = array(
			array( 'Source air temperature, in and out', 'Energy available, and how much was taken', 'Field sensors' ),
			array( 'Loop supply and return temperature', 'What the circuit is actually carrying', 'Field sensors' ),
			array( 'Fluid flow', 'Circulation against the design case', 'Field sensors' ),
			array( 'Equipment status and alarms', 'Whether it is running, and why not', 'Control logic' ),
			array( 'Operating time and overlap', 'Hours the source and the demand coincide', 'Control logic' ),
			array( 'Calculated thermal energy', 'The figure an incentive process asks for', 'Approved M&V method' ),
		);

		$out = array();

		foreach ( $rows as $row ) {
			$out[] = array(
				'cell_1' => $row[0],
				'cell_2' => $row[1],
				'cell_3' => $row[2],
			);
		}

		return $out;
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$rows = Table_Content::visible_rows( $settings );

		if ( empty( $rows ) ) {
			return;
		}

		$columns  = Table_Content::column_count( $settings );
		$headings = Table_Content::headings( $settings );
		$showHead = 'yes' === Table_Content::text( $settings, 'show_head' ) && ! empty( $headings );
		$caption  = Table_Content::text( $settings, 'caption' );

		$classes = 'etbl etbl--cols-' . (int) $columns;

		if ( 'yes' === Table_Content::text( $settings, 'band' ) ) {
			$classes .= ' etbl--banded';
		}

		/*
		 * The template is written inline rather than through a selector,
		 * because it is the one value that depends on how many columns there
		 * turned out to be -- and Elementor's selectors run before anybody has
		 * counted them. A responsive override set in the panel writes the same
		 * property from the stylesheet and wins on the cascade, which is why
		 * this is a fallback rather than !important.
		 */
		$template = Table_Content::template( $settings, $columns );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" style="--etbl-cols-default: <?php echo esc_attr( $template ); ?>;">
			<table class="etbl__table">
				<?php if ( '' !== $caption ) : ?>
					<caption class="etbl__caption"><?php echo esc_html( $caption ); ?></caption>
				<?php endif; ?>

				<?php if ( $showHead ) : ?>
					<thead>
						<tr class="etbl__tr etbl__tr--head">
							<?php for ( $i = 0; $i < $columns; $i++ ) : ?>
								<th class="etbl__th" scope="col"><?php echo esc_html( Table_Content::cell_label( $headings, $i ) ); ?></th>
							<?php endfor; ?>
						</tr>
					</thead>
				<?php endif; ?>

				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="etbl__tr">
							<?php
							foreach ( Table_Content::cells( $row, $columns ) as $i => $cell ) :
								$label = Table_Content::cell_label( $headings, $i );
								?>
								<td class="etbl__td"<?php echo '' !== $label ? ' data-etbl-label="' . esc_attr( $label ) . '"' : ''; ?>><?php echo esc_html( $cell ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
