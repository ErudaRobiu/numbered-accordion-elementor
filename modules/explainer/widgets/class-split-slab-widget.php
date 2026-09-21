<?php
/**
 * Split Slab widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Explainer\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Explainer\Explainer_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One rounded object split down the middle by a coloured bar: a light panel
 * that states the problem and an ink panel that answers it.
 *
 * Two fixed panels rather than a repeater. The whole point of the shape is
 * that there are exactly two sides to the argument, and a third would break
 * both the grid and the reading.
 */
class Split_Slab_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eexp-split-slab';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Split Slab', 'numbered-accordion' );
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
		return array( 'slab', 'split', 'panels', 'steps', 'problem', 'solution', 'two', 'compare' );
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
		$this->register_panel_one_controls();
		$this->register_panel_two_controls();

		$this->register_slab_style_controls();
		$this->register_panel_one_style_controls();
		$this->register_panel_two_style_controls();
	}

	/**
	 * Step controls, the same shape on both panels.
	 *
	 * @param string $prefix  Control prefix, p1 or p2.
	 * @param string $default Default label.
	 */
	private function add_step_controls( $prefix, $default ) {
		$this->add_control(
			$prefix . '_step',
			array(
				'label'       => esc_html__( 'Step number', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'e.g. 01', 'numbered-accordion' ),
				'description' => esc_html__( 'Empty hides the number and closes the gap before the label.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			$prefix . '_label',
			array(
				'label'       => esc_html__( 'Step label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => $default,
			)
		);
	}

	/**
	 * The light panel.
	 */
	private function register_panel_one_controls() {
		$this->start_controls_section(
			'section_panel_one',
			array( 'label' => esc_html__( 'Panel 1 (light)', 'numbered-accordion' ) )
		);

		$this->add_step_controls( 'p1', esc_html__( 'What arrives', 'numbered-accordion' ) );

		$this->add_control(
			'p1_title',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'The problem, named', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_title_tag',
			array(
				'label'   => esc_html__( 'Heading tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'p1_body',
			array(
				'label'   => esc_html__( 'Body', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Two lines on what the reader is up against.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_image',
			array(
				'label'       => esc_html__( 'Image', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'This panel stays light, so a cut-out render with dark labels baked into it reads correctly.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_loads_label',
			array(
				'label'       => esc_html__( 'Footer label', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'What it has to handle', 'numbered-accordion' ),
			)
		);

		$pills = new Repeater();

		$pills->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Item', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p1_pills',
			array(
				'label'       => esc_html__( 'Footer pills', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $pills->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => $this->default_pills(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The ink panel.
	 */
	private function register_panel_two_controls() {
		$this->start_controls_section(
			'section_panel_two',
			array( 'label' => esc_html__( 'Panel 2 (ink)', 'numbered-accordion' ) )
		);

		$this->add_step_controls( 'p2', esc_html__( 'What answers it', 'numbered-accordion' ) );

		$this->add_control(
			'p2_title',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'The answer, shown', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_title_tag',
			array(
				'label'   => esc_html__( 'Heading tag', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
			)
		);

		$this->add_control(
			'p2_body',
			array(
				'label'   => esc_html__( 'Body', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Two lines on how it is answered.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_image',
			array(
				'label'       => esc_html__( 'Diagram', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Artwork drawn on black. It is screened onto the plate, so the black falls away and only the drawing remains.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_plate',
			array(
				'label'        => esc_html__( 'Put it on a plate', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'The bordered black panel the diagram is screened onto. Switch it off for artwork that already has a background of its own.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_caption_left',
			array(
				'label'       => esc_html__( 'Caption, left', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'In', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_caption_right',
			array(
				'label'       => esc_html__( 'Caption, right', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Out →', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_motes',
			array(
				'label'       => esc_html__( 'Drifting motes', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 7,
				'step'        => 1,
				'default'     => 5,
				'description' => esc_html__( 'Specks that drift across the diagram. Zero switches them off, and they never run for a visitor who asked for reduced motion.', 'numbered-accordion' ),
			)
		);

		$specs = new Repeater();

		$specs->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Label', 'numbered-accordion' ),
			)
		);

		$specs->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Value', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Value', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'p2_specs',
			array(
				'label'       => esc_html__( 'Footer specs', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $specs->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => $this->default_specs(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The slab's own shape.
	 */
	private function register_slab_style_controls() {
		$this->start_controls_section(
			'section_style_slab',
			array(
				'label' => esc_html__( 'Slab', 'numbered-accordion' ),
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
			'bar_colour',
			array(
				'label'     => esc_html__( 'Divider bar', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp' => '--eexp-green: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_width',
			array(
				'label'      => esc_html__( 'Divider width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 24,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .eexp' => '--eexp-bar: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'slab_radius',
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
					'{{WRAPPER}} .eexp' => '--eexp-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'slab_split',
			array(
				'label'       => esc_html__( 'Weight of the light panel', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 30,
						'max' => 70,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 56,
				),
				'description' => esc_html__( 'How much of the width the light panel takes. The ink panel takes the rest.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp-slab' => 'grid-template-columns: {{SIZE}}% var(--eexp-bar) auto;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The light panel's colours.
	 */
	private function register_panel_one_style_controls() {
		$this->start_controls_section(
			'section_style_panel_one',
			array(
				'label' => esc_html__( 'Panel 1 (light)', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'p1_background',
			array(
				'label'     => esc_html__( 'Background', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_title_colour',
			array(
				'label'     => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light .eexp-panel__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_text_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--light' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_pill_colour',
			array(
				'label'     => esc_html__( 'Pill text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p1_pill_border',
			array(
				'label'     => esc_html__( 'Pill border', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-loads li' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The ink panel: a background group over the colour, plus the veil that
	 * keeps the text legible on top of whatever gets uploaded.
	 */
	private function register_panel_two_style_controls() {
		$this->start_controls_section(
			'section_style_panel_two',
			array(
				'label' => esc_html__( 'Panel 2 (ink)', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'p2_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .eexp-panel--ink',
			)
		);

		$this->add_control(
			'p2_dim',
			array(
				'label'       => esc_html__( 'Darken behind the text', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'     => array(
					'unit' => '%',
					'size' => 0,
				),
				'description' => esc_html__( 'A black veil between the background and the words. Raise it until the white text and the accent hold their contrast over a picture.', 'numbered-accordion' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp' => '--eexp-dim: calc({{SIZE}} / 100);',
				),
			)
		);

		$this->add_control(
			'p2_title_colour',
			array(
				'label'     => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--ink .eexp-panel__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p2_text_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .eexp-panel--ink' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'p2_plate_colour',
			array(
				'label'       => esc_html__( 'Diagram plate', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'condition'   => array( 'p2_plate' => 'yes' ),
				'selectors'   => array(
					'{{WRAPPER}} .eexp-flow' => 'background-color: {{VALUE}};',
				),
				'description' => esc_html__( 'The artwork is screened over this, so a near-black plate keeps the drawing clean.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ *
	 * Defaults
	 * ------------------------------------------------------------------ */

	/**
	 * The pills the light panel ships with.
	 *
	 * @return array
	 */
	private function default_pills() {
		$rows = array();

		foreach ( array( 'One', 'Two', 'Three', 'Four' ) as $item ) {
			$rows[] = array( 'text' => $item );
		}

		return $rows;
	}

	/**
	 * The spec rows the ink panel ships with.
	 *
	 * @return array
	 */
	private function default_specs() {
		$rows = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$rows[] = array(
				'label' => esc_html__( 'Label', 'numbered-accordion' ),
				'value' => esc_html__( 'Value', 'numbered-accordion' ),
			);
		}

		return $rows;
	}

	/* ------------------------------------------------------------------ *
	 * Render
	 * ------------------------------------------------------------------ */

	/**
	 * One of the four heading tags, or the default.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Control key.
	 * @return string
	 */
	private function heading_tag( $settings, $key ) {
		$tag = Explainer_Content::text( $settings, $key );

		return in_array( $tag, array( 'h2', 'h3', 'h4', 'div' ), true ) ? $tag : 'h3';
	}

	/**
	 * Print one panel's step line.
	 *
	 * @param array  $settings Settings.
	 * @param string $prefix   Control prefix.
	 */
	private function render_step( $settings, $prefix ) {
		$number = Explainer_Content::text( $settings, $prefix . '_step' );
		$label  = Explainer_Content::text( $settings, $prefix . '_label' );

		if ( '' === $number && '' === $label ) {
			return;
		}
		?>
		<p class="<?php echo esc_attr( Explainer_Content::step_classes( $number ) ); ?> eexp-label">
			<?php if ( Explainer_Content::has_step( $number ) ) : ?>
				<b class="eexp-step__num"><?php echo esc_html( Explainer_Content::step_text( $number ) ); ?></b>
			<?php endif; ?>
			<?php if ( '' !== $label ) : ?>
				<span><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Print a panel image.
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
					'loading' => 'lazy',
					'sizes'   => Explainer_Content::panel_sizes_attr(),
				)
			);

			return;
		}
		?>
		<img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy" />
		<?php
	}

	/**
	 * Print the light panel.
	 *
	 * @param array $settings Settings.
	 */
	private function render_panel_one( $settings ) {
		$tag   = $this->heading_tag( $settings, 'p1_title_tag' );
		$title = Explainer_Content::text( $settings, 'p1_title' );
		$body  = Explainer_Content::text( $settings, 'p1_body' );
		$label = Explainer_Content::text( $settings, 'p1_loads_label' );
		$pills = Explainer_Content::rows( $settings, 'p1_pills' );
		?>
		<div class="eexp-panel eexp-panel--light">
			<?php $this->render_step( $settings, 'p1' ); ?>

			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="eexp-heading eexp-panel__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== $body ) : ?>
				<p class="eexp-panel__body"><?php echo esc_html( $body ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['p1_image']['url'] ) ) : ?>
				<div class="eexp-stage">
					<?php $this->render_image( $settings['p1_image'], 'eexp-stage__img' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $pills ) || '' !== $label ) : ?>
				<div class="eexp-loads">
					<?php if ( '' !== $label ) : ?>
						<p class="eexp-loads__label"><?php echo esc_html( $label ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $pills ) ) : ?>
						<ul>
							<?php foreach ( $pills as $pill ) : ?>
								<?php
								$text = Explainer_Content::text( $pill, 'text' );

								if ( '' === $text ) {
									continue;
								}
								?>
								<li><?php echo esc_html( $text ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Print the ink panel.
	 *
	 * @param array $settings Settings.
	 */
	private function render_panel_two( $settings ) {
		$tag     = $this->heading_tag( $settings, 'p2_title_tag' );
		$title   = Explainer_Content::text( $settings, 'p2_title' );
		$body    = Explainer_Content::text( $settings, 'p2_body' );
		$specs   = Explainer_Content::rows( $settings, 'p2_specs' );
		$plate   = 'yes' === Explainer_Content::text( $settings, 'p2_plate' );
		$left    = Explainer_Content::text( $settings, 'p2_caption_left' );
		$right   = Explainer_Content::text( $settings, 'p2_caption_right' );
		$motes   = Explainer_Content::mote_count( isset( $settings['p2_motes'] ) ? $settings['p2_motes'] : 0 );
		$classes = $plate ? 'eexp-flow' : 'eexp-flow eexp-flow--bare';
		?>
		<div class="eexp-panel eexp-panel--ink">
			<?php $this->render_step( $settings, 'p2' ); ?>

			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="eexp-heading eexp-panel__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php endif; ?>

			<?php if ( '' !== $body ) : ?>
				<p class="eexp-panel__body"><?php echo esc_html( $body ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['p2_image']['url'] ) ) : ?>
				<div class="eexp-stage">
					<figure class="<?php echo esc_attr( $classes ); ?>">
						<?php $this->render_image( $settings['p2_image'], 'eexp-flow__img' ); ?>

						<?php for ( $i = 0; $i < $motes; $i++ ) : ?>
							<span class="eexp-mote" style="<?php echo esc_attr( Explainer_Content::mote_style( $i, $motes ) ); ?>"></span>
						<?php endfor; ?>

						<?php if ( '' !== $left || '' !== $right ) : ?>
							<figcaption class="eexp-flow__caption">
								<span><?php echo esc_html( $left ); ?></span>
								<i><?php echo esc_html( $right ); ?></i>
							</figcaption>
						<?php endif; ?>
					</figure>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $specs ) ) : ?>
				<ul class="eexp-specs">
					<?php foreach ( $specs as $spec ) : ?>
						<?php
						$spec_label = Explainer_Content::text( $spec, 'label' );
						$spec_value = Explainer_Content::text( $spec, 'value' );

						if ( '' === $spec_label && '' === $spec_value ) {
							continue;
						}
						?>
						<li>
							<b><?php echo esc_html( $spec_label ); ?></b>
							<span><?php echo esc_html( $spec_value ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="eexp eexp-no-js">
			<div class="eexp-slab eexp-rise">
				<?php $this->render_panel_one( $settings ); ?>
				<div class="eexp-bar" role="presentation"></div>
				<?php $this->render_panel_two( $settings ); ?>
			</div>
		</div>
		<?php
	}
}
