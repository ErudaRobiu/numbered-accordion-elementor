<?php
/**
 * Load the page 26 documents into the Documents post type, or bring them back
 * in line with DocShelf_Content::default_docs().
 *
 *   wp eval-file bin/seed-documents.php
 *
 * Safe to run again: a document is found by its title and updated. Each PDF
 * must already be in the media library (uploaded with an _ecs_seed_file of
 * its file name, as the document upload of 29 Sep 2026 did); covers are
 * uploaded from the plugin's own assets once and reused after. Needs the
 * Document Shelf module and ACF active.
 *
 * bin/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ErudaToolkit\Modules\DocShelf\DocShelf_Content;

if ( ! class_exists( DocShelf_Content::class ) || ! post_type_exists( DocShelf_Content::POST_TYPE ) ) {
	WP_CLI::error( 'The Document Shelf module is not active.' );
}

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF is not active.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * The attachment this site's seeding uploaded under a name.
 *
 * @param string $name Seed name.
 * @return int 0 when there is none.
 */
function edoc_seed_find( $name ) {
	$ids = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => '_ecs_seed_file', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $name, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

// The filters, in their order.
$terms = array();
foreach ( DocShelf_Content::filters() as $slug => $name ) {
	$term = term_exists( $slug, DocShelf_Content::TAXONOMY );

	if ( ! $term ) {
		$term = wp_insert_term( $name, DocShelf_Content::TAXONOMY, array( 'slug' => $slug ) );
		WP_CLI::log( "filter {$name}" );
	}

	if ( is_wp_error( $term ) ) {
		WP_CLI::error( $term->get_error_message() );
	}

	$terms[ $slug ] = (int) $term['term_id'];
}

$keys = array();
foreach ( DocShelf_Content::field_group()['fields'] as $field ) {
	if ( '' !== $field['name'] ) {
		$keys[ $field['name'] ] = $field['key'];
	}
}

foreach ( DocShelf_Content::default_docs() as $i => $doc ) {
	// The PDF: already in the library.
	$file = edoc_seed_find( basename( $doc['url'] ) );

	if ( ! $file ) {
		WP_CLI::warning( "{$doc['title']}: no PDF in the library named " . basename( $doc['url'] ) . ' — it will show Coming soon.' );
	}

	// The cover: uploaded once, from the plugin's assets.
	$cover_name = 'doc-cover-' . $doc['cover'];
	$cover      = edoc_seed_find( $cover_name );

	if ( ! $cover ) {
		$tmp = wp_tempnam( $cover_name );
		copy( ERUDA_PATH . DocShelf_Content::COVER_DIR . $doc['cover'], $tmp );
		$cover = media_handle_sideload( array( 'name' => $cover_name, 'tmp_name' => $tmp ), 0, $doc['title'] . ' (cover)' );

		if ( is_wp_error( $cover ) ) {
			WP_CLI::error( "{$cover_name}: " . $cover->get_error_message() );
		}

		update_post_meta( $cover, '_ecs_seed_file', $cover_name );
		update_post_meta( $cover, '_wp_attachment_image_alt', '' );
	}

	$found = get_posts(
		array(
			'post_type'   => DocShelf_Content::POST_TYPE,
			'post_status' => 'any',
			'title'       => $doc['title'],
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	$postarr = array(
		'post_type'   => DocShelf_Content::POST_TYPE,
		'post_title'  => $doc['title'],
		'post_status' => 'publish',
		'menu_order'  => ( $i + 1 ) * 10,
	);

	if ( $found ) {
		$postarr['ID'] = (int) $found[0];
	}

	$id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $doc['title'] . ': ' . $id->get_error_message() );
	}

	set_post_thumbnail( $id, $cover );
	wp_set_object_terms( $id, array( $terms[ $doc['key'] ] ), DocShelf_Content::TAXONOMY );

	update_field( $keys['doc_filter'], $terms[ $doc['key'] ], $id );
	update_field( $keys['doc_type'], $doc['type'], $id );
	update_field( $keys['doc_desc'], $doc['desc'], $id );
	update_field( $keys['doc_meta'], $doc['meta'], $id );
	update_field( $keys['doc_file'], $file ? $file : '', $id );
	update_field( $keys['doc_url'], '', $id );
	update_field( $keys['doc_new_tab'], 1, $id );
	update_field( $keys['doc_download'], 0, $id );

	WP_CLI::log( ( $found ? 'updated ' : 'created ' ) . "#{$id} {$doc['title']}" );
}

WP_CLI::success( 'Documents are in place.' );
