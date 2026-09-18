<?php
/**
 * Hotspot Stats widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Spots\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Figures pinned to points on a photograph, joined to them by leader lines.
 */
class Hotspot_Stats_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-hotspot-stats';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Hotspot Stats', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-hotspot';
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
		return array( 'hotspot', 'stats', 'counter', 'callout', 'diagram', 'annotated', 'product' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Spots\Spots_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Spots\Spots_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_picture_controls();
		$this->register_hotspot_controls();
		$this->register_line_controls();
		$this->register_label_controls();
		$this->register_motion_controls();
	}

	/**
	 * The photograph everything is pinned to.
	 */
	private function register_picture_controls() {
		$this->start_controls_section(
			'section_picture',
			array( 'label' => esc_html__( 'Picture', 'numbered-accordion' ) )
		);

		$this->add_control(
			'image',
			array(
				'label' => esc_html__( 'Image', 'numbered-accordion' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'alt',
			array(
				'label'       => esc_html__( 'Description of the image', 'numbered-accordion' ),
				'description' => esc_html__( 'What the picture shows, for anyone who cannot see it. The figures beside it are read out on their own, so this only has to describe the thing itself.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Largest it gets', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 320, 'max' => 1600 ),
					'%'  => array( 'min' => 20, 'max' => 100 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 100 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The hotspots themselves.
	 */
	private function register_hotspot_controls() {
		$this->start_controls_section(
			'section_spots',
			array( 'label' => esc_html__( 'Hotspots', 'numbered-accordion' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Label', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Countries Installed', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Figure', 'numbered-accordion' ),
				'description' => esc_html__( 'Written however you want it read: 13+, 1,200, $4.5m. Whatever number is in there counts up and the rest is kept exactly as typed.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '13+',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'note',
			array(
				'label'   => esc_html__( 'Note', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => '',
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'numbered-accordion' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'numbered-accordion' ),
				'description' => esc_html__( 'Optional. A hotspot that goes somewhere underlines its label on hover; one that does not stays a plain figure.', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'point_heading',
			array(
				'label'       => esc_html__( 'Where it points', 'numbered-accordion' ),
				'description' => esc_html__( 'Both positions are a percentage across and down the picture, so a hotspot stays on the same rivet at every screen width.', 'numbered-accordion' ),
				'type'        => Controls_Manager::HEADING,
				'separator'   => 'before',
			)
		);

		$repeater->add_responsive_control(
			'ax',
			array(
				'label'      => esc_html__( 'Point across', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--espot-ax: {{SIZE}}%;' ),
			)
		);

		$repeater->add_responsive_control(
			'ay',
			array(
				'label'      => esc_html__( 'Point down', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => '%', 'size' => 50 ),
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--espot-ay: {{SIZE}}%;' ),
			)
		);

		$repeater->add_responsive_control(
			'lx',
			array(
				'label'      => esc_html__( 'Label across', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => '%', 'size' => 15 ),
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--espot-lx: {{SIZE}}%;' ),
			)
		);

		$repeater->add_responsive_control(
			'ly',
			array(
				'label'      => esc_html__( 'Label down', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => '%', 'size' => 15 ),
				'selectors'  => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => '--espot-ly: {{SIZE}}%;' ),
			)
		);

		$repeater->add_control(
			'side',
			array(
				'label'       => esc_html__( 'Label sits', 'numbered-accordion' ),
				'description' => esc_html__( 'Which side of its own position the label runs from. Use Left for anything near the right-hand edge, or it will run off it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'right',
				'options'     => array(
					'right' => esc_html__( 'To the right of its position', 'numbered-accordion' ),
					'left'  => esc_html__( 'To the left of its position', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'spots',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}} &mdash; {{{ title }}}',
				'default'     => array(
					array(
						'title' => esc_html__( 'Countries Installed', 'numbered-accordion' ),
						'value' => '13+',
						'ax'    => array( 'unit' => '%', 'size' => 30 ),
						'ay'    => array( 'unit' => '%', 'size' => 33 ),
						'lx'    => array( 'unit' => '%', 'size' => 14 ),
						'ly'    => array( 'unit' => '%', 'size' => 16 ),
						'side'  => 'right',
					),
					array(
						'title' => esc_html__( 'Customer Brands', 'numbered-accordion' ),
						'value' => '50+',
						'ax'    => array( 'unit' => '%', 'size' => 57 ),
						'ay'    => array( 'unit' => '%', 'size' => 38 ),
						'lx'    => array( 'unit' => '%', 'size' => 86 ),
						'ly'    => array( 'unit' => '%', 'size' => 16 ),
						'side'  => 'left',
					),
					array(
						'title' => esc_html__( 'Restaurants and Breweries', 'numbered-accordion' ),
						'value' => '30+',
						'ax'    => array( 'unit' => '%', 'size' => 28 ),
						'ay'    => array( 'unit' => '%', 'size' => 55 ),
						'lx'    => array( 'unit' => '%', 'size' => 9 ),
						'ly'    => array( 'unit' => '%', 'size' => 38 ),
						'side'  => 'right',
					),
					array(
						'title' => esc_html__( 'Hotel Brands', 'numbered-accordion' ),
						'value' => '5+',
						'ax'    => array( 'unit' => '%', 'size' => 65 ),
						'ay'    => array( 'unit' => '%', 'size' => 63 ),
						'lx'    => array( 'unit' => '%', 'size' => 90 ),
						'ly'    => array( 'unit' => '%', 'size' => 45 ),
						'side'  => 'left',
					),
					array(
						'title' => esc_html__( 'Industrial Sites', 'numbered-accordion' ),
						'value' => '10+',
						'ax'    => array( 'unit' => '%', 'size' => 25 ),
						'ay'    => array( 'unit' => '%', 'size' => 68 ),
						'lx'    => array( 'unit' => '%', 'size' => 10 ),
						'ly'    => array( 'unit' => '%', 'size' => 80 ),
						'side'  => 'right',
					),
					array(
						'title' => esc_html__( 'School and Office Kitchens', 'numbered-accordion' ),
						'value' => '8+',
						'ax'    => array( 'unit' => '%', 'size' => 60 ),
						'ay'    => array( 'unit' => '%', 'size' => 72 ),
						'lx'    => array( 'unit' => '%', 'size' => 52 ),
						'ly'    => array( 'unit' => '%', 'size' => 92 ),
						'side'  => 'right',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The dots and the leaders.
	 */
	private function register_line_controls() {
		$this->start_controls_section(
			'section_lines',
			array(
				'label' => esc_html__( 'Dots and lines', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'dot_size',
			array(
				'label'      => esc_html__( 'Dot size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 28 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 9 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-dot: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'dot_colour',
			array(
				'label'     => esc_html__( 'Dot colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-dot-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'       => esc_html__( 'Accent', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour a hotspot takes when it is the one being looked at.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#4ADE80',
				'selectors'   => array( '{{WRAPPER}} .espot' => '--espot-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'line_colour',
			array(
				'label'     => esc_html__( 'Line colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.55)',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-line: {{VALUE}};' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'line_width',
			array(
				'label'      => esc_html__( 'Line width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0.5, 'max' => 6, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 1 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-line-w: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'stub',
			array(
				'label'       => esc_html__( 'Straight run from the label', 'numbered-accordion' ),
				'description' => esc_html__( 'How far a leader runs level before it turns for its dot. A single diagonal from a word to a rivet reads as a stray mark; the straight run is what makes it a leader.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 18 ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The words.
	 */
	private function register_label_controls() {
		$this->start_controls_section(
			'section_label',
			array(
				'label' => esc_html__( 'Labels', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Icon size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 72 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-icon-size: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'icon_colour',
			array(
				'label'     => esc_html__( 'Icon colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-icon: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'label_max',
			array(
				'label'       => esc_html__( 'Widest a label gets', 'numbered-accordion' ),
				'description' => esc_html__( 'Before it wraps. Keep it short enough that two labels never overlap.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 120, 'max' => 640 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 320 ),
				'selectors'   => array( '{{WRAPPER}} .espot' => '--espot-label-max: {{SIZE}}px;' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'title_typography',
				'selector'  => '{{WRAPPER}} .espot__title',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title_colour',
			array(
				'label'     => esc_html__( 'Label colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#EEF3F1',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-title: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'value_typography',
				'selector'  => '{{WRAPPER}} .espot__value',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'value_colour',
			array(
				'label'     => esc_html__( 'Figure colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-value: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'note_colour',
			array(
				'label'     => esc_html__( 'Note colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#B9C7C2',
				'selectors' => array( '{{WRAPPER}} .espot' => '--espot-note: {{VALUE}};' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Arriving, counting and the dimming.
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
			'count_ms',
			array(
				'label'       => esc_html__( 'Counting length', 'numbered-accordion' ),
				'description' => esc_html__( 'How long a figure takes to count up to itself. Zero leaves every figure at its final value.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 4000, 'step' => 100 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 1200 ),
			)
		);

		$this->add_control(
			'draw_ms',
			array(
				'label'      => esc_html__( 'Line drawing length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 0, 'max' => 3000, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 900 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-draw: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'stagger',
			array(
				'label'       => esc_html__( 'Stagger', 'numbered-accordion' ),
				'description' => esc_html__( 'The gap between one hotspot arriving and the next, so they land in reading order rather than all at once.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 400, 'step' => 10 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 90 ),
			)
		);

		$this->add_control(
			'dim',
			array(
				'label'       => esc_html__( 'The others dim to', 'numbered-accordion' ),
				'description' => esc_html__( 'Hovering one hotspot dims the rest rather than brightening it, so the picture never gets louder than it started. 100 turns the dimming off.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 10, 'max' => 100 ) ),
				'default'     => array( 'unit' => '%', 'size' => 30 ),
				'selectors'   => array( '{{WRAPPER}} .espot' => '--espot-dim: calc({{SIZE}} / 100);' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'hover_ms',
			array(
				'label'      => esc_html__( 'Hover length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 60, 'max' => 900, 'step' => 20 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 320 ),
				'selectors'  => array( '{{WRAPPER}} .espot' => '--espot-hover-ms: {{SIZE}}ms;' ),
			)
		);

		$this->add_responsive_control(
			'columns_mobile',
			array(
				'label'       => esc_html__( 'Columns on a phone', 'numbered-accordion' ),
				'description' => esc_html__( 'Below 768px the leaders come off the picture and the figures become a plain list under it, because there is no room for lines that do not cross each other.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 1, 'max' => 3 ) ),
				'default'     => array( 'size' => 2 ),
				'selectors'   => array( '{{WRAPPER}} .espot' => '--espot-columns-mobile: {{SIZE}};' ),
				'separator'   => 'before',
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
	 * The figure is finished without the script: every label is readable and
	 * every number is already its final value. The script draws the leaders
	 * between them and counts up to what is already there.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$spots    = isset( $settings['spots'] ) && is_array( $settings['spots'] ) ? $settings['spots'] : array();

		if ( empty( $spots ) ) {
			return;
		}

		$count   = $this->slider( $settings, 'count_ms', 1200, 0, 4000 );
		$stagger = $this->slider( $settings, 'stagger', 90, 0, 400 );
		$stub    = $this->slider( $settings, 'stub', 18, 0, 120 );

		$image = isset( $settings['image']['url'] ) ? $settings['image']['url'] : '';
		$alt   = isset( $settings['alt'] ) ? $settings['alt'] : '';
		?>
		<div
			class="espot"
			data-espot-count="<?php echo esc_attr( (string) $count ); ?>"
			data-espot-stagger="<?php echo esc_attr( (string) $stagger ); ?>"
			data-espot-stub="<?php echo esc_attr( (string) $stub ); ?>"
		>
			<div class="espot__frame">
				<?php if ( '' !== $image ) : ?>
					<img
						class="espot__img"
						src="<?php echo esc_url( $image ); ?>"
						alt="<?php echo esc_attr( $alt ); ?>"
						loading="lazy"
						decoding="async"
					/>
				<?php endif; ?>

				<svg class="espot__lines" aria-hidden="true" preserveAspectRatio="none"></svg>

				<ul class="espot__list">
					<?php
					foreach ( $spots as $spot ) :
						$side     = isset( $spot['side'] ) && 'left' === $spot['side'] ? 'left' : 'right';
						$url      = isset( $spot['link']['url'] ) ? $spot['link']['url'] : '';
						$element  = '' !== $url ? 'a' : 'span';
						$external = ! empty( $spot['link']['is_external'] );
						$nofollow = ! empty( $spot['link']['nofollow'] );
						?>
						<li
							class="espot__item elementor-repeater-item-<?php echo esc_attr( isset( $spot['_id'] ) ? $spot['_id'] : '' ); ?>"
							data-espot-side="<?php echo esc_attr( $side ); ?>"
						>
							<<?php echo esc_html( $element ); ?>
								class="espot__hit"
								<?php if ( '' !== $url ) : ?>
									href="<?php echo esc_url( $url ); ?>"
									<?php echo $external ? ' target="_blank"' : ''; ?>
									<?php echo $external || $nofollow ? ' rel="' . esc_attr( trim( ( $nofollow ? 'nofollow ' : '' ) . ( $external ? 'noopener noreferrer' : '' ) ) ) . '"' : ''; ?>
								<?php endif; ?>
							>
								<span class="espot__dot" aria-hidden="true"></span>

								<span class="espot__label">
									<span class="espot__head">
										<?php if ( ! empty( $spot['icon']['value'] ) ) : ?>
											<span class="espot__icon">
												<?php Icons_Manager::render_icon( $spot['icon'], array( 'aria-hidden' => 'true' ) ); ?>
											</span>
										<?php endif; ?>

										<?php if ( ! empty( $spot['title'] ) ) : ?>
											<span class="espot__title"><?php echo esc_html( $spot['title'] ); ?></span>
										<?php endif; ?>
									</span>

									<?php if ( isset( $spot['value'] ) && '' !== $spot['value'] ) : ?>
										<span
											class="espot__value"
											data-espot-value="<?php echo esc_attr( $spot['value'] ); ?>"
										><?php echo esc_html( $spot['value'] ); ?></span>
									<?php endif; ?>

									<?php if ( ! empty( $spot['note'] ) ) : ?>
										<span class="espot__note"><?php echo esc_html( $spot['note'] ); ?></span>
									<?php endif; ?>
								</span>
							</<?php echo esc_html( $element ); ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}
}
