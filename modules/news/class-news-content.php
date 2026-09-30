<?php
/**
 * News content helpers.
 *
 * The pure logic, kept out of the widgets so it can be tested without
 * WordPress, Elementor or ACF. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The news fields, the categories, and how a list of posts becomes a bento.
 *
 * News items are WordPress's own Posts. Categories are the core category
 * taxonomy and topics are core tags, so nothing here registers a type.
 */
final class News_Content {

	/**
	 * The categories, in the order the chips show them, with each content-doc
	 * H2 as the term description.
	 *
	 * @return array<string, array{name: string, description: string}>
	 */
	public static function categories() {
		return array(
			'insights' => array(
				'name'        => 'Insights',
				'description' => 'Waste heat recovery technology and application insights',
			),
			'updates'  => array(
				'name'        => 'Updates',
				'description' => 'Customer and partner updates',
			),
			'media'    => array(
				'name'        => 'Media',
				'description' => 'Audio video and media',
			),
		);
	}

	/**
	 * The page 26 topics, as tags.
	 *
	 * @return string[]
	 */
	public static function topics() {
		return array( 'Basics', 'Equipment', 'Fouling', 'Boilers', 'Monitoring', 'Efficiency' );
	}

	/**
	 * The "News item" form on every post, as an ACF local field group.
	 *
	 * Field names are the post meta keys the widgets read, so the site
	 * never needs ACF to show a post, only to edit one.
	 *
	 * @return array
	 */
	public static function field_group() {
		// Either rule shows the quote: an audio file, or a Podcast card that
		// sends listeners to the episode page.
		$has_audio = array(
			array( array( 'field' => 'field_enws_audio', 'operator' => '!=empty' ) ),
			array( array( 'field' => 'field_enws_layout', 'operator' => '==', 'value' => 'audio' ) ),
		);

		return array(
			'key'                   => 'group_enws_news_item',
			'title'                 => 'News item',
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'menu_order'            => 0,
			'active'                => true,
			'show_in_rest'          => 1,
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'fields'                => array(
				array(
					'key'           => 'field_enws_related',
					'label'         => 'Related page',
					'name'          => 'nw_related',
					'type'          => 'post_object',
					'instructions'  => 'Every post links on to a product, industry, partner or library page.',
					'required'      => 1,
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 0,
					'multiple'      => 0,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_enws_featured',
					'label'         => 'Featured',
					'name'          => 'nw_featured',
					'type'          => 'true_false',
					'instructions'  => 'The newest featured post takes the big first tile on the News page.',
					'ui'            => 1,
					'default_value' => 0,
				),
				array(
					'key'          => 'field_enws_source_name',
					'label'        => 'Source',
					'name'         => 'nw_source_name',
					'type'         => 'text',
					'instructions' => 'For coverage by someone else, e.g. "BBC News".',
					'maxlength'    => 60,
				),
				array(
					'key'          => 'field_enws_source_url',
					'label'        => 'Source link',
					'name'         => 'nw_source_url',
					'type'         => 'url',
					'instructions' => 'The original article, or for a podcast the episode\'s own page (e.g. BBC Audio). A podcast card\'s Listen button goes here.',
				),
				array(
					'key'           => 'field_enws_audio',
					'label'         => 'Audio file',
					'name'          => 'nw_audio',
					'type'          => 'file',
					'instructions'  => 'Optional. With an MP3 the podcast card plays it in the page; without one its player opens the Source link.',
					'return_format' => 'id',
					'library'       => 'all',
					'mime_types'    => 'mp3, m4a',
				),
				array(
					'key'               => 'field_enws_audio_quote',
					'label'             => 'Quote from the audio',
					'name'              => 'nw_audio_quote',
					'type'              => 'textarea',
					'instructions'      => 'A line said in the recording, shown on the podcast card.',
					'rows'              => 3,
					'maxlength'         => 240,
					'new_lines'         => '',
					'conditional_logic' => $has_audio,
				),
				array(
					'key'               => 'field_enws_audio_credit',
					'label'             => 'Who said it',
					'name'              => 'nw_audio_credit',
					'type'              => 'text',
					'maxlength'         => 80,
					'conditional_logic' => $has_audio,
				),
				array(
					'key'           => 'field_enws_layout',
					'label'         => 'Card on the News page',
					'name'          => 'nw_layout',
					'type'          => 'select',
					'instructions'  => 'Automatic makes a post with an audio file a podcast card. Choose Podcast for an episode that lives elsewhere (it needs a Source link).',
					'choices'       => array(
						'auto'  => 'Automatic',
						'image' => 'Photo card',
						'audio' => 'Podcast',
					),
					'default_value' => 'auto',
					'return_format' => 'value',
				),
			),
		);
	}

	/**
	 * Turn one post's saved values into a card.
	 *
	 * @param array $raw {
	 *     @type int    $id         Post id.
	 *     @type string $title      Title.
	 *     @type string $url        Permalink.
	 *     @type string $date       Post date, Y-m-d H:i:s.
	 *     @type string $excerpt    Excerpt.
	 *     @type array  $cats       Categories, each array{slug: string, name: string}.
	 *     @type array  $meta       Post meta, key => single value.
	 *     @type string $audio_url  URL of the audio file, if any.
	 *     @type string $image      Image markup, already escaped.
	 * }
	 * @return array|null Null when there is no title.
	 */
	public static function card( $raw ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$meta  = isset( $raw['meta'] ) && is_array( $raw['meta'] ) ? $raw['meta'] : array();
		$cats  = isset( $raw['cats'] ) && is_array( $raw['cats'] ) ? array_values( $raw['cats'] ) : array();
		$get   = function ( $key ) use ( $meta ) {
			return isset( $meta[ $key ] ) && is_scalar( $meta[ $key ] ) ? trim( (string) $meta[ $key ] ) : '';
		};
		$str   = function ( $key ) use ( $raw ) {
			return isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? trim( (string) $raw[ $key ] ) : '';
		};
		$title = $str( 'title' );

		if ( '' === $title ) {
			return null;
		}

		$audio  = $str( 'audio_url' );
		$layout = $get( 'nw_layout' );
		// "Big photo" existed before the grid became plain cards.
		$layout = 'wide' === $layout ? 'image' : $layout;
		$listen = $get( 'nw_source_url' );
		$podcast = '' !== $audio || ( 'audio' === $layout && '' !== $listen );
		$time   = strtotime( $str( 'date' ) );

		return array(
			'id'           => isset( $raw['id'] ) ? (int) $raw['id'] : 0,
			'title'        => $title,
			'url'          => $str( 'url' ),
			'date'         => false === $time ? '' : gmdate( 'j M Y', $time ),
			'datetime'     => false === $time ? '' : gmdate( 'Y-m-d', $time ),
			'excerpt'      => $str( 'excerpt' ),
			'cat'          => isset( $cats[0]['slug'] ) ? (string) $cats[0]['slug'] : '',
			'cat_name'     => isset( $cats[0]['name'] ) ? (string) $cats[0]['name'] : '',
			'cats'         => array_values( array_filter( array_map( function ( $c ) {
				return isset( $c['slug'] ) ? (string) $c['slug'] : '';
			}, $cats ) ) ),
			'featured'     => in_array( $get( 'nw_featured' ), array( '1', 'yes', 'true' ), true ),
			'layout'       => in_array( $layout, array( 'image', 'audio' ), true ) ? $layout : 'auto',
			'audio'        => $audio,
			'quote'        => $get( 'nw_audio_quote' ),
			'credit'       => $get( 'nw_audio_credit' ),
			'source'       => $get( 'nw_source_name' ),
			'source_url'   => $listen,
			'type'         => $podcast && 'image' !== $layout ? 'Podcast' : ( '' !== $get( 'nw_source_name' ) ? $get( 'nw_source_name' ) : 'Article' ),
			'image'        => isset( $raw['image'] ) && is_string( $raw['image'] ) ? $raw['image'] : '',
		);
	}

	/**
	 * Which card each post gets: a podcast card or a photo card.
	 *
	 * A post is a podcast when it has an audio file, or when its card is set
	 * to Podcast and it has a Source link to send listeners to. "Photo card"
	 * always wins.
	 *
	 * @param array $cards Cards, in order.
	 * @return string[] One variant per card: audio or image.
	 */
	public static function variants( $cards ) {
		$out = array();

		foreach ( array_values( (array) $cards ) as $card ) {
			$playable = '' !== $card['audio'];
			$linked   = 'audio' === $card['layout'] && '' !== $card['source_url'];

			$out[] = 'image' !== $card['layout'] && ( $playable || $linked ) ? 'audio' : 'image';
		}

		return $out;
	}

	/**
	 * A waveform for a player: bar heights that look like speech, the same
	 * every time for the same post, different from post to post.
	 *
	 * @param int $seed Post id.
	 * @param int $bars How many bars.
	 * @return int[] Heights in percent, 22 to 92.
	 */
	public static function waveform( $seed, $bars = 36 ) {
		$out = array();

		for ( $i = 0; $i < $bars; $i++ ) {
			$n     = crc32( $seed . ':' . $i );
			// Louder in the middle of a phrase, quieter at its ends.
			$shape = sin( M_PI * ( ( $i % 9 ) + 1 ) / 10 );
			$out[] = (int) round( 22 + ( ( $n % 70 ) * ( 0.45 + 0.55 * $shape ) ) );
		}

		return $out;
	}

	/**
	 * The chips: the categories the cards use, in the preferred order, then
	 * any others by name.
	 *
	 * @param array $terms Categories that have posts, each array{slug: string, name: string}.
	 * @return array<string, string> Slug => name.
	 */
	public static function chips( $terms ) {
		$order = array_keys( self::categories() );
		$terms = array_values( array_filter( (array) $terms, function ( $t ) {
			return isset( $t['slug'], $t['name'] ) && 'uncategorized' !== $t['slug'];
		} ) );

		usort(
			$terms,
			function ( $a, $b ) use ( $order ) {
				$ia = array_search( $a['slug'], $order, true );
				$ib = array_search( $b['slug'], $order, true );
				$ia = false === $ia ? 99 : $ia;
				$ib = false === $ib ? 99 : $ib;

				return $ia === $ib ? strcmp( $a['name'], $b['name'] ) : $ia - $ib;
			}
		);

		$chips = array();

		foreach ( $terms as $term ) {
			$chips[ $term['slug'] ] = $term['name'];
		}

		return $chips;
	}

	/**
	 * Clamp a page size.
	 *
	 * @param mixed $value Value.
	 * @return int 1 to 24; 9 when unreadable.
	 */
	public static function per_page( $value ) {
		$value = is_numeric( $value ) ? (int) $value : 9;

		return max( 1, min( 24, $value ) );
	}
}
