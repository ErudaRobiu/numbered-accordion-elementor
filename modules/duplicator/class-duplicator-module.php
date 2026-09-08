<?php
/**
 * Duplicator module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Duplicator;

use ErudaToolkit\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-duplicator.php';

/**
 * Adds a Duplicate action to the Pages and Posts list tables.
 *
 * Every hook here is admin-side. Nothing in this module can reach a front end.
 */
final class Duplicator_Module implements Module {

	const ACTION = 'eruda_duplicate';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'duplicator';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Duplicate Pages', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds a Duplicate link to pages and posts. The copy is always created as a draft.', 'numbered-accordion' );
	}

	/**
	 * Nothing beyond core is needed.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return true;
	}

	/**
	 * Never anything to report.
	 *
	 * @return string[]
	 */
	public static function requirement_messages() {
		return array();
	}

	/**
	 * Post types the Duplicate action is offered for.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		/**
		 * Filter the post types offered a Duplicate action.
		 *
		 * @param string[] $post_types Post type slugs.
		 */
		return (array) apply_filters( 'eruda_duplicator_post_types', array( 'page', 'post' ) );
	}

	/**
	 * Hook everything up. Admin-side only.
	 */
	public function boot() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'page_row_actions', array( $this, 'row_action' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );

		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_single' ) );

		foreach ( self::post_types() as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'bulk_action' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk' ), 10, 3 );
		}

		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * May the current user duplicate this post?
	 *
	 * Two checks, not one. Reading the source is not enough: the user must also
	 * be allowed to create posts of that type. Checking only edit_post is a
	 * common defect in duplicator plugins, and it lets a Contributor mint posts
	 * of a type they could not otherwise create.
	 *
	 * @param \WP_Post $post Source post.
	 * @return bool
	 */
	public static function user_can_duplicate( $post ) {
		if ( ! $post || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}

		$type = get_post_type_object( $post->post_type );

		if ( ! $type || ! isset( $type->cap->create_posts ) ) {
			return false;
		}

		return current_user_can( $type->cap->create_posts );
	}

	/**
	 * The Duplicate link in a list table row.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Post $post    The row's post.
	 * @return array
	 */
	public function row_action( $actions, $post ) {
		if ( ! self::user_can_duplicate( $post ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => self::ACTION,
					'post'   => (int) $post->ID,
				),
				admin_url( 'admin-post.php' )
			),
			self::ACTION . '_' . $post->ID
		);

		$actions[ self::ACTION ] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr(
				sprintf(
					/* translators: %s: post title */
					__( 'Duplicate "%s" as a new draft', 'numbered-accordion' ),
					$post->post_title
				)
			),
			esc_html__( 'Duplicate', 'numbered-accordion' )
		);

		return $actions;
	}

	/**
	 * Add Duplicate to the bulk action dropdown.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array
	 */
	public function bulk_action( $actions ) {
		$actions[ self::ACTION ] = esc_html__( 'Duplicate', 'numbered-accordion' );
		return $actions;
	}

	/**
	 * Handle a single Duplicate click.
	 */
	public function handle_single() {
		$post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'No item was given to duplicate.', 'numbered-accordion' ) );
		}

		check_admin_referer( self::ACTION . '_' . $post_id );

		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_die( esc_html__( 'That item no longer exists.', 'numbered-accordion' ) );
		}

		if ( ! self::user_can_duplicate( $post ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this item.', 'numbered-accordion' ) );
		}

		$result = Duplicator::duplicate( $post_id );

		$args = is_wp_error( $result )
			? array( 'eruda_duplicate_error' => 1 )
			: array(
				'eruda_duplicated' => 1,
				'eruda_new'        => (int) $result,
			);

		if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Eruda Toolkit: duplication failed - ' . $result->get_error_message() );
		}

		wp_safe_redirect( add_query_arg( $args, $this->return_url( $post->post_type ) ) );
		exit;
	}

	/**
	 * Handle the bulk action.
	 *
	 * @param string $redirect_to Where the list table will send the user.
	 * @param string $doaction    The chosen action.
	 * @param array  $post_ids    Selected post ids.
	 * @return string
	 */
	public function handle_bulk( $redirect_to, $doaction, $post_ids ) {
		if ( self::ACTION !== $doaction ) {
			return $redirect_to;
		}

		$done   = 0;
		$failed = 0;

		foreach ( (array) $post_ids as $post_id ) {
			$post = get_post( (int) $post_id );

			if ( ! self::user_can_duplicate( $post ) ) {
				++$failed;
				continue;
			}

			$result = Duplicator::duplicate( (int) $post_id );

			if ( is_wp_error( $result ) ) {
				++$failed;
				continue;
			}

			++$done;
		}

		$redirect_to = remove_query_arg( array( 'eruda_duplicated', 'eruda_new', 'eruda_duplicate_error' ), $redirect_to );

		return add_query_arg(
			array(
				'eruda_duplicated'      => $done,
				'eruda_duplicate_error' => $failed,
			),
			$redirect_to
		);
	}

	/**
	 * Where to send the user back to after a single duplication.
	 *
	 * @param string $post_type Post type of the source.
	 * @return string
	 */
	private function return_url( $post_type ) {
		$referer = wp_get_referer();

		if ( $referer ) {
			return $referer;
		}

		return admin_url( 'edit.php?post_type=' . rawurlencode( $post_type ) );
	}

	/**
	 * Report the outcome on the list table.
	 */
	public function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect result.
		$done   = isset( $_GET['eruda_duplicated'] ) ? absint( $_GET['eruda_duplicated'] ) : 0;
		$failed = isset( $_GET['eruda_duplicate_error'] ) ? absint( $_GET['eruda_duplicate_error'] ) : 0;
		$new_id = isset( $_GET['eruda_new'] ) ? absint( $_GET['eruda_new'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $done && ! $failed ) {
			return;
		}

		if ( $done ) {
			$message = sprintf(
				/* translators: %s: number of items duplicated */
				_n( '%s item duplicated as a draft.', '%s items duplicated as drafts.', $done, 'numbered-accordion' ),
				number_format_i18n( $done )
			);

			$link = '';

			if ( 1 === $done && $new_id && current_user_can( 'edit_post', $new_id ) ) {
				$link = sprintf(
					' <a href="%s">%s</a>',
					esc_url( get_edit_post_link( $new_id ) ),
					esc_html__( 'Edit the copy', 'numbered-accordion' )
				);
			}

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s%s</p></div>',
				esc_html( $message ),
				wp_kses_post( $link )
			);
		}

		if ( $failed ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: number of items that could not be duplicated */
						_n( '%s item could not be duplicated.', '%s items could not be duplicated.', $failed, 'numbered-accordion' ),
						number_format_i18n( $failed )
					)
				)
			);
		}
	}
}
