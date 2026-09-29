<?php
/**
 * Document Shelf widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\DocShelf\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\DocShelf\DocShelf_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The published Documents as cards with a paper sheet rising out of each,
 * filtered by type. Nothing is typed on the page: the list lives in the
 * dashboard, so every shelf on the site shows the same, current documents.
 */
class Document_Shelf_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'edoc-document-shelf';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Document Shelf', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-document-file';
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
		return array( 'documents', 'pdf', 'downloads', 'library', 'resources', 'white paper', 'shelf' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\DocShelf\DocShelf_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\DocShelf\DocShelf_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_document_controls();
		$this->register_filter_controls();
		$this->register_grid_style_controls();
		$this->register_card_style_controls();
	}

	/**
	 * Which documents.
	 */
	private function register_document_controls() {
		$this->start_controls_section(
			'section_documents',
			array( 'label' => esc_html__( 'Documents', 'numbered-accordion' ) )
		);

		$manage = function_exists( 'admin_url' ) ? admin_url( 'edit.php?post_type=' . DocShelf_Content::POST_TYPE ) : '#';

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => sprintf(
					/* translators: %s: link to the Documents list */
					esc_html__( 'The cards are your published documents, in their Order. To add or change one, go to %s in the dashboard.', 'numbered-accordion' ),
					'<a href="' . esc_url( $manage ) . '" target="_blank">' . esc_html__( 'Documents', 'numbered-accordion' ) . '</a>'
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'filters',
			array(
				'label'       => esc_html__( 'Only these filters', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->filter_options(),
				'description' => esc_html__( 'Empty shows every document. On another page, e.g. only Performance evidence.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => esc_html__( 'How many', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 48,
				'default'     => 0,
				'description' => esc_html__( '0 shows them all.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The filters, for the picker.
	 *
	 * @return array<string, string>
	 */
	private function filter_options() {
		$options = DocShelf_Content::filters();

		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => DocShelf_Content::TAXONOMY,
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
	 * The published documents, as cards, in their Order.
	 *
	 * Protected so the tests can hand the widget documents without a database.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	protected function get_docs( $settings ) {
		if ( ! class_exists( '\WP_Query' ) ) {
			return array();
		}

		$limit   = isset( $settings['limit'] ) && is_numeric( $settings['limit'] ) ? max( 0, (int) $settings['limit'] ) : 0;
		$filters = isset( $settings['filters'] ) && is_array( $settings['filters'] ) ? array_values( array_filter( array_map( 'sanitize_key', $settings['filters'] ) ) ) : array();
		$args    = array(
			'post_type'              => DocShelf_Content::POST_TYPE,
			'post_status'            => 'publish',
			'orderby'                => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'posts_per_page'         => $limit > 0 ? $limit : 200,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
		);

		if ( $filters ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => DocShelf_Content::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $filters,
				),
			);
		}

		$docs = array();

		foreach ( ( new \WP_Query( $args ) )->posts as $post ) {
			$meta = array();

			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( 0 === strpos( $key, 'doc_' ) ) {
					$meta[ $key ] = isset( $values[0] ) ? $values[0] : '';
				}
			}

			$terms = get_the_terms( $post->ID, DocShelf_Content::TAXONOMY );
			$found = array();

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$found[] = array( 'slug' => $term->slug, 'name' => $term->name );
				}
			}

			$file = isset( $meta['doc_file'] ) ? (int) $meta['doc_file'] : 0;
			$card = DocShelf_Content::card(
				array(
					'title'    => get_the_title( $post ),
					'meta'     => $meta,
					'filters'  => $found,
					'file_url' => $file > 0 ? (string) wp_get_attachment_url( $file ) : '',
					'cover_id' => (int) get_post_thumbnail_id( $post ),
				)
			);

			if ( null !== $card ) {
				$docs[] = $card;
			}
		}

		return $docs;
	}

	/**
	 * The chips and the words on every card.
	 */
	private function register_filter_controls() {
		$this->start_controls_section(
			'section_filters',
			array( 'label' => esc_html__( 'Filters and wording', 'numbered-accordion' ) )
		);

		$this->add_control(
			'show_chips',
			array(
				'label'        => esc_html__( 'Filter chips', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'One chip per filter that has documents, in their Order. Rename a chip under Documents → Filters. A link ending in #docs-perf opens with that chip on.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'show_all',
			array(
				'label'        => esc_html__( '"All" chip', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_chips' => 'yes' ),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'     => esc_html__( '"All" chip text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'numbered-accordion' ),
				'condition' => array(
					'show_chips' => 'yes',
					'show_all'   => 'yes',
				),
			)
		);

		$this->add_control(
			'download_text',
			array(
				'label'   => esc_html__( 'Link text', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Download ↓', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'soon_text',
			array(
				'label'   => esc_html__( 'Without a link', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Coming soon', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'   => esc_html__( 'Badge', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'PDF',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Grid and chips.
	 */
	private function register_grid_style_controls() {
		$this->start_controls_section(
			'section_style_grid',
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
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'selectors'      => array( '{{WRAPPER}} .edoc' => '--edoc-cols: {{VALUE}};' ),
			)
		);

		$sizes = array(
			'gap'          => array( esc_html__( 'Gap', 'numbered-accordion' ), '--edoc-gap', 0, 48 ),
			'cover_height' => array( esc_html__( 'Cover height', 'numbered-accordion' ), '--edoc-cover-h', 120, 360 ),
			'radius'       => array( esc_html__( 'Corner radius', 'numbered-accordion' ), '--edoc-radius', 0, 40 ),
		);

		foreach ( $sizes as $id => $size ) {
			$this->add_responsive_control(
				$id,
				array(
					'label'      => $size[0],
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => $size[2], 'max' => $size[3] ) ),
					'selectors'  => array( '{{WRAPPER}} .edoc' => $size[1] . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chip_typography',
				'label'    => esc_html__( 'Chip type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .edoc__chip',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Card colours and type.
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colours = array(
			'accent'   => array( esc_html__( 'Green: type, link, active chip', 'numbered-accordion' ), '--edoc-green' ),
			'ink'      => array( esc_html__( 'Text and badge', 'numbered-accordion' ), '--edoc-navy' ),
			'card_bg'  => array( esc_html__( 'Card', 'numbered-accordion' ), '--edoc-card' ),
			'border'   => array( esc_html__( 'Borders', 'numbered-accordion' ), '--edoc-line' ),
			'cover_a'  => array( esc_html__( 'Cover area, top', 'numbered-accordion' ), '--edoc-cover-a' ),
			'cover_b'  => array( esc_html__( 'Cover area, bottom', 'numbered-accordion' ), '--edoc-cover-b' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .edoc' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$type = array(
			'type_typography'  => array( esc_html__( 'Type label', 'numbered-accordion' ), '.edoc__type' ),
			'title_typography' => array( esc_html__( 'Title', 'numbered-accordion' ), '.edoc__title' ),
			'desc_typography'  => array( esc_html__( 'Description', 'numbered-accordion' ), '.edoc__desc' ),
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
	 * Print a cover.
	 *
	 * Decorative: the card's link names the document, and a second name from
	 * the cover would only repeat it.
	 *
	 * @param array $doc Built card.
	 */
	private function render_cover( $doc ) {
		if ( $doc['cover_id'] > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			$image = wp_get_attachment_image(
				$doc['cover_id'],
				'medium_large',
				false,
				array(
					'alt'     => '',
					'loading' => 'lazy',
					'sizes'   => '(max-width: 760px) 70vw, 220px',
				)
			);

			if ( '' !== $image ) {
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}
		}

		if ( '' !== $doc['cover'] ) {
			printf( '<img src="%s" alt="" loading="lazy" decoding="async" />', esc_url( $doc['cover'] ) );
		}
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$docs     = $this->get_docs( $settings );

		if ( empty( $docs ) ) {
			$editing = class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

			if ( $editing ) {
				echo '<div class="edoc edoc--empty"><p>' . esc_html__( 'No published documents match. Add them under Documents in the dashboard, or change "Only these filters".', 'numbered-accordion' ) . '</p></div>';
			}

			return;
		}

		$chips     = DocShelf_Content::chips( $docs );
		$use_chips = isset( $settings['show_chips'] ) && 'yes' === $settings['show_chips'] && count( $chips ) > 1;
		$show_all  = isset( $settings['show_all'] ) && 'yes' === $settings['show_all'];
		$all       = $this->word( $settings, 'all_label', __( 'All', 'numbered-accordion' ) );
		$download  = $this->word( $settings, 'download_text', __( 'Download ↓', 'numbered-accordion' ) );
		$soon      = $this->word( $settings, 'soon_text', __( 'Coming soon', 'numbered-accordion' ) );
		$badge     = $this->word( $settings, 'badge_text', 'PDF' );
		$first     = (string) key( $chips );
		?>
		<div class="edoc" data-hash-prefix="<?php echo esc_attr( DocShelf_Content::HASH_PREFIX ); ?>">
			<?php if ( $use_chips ) : ?>
				<div class="edoc__chips" role="group" aria-label="<?php esc_attr_e( 'Filter documents by type', 'numbered-accordion' ); ?>">
					<?php if ( $show_all ) : ?>
						<button type="button" class="edoc__chip" data-seg="all" aria-pressed="true"><?php echo esc_html( '' !== $all ? $all : __( 'All', 'numbered-accordion' ) ); ?></button>
					<?php endif; ?>
					<?php foreach ( $chips as $key => $label ) : ?>
						<button type="button" class="edoc__chip" data-seg="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo ! $show_all && $key === $first ? 'true' : 'false'; ?>"><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="edoc__grid">
				<?php foreach ( $docs as $doc ) : ?>
					<?php
					$linked = '' !== $doc['url'];
					$tag    = $linked ? 'a' : 'div';
					// Without "All", the shelf opens on the first filter; the
					// rest are hidden from the server so nothing flashes.
					$hidden = $use_chips && ! $show_all && $doc['key'] !== $first;
					$attrs  = ' class="edoc__card' . ( $linked ? '' : ' is-soon' ) . '" data-seg="' . esc_attr( $doc['key'] ) . '"';

					if ( $linked ) {
						$rel    = array_filter( array( $doc['new_tab'] ? 'noopener' : '', $doc['nofollow'] ? 'nofollow' : '' ) );
						$attrs .= ' href="' . esc_url( $doc['url'] ) . '"';
						$attrs .= ' aria-label="' . esc_attr( DocShelf_Content::link_label( $doc ) ) . '"';
						$attrs .= $doc['new_tab'] ? ' target="_blank"' : '';
						$attrs .= $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '';
						$attrs .= $doc['download'] ? ' download' : '';
					}

					$attrs .= $hidden ? ' hidden' : '';
					?>
					<<?php echo $tag . $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>>
						<span class="edoc__cover"><?php $this->render_cover( $doc ); ?></span>
						<span class="edoc__body">
							<?php if ( '' !== $doc['type'] ) : ?>
								<span class="edoc__type"><?php echo esc_html( $doc['type'] ); ?></span>
							<?php endif; ?>
							<strong class="edoc__title"><?php echo esc_html( $doc['title'] ); ?></strong>
							<?php if ( '' !== $doc['desc'] ) : ?>
								<span class="edoc__desc"><?php echo esc_html( $doc['desc'] ); ?></span>
							<?php endif; ?>
							<span class="edoc__meta">
								<?php if ( '' !== $badge ) : ?>
									<i><?php echo esc_html( $badge ); ?></i>
								<?php endif; ?>
								<span class="edoc__size"><?php echo esc_html( $doc['meta'] ); ?></span>
								<em><?php echo esc_html( $linked ? $download : $soon ); ?></em>
							</span>
						</span>
					</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
