<?php
/**
 * Case Studies widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\CaseStudies\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\CaseStudies\CaseStudies_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The case studies from the dashboard, as cards with sector chips.
 *
 * Nothing to fill in on the page: the cards are the published Case Studies,
 * in their Order. The panel only chooses which of them and how they read.
 */
class Case_Studies_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ecs-case-studies';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Case Studies', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
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
		return array( 'case', 'studies', 'results', 'projects', 'cards', 'evidence', 'portfolio' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\CaseStudies\CaseStudies_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\CaseStudies\CaseStudies_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_source_controls();
		$this->register_card_controls();
		$this->register_layout_style_controls();
		$this->register_card_style_controls();
	}

	/**
	 * Which cases, and the chips.
	 */
	private function register_source_controls() {
		$this->start_controls_section(
			'section_source',
			array( 'label' => esc_html__( 'Case studies', 'numbered-accordion' ) )
		);

		$manage = function_exists( 'admin_url' ) ? admin_url( 'edit.php?post_type=' . CaseStudies_Content::POST_TYPE ) : '#';

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => sprintf(
					/* translators: %s: link to the Case Studies list */
					esc_html__( 'The cards are your published case studies, in their Order. To add or change one, go to %s in the dashboard.', 'numbered-accordion' ),
					'<a href="' . esc_url( $manage ) . '" target="_blank">' . esc_html__( 'Case Studies', 'numbered-accordion' ) . '</a>'
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'sectors',
			array(
				'label'       => esc_html__( 'Only these sectors', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->sector_options(),
				'description' => esc_html__( 'Empty shows every sector. On an industry page, pick its sector and switch the chips off.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => esc_html__( 'How many', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 24,
				'default'     => 0,
				'description' => esc_html__( '0 shows them all. The home page might show 3.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'show_chips',
			array(
				'label'        => esc_html__( 'Sector chips', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Only sectors that have a case get a chip. With a single sector there are no chips.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'     => esc_html__( '"All" chip text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'numbered-accordion' ),
				'condition' => array( 'show_chips' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The words on every card that are not the case's own.
	 */
	private function register_card_controls() {
		$this->start_controls_section(
			'section_card',
			array( 'label' => esc_html__( 'Card wording', 'numbered-accordion' ) )
		);

		$this->add_control(
			'show_estimate',
			array(
				'label'        => esc_html__( '"Estimate my site" button', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'estimate_text',
			array(
				'label'     => esc_html__( 'Button text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Estimate my site', 'numbered-accordion' ),
				'condition' => array( 'show_estimate' => 'yes' ),
			)
		);

		$this->add_control(
			'estimate_url',
			array(
				'label'       => esc_html__( 'Button goes to', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '/request-assessment/',
				'description' => esc_html__( 'The assessment form page. The card adds ?sector=… so the form can open on the right industry.', 'numbered-accordion' ),
				'condition'   => array( 'show_estimate' => 'yes' ),
			)
		);

		$words = array(
			'now_label'      => array( esc_html__( 'Ongoing: headline', 'numbered-accordion' ), esc_html__( 'Now measuring', 'numbered-accordion' ) ),
			'step_installed' => array( esc_html__( 'Ongoing: step 1', 'numbered-accordion' ), esc_html__( 'Installed', 'numbered-accordion' ) ),
			'step_measuring' => array( esc_html__( 'Ongoing: step 2', 'numbered-accordion' ), esc_html__( 'Measuring', 'numbered-accordion' ) ),
			'step_results'   => array( esc_html__( 'Ongoing: step 3', 'numbered-accordion' ), esc_html__( 'Results', 'numbered-accordion' ) ),
			'basis_prefix'   => array( esc_html__( 'Before the basis line', 'numbered-accordion' ), esc_html__( 'Basis:', 'numbered-accordion' ) ),
		);

		foreach ( $words as $id => $word ) {
			$this->add_control(
				$id,
				array(
					'label'   => $word[0],
					'type'    => Controls_Manager::TEXT,
					'default' => $word[1],
				)
			);
		}

		$this->add_control(
			'empty_text',
			array(
				'label'       => esc_html__( 'When there are none', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
				'description' => esc_html__( 'Shown to visitors when no case matches. Empty shows nothing at all.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Grid, chips and spacing.
	 */
	private function register_layout_style_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => esc_html__( 'Layout', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'numbered-accordion' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'selectors'      => array( '{{WRAPPER}} .ecs' => '--ecs-cols: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .ecs' => '--ecs-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'photo_ratio',
			array(
				'label'     => esc_html__( 'Photo shape', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16 / 9',
				'options'   => array(
					'16 / 9'  => '16:9',
					'16 / 10' => '16:10',
					'4 / 3'   => '4:3',
					'1 / 1'   => '1:1',
				),
				'selectors' => array( '{{WRAPPER}} .ecs' => '--ecs-ratio: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chip_typography',
				'label'    => esc_html__( 'Chip type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .ecs__chip',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Card colours and type.
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colours = array(
			'accent'      => array( esc_html__( 'Green: figure, tag, button, active chip', 'numbered-accordion' ), '--ecs-green' ),
			'ink'         => array( esc_html__( 'Text', 'numbered-accordion' ), '--ecs-navy' ),
			'card_bg'     => array( esc_html__( 'Card', 'numbered-accordion' ), '--ecs-card' ),
			'card_border' => array( esc_html__( 'Borders', 'numbered-accordion' ), '--ecs-line' ),
			'logo_dark'   => array( esc_html__( 'Dark logo badge', 'numbered-accordion' ), '--ecs-dark' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .ecs' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ecs' => '--ecs-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$type = array(
			'figure_typography' => array( esc_html__( 'Figure type', 'numbered-accordion' ), '.ecs__big b' ),
			'label_typography'  => array( esc_html__( 'Figure label type', 'numbered-accordion' ), '.ecs__big span' ),
			'tag_typography'    => array( esc_html__( 'Tag type', 'numbered-accordion' ), '.ecs__tag' ),
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
	 * The sectors, for the picker.
	 *
	 * @return array<string, string>
	 */
	private function sector_options() {
		$options = CaseStudies_Content::sectors();

		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => CaseStudies_Content::TAXONOMY,
					'hide_empty' => false,
				)
			);

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ $term->slug ] = $term->name;
				}
			}
		}

		return $options;
	}

	/**
	 * The published cases, as cards, in their Order.
	 *
	 * Protected so the tests can hand the widget cases without a database.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	protected function get_cards( $settings ) {
		if ( ! class_exists( '\WP_Query' ) ) {
			return array();
		}

		$limit   = isset( $settings['limit'] ) && is_numeric( $settings['limit'] ) ? max( 0, (int) $settings['limit'] ) : 0;
		$sectors = isset( $settings['sectors'] ) && is_array( $settings['sectors'] ) ? array_values( array_filter( array_map( 'sanitize_key', $settings['sectors'] ) ) ) : array();
		$args    = array(
			'post_type'              => CaseStudies_Content::POST_TYPE,
			'post_status'            => 'publish',
			'orderby'                => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			// Over-fetch a little: a published case without a figure is
			// skipped, and should not cost the home page one of its three.
			'posts_per_page'         => $limit > 0 ? $limit + 6 : 100,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
		);

		if ( $sectors ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => CaseStudies_Content::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $sectors,
				),
			);
		}

		$cards = array();

		foreach ( ( new \WP_Query( $args ) )->posts as $post ) {
			$meta = array();

			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( 0 === strpos( $key, 'cs_' ) ) {
					$meta[ $key ] = isset( $values[0] ) ? $values[0] : '';
				}
			}

			$terms   = get_the_terms( $post->ID, CaseStudies_Content::TAXONOMY );
			$sectors_of = array();

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$sectors_of[] = array( 'slug' => $term->slug, 'name' => $term->name );
				}
			}

			$pdf  = isset( $meta['cs_pdf'] ) ? (int) $meta['cs_pdf'] : 0;
			$card = CaseStudies_Content::card(
				array(
					'title'   => get_the_title( $post ),
					'meta'    => $meta,
					'sectors' => $sectors_of,
					'pdf_url' => $pdf > 0 ? (string) wp_get_attachment_url( $pdf ) : '',
				)
			);

			if ( null === $card ) {
				continue;
			}

			$card['photo_id']  = (int) get_post_thumbnail_id( $post );
			$card['photo_url'] = '';
			$card['logo_url']  = $card['logo_id'] > 0 ? (string) wp_get_attachment_url( $card['logo_id'] ) : '';
			$cards[]           = $card;

			if ( $limit > 0 && count( $cards ) >= $limit ) {
				break;
			}
		}

		return $cards;
	}

	/**
	 * Settings text, trimmed, with a fallback for never-saved controls.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Key.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function word( $settings, $key, $fallback ) {
		if ( ! array_key_exists( $key, $settings ) ) {
			return $fallback;
		}

		return is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
	}

	/**
	 * Print a card's photo.
	 *
	 * @param array $card Card.
	 */
	private function render_photo( $card ) {
		if ( $card['photo_id'] > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$card['photo_id'],
				'large',
				false,
				array(
					'class'   => 'ecs__photo',
					'loading' => 'lazy',
					'sizes'   => '(max-width: 767px) 92vw, (max-width: 1024px) 46vw, 400px',
				)
			);
			return;
		}

		if ( '' !== $card['photo_url'] ) {
			printf( '<img class="ecs__photo" src="%s" alt="" loading="lazy" />', esc_url( $card['photo_url'] ) );
			return;
		}

		echo '<span class="ecs__photo ecs__photo--none" aria-hidden="true"></span>';
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$cards    = $this->get_cards( $settings );

		if ( empty( $cards ) ) {
			$empty   = $this->word( $settings, 'empty_text', '' );
			$editing = class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

			if ( $editing ) {
				echo '<div class="ecs ecs--empty"><p>' . esc_html__( 'No published case studies match. Add them under Case Studies in the dashboard, or change "Only these sectors".', 'numbered-accordion' ) . '</p></div>';
			} elseif ( '' !== $empty ) {
				echo '<div class="ecs ecs--empty"><p>' . esc_html( $empty ) . '</p></div>';
			}

			return;
		}

		$chips     = CaseStudies_Content::chips( $cards );
		$use_chips = isset( $settings['show_chips'] ) && 'yes' === $settings['show_chips'] && count( $chips ) > 1;
		$estimate  = isset( $settings['show_estimate'] ) && 'yes' === $settings['show_estimate'];
		$est_text  = $this->word( $settings, 'estimate_text', __( 'Estimate my site', 'numbered-accordion' ) );
		$est_url   = $this->word( $settings, 'estimate_url', '/request-assessment/' );
		$all       = $this->word( $settings, 'all_label', __( 'All', 'numbered-accordion' ) );
		$basis     = $this->word( $settings, 'basis_prefix', __( 'Basis:', 'numbered-accordion' ) );
		$steps     = array(
			$this->word( $settings, 'step_installed', __( 'Installed', 'numbered-accordion' ) ),
			$this->word( $settings, 'step_measuring', __( 'Measuring', 'numbered-accordion' ) ),
			$this->word( $settings, 'step_results', __( 'Results', 'numbered-accordion' ) ),
		);
		$now       = $this->word( $settings, 'now_label', __( 'Now measuring', 'numbered-accordion' ) );
		?>
		<div class="ecs">
			<?php if ( $use_chips ) : ?>
				<div class="ecs__chips" role="group" aria-label="<?php esc_attr_e( 'Filter case studies by sector', 'numbered-accordion' ); ?>">
					<button type="button" class="ecs__chip" data-seg="all" aria-pressed="true"><?php echo esc_html( '' !== $all ? $all : __( 'All', 'numbered-accordion' ) ); ?></button>
					<?php foreach ( $chips as $slug => $name ) : ?>
						<button type="button" class="ecs__chip" data-seg="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="ecs__grid">
				<?php foreach ( $cards as $card ) : ?>
					<article class="ecs__card" data-seg="<?php echo esc_attr( implode( ' ', $card['sectors'] ) ); ?>">
						<figure class="ecs__img">
							<?php $this->render_photo( $card ); ?>
							<?php if ( '' !== $card['logo_url'] ) : ?>
								<img class="ecs__logo<?php echo $card['logo_dark'] ? ' ecs__logo--dark' : ''; ?>" src="<?php echo esc_url( $card['logo_url'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>" loading="lazy" decoding="async" />
							<?php endif; ?>
						</figure>

						<div class="ecs__body">
							<h3 class="ecs__name"><?php echo esc_html( $card['title'] ); ?></h3>

							<?php if ( '' !== $card['tag'] ) : ?>
								<span class="ecs__tag"><?php echo esc_html( $card['tag'] ); ?></span>
							<?php endif; ?>

							<?php if ( 'published' === $card['status'] ) : ?>
								<p class="ecs__big">
									<b><?php echo esc_html( $card['figure'] ); ?></b>
									<?php if ( '' !== $card['figure_label'] ) : ?>
										<span><?php echo esc_html( $card['figure_label'] ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $card['basis'] ) : ?>
										<small><?php echo esc_html( trim( $basis . ' ' . $card['basis'] ) ); ?></small>
									<?php endif; ?>
								</p>
							<?php else : ?>
								<div class="ecs__status">
									<span class="ecs__now"><i aria-hidden="true"></i><?php echo esc_html( $now ); ?></span>
									<ol class="ecs__track">
										<li class="is-done"><?php echo esc_html( $steps[0] ); ?></li>
										<li class="is-now" aria-current="step"><?php echo esc_html( $steps[1] ); ?></li>
										<li><?php echo esc_html( $steps[2] ); ?></li>
									</ol>
									<?php if ( '' !== $card['status_note'] ) : ?>
										<small><?php echo esc_html( $card['status_note'] ); ?></small>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( '' !== $card['source'] && '' !== $card['use'] ) : ?>
								<p class="ecs__flow"><?php echo esc_html( $card['source'] ); ?> <i aria-hidden="true">→</i><span class="ecs__sr"> <?php esc_html_e( 'to', 'numbered-accordion' ); ?></span> <?php echo esc_html( $card['use'] ); ?></p>
							<?php endif; ?>

							<?php
							$est = $estimate ? CaseStudies_Content::estimate_url( $est_url, isset( $card['sectors'][0] ) ? $card['sectors'][0] : '' ) : '';
							?>
							<?php if ( '' !== $card['link'] || '' !== $est ) : ?>
								<div class="ecs__acts">
									<?php if ( '' !== $card['link'] ) : ?>
										<a class="ecs__go" href="<?php echo esc_url( $card['link'] ); ?>"<?php echo $card['link_is_pdf'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $card['link_text'] ); ?> <span aria-hidden="true">→</span></a>
									<?php endif; ?>
									<?php if ( '' !== $est ) : ?>
										<a class="ecs__est" href="<?php echo esc_url( $est ); ?>"><?php echo esc_html( $est_text ); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
