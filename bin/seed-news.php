<?php
/**
 * Set up the news categories and topics, and load the launch posts.
 *
 *   wp eval-file bin/seed-news.php <dir>
 *
 * <dir> holds news-data.php, each post's body HTML and its images. Safe to
 * run again: categories and tags are found by slug, posts by slug, and a
 * file already uploaded by a seed script is reused. Also renames
 * "Uncategorized" to Insights, so a post saved without a category still
 * lands in a real one, and deletes the default "Hello world!".
 *
 * bin/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ErudaToolkit\Modules\News\News_Content;

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';

if ( '' === $dir || ! is_file( $dir . '/news-data.php' ) ) {
	WP_CLI::error( 'Pass the folder holding news-data.php.' );
}

if ( ! class_exists( News_Content::class ) || ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'The News module and ACF must both be active.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Upload a file once, and reuse it after.
 *
 * @param string $path File.
 * @param string $alt  Alt text.
 * @return int
 */
function enws_seed_media( $path, $alt ) {
	$name = basename( $path );
	$ids  = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => '_ecs_seed_file', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $name, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	if ( $ids ) {
		$id = (int) $ids[0];
	} else {
		$tmp = wp_tempnam( $name );
		copy( $path, $tmp );
		$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0 );

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "{$name}: " . $id->get_error_message() );
		}

		update_post_meta( $id, '_ecs_seed_file', $name );
		WP_CLI::log( "  uploaded {$name} (#{$id})" );
	}

	update_post_meta( $id, '_wp_attachment_image_alt', $alt );

	return (int) $id;
}

// Categories. The default category (Uncategorized) becomes Insights, so it
// can never show up as a chip or a pill.
$cats    = array();
$default = (int) get_option( 'default_category' );

foreach ( News_Content::categories() as $slug => $cat ) {
	$term = get_term_by( 'slug', $slug, 'category' );

	if ( ! $term && 'insights' === $slug && $default ) {
		$was = get_term( $default, 'category' );

		if ( $was && 'uncategorized' === $was->slug ) {
			wp_update_term( $default, 'category', array( 'name' => $cat['name'], 'slug' => $slug, 'description' => $cat['description'] ) );
			$term = get_term( $default, 'category' );
			WP_CLI::log( 'renamed Uncategorized to Insights' );
		}
	}

	if ( ! $term ) {
		$made = wp_insert_term( $cat['name'], 'category', array( 'slug' => $slug, 'description' => $cat['description'] ) );

		if ( is_wp_error( $made ) ) {
			WP_CLI::error( $made->get_error_message() );
		}

		$term = get_term( $made['term_id'], 'category' );
		WP_CLI::log( "category {$cat['name']}" );
	} else {
		wp_update_term( $term->term_id, 'category', array( 'name' => $cat['name'], 'description' => $cat['description'] ) );
	}

	$cats[ $slug ] = (int) $term->term_id;
}

update_option( 'default_category', $cats['insights'] );

foreach ( News_Content::topics() as $topic ) {
	if ( ! term_exists( $topic, 'post_tag' ) ) {
		wp_insert_term( $topic, 'post_tag' );
		WP_CLI::log( "topic {$topic}" );
	}
}

$keys = array();
foreach ( News_Content::field_group()['fields'] as $field ) {
	$keys[ $field['name'] ] = $field['key'];
}

foreach ( require $dir . '/news-data.php' as $item ) {
	$body = (string) file_get_contents( $dir . '/' . $item['body'] );

	if ( ! empty( $item['inline'] ) ) {
		foreach ( $item['inline'] as $marker => $file ) {
			$img  = enws_seed_media( $dir . '/' . $file, '' );
			$body = str_replace( $marker, '<figure class="wp-block-image size-large">' . wp_get_attachment_image( $img, 'large' ) . '</figure>' . "\n", $body );
		}
	}

	$found   = get_posts( array( 'name' => $item['slug'], 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$postarr = array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_title'    => $item['title'],
		'post_name'     => $item['slug'],
		'post_content'  => $body,
		'post_excerpt'  => $item['excerpt'],
		'post_date'     => $item['date'],
		'post_date_gmt' => get_gmt_from_date( $item['date'] ),
		'post_category' => array( $cats[ $item['cat'] ] ),
		'tags_input'    => $item['tags'],
	);

	if ( $found ) {
		$postarr['ID'] = (int) $found[0];
	}

	$id = wp_insert_post( wp_slash( $postarr ), true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $item['title'] . ': ' . $id->get_error_message() );
	}

	set_post_thumbnail( $id, enws_seed_media( $dir . '/' . $item['image'], $item['alt'] ) );

	// By slug, not path: most target pages sit under a parent
	// (/industries/restaurants-commercial-kitchens/), and get_page_by_path()
	// wants the whole path.
	$related = get_posts( array( 'post_type' => 'page', 'name' => $item['related'], 'post_status' => 'publish', 'numberposts' => 1 ) );

	if ( $related ) {
		update_field( $keys['nw_related'], $related[0]->ID, $id );
	} else {
		WP_CLI::warning( "{$item['title']}: no published page at /{$item['related']}/ for Related — left empty." );
	}

	foreach ( array( 'nw_featured', 'nw_source_name', 'nw_source_url', 'nw_audio_quote', 'nw_audio_credit' ) as $name ) {
		update_field( $keys[ $name ], isset( $item['meta'][ $name ] ) ? $item['meta'][ $name ] : ( 'nw_featured' === $name ? 0 : '' ), $id );
	}

	update_field( $keys['nw_layout'], isset( $item['meta']['nw_layout'] ) ? $item['meta']['nw_layout'] : 'auto', $id );

	WP_CLI::log( ( $found ? 'updated ' : 'created ' ) . "#{$id} {$item['title']}" );
}

$hello = get_posts( array( 'name' => 'hello-world', 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1 ) );

if ( $hello && 'Hello world!' === $hello[0]->post_title ) {
	wp_delete_post( $hello[0]->ID, true );
	WP_CLI::log( 'deleted "Hello world!"' );
}

WP_CLI::success( 'News is in place.' );
