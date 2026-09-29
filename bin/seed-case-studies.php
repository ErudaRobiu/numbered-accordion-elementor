<?php
/**
 * Load the page 25 case studies into a site, or bring them back in line.
 *
 *   wp eval-file bin/seed-case-studies.php <dir>
 *
 * <dir> holds casestudies-data.php and every file it names: the photos, the
 * logos and the PDFs. Safe to run again: a case is found by its title and
 * updated, and a file already uploaded by this script is reused rather than
 * uploaded twice. Needs the Case Studies module and ACF active.
 *
 * bin/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ErudaToolkit\Modules\CaseStudies\CaseStudies_Content;

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';

if ( '' === $dir || ! is_file( $dir . '/casestudies-data.php' ) ) {
	WP_CLI::error( 'Pass the folder holding casestudies-data.php and its files.' );
}

if ( ! class_exists( CaseStudies_Content::class ) || ! post_type_exists( CaseStudies_Content::POST_TYPE ) ) {
	WP_CLI::error( 'The Case Studies module is not active.' );
}

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF is not active.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// One SVG logo (Bruzaholms). WordPress refuses SVG uploads, rightly, from
// visitors; this is our own file, and the permission lasts only this run.
add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
} );
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename ) {
	if ( '.svg' === strtolower( substr( $filename, -4 ) ) ) {
		$data = array( 'ext' => 'svg', 'type' => 'image/svg+xml', 'proper_filename' => false );
	}
	return $data;
}, 10, 3 );

/**
 * Upload a file once, and reuse it after.
 *
 * @param string $path Local file.
 * @param string $alt  Alt text, for images.
 * @return int Attachment id.
 */
function ecs_seed_media( $path, $alt = '' ) {
	$name     = basename( $path );
	$existing = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => '_ecs_seed_file', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $name, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	if ( $existing ) {
		$id = (int) $existing[0];
	} else {
		if ( ! is_file( $path ) ) {
			WP_CLI::error( "Missing file: {$path}" );
		}

		$tmp = wp_tempnam( $name );
		copy( $path, $tmp );
		$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0 );

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "{$name}: " . $id->get_error_message() );
		}

		update_post_meta( $id, '_ecs_seed_file', $name );
		WP_CLI::log( "  uploaded {$name} (#{$id})" );
	}

	if ( '' !== $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}

	return (int) $id;
}

// The sectors, in their order.
$terms = array();
foreach ( CaseStudies_Content::sectors() as $slug => $name ) {
	$term = term_exists( $slug, CaseStudies_Content::TAXONOMY );

	if ( ! $term ) {
		$term = wp_insert_term( $name, CaseStudies_Content::TAXONOMY, array( 'slug' => $slug ) );
		WP_CLI::log( "sector {$name}" );
	}

	if ( is_wp_error( $term ) ) {
		WP_CLI::error( $term->get_error_message() );
	}

	$terms[ $slug ] = (int) $term['term_id'];
}

// Field name => field key, so ACF keeps its references.
$keys = array();
foreach ( CaseStudies_Content::field_group()['fields'] as $field ) {
	if ( '' !== $field['name'] ) {
		$keys[ $field['name'] ] = $field['key'];
	}
}

foreach ( require $dir . '/casestudies-data.php' as $case ) {
	$found = get_posts(
		array(
			'post_type'   => CaseStudies_Content::POST_TYPE,
			'post_status' => 'any',
			'title'       => $case['title'],
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	$postarr = array(
		'post_type'   => CaseStudies_Content::POST_TYPE,
		'post_title'  => $case['title'],
		'post_status' => 'publish',
		'menu_order'  => (int) $case['order'],
	);

	if ( $found ) {
		$postarr['ID'] = (int) $found[0];
	}

	$id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $case['title'] . ': ' . $id->get_error_message() );
	}

	WP_CLI::log( ( $found ? 'updated ' : 'created ' ) . $case['title'] . " (#{$id})" );

	set_post_thumbnail( $id, ecs_seed_media( $dir . '/' . $case['photo'], isset( $case['alt'] ) ? $case['alt'] : '' ) );

	wp_set_object_terms( $id, array( $terms[ $case['sector'] ] ), CaseStudies_Content::TAXONOMY );
	update_field( $keys['cs_sector'], $terms[ $case['sector'] ], $id );

	foreach ( $case['meta'] as $name => $value ) {
		update_field( $keys[ $name ], $value, $id );
	}

	update_field( $keys['cs_logo'], ecs_seed_media( $dir . '/' . $case['logo'], $case['title'] ), $id );
	update_field( $keys['cs_pdf'], '' === $case['pdf'] ? '' : ecs_seed_media( $dir . '/' . $case['pdf'] ), $id );
}

WP_CLI::success( 'Case studies are in place.' );
