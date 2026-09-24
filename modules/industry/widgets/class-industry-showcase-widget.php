<?php
/**
 * Industry Showcase widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Industry\Widgets;

use Elementor\Controls_Manager;
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
				'default'    => array( 'unit' => 'px', 'size' => 400 ),
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
				'default'    => array( 'unit' => 'px', 'size' => 440 ),
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
				'default'    => array( 'unit' => 'px', 'size' => 72 ),
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
				'default'     => array( 'unit' => 'px', 'size' => 48 ),
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
				'default'    => array( 'unit' => 'px', 'size' => 14 ),
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
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
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

		printf(
			'<div class="eind" style="height:%dvh">',
			(int) Industry_Content::height( $count, $pace )
		);

		$this->render_pinned( $settings, $items, $count );
		$this->render_stack( $items );

		echo '</div>';
	}

	/**
	 * The pinned panel: names, picture, words.
	 *
	 * @param array $settings Settings.
	 * @param array $items    Usable items.
	 * @param int   $count    Item count.
	 * @return void
	 */
	private function render_pinned( $settings, $items, $count ) {
		echo '<div class="eind__pin">';

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

		foreach ( $items as $i => $item ) {
			$url = isset( $item['image']['url'] ) ? $item['image']['url'] : '';

			if ( '' === $url ) {
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
