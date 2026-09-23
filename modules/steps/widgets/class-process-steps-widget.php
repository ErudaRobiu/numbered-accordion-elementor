<?php
/**
 * Process Steps widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Steps\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Steps\Steps_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A numbered process read downwards: the words on the left, and on the right a
 * small object standing in space that says the same thing without words.
 *
 * The figures are drawn rather than uploaded -- five arrangements of the same
 * four things, so a list of them reads as one drawing broken into steps rather
 * than as four pieces of clipart in a row. Nothing here carries a background,
 * so it sits on whatever colour the section behind it is.
 */
class Process_Steps_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'estp-process-steps';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Process Steps', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-number-field';
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
		return array( 'steps', 'process', 'timeline', 'numbered', 'how it works', 'stages', '3d', 'illustration' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Steps\Steps_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Steps\Steps_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_colour_controls();
		$this->register_type_controls();
	}

	/**
	 * The steps.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'section_steps',
			array( 'label' => esc_html__( 'Steps', 'numbered-accordion' ) )
		);

		$steps = new Repeater();

		$steps->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Heading', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$steps->add_control(
			'body',
			array(
				'label'       => esc_html__( 'Text', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
			)
		);

		$steps->add_control(
			'figure',
			array(
				'label'   => esc_html__( 'Illustration', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'converge',
				'options' => $this->figure_options(),
			)
		);

		$steps->add_control(
			'note',
			array(
				'label'       => esc_html__( 'Label on the line', 'numbered-accordion' ),
				'description' => esc_html__( 'Only the baseline illustration uses it. It is the word riding on the line it draws.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Baseline', 'numbered-accordion' ),
				'condition'   => array( 'figure' => 'datum' ),
			)
		);

		$steps->add_control(
			'number',
			array(
				'label'       => esc_html__( 'Number', 'numbered-accordion' ),
				'description' => esc_html__( 'Left empty it counts from one, padded to two digits. Fill it in to start somewhere else, or to use something that is not a number.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'steps',
			array(
				'label'       => esc_html__( 'Steps', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $steps->get_controls(),
				'default'     => $this->default_steps(),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The shape of the list.
	 */
	private function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'sides',
			array(
				'label'       => esc_html__( 'Which side the illustration sits on', 'numbered-accordion' ),
				'description' => esc_html__( 'Alternating swaps the words and the illustration on every other step. The numbers stay in their own column either way — a sequence that zigzags stops reading as a sequence.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'right',
				'options'     => array(
					'right' => esc_html__( 'Always on the right', 'numbered-accordion' ),
					'alt'   => esc_html__( 'Alternating', 'numbered-accordion' ),
				),
			)
		);

		$this->add_responsive_control(
			'zoom',
			array(
				'label'       => esc_html__( 'Illustration size', 'numbered-accordion' ),
				'description' => esc_html__( 'How big the drawing is, which is a different question from how much room it has. The eye moves back as it grows, so the perspective stays as drawn instead of hardening.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0.6, 'max' => 2.2, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 1.4 ),
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-zoom: {{SIZE}};' ),
			)
		);

		$this->add_responsive_control(
			'fig_width',
			array(
				'label'      => esc_html__( 'Illustration width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 180, 'max' => 620 ), 'vw' => array( 'min' => 10, 'max' => 48 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 380 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-fig: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'fig_height',
			array(
				'label'      => esc_html__( 'Illustration height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 130, 'max' => 460 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 280 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-fig-h: {{SIZE}}{{UNIT}}; --estp-fig-h-mobile: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'step_gap',
			array(
				'label'      => esc_html__( 'Space between steps', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 200 ), 'rem' => array( 'min' => 1, 'max' => 12, 'step' => 0.25 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 72 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-step-gap: {{SIZE}}{{UNIT}}; --estp-step-gap-mobile: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'col_gap',
			array(
				'label'      => esc_html__( 'Space between the columns', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 140 ), 'rem' => array( 'min' => 1, 'max' => 9, 'step' => 0.25 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 56 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'disc_size',
			array(
				'label'      => esc_html__( 'Number disc', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 26, 'max' => 72 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-disc: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'persp',
			array(
				'label'       => esc_html__( 'How close the eye is', 'numbered-accordion' ),
				'description' => esc_html__( 'The distance from the viewer to the illustrations. Smaller is a harder perspective; under about 400 it stops reading as depth and starts reading as a fisheye.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 300, 'max' => 2000, 'step' => 20 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 900 ),
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-persp: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The colours.
	 */
	private function register_colour_controls() {
		$this->start_controls_section(
			'section_colour',
			array(
				'label' => esc_html__( 'Colours', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'       => esc_html__( 'Accent', 'numbered-accordion' ),
				'description' => esc_html__( 'The numbered discs, the line between them, and the single element in each illustration that is the answer.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#2f7a4a',
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ink',
			array(
				'label'     => esc_html__( 'Headings', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1b3a61',
				'selectors' => array( '{{WRAPPER}} .estp' => '--estp-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'body_colour',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#55708b',
				'selectors' => array( '{{WRAPPER}} .estp' => '--estp-body: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'face',
			array(
				'label'       => esc_html__( 'Illustration faces', 'numbered-accordion' ),
				'description' => esc_html__( 'The widget has no background of its own, so this is what separates the drawings from the section behind them. On a dark section make it a light translucent white; on a light one, near-white.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => 'rgba(255, 255, 255, 0.86)',
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-face: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'hairline',
			array(
				'label'     => esc_html__( 'Illustration edges', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(27, 58, 97, 0.16)',
				'selectors' => array( '{{WRAPPER}} .estp' => '--estp-hairline: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ring',
			array(
				'label'       => esc_html__( 'Orbit rings', 'numbered-accordion' ),
				'description' => esc_html__( 'Only the running illustration uses them. A ring has no face to give it presence, so it needs a stronger line than the other edges.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => 'rgba(27, 58, 97, 0.3)',
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-ring: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'shadow',
			array(
				'label'       => esc_html__( 'Shadow under the illustration', 'numbered-accordion' ),
				'description' => esc_html__( 'What says the drawing is standing on something rather than floating in the page. Clear it on a dark section, where a dark shadow does nothing.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => 'rgba(27, 58, 97, 0.28)',
				'selectors'   => array( '{{WRAPPER}} .estp' => '--estp-shadow: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The type.
	 */
	private function register_type_controls() {
		$this->start_controls_section(
			'section_type',
			array(
				'label' => esc_html__( 'Typography', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Heading', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .estp__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'label'    => esc_html__( 'Text', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .estp__body',
			)
		);

		$this->add_control(
			'disc_font',
			array(
				'label'      => esc_html__( 'Number size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 9, 'max' => 24 ), 'rem' => array( 'min' => 0.5, 'max' => 1.6, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 13 ),
				'selectors'  => array( '{{WRAPPER}} .estp' => '--estp-disc-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'       => esc_html__( 'Heading tag', 'numbered-accordion' ),
				'description' => esc_html__( 'Pick the one that fits the outline of the page, not the one that looks right. The size is a typography setting.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'h3',
				'options'     => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'div'  => esc_html__( 'Not a heading', 'numbered-accordion' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The illustration choices, labelled.
	 *
	 * @return array<string, string>
	 */
	private function figure_options() {
		$out = array();

		foreach ( Steps_Content::figures() as $id => $figure ) {
			$out[ $id ] = esc_html__( $figure['label'], 'numbered-accordion' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

		return $out;
	}

	/**
	 * The steps the widget ships with.
	 *
	 * A real process rather than lorem, because the thing being judged is
	 * whether an illustration sits well beside two lines of prose, and that
	 * cannot be judged against placeholder text.
	 *
	 * @return array
	 */
	private function default_steps() {
		return array(
			array(
				'title'  => esc_html__( 'Agree the method', 'numbered-accordion' ),
				'body'   => esc_html__( 'Which calculation, which inputs, which baseline — before anything is installed.', 'numbered-accordion' ),
				'figure' => 'converge',
			),
			array(
				'title'  => esc_html__( 'Instrument to it', 'numbered-accordion' ),
				'body'   => esc_html__( 'Sensors placed and rated for the accuracy the method assumes.', 'numbered-accordion' ),
				'figure' => 'probe',
			),
			array(
				'title'  => esc_html__( 'Establish the baseline', 'numbered-accordion' ),
				'body'   => esc_html__( 'What the facility used before, over a period the program will accept.', 'numbered-accordion' ),
				'figure' => 'datum',
				'note'   => esc_html__( 'Baseline', 'numbered-accordion' ),
			),
			array(
				'title'  => esc_html__( 'Report the period', 'numbered-accordion' ),
				'body'   => esc_html__( 'Operating data connected to the energy delivered, for the reporting period agreed.', 'numbered-accordion' ),
				'figure' => 'sheets',
			),
		);
	}

	/**
	 * The heading tag, as an element name that is safe to print.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function title_tag( $settings ) {
		$allowed = array( 'h2', 'h3', 'h4', 'h5', 'div' );
		$asked   = Steps_Content::text( $settings, 'title_tag' );

		return in_array( $asked, $allowed, true ) ? $asked : 'h3';
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$steps = Steps_Content::visible_steps( $settings );

		if ( empty( $steps ) ) {
			return;
		}

		$tag   = $this->title_tag( $settings );
		$sides = 'alt' === Steps_Content::text( $settings, 'sides' ) ? ' estp--alt' : '';
		?>
		<div class="estp<?php echo esc_attr( $sides ); ?>">
			<?php foreach ( $steps as $position => $step ) : ?>
				<?php
				$figure_id = Steps_Content::figure_id( $step );
				$figure    = Steps_Content::figure( $figure_id );
				$body      = Steps_Content::text( $step, 'body' );
				$title     = Steps_Content::text( $step, 'title' );
				?>
				<div class="estp__step">
					<div class="estp__rail">
						<div class="estp__disc"><?php echo esc_html( Steps_Content::number( $step, $position ) ); ?></div>
					</div>

					<div class="estp__words">
						<?php if ( '' !== $title ) : ?>
							<<?php echo esc_attr( $tag ); ?> class="estp__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $tag ); ?>>
						<?php endif; ?>

						<?php if ( '' !== $body ) : ?>
							<p class="estp__body"><?php echo esc_html( $body ); ?></p>
						<?php endif; ?>
					</div>

					<?php
					/*
					 * aria-hidden, and that is not laziness.
					 *
					 * The figure is the heading said again without words. It
					 * carries nothing the text beside it does not, so
					 * describing it to a screen reader would be reading the
					 * step twice -- and any alt text honest about what is
					 * actually there ("three overlapping rectangles") is worse
					 * than silence.
					 */
					?>
					<div class="estp__fig estp__fig--<?php echo esc_attr( $figure_id ); ?>" aria-hidden="true">
						<?php
						/*
						 * Outside the scene on purpose. Inside it the shadow
						 * was scaled by the illustration size along with
						 * everything else -- blur included -- and at the top
						 * of the range it became a smear that hung below the
						 * box and landed on the next step's heading.
						 */
						?>
						<span class="estp__ground"></span>
						<div class="estp__scene">
							<?php
							$parts = isset( $figure['parts'] ) ? $figure['parts'] : array();
							$deck  = ! empty( $figure['deck'] );

							if ( $deck ) {
								echo '<span class="estp__deck">';
							}

							foreach ( $parts as $part ) :
								$style = Steps_Content::part_style( $part );
								$note  = ! empty( $part['note'] ) ? Steps_Content::text( $step, 'note' ) : '';
								?>
								<span class="<?php echo esc_attr( Steps_Content::part_class( $part ) ); ?>"
									<?php echo '' !== $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>
									<?php echo '' !== $note ? ' data-estp-note="' . esc_attr( $note ) . '"' : ''; ?>></span>
								<?php
							endforeach;

							if ( $deck ) {
								echo '</span>';
							}
							?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
