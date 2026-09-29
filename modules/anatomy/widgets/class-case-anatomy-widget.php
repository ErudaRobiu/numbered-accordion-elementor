<?php
/**
 * Case Anatomy widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Anatomy\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Anatomy\Anatomy_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Case studies answered in the same six questions, one project per logo tab.
 */
class Case_Anatomy_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ecan-case-anatomy';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Case Anatomy', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
		return array( 'case', 'study', 'evidence', 'results', 'tabs', 'projects', 'anatomy' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Anatomy\Anatomy_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Anatomy\Anatomy_Module::SCRIPT_HANDLE );
	}

	/**
	 * Where the bundled logos live.
	 *
	 * @return string
	 */
	private function logo_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . Anatomy_Content::LOGO_DIR;
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_project_controls();
		$this->register_question_controls();
		$this->register_card_style_controls();
		$this->register_tile_style_controls();
	}

	/**
	 * The projects, one per tab.
	 */
	private function register_project_controls() {
		$this->start_controls_section(
			'section_projects',
			array( 'label' => esc_html__( 'Projects', 'numbered-accordion' ) )
		);

		$labels   = Anatomy_Content::default_labels();
		$projects = new Repeater();

		$projects->add_control(
			'logo',
			array(
				'label'   => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);

		$projects->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Organisation name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => esc_html__( 'Read aloud in place of the logo.', 'numbered-accordion' ),
			)
		);

		$projects->add_control(
			'dark',
			array(
				'label'        => esc_html__( 'Dark logo chip', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'For a pale logo that disappears on white.', 'numbered-accordion' ),
			)
		);

		for ( $i = 1; $i <= Anatomy_Content::QUESTIONS; $i++ ) {
			$projects->add_control(
				'answer_' . $i,
				array(
					/* translators: 1: question number, 2: the question's default label */
					'label' => sprintf( esc_html__( '%1$02d · %2$s', 'numbered-accordion' ), $i, $labels[ $i - 1 ] ),
					'type'  => Controls_Manager::TEXTAREA,
					'rows'  => 2,
				)
			);
		}

		$projects->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link under the answers', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Optional, e.g. the case PDF.', 'numbered-accordion' ),
			)
		);

		$projects->add_control(
			'link_text',
			array(
				'label'       => esc_html__( 'Link text', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Read the case (PDF)', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'projects',
			array(
				'label'       => esc_html__( 'Projects', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $projects->get_controls(),
				'default'     => $this->default_projects(),
				'title_field' => '{{{ name }}}',
				'max_items'   => Anatomy_Content::MAX_PROJECTS,
			)
		);

		$this->add_control(
			'default_tab',
			array(
				'label'       => esc_html__( 'Opens on project', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => Anatomy_Content::MAX_PROJECTS,
				'default'     => 1,
				'description' => esc_html__( 'Counted from the left.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'tablist_label',
			array(
				'label'       => esc_html__( 'Tabs read aloud as', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( 'Choose a project', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'highlight',
			array(
				'label'        => esc_html__( 'Highlight the last answer', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'The result tile goes navy, so the outcome is the first thing read.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Animate on switch', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'The answers rise in turn when another project is picked. Visitors who ask for less motion never get it.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The six questions, once for every project.
	 */
	private function register_question_controls() {
		$this->start_controls_section(
			'section_questions',
			array( 'label' => esc_html__( 'Questions', 'numbered-accordion' ) )
		);

		foreach ( Anatomy_Content::default_labels() as $i => $label ) {
			$this->add_control(
				'label_' . ( $i + 1 ),
				array(
					/* translators: %02d: question number */
					'label'       => sprintf( esc_html__( 'Question %02d', 'numbered-accordion' ), $i + 1 ),
					'type'        => Controls_Manager::TEXT,
					'label_block' => true,
					'default'     => $label,
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * The page 25 projects as repeater rows.
	 *
	 * @return array
	 */
	private function default_projects() {
		$rows = array();

		foreach ( Anatomy_Content::default_projects() as $project ) {
			$row = array(
				'logo'      => array( 'url' => $this->logo_url() . $project['file'] ),
				'name'      => $project['name'],
				'dark'      => $project['dark'] ? 'yes' : '',
				'link'      => array( 'url' => $project['link'] ),
				'link_text' => $project['link_text'],
			);

			foreach ( $project['answers'] as $i => $answer ) {
				$row[ 'answer_' . ( $i + 1 ) ] = $answer;
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * The card and its tabs.
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card and tabs', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colours = array(
			'card_bg'     => array( esc_html__( 'Card', 'numbered-accordion' ), '--ecan-card' ),
			'card_border' => array( esc_html__( 'Borders', 'numbered-accordion' ), '--ecan-line' ),
			'tab_bg'      => array( esc_html__( 'Tab', 'numbered-accordion' ), '--ecan-tab' ),
			'tab_active'  => array( esc_html__( 'Selected tab outline', 'numbered-accordion' ), '--ecan-green' ),
			'chip_dark'   => array( esc_html__( 'Dark logo chip', 'numbered-accordion' ), '--ecan-chip' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .ecan' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ecan' => 'padding: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ecan' => '--ecan-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The answer tiles.
	 */
	private function register_tile_style_controls() {
		$this->start_controls_section(
			'section_style_tiles',
			array(
				'label' => esc_html__( 'Answers', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colours = array(
			'tile_bg'       => array( esc_html__( 'Tile', 'numbered-accordion' ), '--ecan-tile' ),
			'number_colour' => array( esc_html__( 'Number', 'numbered-accordion' ), '--ecan-number' ),
			'label_colour'  => array( esc_html__( 'Question', 'numbered-accordion' ), '--ecan-label' ),
			'answer_colour' => array( esc_html__( 'Answer', 'numbered-accordion' ), '--ecan-answer' ),
			'result_bg'     => array( esc_html__( 'Result tile', 'numbered-accordion' ), '--ecan-result' ),
			'result_number' => array( esc_html__( 'Result number', 'numbered-accordion' ), '--ecan-result-number' ),
			'result_label'  => array( esc_html__( 'Result question', 'numbered-accordion' ), '--ecan-result-label' ),
			'result_answer' => array( esc_html__( 'Result answer', 'numbered-accordion' ), '--ecan-result-answer' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .ecan' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$type = array(
			'number_typography' => array( esc_html__( 'Number type', 'numbered-accordion' ), '.ecan__num' ),
			'label_typography'  => array( esc_html__( 'Question type', 'numbered-accordion' ), '.ecan__label' ),
			'answer_typography' => array( esc_html__( 'Answer type', 'numbered-accordion' ), '.ecan__answer' ),
			'link_typography'   => array( esc_html__( 'Link type', 'numbered-accordion' ), '.ecan__link' ),
		);

		foreach ( $type as $id => $group ) {
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
	 * Print a tab's logo.
	 *
	 * @param array $project Built project.
	 */
	private function render_logo( $project ) {
		if ( '' === $project['logo'] ) {
			echo '<span class="ecan__name">' . esc_html( $project['name'] ) . '</span>';
			return;
		}

		printf(
			'<img class="ecan__logo%1$s" src="%2$s" alt="%3$s" decoding="async" />',
			$project['dark'] ? ' ecan__logo--dark' : '',
			esc_url( $project['logo'] ),
			esc_attr( $project['name'] )
		);
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$projects = Anatomy_Content::build(
			isset( $settings['projects'] ) ? $settings['projects'] : array(),
			Anatomy_Content::labels( $settings ),
			isset( $settings['highlight'] ) && 'yes' === $settings['highlight']
		);

		if ( empty( $projects ) ) {
			return;
		}

		$open    = Anatomy_Content::default_index( isset( $settings['default_tab'] ) ? $settings['default_tab'] : 1, count( $projects ) );
		$label   = isset( $settings['tablist_label'] ) && is_scalar( $settings['tablist_label'] ) ? trim( (string) $settings['tablist_label'] ) : '';
		$animate = isset( $settings['animate'] ) && 'yes' === $settings['animate'];
		$uid     = 'ecan-' . ( method_exists( $this, 'get_id' ) ? $this->get_id() : substr( md5( implode( ',', array_column( $projects, 'name' ) ) ), 0, 7 ) );
		?>
		<div class="ecan<?php echo $animate ? ' ecan--animate' : ''; ?>" style="--ecan-count:<?php echo (int) count( $projects ); ?>">
			<div class="ecan__tabs" role="tablist"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
				<?php foreach ( $projects as $i => $project ) : ?>
					<button type="button" class="ecan__tab" role="tab"
						id="<?php echo esc_attr( $uid . '-t-' . $i ); ?>"
						aria-controls="<?php echo esc_attr( $uid . '-p-' . $i ); ?>"
						aria-selected="<?php echo $i === $open ? 'true' : 'false'; ?>"
						tabindex="<?php echo $i === $open ? '0' : '-1'; ?>">
						<?php $this->render_logo( $project ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $projects as $i => $project ) : ?>
				<div class="ecan__panel" role="tabpanel" tabindex="0"
					id="<?php echo esc_attr( $uid . '-p-' . $i ); ?>"
					aria-labelledby="<?php echo esc_attr( $uid . '-t-' . $i ); ?>"
					<?php echo $i === $open ? '' : 'hidden'; ?>>
					<ol class="ecan__rows">
						<?php foreach ( $project['tiles'] as $tile ) : ?>
							<li class="ecan__tile<?php echo $tile['result'] ? ' ecan__tile--result' : ''; ?>">
								<b class="ecan__num" aria-hidden="true"><?php echo esc_html( $tile['number'] ); ?></b>
								<?php if ( '' !== $tile['label'] ) : ?>
									<span class="ecan__label"><?php echo esc_html( $tile['label'] ); ?></span>
								<?php endif; ?>
								<p class="ecan__answer"><?php echo esc_html( $tile['answer'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ol>
					<?php if ( '' !== $project['link'] ) : ?>
						<?php $rel = array_filter( array( $project['nofollow'] ? 'nofollow' : '', $project['external'] ? 'noopener' : '' ) ); ?>
						<a class="ecan__link" href="<?php echo esc_url( $project['link'] ); ?>"<?php echo $project['external'] ? ' target="_blank"' : ''; ?><?php echo $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : ''; ?>><?php echo esc_html( '' !== $project['link_text'] ? $project['link_text'] : $project['link'] ); ?> <span aria-hidden="true">→</span></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
