<?php
/**
 * News Grid widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use ErudaToolkit\Modules\News\News_Content;
use ErudaToolkit\Modules\News\News_Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-news-widget.php';

/**
 * The news archive as a grid of equal cards: the featured post first,
 * podcasts as player cards, everything else as photo cards, with category
 * chips and Load more.
 */
class News_Grid_Widget extends News_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'enws-news-grid';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'News Grid', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-masonry';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'news', 'posts', 'blog', 'archive', 'cards', 'grid', 'podcast' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_posts',
			array( 'label' => esc_html__( 'Posts', 'numbered-accordion' ) )
		);

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Shows your published Posts, newest first, with the newest Featured post leading. A post with an audio file, or with its card set to Podcast, becomes a podcast card with a player.', 'numbered-accordion' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'per_page',
			array(
				'label'   => esc_html__( 'Posts per load', 'numbered-accordion' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 9,
			)
		);

		$this->add_control(
			'cats',
			array(
				'label'       => esc_html__( 'Only these categories', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->term_options( 'category' ),
				'description' => esc_html__( 'Empty shows every category.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'follow_archive',
			array(
				'label'        => esc_html__( 'Follow the archive', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'On a category or tag page, show only its posts. Ignored when categories are picked above.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'show_chips',
			array(
				'label'        => esc_html__( 'Category chips', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'A link ending in #news-media opens with that chip on.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'chips_align',
			array(
				'label'     => esc_html__( 'Chips sit', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'end',
				'options'   => array(
					'start' => esc_html__( 'Left', 'numbered-accordion' ),
					'end'   => esc_html__( 'Right', 'numbered-accordion' ),
				),
				'condition' => array( 'show_chips' => 'yes' ),
				'selectors' => array( '{{WRAPPER}} .enws-grid__chips' => 'justify-content: flex-{{VALUE}};' ),
			)
		);

		$words = array(
			'all_label'  => array( esc_html__( '"All" chip text', 'numbered-accordion' ), esc_html__( 'All', 'numbered-accordion' ) ),
			'more_text'  => array( esc_html__( 'Load more text', 'numbered-accordion' ), esc_html__( 'Load more', 'numbered-accordion' ) ),
			'listen_text' => array( esc_html__( 'Podcast button (%s is the source)', 'numbered-accordion' ), esc_html__( 'Listen on %s', 'numbered-accordion' ) ),
			'empty_text' => array( esc_html__( 'When a chip has no posts', 'numbered-accordion' ), esc_html__( 'Nothing here yet. New posts appear as they\'re published.', 'numbered-accordion' ) ),
		);

		foreach ( $words as $id => $word ) {
			$this->add_control(
				$id,
				array(
					'label'       => $word[0],
					'type'        => Controls_Manager::TEXT,
					'label_block' => true,
					'default'     => $word[1],
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_next',
			array( 'label' => esc_html__( '"Go deeper" tile', 'numbered-accordion' ) )
		);

		$this->add_control(
			'show_next',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'A last card pointing somewhere else, such as the Resource Library.', 'numbered-accordion' ),
			)
		);

		$next = array(
			'next_chip'    => array( esc_html__( 'Label', 'numbered-accordion' ), esc_html__( 'Go deeper', 'numbered-accordion' ), Controls_Manager::TEXT ),
			'next_heading' => array( esc_html__( 'Heading', 'numbered-accordion' ), esc_html__( 'Looking for white papers and case studies?', 'numbered-accordion' ), Controls_Manager::TEXT ),
			'next_text'    => array( esc_html__( 'Text', 'numbered-accordion' ), esc_html__( 'The Resource Library has every published ThermStar document.', 'numbered-accordion' ), Controls_Manager::TEXTAREA ),
			'next_button'  => array( esc_html__( 'Button', 'numbered-accordion' ), esc_html__( 'Resource Library', 'numbered-accordion' ), Controls_Manager::TEXT ),
		);

		foreach ( $next as $id => $field ) {
			$this->add_control(
				$id,
				array(
					'label'       => $field[0],
					'type'        => $field[2],
					'label_block' => true,
					'default'     => $field[1],
					'condition'   => array( 'show_next' => 'yes' ),
				)
			);
		}

		$this->add_control(
			'next_link',
			array(
				'label'     => esc_html__( 'Button link', 'numbered-accordion' ),
				'type'      => Controls_Manager::URL,
				'default'   => array( 'url' => '/resources/' ),
				'condition' => array( 'show_next' => 'yes' ),
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
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
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
				'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'selectors'      => array( '{{WRAPPER}} .enws-grid' => '--enws-cols: {{VALUE}};' ),
			)
		);

		foreach ( array(
			'gap'    => array( esc_html__( 'Gap', 'numbered-accordion' ), '--enws-gap', 0, 40 ),
			'radius' => array( esc_html__( 'Corner radius', 'numbered-accordion' ), '--enws-radius', 0, 40 ),
		) as $id => $size ) {
			$this->add_responsive_control(
				$id,
				array(
					'label'      => $size[0],
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => $size[2], 'max' => $size[3] ) ),
					'selectors'  => array( '{{WRAPPER}} .enws-grid' => $size[1] . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		foreach ( array(
			'green'       => array( esc_html__( 'Green: chips, links', 'numbered-accordion' ), '--enws-green' ),
			'green_light' => array( esc_html__( 'Light green: pills, arrow', 'numbered-accordion' ), '--enws-green-light' ),
			'navy'        => array( esc_html__( 'Text', 'numbered-accordion' ), '--enws-navy' ),
			'ink'         => array( esc_html__( 'Dark: podcast card', 'numbered-accordion' ), '--enws-ink' ),
		) as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .enws-grid' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'audio_type' => array( esc_html__( 'Podcast title', 'numbered-accordion' ), '.enws-card--audio .enws-card__title' ),
			'image_type' => array( esc_html__( 'Photo card title', 'numbered-accordion' ), '.enws-card--image strong' ),
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
	 * Which categories and tags to read: the ones picked, or the archive's.
	 *
	 * @param array $settings Settings.
	 * @return array{cats: string[], tags: string[], archive: bool}
	 */
	protected function scope( $settings ) {
		$cats = isset( $settings['cats'] ) && is_array( $settings['cats'] ) ? array_values( array_filter( array_map( 'sanitize_title', $settings['cats'] ) ) ) : array();

		if ( $cats ) {
			return array( 'cats' => $cats, 'tags' => array(), 'archive' => false );
		}

		if ( 'yes' === $this->word( $settings, 'follow_archive', 'yes' ) && function_exists( 'is_category' ) ) {
			$term = get_queried_object();

			if ( is_category() && isset( $term->slug ) ) {
				return array( 'cats' => array( $term->slug ), 'tags' => array(), 'archive' => true );
			}

			if ( is_tag() && isset( $term->slug ) ) {
				return array( 'cats' => array(), 'tags' => array( $term->slug ), 'archive' => true );
			}
		}

		return array( 'cats' => array(), 'tags' => array(), 'archive' => false );
	}

	/**
	 * The first page of posts. Protected so the tests can hand it cards.
	 *
	 * @param array $settings Settings.
	 * @param array $scope    See scope().
	 * @return array{cards: array, more: bool, featured: int, next: int}
	 */
	protected function first_page( $settings, $scope ) {
		return News_Render::query(
			array(
				'per'      => News_Content::per_page( isset( $settings['per_page'] ) ? $settings['per_page'] : 9 ),
				'offset'   => 0,
				'cats'     => $scope['cats'],
				'tags'     => $scope['tags'],
				'featured' => -1,
			)
		);
	}

	/**
	 * The categories that have posts in this scope. Protected for the tests.
	 *
	 * @param array $scope See scope().
	 * @return array<int, array{slug: string, name: string}>
	 */
	protected function chip_terms( $scope ) {
		$args  = array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
		);

		if ( $scope['cats'] ) {
			$args['slug'] = $scope['cats'];
		}

		$terms = get_terms( $args );
		$out   = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[] = array( 'slug' => $term->slug, 'name' => $term->name );
			}
		}

		return $out;
	}

	/**
	 * Where Load more asks for the next cards.
	 *
	 * @return string
	 */
	protected function rest_url() {
		return function_exists( 'rest_url' ) ? rest_url( \ErudaToolkit\Modules\News\News_Module::REST_NS . \ErudaToolkit\Modules\News\News_Module::REST_ROUTE ) : '';
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$scope    = $this->scope( $settings );
		$page     = $this->first_page( $settings, $scope );
		$empty    = $this->word( $settings, 'empty_text', __( 'Nothing here yet. New posts appear as they\'re published.', 'numbered-accordion' ) );

		if ( empty( $page['cards'] ) ) {
			echo '<div class="enws-grid enws-grid--empty"><p class="enws-empty">' . esc_html( $empty ) . '</p></div>';
			return;
		}

		$chips     = News_Content::chips( $this->chip_terms( $scope ) );
		$use_chips = 'yes' === $this->word( $settings, 'show_chips', 'yes' ) && ! $scope['archive'] && count( $chips ) > 1;
		$all       = $this->word( $settings, 'all_label', __( 'All', 'numbered-accordion' ) );
		$labels    = News_Render::labels( array( 'listen' => $this->word( $settings, 'listen_text', '' ) ) );
		$link      = isset( $settings['next_link'] ) && is_array( $settings['next_link'] ) && isset( $settings['next_link']['url'] ) ? trim( (string) $settings['next_link']['url'] ) : '';
		?>
		<div class="enws-grid"
			data-rest="<?php echo esc_url( $this->rest_url() ); ?>"
			data-per="<?php echo (int) News_Content::per_page( isset( $settings['per_page'] ) ? $settings['per_page'] : 9 ); ?>"
			data-next="<?php echo (int) $page['next']; ?>"
			data-featured="<?php echo (int) $page['featured']; ?>"
			data-cats="<?php echo esc_attr( implode( ',', $scope['cats'] ) ); ?>"
			data-tags="<?php echo esc_attr( implode( ',', $scope['tags'] ) ); ?>"
			data-listen="<?php echo esc_attr( $labels['listen'] ); ?>"
			data-hash-prefix="news-">
			<?php if ( $use_chips ) : ?>
				<div class="enws-grid__chips" role="group" aria-label="<?php esc_attr_e( 'Filter posts by category', 'numbered-accordion' ); ?>">
					<button type="button" class="enws-chip" data-seg="all" aria-pressed="true"><?php echo esc_html( '' !== $all ? $all : __( 'All', 'numbered-accordion' ) ); ?></button>
					<?php foreach ( $chips as $slug => $name ) : ?>
						<button type="button" class="enws-chip" data-seg="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="enws-cards" aria-live="polite">
				<?php echo News_Render::grid_cards( $page['cards'], $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in News_Render ?>
				<?php if ( 'yes' === $this->word( $settings, 'show_next', '' ) ) : ?>
					<div class="enws-card enws-card--next">
						<?php $chip = $this->word( $settings, 'next_chip', '' ); ?>
						<?php echo '' !== $chip ? '<span class="enws-next__chip">' . esc_html( $chip ) . '</span>' : ''; ?>
						<strong><?php echo esc_html( $this->word( $settings, 'next_heading', '' ) ); ?></strong>
						<span class="enws-next__text"><?php echo esc_html( $this->word( $settings, 'next_text', '' ) ); ?></span>
						<?php if ( '' !== $link ) : ?>
							<a class="enws-next__btn" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $this->word( $settings, 'next_button', '' ) ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<p class="enws-empty" hidden><?php echo esc_html( $empty ); ?></p>

			<?php if ( $page['more'] ) : ?>
				<div class="enws-more"><button type="button" class="enws-more__btn"><?php echo esc_html( $this->word( $settings, 'more_text', __( 'Load more', 'numbered-accordion' ) ) ); ?></button></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
