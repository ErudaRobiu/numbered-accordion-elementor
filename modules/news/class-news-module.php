<?php
/**
 * News module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "News item" form on posts, the Load more route, and the four news
 * widgets: News Grid, News Carousel, Press Quotes and Post Source Box.
 *
 * News items are ordinary Posts, so there is no type to register: the
 * categories are core categories and the topics are core tags.
 */
final class News_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'enws-news';
	const SCRIPT_HANDLE = 'enws-news';
	const REST_NS       = 'eruda/v1';
	const REST_ROUTE    = '/news-grid';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'news';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'News', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds news fields to posts and four widgets that read them: News Grid, News Carousel, Press Quotes and Post Source Box.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/news/class-news-content.php';
		require_once ERUDA_PATH . 'modules/news/class-news-render.php';

		add_action( 'acf/init', array( $this, 'register_fields' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * The "News item" form. See News_Content::field_group().
	 */
	public function register_fields() {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group( News_Content::field_group() );
		}
	}

	/**
	 * GET /wp-json/eruda/v1/news-grid: the next cards for "Load more".
	 *
	 * Public and read-only: it returns what the archive already shows to
	 * anyone, one page at a time.
	 */
	public function register_route() {
		register_rest_route(
			self::REST_NS,
			self::REST_ROUTE,
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'offset'   => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
					'per'      => array( 'type' => 'integer', 'default' => 9, 'minimum' => 1, 'maximum' => 24 ),
					'featured' => array( 'type' => 'integer', 'default' => 0, 'minimum' => 0 ),
					'cats'     => array( 'type' => 'string', 'default' => '' ),
					'tags'     => array( 'type' => 'string', 'default' => '' ),
				),
				'callback'            => function ( $request ) {
					$page = News_Render::query(
						array(
							'per'      => $request['per'],
							'offset'   => $request['offset'],
							'featured' => $request['featured'],
							'cats'     => array_filter( explode( ',', (string) $request['cats'] ) ),
							'tags'     => array_filter( explode( ',', (string) $request['tags'] ) ),
						)
					);

					return rest_ensure_response(
						array(
							'html' => News_Render::grid_cards( $page['cards'], false ),
							'more' => $page['more'],
							'next' => $page['next'],
						)
					);
				},
			)
		);
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style( self::STYLE_HANDLE, ERUDA_URL . 'modules/news/assets/css/news.css', array(), ERUDA_VERSION );
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script( self::SCRIPT_HANDLE, ERUDA_URL . 'modules/news/assets/js/news.js', array(), ERUDA_VERSION, true );
	}

	/**
	 * Register the widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widget manager.
	 */
	public function register_widgets( $widgets_manager ) {
		try {
			if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
				return;
			}

			require_once ERUDA_PATH . 'modules/news/widgets/class-news-grid-widget.php';
			require_once ERUDA_PATH . 'modules/news/widgets/class-news-carousel-widget.php';
			require_once ERUDA_PATH . 'modules/news/widgets/class-press-quotes-widget.php';
			require_once ERUDA_PATH . 'modules/news/widgets/class-post-source-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\News\Widgets\News_Grid_Widget' ) ) {
				$widgets_manager->register( new Widgets\News_Grid_Widget() );
			}

			if ( class_exists( '\ErudaToolkit\Modules\News\Widgets\News_Carousel_Widget' ) ) {
				$widgets_manager->register( new Widgets\News_Carousel_Widget() );
			}

			if ( class_exists( '\ErudaToolkit\Modules\News\Widgets\Press_Quotes_Widget' ) ) {
				$widgets_manager->register( new Widgets\Press_Quotes_Widget() );
			}

			if ( class_exists( '\ErudaToolkit\Modules\News\Widgets\Post_Source_Widget' ) ) {
				$widgets_manager->register( new Widgets\Post_Source_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the news widgets - ' . $e->getMessage() );
			}
		}
	}
}
