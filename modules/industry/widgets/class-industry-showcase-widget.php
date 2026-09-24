<?php
/**
 * Industry Showcase widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Industry\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Industry\Industry_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A list of industries walked by scrolling.
 *
 * The section is deliberately taller than the screen and the panel inside it
 * is pinned, so scrolling past it steps through the list rather than moving
 * it. Names on the left, one picture in the middle that settles in as it
 * changes, and the words on the right.
 *
 * It carries no background of its own, so it sits on whatever colour the
 * section behind it is -- which on the sites this was built for is a dark one.
 *
 * On a phone none of that applies. Driving a pinned panel from scroll position
 * fights the browser on touch, where scrolling is also how the address bar is
 * dismissed, so below the breakpoint the whole thing is a plain stack.
 */
class Industry_Showcase_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eind-industry-showcase';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Industry Showcase', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-vertical';
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
		return array( 'industries', 'sectors', 'showcase', 'scroll', 'sticky', 'markets', 'verticals' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Industry\Industry_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Industry\Industry_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_colour_controls();
		$this->register_background_controls();
		$this->register_size_controls();
		$this->register_type_controls();
	}

	/**
	 * The industries.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Industries', 'numbered-accordion' ) )
		);

		$items = new Repeater();

		$items->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Industry', 'numbered-accordion' ),
				'description' => esc_html__( 'What appears in the list on the left.', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$items->add_control(
			'image',
			array(
				'label' => esc_html__( 'Picture', 'numbered-accordion' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$items->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'What this industry gets', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$items->add_control(
			'body',
			array(
				'label'       => esc_html__( 'Description', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'label_block' => true,
			)
		);

		$items->add_control(
			'link_text',
			array(
				'label'       => esc_html__( 'Link text', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Leave empty for no link.', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$items->add_control(
			'link',
			array(
				'label'     => esc_html__( 'Link', 'numbered-accordion' ),
				'type'      => Controls_Manager::URL,
				'condition' => array( 'link_text!' => '' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Industries', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array(
						'name'    => esc_html__( 'First industry', 'numbered-accordion' ),
						'heading' => esc_html__( 'What this industry gets', 'numbered-accordion' ),
					),
					array(
						'name'    => esc_html__( 'Second industry', 'numbered-accordion' ),
						'heading' => esc_html__( 'What this industry gets', 'numbered-accordion' ),
					),
					array(
						'name'    => esc_html__( 'Third industry', 'numbered-accordion' ),
						'heading' => esc_html__( 'What this industry gets', 'numbered-accordion' ),
					),
				),
			)
		);

		$this->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Section label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Small line centred at the top. Leave empty for none.', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'     => esc_html__( 'Show the counter', 'numbered-accordion' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * How far it scrolls.
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array( 'label' => esc_html__( 'Scrolling', 'numbered-accordion' ) )
		);

		$this->add_control(
			'pace',
			array(
				'label'       => esc_html__( 'Scroll per industry', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'vh' ),
				'range'       => array(
					'vh' => array(
						'min'  => Industry_Content::MIN_PACE * 100,
						'max'  => Industry_Content::MAX_PACE * 100,
						'step' => 5,
					),
				),
				'default'     => array(
					'unit' => 'vh',
					'size' => 85,
				),
				'description' => esc_html__( 'How much scrolling each industry is worth. Lower moves through them faster and makes the whole section shorter.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Colours.
	 */
	private function register_colour_controls() {
		$this->start_controls_section(
			'section_colours',
			array(
				'label' => esc_html__( 'Colours', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'colour_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'The defaults suit a dark section. The widget draws no background of its own.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$map = array(
			'ink'    => array( esc_html__( 'Text', 'numbered-accordion' ), '#ffffff' ),
			'body'   => array( esc_html__( 'Description', 'numbered-accordion' ), 'rgba(255,255,255,0.72)' ),
			'muted'  => array( esc_html__( 'Inactive names', 'numbered-accordion' ), 'rgba(255,255,255,0.34)' ),
			'accent' => array( esc_html__( 'Accent', 'numbered-accordion' ), '#7ab648' ),
			'line'   => array( esc_html__( 'Ticks', 'numbered-accordion' ), 'rgba(255,255,255,0.18)' ),
			'frame'  => array( esc_html__( 'Empty frame', 'numbered-accordion' ), 'rgba(255,255,255,0.06)' ),
		);

		foreach ( $map as $key => $spec ) {
			$this->add_control(
				'colour_' . $key,
				array(
					'label'     => $spec[0],
					'type'      => Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .eind' => '--eind-' . $key . ': {{VALUE}};',
					),
				)
			);
		}

		$this->end_controls_section();

		$this->register_reveal_controls();
	}

	/**
	 * How the picture changes.
	 */
	private function register_reveal_controls() {
		$this->start_controls_section(
			'section_reveal',
			array( 'label' => esc_html__( 'Picture change', 'numbered-accordion' ) )
		);

		$this->add_control(
			'reveal_style',
			array(
				'label'   => esc_html__( 'How it changes', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade',
				'options' => array(
					'fade' => esc_html__( 'Dissolve', 'numbered-accordion' ),
					'grid' => esc_html__( 'Grid sweep', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'reveal_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'The grid sweep runs a hard edge across the frame with a ragged band of blocks riding it. Visitors who have asked for reduced motion get a plain change either way.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'reveal_style' => 'grid' ),
			)
		);

		$this->add_control(
			'reveal_axis',
			array(
				'condition' => array( 'reveal_style' => 'grid' ),
				'label'   => esc_html__( 'Direction', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'y',
				'options' => array(
					'y'  => esc_html__( 'Downwards', 'numbered-accordion' ),
					'yr' => esc_html__( 'Upwards', 'numbered-accordion' ),
					'x'  => esc_html__( 'Left to right', 'numbered-accordion' ),
					'xr' => esc_html__( 'Right to left', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'reveal_block',
			array(
				'condition' => array( 'reveal_style' => 'grid' ),
				'label'       => esc_html__( 'Block size', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 12, 'max' => 120, 'step' => 2 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 44 ),
				'description' => esc_html__( 'Smaller blocks read as grain, larger ones as tiles.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eind' => '--eind-block: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'reveal_wipe',
			array(
				'condition' => array( 'reveal_style' => 'grid' ),
				'label'      => esc_html__( 'How long it takes', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 300, 'max' => 2500, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 900 ),
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-wipe: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'reveal_jitter',
			array(
				'condition' => array( 'reveal_style' => 'grid' ),
				'label'       => esc_html__( 'Raggedness', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 2, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 0.55 ),
				'description' => esc_html__( 'Zero gives a straight edge. Higher scatters the blocks further ahead of and behind it.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'reveal_band',
			array(
				'condition' => array( 'reveal_style' => 'grid' ),
				'label'     => esc_html__( 'Block colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .eind' => '--eind-band: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The background the pinned panel carries.
	 */
	private function register_background_controls() {
		$this->start_controls_section(
			'section_bed',
			array(
				'label' => esc_html__( 'Background', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'bed_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Set here rather than on the section. A background on the section scrolls with the whole showcase, so a pattern slides past the panel in front of it; set here it is pinned along with the panel and holds still.', 'numbered-accordion' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'bed',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .eind__bed',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Widths and spacing.
	 */
	private function register_size_controls() {
		$this->start_controls_section(
			'section_sizes',
			array(
				'label' => esc_html__( 'Width and spacing', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'frame_width',
			array(
				'label'      => esc_html__( 'Picture width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 220, 'max' => 1100, 'step' => 10 ),
					'vw' => array( 'min' => 15, 'max' => 70, 'step' => 1 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-frame-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'frame_ratio',
			array(
				'label'     => esc_html__( 'Picture shape', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '3 / 4',
				'options'   => array(
					'3 / 4'  => esc_html__( 'Portrait 3:4', 'numbered-accordion' ),
					'4 / 5'  => esc_html__( 'Portrait 4:5', 'numbered-accordion' ),
					'1 / 1'  => esc_html__( 'Square', 'numbered-accordion' ),
					'4 / 3'  => esc_html__( 'Landscape 4:3', 'numbered-accordion' ),
					'16 / 9' => esc_html__( 'Landscape 16:9', 'numbered-accordion' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .eind' => '--eind-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_width',
			array(
				'label'      => esc_html__( 'Text width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 260, 'max' => 900, 'step' => 10 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-text-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'columns_gap',
			array(
				'label'      => esc_html__( 'Gap between columns', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200, 'step' => 4 ) ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'side_padding',
			array(
				'label'       => esc_html__( 'Side padding', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 200, 'step' => 4 ) ),
				'description' => esc_html__( 'Set this to zero to let the section\'s own padding do the work.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eind' => '--eind-pad: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'list_gap',
			array(
				'label'      => esc_html__( 'Gap between names', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60, 'step' => 2 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-list-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'stack_gap',
			array(
				'label'      => esc_html__( 'Gap inside the text', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60, 'step' => 2 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .eind' => '--eind-stack-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Type.
	 *
	 * Left unset by default so the theme's own faces and sizes carry through.
	 * A control that is set wins over the stylesheet, including over the
	 * smaller heading the phone layout asks for, which is what somebody who
	 * has bothered to set one expects.
	 */
	private function register_type_controls() {
		$this->start_controls_section(
			'section_type',
			array(
				'label' => esc_html__( 'Type', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$faces = array(
			'name'    => array( esc_html__( 'Names', 'numbered-accordion' ), '{{WRAPPER}} .eind__name' ),
			'heading' => array( esc_html__( 'Heading', 'numbered-accordion' ), '{{WRAPPER}} .eind__heading' ),
			'body'    => array( esc_html__( 'Description', 'numbered-accordion' ), '{{WRAPPER}} .eind__body' ),
			'link'    => array( esc_html__( 'Link', 'numbered-accordion' ), '{{WRAPPER}} .eind__link' ),
			'label'   => array( esc_html__( 'Section label', 'numbered-accordion' ), '{{WRAPPER}} .eind__label' ),
			'count'   => array( esc_html__( 'Counter', 'numbered-accordion' ), '{{WRAPPER}} .eind__count' ),
		);

		foreach ( $faces as $key => $spec ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'type_' . $key,
					'label'    => $spec[0],
					'selector' => $spec[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = Industry_Content::usable( isset( $settings['items'] ) ? $settings['items'] : array() );
		$count    = count( $items );

		if ( 0 === $count ) {
			return;
		}

		$pace = isset( $settings['pace']['size'] ) ? ( (float) $settings['pace']['size'] ) / 100 : 0.85;

		$axis  = isset( $settings['reveal_axis'] ) ? (string) $settings['reveal_axis'] : 'y';
		$style = isset( $settings['reveal_style'] ) && 'grid' === $settings['reveal_style'] ? 'grid' : 'fade';

		printf(
			'<div class="eind" style="height:%dvh" data-eind-reveal="%s" data-eind-axis="%s" data-eind-flip="%s" data-eind-block="%d" data-eind-wipe="%d" data-eind-jitter="%s">',
			(int) Industry_Content::height( $count, $pace ),
			esc_attr( $style ),
			esc_attr( 'x' === $axis || 'xr' === $axis ? 'x' : 'y' ),
			esc_attr( 'yr' === $axis || 'xr' === $axis ? '1' : '0' ),
			isset( $settings['reveal_block']['size'] ) ? (int) $settings['reveal_block']['size'] : 44,
			isset( $settings['reveal_wipe']['size'] ) ? (int) $settings['reveal_wipe']['size'] : 900,
			esc_attr( isset( $settings['reveal_jitter']['size'] ) ? (string) (float) $settings['reveal_jitter']['size'] : '0.55' )
		);

		$this->render_pinned( $settings, $items, $count, $style );
		$this->render_stack( $items );

		echo '</div>';
	}

	/**
	 * The pinned panel: names, picture, words.
	 *
	 * @param array  $settings Settings.
	 * @param array  $items    Usable items.
	 * @param int    $count    Item count.
	 * @param string $style    'fade' or 'grid'.
	 * @return void
	 */
	private function render_pinned( $settings, $items, $count, $style = 'fade' ) {
		echo '<div class="eind__pin">';

		// First child, so it sits behind everything without needing to be
		// taken out of the grid's flow by hand.
		echo '<div class="eind__bed" aria-hidden="true"></div>';

		if ( ! empty( $settings['label'] ) ) {
			printf( '<p class="eind__label">%s</p>', esc_html( $settings['label'] ) );
		}

		// The names. A real list of real buttons: this is the navigation for
		// the section, and a visitor who cannot scroll must still be able to
		// reach every item with a keyboard.
		echo '<ul class="eind__list">';

		foreach ( $items as $i => $item ) {
			printf(
				'<li><button type="button" class="eind__name" aria-current="%s">%s</button></li>',
				0 === $i ? 'true' : 'false',
				esc_html( $item['name'] )
			);
		}

		echo '</ul>';

		// The picture, with every one stacked in the same frame.
		echo '<div class="eind__media"><div class="eind__frame">';

		// One element per industry, always, including the ones with no picture
		// set. The script lights a picture by the industry's own position, so
		// skipping an item here would shift every picture after it onto the
		// wrong industry -- and once the position runs past the end of the
		// shortened list, nothing is lit at all and the frame simply stops
		// changing. An industry without a picture shows the empty frame, which
		// is the honest answer and keeps the counting straight.
		foreach ( $items as $i => $item ) {
			$url = isset( $item['image']['url'] ) ? $item['image']['url'] : '';

			if ( '' === $url ) {
				printf(
					'<span class="eind__shot%s" aria-hidden="true"></span>',
					0 === $i ? ' eind__shot--on' : ''
				);
				continue;
			}

			printf(
				'<img class="eind__shot%s" src="%s" alt="" loading="%s" decoding="async" />',
				0 === $i ? ' eind__shot--on' : '',
				esc_url( $url ),
				// The first one is what a visitor arrives to; the rest can
				// wait until the browser has time.
				0 === $i ? 'eager' : 'lazy'
			);
		}

		// Only when the grid sweep is the chosen change. A dissolve has no
		// blocks, so there is no reason to put an empty grid in the page.
		if ( 'grid' === $style ) {
			// Filled by the script from the frame's measured size, so the
			// blocks stay square whatever shape the frame is.
			echo '<div class="eind__grid" aria-hidden="true"></div>';
		}

		echo '</div>';

		echo '<div class="eind__ticks" aria-hidden="true">';

		for ( $i = 0; $i < $count; $i++ ) {
			printf( '<span class="eind__tick%s"></span>', 0 === $i ? ' eind__tick--on' : '' );
		}

		echo '</div></div>';

		// The words.
		echo '<div class="eind__text">';

		foreach ( $items as $i => $item ) {
			printf(
				'<div class="eind__panel%s" aria-hidden="%s">',
				0 === $i ? ' eind__panel--on' : '',
				0 === $i ? 'false' : 'true'
			);

			$this->render_body( $item );

			echo '</div>';
		}

		echo '</div>';

		if ( ! empty( $settings['show_count'] ) && 'yes' === $settings['show_count'] ) {
			printf(
				'<p class="eind__count" data-eind-format="%%1$s / %%2$s">%s</p>',
				esc_html( Industry_Content::counter( 0, $count ) )
			);
		}

		echo '</div>';
	}

	/**
	 * The stacked version, which is what a phone gets.
	 *
	 * Rendered rather than built by the script so it is there whether or not
	 * the script runs, and so a search engine reads the same content either
	 * way.
	 *
	 * @param array $items Usable items.
	 * @return void
	 */
	private function render_stack( $items ) {
		echo '<div class="eind__stack">';

		foreach ( $items as $item ) {
			echo '<article class="eind__card">';

			$url = isset( $item['image']['url'] ) ? $item['image']['url'] : '';

			if ( '' !== $url ) {
				printf( '<img src="%s" alt="" loading="lazy" decoding="async" />', esc_url( $url ) );
			}

			$this->render_body( $item );

			echo '</article>';
		}

		echo '</div>';

		// Without a script nothing ever changes which industry is showing, so
		// the panel would be a single frozen one. The stack is already in the
		// markup; this is what reveals it.
		echo '<noscript><style>' .
			'.eind__pin{display:none}' .
			'.eind__stack{display:flex;flex-direction:column;' .
			'gap:clamp(32px,7vw,56px);padding:clamp(24px,7vw,48px) clamp(18px,5vw,32px)}' .
			'</style></noscript>';
	}

	/**
	 * Heading, description and link. The same in both layouts.
	 *
	 * @param array $item One item.
	 * @return void
	 */
	private function render_body( $item ) {
		printf( '<h3 class="eind__heading">%s</h3>', esc_html( $item['heading'] ) );

		if ( ! empty( $item['body'] ) ) {
			printf( '<p class="eind__body">%s</p>', esc_html( $item['body'] ) );
		}

		$text = isset( $item['link_text'] ) ? trim( (string) $item['link_text'] ) : '';
		$href = isset( $item['link']['url'] ) ? $item['link']['url'] : '';

		if ( '' === $text || '' === $href ) {
			return;
		}

		$attrs = '';

		if ( ! empty( $item['link']['is_external'] ) ) {
			$attrs .= ' target="_blank"';
		}

		if ( ! empty( $item['link']['nofollow'] ) ) {
			$attrs .= ' rel="nofollow"';
		}

		printf(
			'<a class="eind__link" href="%s"%s>%s</a>',
			esc_url( $href ),
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $text )
		);
	}
}
