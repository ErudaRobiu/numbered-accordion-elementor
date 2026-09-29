<?php
/**
 * Case studies module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\CaseStudies;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Case Studies post type and its edit form, and the widget that shows them.
 *
 * Everything a case needs is set up here, so adding one is filling in one form
 * under Case Studies in the dashboard; the widget on the page picks it up.
 */
final class CaseStudies_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'ecs-case-studies';
	const SCRIPT_HANDLE = 'ecs-case-studies';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'casestudies';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Case Studies', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a Case Studies section to the dashboard (one form per case, with ACF) and a "Case Studies" widget that shows them as filterable cards.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/casestudies/class-casestudies-content.php';

		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'acf/init', array( $this, 'register_fields' ) );

		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		if ( is_admin() ) {
			add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
			add_filter( 'manage_' . CaseStudies_Content::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
			add_action( 'manage_' . CaseStudies_Content::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
			add_filter( 'manage_edit-' . CaseStudies_Content::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'admin_order' ) );
			add_action( 'admin_notices', array( $this, 'acf_notice' ) );
		}
	}

	/**
	 * The post type and the sectors.
	 *
	 * No single pages and no archive: a card links to its PDF, and a page
	 * holding only the card's own words would be thin. Switch
	 * publicly_queryable on if full case pages are ever written.
	 */
	public function register_content_types() {
		register_post_type(
			CaseStudies_Content::POST_TYPE,
			array(
				'labels'             => array(
					'name'                  => __( 'Case Studies', 'numbered-accordion' ),
					'singular_name'         => __( 'Case Study', 'numbered-accordion' ),
					'add_new'               => __( 'Add case study', 'numbered-accordion' ),
					'add_new_item'          => __( 'Add a case study', 'numbered-accordion' ),
					'edit_item'             => __( 'Edit case study', 'numbered-accordion' ),
					'new_item'              => __( 'New case study', 'numbered-accordion' ),
					'search_items'          => __( 'Search case studies', 'numbered-accordion' ),
					'not_found'             => __( 'No case studies yet.', 'numbered-accordion' ),
					'all_items'             => __( 'All case studies', 'numbered-accordion' ),
					'featured_image'        => __( 'Card photo', 'numbered-accordion' ),
					'set_featured_image'    => __( 'Set card photo', 'numbered-accordion' ),
					'remove_featured_image' => __( 'Remove card photo', 'numbered-accordion' ),
					'use_featured_image'    => __( 'Use as card photo', 'numbered-accordion' ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'has_archive'        => false,
				'rewrite'            => false,
				'menu_position'      => 21,
				'menu_icon'          => 'dashicons-portfolio',
				'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
			)
		);

		register_taxonomy(
			CaseStudies_Content::TAXONOMY,
			CaseStudies_Content::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Sectors', 'numbered-accordion' ),
					'singular_name' => __( 'Sector', 'numbered-accordion' ),
					'menu_name'     => __( 'Sectors', 'numbered-accordion' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'rewrite'           => false,
				// The sector is picked inside the card form; a second box in
				// the sidebar for the same thing would only disagree with it.
				'meta_box_cb'       => false,
			)
		);
	}

	/**
	 * The card form. See CaseStudies_Content::field_group().
	 */
	public function register_fields() {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group( CaseStudies_Content::field_group() );
		}
	}

	/**
	 * The title box asks for the customer.
	 *
	 * @param string   $text Placeholder.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function title_placeholder( $text, $post ) {
		return isset( $post->post_type ) && CaseStudies_Content::POST_TYPE === $post->post_type
			? __( 'Customer name, e.g. CWS Workwear', 'numbered-accordion' )
			: $text;
	}

	/**
	 * List columns: enough to check the whole set at a glance.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$out = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['ecs_photo'] = __( 'Photo', 'numbered-accordion' );
			}

			if ( 'date' === $key ) {
				continue;
			}

			$out[ $key ] = $label;

			if ( 'title' === $key ) {
				$out['ecs_sector'] = __( 'Sector', 'numbered-accordion' );
				$out['ecs_result'] = __( 'On the card', 'numbered-accordion' );
				$out['ecs_link']   = __( 'Link', 'numbered-accordion' );
				$out['menu_order'] = __( 'Order', 'numbered-accordion' );
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
			case 'ecs_photo':
				echo get_the_post_thumbnail( $post_id, array( 64, 40 ), array( 'style' => 'width:64px;height:40px;object-fit:cover;border-radius:4px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'ecs_sector':
				$terms = get_the_terms( $post_id, CaseStudies_Content::TAXONOMY );
				echo esc_html( is_array( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '—' );
				break;

			case 'ecs_result':
				if ( 'ongoing' === get_post_meta( $post_id, 'cs_status', true ) ) {
					echo '<em>' . esc_html__( 'Now measuring', 'numbered-accordion' ) . '</em>';
				} else {
					$figure = (string) get_post_meta( $post_id, 'cs_figure', true );
					$basis  = (string) get_post_meta( $post_id, 'cs_basis', true );
					echo '' === $figure
						? '<strong style="color:#b32d2e">' . esc_html__( 'No figure — card hidden', 'numbered-accordion' ) . '</strong>'
						: '<strong>' . esc_html( $figure ) . '</strong> ' . esc_html( (string) get_post_meta( $post_id, 'cs_figure_label', true ) );

					if ( '' !== $figure && '' === $basis ) {
						echo '<br><span style="color:#b32d2e">' . esc_html__( 'Basis missing', 'numbered-accordion' ) . '</span>';
					}
				}
				break;

			case 'ecs_link':
				$pdf = (int) get_post_meta( $post_id, 'cs_pdf', true );
				$url = (string) get_post_meta( $post_id, 'cs_link_url', true );
				echo esc_html( $pdf > 0 ? __( 'PDF', 'numbered-accordion' ) : ( '' !== $url ? $url : '—' ) );
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
		if ( ! $query->is_main_query() || CaseStudies_Content::POST_TYPE !== $query->get( 'post_type' ) || $query->get( 'orderby' ) ) {
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

		if ( ! $screen || CaseStudies_Content::POST_TYPE !== $screen->post_type ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Case study fields need the Advanced Custom Fields plugin. The cards on the site keep working without it, but they cannot be edited until it is active.', 'numbered-accordion' ) . '</p></div>';
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style( self::STYLE_HANDLE, ERUDA_URL . 'modules/casestudies/assets/css/case-studies.css', array(), ERUDA_VERSION );
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script( self::SCRIPT_HANDLE, ERUDA_URL . 'modules/casestudies/assets/js/case-studies.js', array(), ERUDA_VERSION, true );
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

			require_once ERUDA_PATH . 'modules/casestudies/widgets/class-case-studies-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\CaseStudies\Widgets\Case_Studies_Widget' ) ) {
				$widgets_manager->register( new Widgets\Case_Studies_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the case studies widget - ' . $e->getMessage() );
			}
		}
	}
}
