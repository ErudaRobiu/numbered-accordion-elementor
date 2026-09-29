<?php
/**
 * Document shelf module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\DocShelf;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Documents post type and its edit form, and the Document Shelf widget
 * that shows them.
 *
 * Adding a document is filling in one form under Documents in the dashboard;
 * every Document Shelf on the site picks it up.
 */
final class DocShelf_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'edoc-document-shelf';
	const SCRIPT_HANDLE = 'edoc-document-shelf';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'docshelf';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Document Shelf', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a Documents section to the dashboard (one form per document, with ACF) and a "Document Shelf" widget that shows them as filterable cards.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/docshelf/class-docshelf-content.php';

		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'acf/init', array( $this, 'register_fields' ) );

		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		if ( is_admin() ) {
			add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
			add_filter( 'manage_' . DocShelf_Content::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
			add_action( 'manage_' . DocShelf_Content::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
			add_filter( 'manage_edit-' . DocShelf_Content::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'admin_order' ) );
			add_action( 'admin_notices', array( $this, 'acf_notice' ) );
		}
	}

	/**
	 * The post type and the filters.
	 *
	 * No single pages and no archive: a card links straight to its file.
	 */
	public function register_content_types() {
		register_post_type(
			DocShelf_Content::POST_TYPE,
			array(
				'labels'             => array(
					'name'                  => __( 'Documents', 'numbered-accordion' ),
					'singular_name'         => __( 'Document', 'numbered-accordion' ),
					'add_new'               => __( 'Add document', 'numbered-accordion' ),
					'add_new_item'          => __( 'Add a document', 'numbered-accordion' ),
					'edit_item'             => __( 'Edit document', 'numbered-accordion' ),
					'new_item'              => __( 'New document', 'numbered-accordion' ),
					'search_items'          => __( 'Search documents', 'numbered-accordion' ),
					'not_found'             => __( 'No documents yet.', 'numbered-accordion' ),
					'all_items'             => __( 'All documents', 'numbered-accordion' ),
					'featured_image'        => __( 'Cover', 'numbered-accordion' ),
					'set_featured_image'    => __( 'Set cover', 'numbered-accordion' ),
					'remove_featured_image' => __( 'Remove cover', 'numbered-accordion' ),
					'use_featured_image'    => __( 'Use as cover', 'numbered-accordion' ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'has_archive'        => false,
				'rewrite'            => false,
				'menu_position'      => 22,
				'menu_icon'          => 'dashicons-media-document',
				'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
			)
		);

		register_taxonomy(
			DocShelf_Content::TAXONOMY,
			DocShelf_Content::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Filters', 'numbered-accordion' ),
					'singular_name' => __( 'Filter', 'numbered-accordion' ),
					'menu_name'     => __( 'Filters', 'numbered-accordion' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'rewrite'           => false,
				// The filter is picked inside the form; a second box in
				// the sidebar for the same thing would only disagree with it.
				'meta_box_cb'       => false,
			)
		);
	}

	/**
	 * The document form. See DocShelf_Content::field_group().
	 */
	public function register_fields() {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group( DocShelf_Content::field_group() );
		}
	}

	/**
	 * The title box asks for the card title.
	 *
	 * @param string   $text Placeholder.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function title_placeholder( $text, $post ) {
		return isset( $post->post_type ) && DocShelf_Content::POST_TYPE === $post->post_type
			? __( 'Card title, e.g. ThermStar System brochure', 'numbered-accordion' )
			: $text;
	}

	/**
	 * List columns: enough to check the whole shelf at a glance.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$out = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['edoc_cover'] = __( 'Cover', 'numbered-accordion' );
			}

			if ( 'date' === $key ) {
				continue;
			}

			$out[ $key ] = $label;

			if ( 'title' === $key ) {
				$out['edoc_filter'] = __( 'Filter', 'numbered-accordion' );
				$out['edoc_type']   = __( 'Type label', 'numbered-accordion' );
				$out['edoc_link']   = __( 'Opens', 'numbered-accordion' );
				$out['menu_order']  = __( 'Order', 'numbered-accordion' );
			}
		}

		return $out;
	}

	/**
	 * One list cell.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post.
	 */
	public function column( $column, $post_id ) {
		switch ( $column ) {
			case 'edoc_cover':
				echo get_the_post_thumbnail( $post_id, array( 40, 52 ), array( 'style' => 'width:40px;height:52px;object-fit:cover;object-position:top;border-radius:3px;box-shadow:0 1px 3px #0003' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'edoc_filter':
				$terms = get_the_terms( $post_id, DocShelf_Content::TAXONOMY );
				echo esc_html( is_array( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '—' );
				break;

			case 'edoc_type':
				echo esc_html( (string) get_post_meta( $post_id, 'doc_type', true ) );
				break;

			case 'edoc_link':
				$url  = (string) get_post_meta( $post_id, 'doc_url', true );
				$file = (int) get_post_meta( $post_id, 'doc_file', true );

				if ( '' !== $url ) {
					echo esc_html( wp_parse_url( $url, PHP_URL_HOST ) ? wp_parse_url( $url, PHP_URL_HOST ) : $url );
				} elseif ( $file > 0 ) {
					echo esc_html( basename( (string) get_attached_file( $file ) ) );
				} else {
					echo '<strong style="color:#b32d2e">' . esc_html__( 'Nothing — shows "Coming soon"', 'numbered-accordion' ) . '</strong>';
				}
				break;

			case 'menu_order':
				echo (int) get_post_field( 'menu_order', $post_id );
				break;
		}
	}

	/**
	 * Order is sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['menu_order'] = 'menu_order';

		return $columns;
	}

	/**
	 * The list opens in the order the cards appear on the site.
	 *
	 * @param \WP_Query $query Query.
	 */
	public function admin_order( $query ) {
		if ( ! $query->is_main_query() || DocShelf_Content::POST_TYPE !== $query->get( 'post_type' ) || $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}

	/**
	 * Without ACF the cards still show, but there is no form to edit them.
	 */
	public function acf_notice() {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || DocShelf_Content::POST_TYPE !== $screen->post_type ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Document fields need the Advanced Custom Fields plugin. The shelf on the site keeps working without it, but documents cannot be edited until it is active.', 'numbered-accordion' ) . '</p></div>';
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style( self::STYLE_HANDLE, ERUDA_URL . 'modules/docshelf/assets/css/document-shelf.css', array(), ERUDA_VERSION );
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script( self::SCRIPT_HANDLE, ERUDA_URL . 'modules/docshelf/assets/js/document-shelf.js', array(), ERUDA_VERSION, true );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widget manager.
	 */
	public function register_widgets( $widgets_manager ) {
		try {
			// Widget_Base only exists from this hook onwards.
			if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
				return;
			}

			require_once ERUDA_PATH . 'modules/docshelf/widgets/class-document-shelf-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\DocShelf\Widgets\Document_Shelf_Widget' ) ) {
				$widgets_manager->register( new Widgets\Document_Shelf_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the document shelf widget - ' . $e->getMessage() );
			}
		}
	}
}
