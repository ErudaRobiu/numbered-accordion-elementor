<?php
/**
 * Customer Logo Tabs content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\LogoTabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page 24 customer base, and how settings become tabs, chips and tiles.
 *
 * Elementor cannot nest a repeater inside another, so tab > segment > logo is
 * stored flat: a repeater of tabs, and one repeater of logos in which every
 * row names its tab by position and its segment by label. The segments of a
 * tab are the labels its logos use, in the order they first appear.
 */
final class LogoTabs_Content {

	/**
	 * Where the bundled logos live, relative to the plugin root.
	 */
	const LOGO_DIR = 'modules/logotabs/assets/logos/';

	/**
	 * The most tabs a logo can be assigned to.
	 */
	const MAX_TABS = 6;

	/**
	 * The three audience tabs from the wireframe. Industrial first: an
	 * industrial visitor must never see restaurants first.
	 *
	 * @return array<int, array{key: string, title: string, subline: string}>
	 */
	public static function default_tabs() {
		return array(
			array(
				'key'     => 'ind',
				'title'   => 'Industrial',
				'subline' => 'Food processing, manufacturing, laundry',
			),
			array(
				'key'     => 'rest',
				'title'   => 'Restaurants & food service',
				'subline' => 'QSR, dining, grocery, schools',
			),
			array(
				'key'     => 'hosp',
				'title'   => 'Hospitality & hotels',
				'subline' => 'Hotels, venues, catering',
			),
		);
	}

	/**
	 * The customer base, in order: tab position, segment, file, name, and the
	 * file's width over height so it can be sized without being opened.
	 *
	 * A row with a wordmark has no file yet and prints the name in type.
	 *
	 * @return array<int, array{tab: int, segment: string, file: string, name: string, aspect: float, word?: string, word_style?: string, dark?: bool}>
	 */
	public static function default_logos() {
		$rows = array(
			// Industrial.
			array( 1, 'Food processing', 'schuld', 'Schuld Bakkerij', 200, 200 ),
			array( 1, 'Food processing', 'mondelez', 'Mondelēz International', 360, 173 ),
			array( 1, 'Food processing', 'smalands-munken', 'Smålands Munken', 289, 200 ),
			array( 1, 'Food processing', 'lantmannen', 'Lantmännen', 360, 66 ),
			array( 1, 'Food processing', 'ekro', 'Ekro', 360, 137 ),
			array( 1, 'Food processing', 'fazer', 'Fazer', 202, 200 ),
			array( 1, 'Manufacturing', '', 'Volvo', 0, 0, 'VOLVO', 'serif' ),
			array( 1, 'Manufacturing', '', 'De Bruyn', 0, 0, 'DE BRUYN', 'sans' ),
			array( 1, 'Manufacturing', 'sports-leisure', 'Sports & Leisure Group', 240, 121 ),
			array( 1, 'Manufacturing', 'bzh', 'BZH', 355, 200 ),
			array( 1, 'Industrial laundry', 'unifirst', 'UniFirst', 360, 76 ),
			array( 1, 'Industrial laundry', 'cleanlease', 'CleanLease', 360, 103 ),
			array( 1, 'Industrial laundry', 'cws', 'CWS Workwear', 360, 55 ),
			// Restaurants and food service.
			array( 2, 'QSR', 'kfc', 'KFC', 199, 200 ),
			array( 2, 'QSR', 'burger-king', 'Burger King', 180, 200 ),
			array( 2, 'QSR', 'popeyes', 'Popeyes', 360, 64 ),
			array( 2, 'QSR', 'chipotle', 'Chipotle', 201, 200 ),
			array( 2, 'QSR', 'five-guys', 'Five Guys', 200, 200 ),
			array( 2, 'QSR', 'wingstop', 'Wingstop', 360, 125 ),
			array( 2, 'QSR', 'max', 'MAX Premium Burgers', 248, 200 ),
			array( 2, 'QSR', 'jureskogs', 'Jureskogs', 199, 200 ),
			array( 2, 'QSR', 'babas', 'Babas', 360, 123 ),
			array( 2, 'Asian', '', 'Dishoom', 0, 0, 'DISHOOM', 'serif' ),
			array( 2, 'Asian', 'sen', 'SEN Street Kitchen', 233, 200 ),
			array( 2, 'Asian', 'chopchop', 'ChopChop Asian Express', 200, 200 ),
			array( 2, 'Asian', 'asian-diamond', 'Asian restaurant', 323, 200 ),
			array( 2, 'Casual & fine dining', 'rasta', 'Rasta', 360, 143 ),
			array( 2, 'Casual & fine dining', 'turtle-bay', 'Turtle Bay', 220, 77 ),
			array( 2, 'Casual & fine dining', 'pepes-bodega', 'Pepes Bodega', 360, 192 ),
			array( 2, 'Casual & fine dining', 'de-kas', 'Restaurant De Kas', 360, 178 ),
			array( 2, 'Casual & fine dining', 'l-and-r', 'L&R', 200, 200 ),
			array( 2, 'Casual & fine dining', 'beso', 'Beso', 198, 200 ),
			array( 2, 'Casual & fine dining', 'de-haas', 'De Haas', 300, 200 ),
			array( 2, 'Casual & fine dining', 'kott-bar', 'Kött & Bar', 267, 200 ),
			array( 2, 'Casual & fine dining', 'roast', 'Roast', 360, 77 ),
			array( 2, 'Casual & fine dining', 'catch', 'Catch', 360, 180 ),
			array( 2, 'Casual & fine dining', 'prime', 'Prime', 360, 72 ),
			array( 2, 'Casual & fine dining', 'davys', "Davy's", 360, 68 ),
			array( 2, 'Casual & fine dining', 'big-mamma', 'Big Mamma', 360, 42 ),
			array( 2, 'Casual & fine dining', 'la-gondola', 'La Gondola', 360, 55 ),
			array( 2, 'Food courts & grocery', 'sainsburys', "Sainsbury's", 360, 69 ),
			array( 2, 'Food courts & grocery', 'ahold-delhaize', 'Ahold Delhaize', 360, 114 ),
			array( 2, 'Food courts & grocery', 'kaufland', 'Kaufland', 199, 200 ),
			array( 2, 'Food courts & grocery', 'stena', 'Stena Fastigheter', 360, 77 ),
			array( 2, 'Schools & golf clubs', 'stora-segerstad', 'Stora Segerstad', 360, 146 ),
			array( 2, 'Schools & golf clubs', 'karlskrona', 'Karlskrona', 360, 110 ),
			array( 2, 'Schools & golf clubs', 'school-crest', 'School', 169, 200 ),
			array( 2, 'Schools & golf clubs', 'de-rooi-pannen', 'De Rooi Pannen', 360, 108 ),
			array( 2, 'Schools & golf clubs', 'angelholm', 'Ängelholm', 360, 168 ),
			array( 2, 'Schools & golf clubs', 'dunbar-golf', 'Dunbar Golf Club', 200, 200 ),
			// Hospitality and hotels: one segment, so no chips.
			array( 3, 'Hotels & venues', 'nordic-choice', 'Nordic Choice Hotels', 360, 156 ),
			array( 3, 'Hotels & venues', 'sodexo', 'Sodexo', 360, 112 ),
			array( 3, 'Hotels & venues', 'flight-club', 'Flight Club', 360, 148 ),
			array( 3, 'Hotels & venues', 'electric-shuffle', 'Electric Shuffle', 360, 169 ),
			array( 3, 'Hotels & venues', 'playdome', 'Playdôme', 360, 120 ),
			array( 3, 'Hotels & venues', 'battersea', 'Battersea Power Station', 287, 200, '', '', true ),
			array( 3, 'Hotels & venues', 'helsingborgs', 'Helsingborgs Stadsteater', 360, 159 ),
			array( 3, 'Hotels & venues', 'coppa', 'Coppa Club', 360, 97 ),
			array( 3, 'Hotels & venues', 'granen', 'Granen Hotell & Restaurang', 360, 164 ),
			array( 3, 'Hotels & venues', 'g-hotel', 'Hotel', 113, 200 ),
			array( 3, 'Hotels & venues', 'gullmarsstrand', 'Gullmarsstrand', 360, 33 ),
		);

		$logos = array();

		foreach ( $rows as $row ) {
			$logos[] = array(
				'tab'        => $row[0],
				'segment'    => $row[1],
				'file'       => '' === $row[2] ? '' : $row[2] . '.webp',
				'name'       => $row[3],
				'aspect'     => $row[5] > 0 ? (float) $row[4] / $row[5] : 0.0,
				'word'       => isset( $row[6] ) ? $row[6] : '',
				'word_style' => isset( $row[7] ) && '' !== $row[7] ? $row[7] : 'serif',
				'dark'       => ! empty( $row[8] ),
			);
		}

		return $logos;
	}

	/**
	 * How wide a logo should sit so a wall of them looks even.
	 *
	 * Equal widths make square badges tiny and equal heights make wordmarks
	 * huge. Holding the *area* steady (about 4000px²) does neither, and the two
	 * caps keep a very wide mark inside the tile and a very tall one under
	 * 58px high.
	 *
	 * @param mixed $aspect Width over height.
	 * @return int Pixels, or 0 when the aspect is unknown.
	 */
	public static function optical_width( $aspect ) {
		if ( ! is_numeric( $aspect ) || (float) $aspect <= 0 ) {
			return 0;
		}

		$aspect = (float) $aspect;

		return (int) round( min( 118, sqrt( 4000 * $aspect ), 58 * $aspect ) );
	}

	/**
	 * The aspect of a logo that shipped with the plugin, found by file name.
	 *
	 * @param mixed $url Image URL.
	 * @return float 0 when it is not one of ours.
	 */
	public static function bundled_aspect( $url ) {
		if ( ! is_string( $url ) || false === strpos( $url, self::LOGO_DIR ) ) {
			return 0.0;
		}

		$path = parse_url( $url, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		$file = basename( is_string( $path ) ? $path : '' );

		foreach ( self::default_logos() as $logo ) {
			if ( '' !== $logo['file'] && $logo['file'] === $file ) {
				return $logo['aspect'];
			}
		}

		return 0.0;
	}

	/**
	 * A key safe for an id, a data attribute and a URL hash.
	 *
	 * @param mixed  $text     Text.
	 * @param string $fallback Used when nothing survives.
	 * @return string
	 */
	public static function slug( $text, $fallback = '' ) {
		$text = is_scalar( $text ) ? strtolower( trim( (string) $text ) ) : '';
		$text = str_replace( '&', 'and', $text );
		$text = preg_replace( '/[^a-z0-9]+/', '-', $text );
		$text = trim( (string) $text, '-' );

		return '' === $text ? $fallback : $text;
	}

	/**
	 * Turn saved settings into tabs, each with its segments and its logos.
	 *
	 * Tab keys are made unique, because they become ids and URL hashes. A logo
	 * naming a tab that does not exist is left out rather than dumped into
	 * another audience. A logo with neither a picture nor a wordmark has
	 * nothing to show and is left out too.
	 *
	 * @param mixed $tabs  Tabs repeater value.
	 * @param mixed $logos Logos repeater value.
	 * @return array<int, array{key: string, title: string, subline: string, segments: array<string, string>, logos: array}>
	 */
	public static function build( $tabs, $logos ) {
		$tabs  = is_array( $tabs ) ? array_values( $tabs ) : array();
		$logos = is_array( $logos ) ? $logos : array();
		$out   = array();
		$taken = array();

		foreach ( array_slice( $tabs, 0, self::MAX_TABS ) as $index => $tab ) {
			$tab   = is_array( $tab ) ? $tab : array();
			$title = self::text( $tab, 'title' );
			$key   = self::slug( self::text( $tab, 'key' ), '' );
			$key   = '' === $key ? self::slug( $title, 'tab-' . ( $index + 1 ) ) : $key;
			$base  = $key;
			$n     = 2;

			while ( isset( $taken[ $key ] ) ) {
				$key = $base . '-' . $n++;
			}

			$taken[ $key ] = true;

			$out[] = array(
				'key'      => $key,
				'title'    => $title,
				'subline'  => self::text( $tab, 'subline' ),
				'segments' => array(),
				'logos'    => array(),
			);
		}

		foreach ( $logos as $logo ) {
			$logo  = is_array( $logo ) ? $logo : array();
			$index = isset( $logo['tab'] ) && is_numeric( $logo['tab'] ) ? (int) $logo['tab'] - 1 : 0;

			if ( ! isset( $out[ $index ] ) ) {
				continue;
			}

			$image = isset( $logo['image'] ) && is_array( $logo['image'] ) ? $logo['image'] : array();
			$url   = isset( $image['url'] ) && is_string( $image['url'] ) ? trim( $image['url'] ) : '';
			$word  = self::text( $logo, 'word' );

			if ( '' === $url && '' === $word ) {
				continue;
			}

			$label = self::text( $logo, 'segment' );
			$seg   = self::slug( $label, 'other' );

			if ( ! isset( $out[ $index ]['segments'][ $seg ] ) ) {
				$out[ $index ]['segments'][ $seg ] = '' === $label ? 'Other' : $label;
			}

			$link  = isset( $logo['link'] ) && is_array( $logo['link'] ) ? $logo['link'] : array();
			$width = isset( $logo['width'] ) && is_array( $logo['width'] ) && isset( $logo['width']['size'] ) && is_numeric( $logo['width']['size'] ) ? (int) $logo['width']['size'] : 0;

			$out[ $index ]['logos'][] = array(
				'segment'    => $seg,
				'url'        => $url,
				'id'         => isset( $image['id'] ) ? (int) $image['id'] : 0,
				'name'       => self::text( $logo, 'name' ),
				'word'       => $word,
				'word_style' => 'sans' === self::text( $logo, 'word_style' ) ? 'sans' : 'serif',
				'dark'       => 'yes' === self::text( $logo, 'dark' ),
				'width'      => $width > 0 ? $width : 0,
				'link'       => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
				'external'   => ! empty( $link['is_external'] ),
				'nofollow'   => ! empty( $link['nofollow'] ),
			);
		}

		return $out;
	}

	/**
	 * Which tab opens first, as an index. Out of range lands on the first.
	 *
	 * @param mixed $value Control value, counted from one.
	 * @param int   $count How many tabs there are.
	 * @return int
	 */
	public static function default_index( $value, $count ) {
		$index = is_numeric( $value ) ? (int) $value - 1 : 0;

		return $index >= 0 && $index < $count ? $index : 0;
	}

	/**
	 * A trimmed text setting, or an empty string.
	 *
	 * @param array  $row Row.
	 * @param string $key Key.
	 * @return string
	 */
	private static function text( $row, $key ) {
		return isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? trim( (string) $row[ $key ] ) : '';
	}
}

