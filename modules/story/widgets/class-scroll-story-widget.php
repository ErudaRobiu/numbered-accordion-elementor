<?php
/**
 * Scroll Story widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Story\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A column of text beside a pinned panel that follows what you are reading.
 */
class Scroll_Story_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-scroll-story';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Scroll Story', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slides';
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
		return array( 'scroll', 'story', 'sticky', 'pinned', 'steps', 'features', 'showcase' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Story\Story_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Story\Story_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_motion_controls();
		$this->register_style_controls();
	}

	/**
	 * The items themselves.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Items', 'numbered-accordion' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Orchestrate Yard Execution', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'body',
			array(
				'label'   => esc_html__( 'Description', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Computer vision automates check-in, location, and validation gate to dock.', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label' => esc_html__( 'Image', 'numbered-accordion' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'title' => esc_html__( 'Orchestrate Yard Execution', 'numbered-accordion' ),
						'body'  => esc_html__( 'Computer vision automates check-in, location, and validation gate to dock.', 'numbered-accordion' ),
					),
					array(
						'title' => esc_html__( 'Build the System You Need', 'numbered-accordion' ),
						'body'  => esc_html__( 'Start with the applications you need most, then expand as your operations grow.', 'numbered-accordion' ),
					),
					array(
						'title' => esc_html__( 'Fast Payback', 'numbered-accordion' ),
						'body'  => esc_html__( 'All-inclusive, priced as a service. Measurable payback in months.', 'numbered-accordion' ),
					),
				),
			)
		);

		$this->add_control(
			'show_numbers',
			array(
				'label'        => esc_html__( 'Number the items', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Proportions, pinning, the notch.
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
			'text_width',
			array(
				'label'      => esc_html__( 'Text column', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'fr' ),
				'range'      => array( 'fr' => array( 'min' => 0.4, 'max' => 2, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'fr', 'size' => 1 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-text: {{SIZE}}fr;' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 48 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'sticky_top',
			array(
				'label'       => esc_html__( 'Pin below', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave room for a sticky site header.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 240 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-top: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'media_height',
			array(
				'label'      => esc_html__( 'Panel height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh' ),
				'range'      => array( 'vh' => array( 'min' => 40, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'vh', 'size' => 100 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-height: {{SIZE}}vh;' ),
			)
		);

		$this->add_responsive_control(
			'item_gap',
			array(
				'label'       => esc_html__( 'Space between items', 'numbered-accordion' ),
				'description' => esc_html__( 'How far you scroll from one item to the next.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'vh' ),
				'range'       => array( 'vh' => array( 'min' => 10, 'max' => 90 ) ),
				'default'     => array( 'unit' => 'vh', 'size' => 45 ),
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-item-gap: {{SIZE}}vh;' ),
			)
		);

		$this->add_control(
			'notch',
			array(
				'label'        => esc_html__( 'Notched edge', 'numbered-accordion' ),
				'description'  => esc_html__( 'Steps the panel out along its left edge, travelling as you scroll. Replaces the corner radius.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'notch_depth',
			array(
				'label'      => esc_html__( 'Notch depth', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-notch: {{SIZE}}px;' ),
				'condition'  => array( 'notch' => 'yes' ),
			)
		);

		$this->add_control(
			'notch_size',
			array(
				'label'      => esc_html__( 'Notch length', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 30, 'max' => 400 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 90 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-band-size: {{SIZE}}px;' ),
				'condition'  => array( 'notch' => 'yes' ),
			)
		);

		$this->add_control(
			'radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-radius: {{SIZE}}px;' ),
				'condition'  => array( 'notch!' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The sweep and the cross-fade.
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
			'sweep_step',
			array(
				'label'       => esc_html__( 'Letter stagger', 'numbered-accordion' ),
				'description' => esc_html__( 'The gap between one letter lighting up and the next.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 60, 'step' => 1 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 14 ),
			)
		);

		$this->add_control(
			'sweep_duration',
			array(
				'label'      => esc_html__( 'Letter duration', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 500 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-sweep: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'fade',
			array(
				'label'      => esc_html__( 'Image cross-fade', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 0, 'max' => 2000, 'step' => 50 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 600 ),
				'selectors'  => array( '{{WRAPPER}} .estry' => '--estry-fade: {{SIZE}}ms;' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Colours and type.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_colours',
			array(
				'label' => esc_html__( 'Colours', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'colour_dim',
			array(
				'label'     => esc_html__( 'Waiting text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#DDDDDD',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-dim: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'colour_flash',
			array(
				'label'       => esc_html__( 'Flash', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour each letter passes through on its way in.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#ABFF04',
				'selectors'   => array( '{{WRAPPER}} .estry' => '--estry-flash: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'colour_lit',
			array(
				'label'     => esc_html__( 'Read text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#052424',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-lit: {{VALUE}}; color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'colour_num',
			array(
				'label'     => esc_html__( 'Numbers', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#9AA4A4',
				'selectors' => array( '{{WRAPPER}} .estry' => '--estry-num: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .estry__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'selector' => '{{WRAPPER}} .estry__body',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 *
	 * The markup is complete and readable on its own: every item's text is in
	 * its lit colour, and the first image is simply the picture. The script
	 * dims and swaps only once it has marked the section ready.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$step = isset( $settings['sweep_step']['size'] ) ? (float) $settings['sweep_step']['size'] : 14;
		$step = max( 0, min( $step, 60 ) ) / 1000;

		$numbered = isset( $settings['show_numbers'] ) && 'yes' === $settings['show_numbers'];
		$notched  = isset( $settings['notch'] ) && 'yes' === $settings['notch'];
		?>
		<div class="estry" data-estry-step="<?php echo esc_attr( (string) $step ); ?>">
			<div class="estry__items">
				<?php foreach ( $items as $index => $item ) : ?>
					<div class="estry__item">
						<?php if ( $numbered ) : ?>
							<span class="estry__num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $item['title'] ) ) : ?>
							<h3 class="estry__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $item['body'] ) ) : ?>
							<p class="estry__body"><?php echo esc_html( $item['body'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="estry__media">
				<div class="estry__frame"<?php echo $notched ? ' data-estry-notch' : ''; ?>>
					<?php
					foreach ( $items as $item ) :
						if ( empty( $item['image']['url'] ) ) {
							continue;
						}
						?>
						<img
							class="estry__img"
							src="<?php echo esc_url( $item['image']['url'] ); ?>"
							alt="<?php echo esc_attr( ! empty( $item['title'] ) ? $item['title'] : '' ); ?>"
							loading="lazy"
							decoding="async"
						/>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
