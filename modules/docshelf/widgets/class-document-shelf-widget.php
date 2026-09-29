<?php
/**
 * Document Shelf widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\DocShelf\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\DocShelf\DocShelf_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloadable documents as cards with a paper sheet rising out of each,
 * filtered by type.
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
	 * Where the bundled covers live.
	 *
	 * @return string
	 */
	private function cover_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . DocShelf_Content::COVER_DIR;
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
	 * The documents.
	 */
	private function register_document_controls() {
		$this->start_controls_section(
			'section_documents',
			array( 'label' => esc_html__( 'Documents', 'numbered-accordion' ) )
		);

		$docs = new Repeater();

		$docs->add_control(
			'cover',
			array(
				'label'       => esc_html__( 'Cover', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Page 1 of the document, about 600px wide.', 'numbered-accordion' ),
			)
		);

		$docs->add_control(
			'key',
			array(
				'label'       => esc_html__( 'Filter', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'white',
				'description' => esc_html__( 'Which chip shows it: white, perf, industry, product, company — or a new word, which makes a new chip.', 'numbered-accordion' ),
			)
		);

		$docs->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type label', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'White paper', 'numbered-accordion' ),
			)
		);

		$docs->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$docs->add_control(
			'desc',
			array(
				'label' => esc_html__( 'Description', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 2,
			)
		);

		$docs->add_control(
			'meta',
			array(
				'label'       => esc_html__( 'Meta', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'After the PDF badge, e.g. "12 pages · May 2026".', 'numbered-accordion' ),
			)
		);

		$docs->add_control(
			'link',
			array(
				'label'       => esc_html__( 'File link', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'options'     => false,
				'description' => esc_html__( 'Any address: the media library, SharePoint, another site. Empty shows the card as "Coming soon".', 'numbered-accordion' ),
			)
		);

		$docs->add_control(
			'new_tab',
			array(
				'label'        => esc_html__( 'Open in a new tab', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$docs->add_control(
			'download',
			array(
				'label'        => esc_html__( 'Download instead of open', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Only works for files on this site. Leave it off for SharePoint links.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'docs',
			array(
				'label'       => esc_html__( 'Documents', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $docs->get_controls(),
				'default'     => $this->default_docs(),
				'title_field' => '{{{ key }}} · {{{ title }}}',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The page 26 shelf as repeater rows.
	 *
	 * @return array
	 */
	private function default_docs() {
		$rows = array();

		foreach ( DocShelf_Content::default_docs() as $doc ) {
			$rows[] = array(
				'cover'    => array( 'url' => $this->cover_url() . $doc['cover'] ),
				'key'      => $doc['key'],
				'type'     => $doc['type'],
				'title'    => $doc['title'],
				'desc'     => $doc['desc'],
				'meta'     => $doc['meta'],
				'link'     => array( 'url' => $doc['url'] ),
				'new_tab'  => 'yes',
				'download' => '',
			);
		}

		return $rows;
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
				'description'  => esc_html__( 'One chip per filter in use, in the order the documents first use them. A link ending in #docs-perf opens with that chip on.', 'numbered-accordion' ),
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
			'chip_map',
			array(
				'label'       => esc_html__( 'Chip labels', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 6,
				'default'     => DocShelf_Content::default_chip_map(),
				'description' => esc_html__( 'One per line: filter: Label.', 'numbered-accordion' ),
				'condition'   => array( 'show_chips' => 'yes' ),
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
		$docs     = DocShelf_Content::build( isset( $settings['docs'] ) ? $settings['docs'] : array() );

		if ( empty( $docs ) ) {
			return;
		}

		$chips     = DocShelf_Content::chips( $docs, DocShelf_Content::parse_chip_map( $this->word( $settings, 'chip_map', DocShelf_Content::default_chip_map() ) ) );
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
