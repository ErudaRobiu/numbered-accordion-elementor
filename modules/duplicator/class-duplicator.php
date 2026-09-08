<?php
/**
 * The duplication itself.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Duplicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copies a post.
 *
 * The decision-making here is deliberately separated from the hook wiring in
 * Duplicator_Module: everything on this class is either pure or takes its
 * inputs as plain arrays, so it can be exercised without a WordPress install.
 */
final class Duplicator {

	/**
	 * Meta keys that must not be carried across.
	 *
	 * The _edit_* pair is a stale editor lock. The _wp_old_* pair is redirect
	 * history belonging to the source's slug.
	 *
	 * The three Elementor entries are caches keyed to the source post id.
	 * Copying them makes the duplicate serve the original's stale CSS, which is
	 * the classic way a duplicated Elementor page comes out looking wrong.
	 * Elementor regenerates all three on first render.
	 *
	 * @return string[]
	 */
	public static function denied_meta_keys() {
		return array(
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_old_date',
			'_elementor_css',
			'_elementor_page_assets',
			'_elementor_element_cache',
		);
	}

	/**
	 * Strip the denylist and re-slash what survives.
	 *
	 * Slashing is the subtle part. _elementor_data is slashed JSON in the
	 * database; get_post_meta() unslashes on the way out and update_post_meta()
	 * slashes on the way in, so a naive get-then-write round trip strips one
	 * level of escaping and corrupts every escaped quote in the layout. Passing
	 * values back through wp_slash() restores the balance. This is the correct
	 * general form for a meta round trip, not an Elementor special case.
	 *
	 * @param array $meta Raw meta, as get_post_meta( $id ) returns it:
	 *                    key => list of unserialized values.
	 * @return array Same shape, filtered and slashed.
	 */
	public static function filter_meta( $meta ) {
		if ( ! is_array( $meta ) ) {
			return array();
		}

		$denied = self::denied_meta_keys();
		$clean  = array();

		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, $denied, true ) ) {
				continue;
			}

			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			$clean[ $key ] = array_map( 'wp_slash', $values );
		}

		return $clean;
	}

	/**
	 * Title for the copy.
	 *
	 * @param string $title Source title.
	 * @return string
	 */
	public static function copy_title( $title ) {
		return sprintf(
			/* translators: %s: the title of the post being duplicated */
			__( '%s (Copy)', 'numbered-accordion' ),
			$title
		);
	}

	/**
	 * Build the wp_insert_post() payload.
	 *
	 * Deliberately omitted, so WordPress generates them fresh: post_name, which
	 * would otherwise collide with the source's slug, and post_date.
	 *
	 * @param array $source    Source post as an array.
	 * @param int   $author_id Author for the copy: whoever clicked Duplicate,
	 *                         not the original author. The copy is their draft.
	 * @return array
	 */
	public static function build_args( $source, $author_id ) {
		$get = function ( $key, $default = '' ) use ( $source ) {
			return isset( $source[ $key ] ) ? $source[ $key ] : $default;
		};

		return array(
			'post_title'            => self::copy_title( (string) $get( 'post_title' ) ),
			'post_content'          => (string) $get( 'post_content' ),
			'post_content_filtered' => (string) $get( 'post_content_filtered' ),
			'post_excerpt'          => (string) $get( 'post_excerpt' ),
			'post_type'             => (string) $get( 'post_type', 'post' ),
			'post_parent'           => (int) $get( 'post_parent', 0 ),
			'menu_order'            => (int) $get( 'menu_order', 0 ),
			'comment_status'        => (string) $get( 'comment_status', 'closed' ),
			'ping_status'           => (string) $get( 'ping_status', 'closed' ),
			'post_password'         => (string) $get( 'post_password' ),
			'post_mime_type'        => (string) $get( 'post_mime_type' ),

			// Always a draft, never published by surprise.
			'post_status'           => 'draft',
			'post_author'           => (int) $author_id,
		);
	}

	/**
	 * Duplicate a post.
	 *
	 * @param int $source_id Post to copy.
	 * @return int|\WP_Error New post id.
	 */
	public static function duplicate( $source_id ) {
		$source = get_post( $source_id );

		if ( ! $source ) {
			return new \WP_Error(
				'eruda_missing_source',
				__( 'That item no longer exists.', 'numbered-accordion' )
			);
		}

		$args = self::build_args( $source->to_array(), get_current_user_id() );

		// wp_insert_post() expects slashed data; $source came out of the
		// database unslashed.
		$new_id = wp_insert_post( wp_slash( $args ), true );

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		self::copy_taxonomies( $source, (int) $new_id );
		self::copy_meta( (int) $source->ID, (int) $new_id );

		/**
		 * Fires once a post has been duplicated.
		 *
		 * @param int      $new_id    The new post id.
		 * @param \WP_Post $source    The post it was copied from.
		 */
		do_action( 'eruda_post_duplicated', (int) $new_id, $source );

		return (int) $new_id;
	}

	/**
	 * Copy every taxonomy term across.
	 *
	 * Covers the post format too, which is the post_format taxonomy rather
	 * than a field of its own.
	 *
	 * @param \WP_Post $source Source post.
	 * @param int      $new_id Destination post id.
	 */
	private static function copy_taxonomies( $source, $new_id ) {
		$taxonomies = get_object_taxonomies( $source->post_type );

		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $source->ID, $taxonomy, array( 'fields' => 'ids' ) );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			wp_set_object_terms( $new_id, $terms, $taxonomy, false );
		}
	}

	/**
	 * Copy meta across.
	 *
	 * add_post_meta() rather than update_post_meta(), so a key holding several
	 * values keeps all of them. The featured image needs no special case: it is
	 * the _thumbnail_id key and rides along here.
	 *
	 * @param int $source_id Source post id.
	 * @param int $new_id    Destination post id.
	 */
	private static function copy_meta( $source_id, $new_id ) {
		$meta = self::filter_meta( get_post_meta( $source_id ) );

		foreach ( $meta as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, $value );
			}
		}
	}
}
