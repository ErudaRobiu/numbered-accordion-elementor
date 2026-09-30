<?php
/**
 * Journey Timeline widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Journey\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Journey\Journey_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Milestones on a vertical line that draws itself in as the list scrolls into
 * view, each year landing as the line reaches it, the current step lit green.
 *
 * A repeater is right here: a company's history is a short fixed list.
 */
class Journey_Timeline_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ejny-journey-timeline';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Journey Timeline', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-time-line';
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
		return array( 'timeline', 'journey', 'history', 'story', 'milestones', 'years', 'about' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Journey\Journey_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Journey\Journey_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Milestones', 'numbered-accordion' ) )
		);

		$items = new Repeater();

		$items->add_control(
			'year',
			array(
				'label' => esc_html__( 'Year', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$items->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$items->add_control(
			'text',
			array(
				'label' => esc_html__( 'Description', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 3,
			)
		);

		$items->add_control(
			'current',
			array(
				'label'        => esc_html__( 'Current step', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'Lit green, with a soft halo. Usually the last one.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Optional. The whole row becomes the link.', 'numbered-accordion' ),
			)
		);

		$defaults = array();

		foreach ( Journey_Content::default_items() as $item ) {
			$defaults[] = array(
				'year'    => $item['year'],
				'title'   => $item['title'],
				'text'    => $item['text'],
				'current' => $item['current'] ? 'yes' : '',
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Milestones', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ year }}} · {{{ title }}}',
			)
		);

		$this->add_control(
			'list_label',
			array(
				'label'   => esc_html__( 'Read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Timeline', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_motion',
			array( 'label' => esc_html__( 'Motion', 'numbered-accordion' ) )
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Draw in on scroll', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Once, when the list comes into view: the line draws down and each year lands as it passes. Not in the editor, and never for visitors who ask for less motion.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'      => esc_html__( 'Line draw time', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 300, 'max' => 4000, 'step' => 100 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 1200 ),
				'condition'  => array( 'animate' => 'yes' ),
			)
		);

		$this->add_control(
			'pulse',
			array(
				'label'        => esc_html__( 'Pulse the current step', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'A slow halo around the current year.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * Style controls.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Timeline', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'size',
			array(
				'label'      => esc_html__( 'Circle size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 36, 'max' => 140 ) ),
				'selectors'  => array( '{{WRAPPER}} .ejny' => '--ejny-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => esc_html__( 'Space between rows', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .ejny' => '--ejny-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		foreach ( array(
			'circle'     => array( esc_html__( 'Circle', 'numbered-accordion' ), '--ejny-circle' ),
			'border'     => array( esc_html__( 'Circle border', 'numbered-accordion' ), '--ejny-border' ),
			'ink'        => array( esc_html__( 'Year and title', 'numbered-accordion' ), '--ejny-ink' ),
			'muted'      => array( esc_html__( 'Description', 'numbered-accordion' ), '--ejny-muted' ),
			'current'    => array( esc_html__( 'Current step and hover', 'numbered-accordion' ), '--ejny-current' ),
			'halo'       => array( esc_html__( 'Halo', 'numbered-accordion' ), '--ejny-halo' ),
			'line_start' => array( esc_html__( 'Line, top', 'numbered-accordion' ), '--ejny-line-a' ),
			'line_end'   => array( esc_html__( 'Line, bottom', 'numbered-accordion' ), '--ejny-line-b' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .ejny' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'year_type'  => array( esc_html__( 'Year type', 'numbered-accordion' ), '.ejny__year' ),
			'title_type' => array( esc_html__( 'Title type', 'numbered-accordion' ), '.ejny__title' ),
			'text_type'  => array( esc_html__( 'Description type', 'numbered-accordion' ), '.ejny__text' ),
		) as $id => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $id,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$rows     = Journey_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );

		if ( empty( $rows ) ) {
			return;
		}

		$animate  = isset( $settings['animate'] ) && 'yes' === $settings['animate'];
		$pulse    = isset( $settings['pulse'] ) && 'yes' === $settings['pulse'];
		$duration = Journey_Content::duration( isset( $settings['duration'] ) ? $settings['duration'] : 1200 );
		$label    = isset( $settings['list_label'] ) && is_scalar( $settings['list_label'] ) ? trim( (string) $settings['list_label'] ) : '';
		$classes  = 'ejny' . ( $animate ? ' ejny--animate' : '' ) . ( $pulse ? ' ejny--pulse' : '' );
		?>
		<ol class="<?php echo esc_attr( $classes ); ?>" style="--ejny-dur:<?php echo (int) $duration; ?>ms"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
			<?php foreach ( $rows as $row ) : ?>
				<?php
				$tag   = '' !== $row['link'] ? 'a' : 'div';
				$rel   = array_filter( array( $row['nofollow'] ? 'nofollow' : '', $row['external'] ? 'noopener' : '' ) );
				$attrs = '' !== $row['link']
					? ' href="' . esc_url( $row['link'] ) . '"' . ( $row['external'] ? ' target="_blank"' : '' ) . ( $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '' )
					: '';
				?>
				<li class="ejny__item<?php echo $row['current'] ? ' is-current' : ''; ?>"<?php echo $row['current'] ? ' aria-current="step"' : ''; ?>>
					<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="ejny__row"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts ?>>
						<span class="ejny__dot"><span class="ejny__year"><?php echo esc_html( $row['year'] ); ?></span></span>
						<span class="ejny__body">
							<?php if ( '' !== $row['title'] ) : ?>
								<strong class="ejny__title"><?php echo esc_html( $row['title'] ); ?></strong>
							<?php endif; ?>
							<?php if ( '' !== $row['text'] ) : ?>
								<span class="ejny__text"><?php echo esc_html( $row['text'] ); ?></span>
							<?php endif; ?>
						</span>
					</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	}
}
