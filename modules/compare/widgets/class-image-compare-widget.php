<?php
/**
 * Image Compare widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Compare\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Compare\Compare_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Two pictures in one frame with a divider dragged across them.
 */
class Image_Compare_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ecmp-image-compare';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Image Compare', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-before-after';
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
		return array( 'compare', 'before', 'after', 'slider', 'reveal', 'swipe', 'twentytwenty', 'images' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Compare\Compare_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Compare\Compare_Module::SCRIPT_HANDLE );
	}

	/**
	 * The grip mark, as inline SVG.
	 *
	 * Inline because it is one glyph and a sprite request for it would cost
	 * more than the markup does.
	 *
	 * @return string
	 */
	private function grip_icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 6-6 6 6 6"/><path d="m15 6 6 6-6 6"/></svg>';
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_behaviour_controls();
		$this->register_frame_style_controls();
		$this->register_divider_style_controls();
		$this->register_label_style_controls();
	}

	/**
	 * The two pictures and their labels.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_images',
			array( 'label' => esc_html__( 'Pictures', 'numbered-accordion' ) )
		);

		$this->add_control(
			'before_image',
			array(
				'label'       => esc_html__( 'First picture', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'The one underneath. It sits in the flow and gives the frame its height, so nothing shifts as it loads.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'after_image',
			array(
				'label'       => esc_html__( 'Second picture', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Revealed as the divider moves. Shot from the same spot, it reads as a change rather than as two pictures.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'before_label',
			array(
				'label'       => esc_html__( 'Label on the first', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Before', 'numbered-accordion' ),
				'description' => esc_html__( 'Empty hides it.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'after_label',
			array(
				'label'       => esc_html__( 'Label on the second', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'After', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * How it moves.
	 */
	private function register_behaviour_controls() {
		$this->start_controls_section(
			'section_behaviour',
			array( 'label' => esc_html__( 'Behaviour', 'numbered-accordion' ) )
		);

		$this->add_control(
			'orientation',
			array(
				'label'   => esc_html__( 'Direction', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => array(
					'horizontal' => esc_html__( 'Side to side', 'numbered-accordion' ),
					'vertical'   => esc_html__( 'Up and down', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'move_on',
			array(
				'label'       => esc_html__( 'Moves on', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'drag',
				'options'     => array(
					'drag'  => esc_html__( 'Drag or click', 'numbered-accordion' ),
					'hover' => esc_html__( 'Hover', 'numbered-accordion' ),
				),
				'description' => esc_html__( 'Hover follows the mouse and springs back when it leaves. A finger has no hover, so on a phone it drags either way.', 'numbered-accordion' ),
			)
		);

		$this->add_responsive_control(
			'start_position',
			array(
				'label'      => esc_html__( 'Starts at', 'numbered-accordion' ),
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
					'size' => 50,
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The frame.
	 */
	private function register_frame_style_controls() {
		$this->start_controls_section(
			'section_style_frame',
			array(
				'label' => esc_html__( 'Frame', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'frame_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ecmp' => '--ecmp-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_height',
			array(
				'label'       => esc_html__( 'Fixed height', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array(
						'min' => 120,
						'max' => 1000,
					),
					'vh' => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'description' => esc_html__( 'Leave it empty to let the first picture set the height. Set it and both pictures are cropped to fill.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .ecmp__frame'              => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ecmp__frame > .ecmp__img' => 'height: 100%; object-fit: cover;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The divider and its grip.
	 */
	private function register_divider_style_controls() {
		$this->start_controls_section(
			'section_style_divider',
			array(
				'label' => esc_html__( 'Divider', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'divider_colour',
			array(
				'label'     => esc_html__( 'Line', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ecmp__divider' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'divider_width',
			array(
				'label'      => esc_html__( 'Line thickness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 12,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ecmp' => '--ecmp-line: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'grip_background',
			array(
				'label'     => esc_html__( 'Grip', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ecmp__grip' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'grip_colour',
			array(
				'label'     => esc_html__( 'Arrows', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ecmp__grip' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'grip_size',
			array(
				'label'      => esc_html__( 'Grip size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 96,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ecmp' => '--ecmp-grip: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'focus_colour',
			array(
				'label'       => esc_html__( 'Focus ring', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					'{{WRAPPER}} .ecmp' => '--ecmp-accent: {{VALUE}};',
				),
				'description' => esc_html__( 'Shown when the divider is reached by keyboard. It has to be visible against both pictures.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The labels.
	 */
	private function register_label_style_controls() {
		$this->start_controls_section(
			'section_style_labels',
			array(
				'label'     => esc_html__( 'Labels', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'before_label!' => '',
				),
			)
		);

		$this->add_control(
			'label_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ecmp__label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_background',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ecmp__label' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .ecmp__label',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Print one picture.
	 *
	 * Through the media library where possible, so WordPress supplies the
	 * dimensions, the alt text and a srcset of its own.
	 *
	 * @param array  $image Media control value.
	 * @param string $class Class for the image.
	 */
	private function render_image( $image, $class ) {
		$url = isset( $image['url'] ) ? $image['url'] : '';
		$id  = isset( $image['id'] ) ? (int) $image['id'] : 0;

		if ( '' === $url ) {
			return;
		}

		if ( $id > 0 ) {
			echo wp_get_attachment_image(
				$id,
				'full',
				false,
				array(
					'class'   => $class,
					'sizes'   => '(max-width: 767px) 92vw, 100vw',
					'loading' => 'lazy',
				)
			);

			return;
		}
		?>
		<img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy" />
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$before = isset( $settings['before_image'] ) ? $settings['before_image'] : array();
		$after  = isset( $settings['after_image'] ) ? $settings['after_image'] : array();

		// One picture is not a comparison.
		if ( empty( $before['url'] ) || empty( $after['url'] ) ) {
			return;
		}

		$orientation   = isset( $settings['orientation'] ) ? $settings['orientation'] : 'horizontal';
		$move_on       = isset( $settings['move_on'] ) ? $settings['move_on'] : 'drag';
		$position      = Compare_Content::clamp_position( isset( $settings['start_position'] ) ? $settings['start_position'] : null );
		$before_label  = isset( $settings['before_label'] ) && is_scalar( $settings['before_label'] ) ? trim( (string) $settings['before_label'] ) : '';
		$after_label   = isset( $settings['after_label'] ) && is_scalar( $settings['after_label'] ) ? trim( (string) $settings['after_label'] ) : '';
		$slider_label  = Compare_Content::slider_label( $before_label, $after_label );
		?>
		<div class="<?php echo esc_attr( Compare_Content::root_classes( $orientation, $move_on ) ); ?>"
			style="<?php echo esc_attr( Compare_Content::frame_style( $position, $orientation ) ); ?>">
			<div class="ecmp__frame">
				<?php $this->render_image( $before, 'ecmp__img ecmp__img--before' ); ?>

				<div class="ecmp__clip">
					<?php $this->render_image( $after, 'ecmp__img ecmp__img--after' ); ?>
				</div>

				<?php if ( '' !== $before_label ) : ?>
					<span class="ecmp__label ecmp__label--before"><?php echo esc_html( $before_label ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $after_label ) : ?>
					<span class="ecmp__label ecmp__label--after"><?php echo esc_html( $after_label ); ?></span>
				<?php endif; ?>

				<input
					class="ecmp__range"
					type="range"
					min="0"
					max="100"
					step="0.1"
					value="<?php echo esc_attr( (string) $position ); ?>"
					aria-label="<?php echo esc_attr( $slider_label ); ?>" />

				<div class="ecmp__divider" aria-hidden="true">
					<span class="ecmp__grip"><?php echo $this->grip_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
			</div>
		</div>
		<?php
	}
}
