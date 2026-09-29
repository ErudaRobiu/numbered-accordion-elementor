<?php
/**
 * Document Shelf content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\DocShelf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page 26 documents, the chip labels, and how settings become cards.
 */
final class DocShelf_Content {

	/**
	 * Where the bundled covers live, relative to the plugin root.
	 */
	const COVER_DIR = 'modules/docshelf/assets/covers/';

	/**
	 * The URL hash that opens a filter is this plus the filter key:
	 * /resources/#docs-perf.
	 */
	const HASH_PREFIX = 'docs-';

	/**
	 * The page 26 shelf. Every file is in this site's media library, and the
	 * links are root-relative, so they survive the move from staging to the
	 * real domain. An empty link would show "Coming soon".
	 *
	 * The Pancake Factory case study (confidential footer) and the Scott
	 * Preston document (internal) are deliberately not here.
	 *
	 * @return array<int, array{key: string, cover: string, type: string, title: string, desc: string, meta: string, url: string}>
	 */
	public static function default_docs() {
		$rows = array(
			array( 'white', 'overview', 'White paper', 'Waste heat recovery for commercial and industrial facilities', 'Fundamentals, dirty-exhaust challenges, PRG and system architecture. By Scott J. Preston, CEM.', '12 pages · May 2026', '/wp-content/uploads/2026/09/ThermStar_White_Paper_FINAL.pdf' ),
			array( 'white', 'technical', 'White paper', 'ThermStar System technical white paper', 'Written for mechanical, energy and HVAC design engineers.', '5 pages', '/wp-content/uploads/2026/09/ThermStar-Technical-Whitepaper-Final.pdf' ),
			array( 'product', 'brochure', 'Product literature', 'ThermStar System brochure', 'The turnkey system at a glance: Lepido®, HeatCore™, sensors and controls.', 'Brochure', '/wp-content/uploads/2026/09/ThermStar-System-Brochure.pdf' ),
			array( 'industry', 'food', 'Industry guide', 'ThermStar System for food production', 'Oven, fryer and dryer exhaust, and where that heat can go.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Food-Production.pdf' ),
			array( 'industry', 'laundry', 'Industry guide', 'ThermStar System for industrial laundry', 'Lint-laden dryer, washer and finisher exhaust, plus the CWS result.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Industrial-Laundry.pdf' ),
			array( 'industry', 'petfood', 'Industry guide', 'ThermStar System for pet food', 'Dryer, oven and cooker exhaust in pet food plants.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Pet-Food.pdf' ),
			array( 'perf', '22month', 'Performance study', '22-month field validation of Lepido®', 'Kitchen exhaust, no pre-filter, no cleaning. Effectiveness above 98%.', '4 pages', '/wp-content/uploads/2026/09/22_Month_Long_Term_Lepido_White_Paper_FINAL.pdf' ),
			array( 'perf', 'cs-cws', 'Case study', 'CWS Workwear, industrial laundry', '63% less gas on the tunnel washers. Den Bosch, Netherlands.', '15 pages', '/wp-content/uploads/2026/09/Case-Study-CWS-laundry.pdf' ),
			array( 'perf', 'cs-laholm', 'Case study', 'Lantmännen, food production', "99% of the site's heat demand covered. Laholm, Sweden.", '8 pages', '/wp-content/uploads/2026/09/Case-Study-Laholm-Food-production.pdf' ),
			array( 'perf', 'cs-bruz', 'Case study', 'Bruzaholms, foundry', '85% of the heating need from sand cooler exhaust. Småland, Sweden.', '7 pages', '/wp-content/uploads/2026/09/Case-Study-Bruzaholms-Foundry.pdf' ),
			array( 'perf', 'cs-bk', 'Comparative study', 'Burger King UK, restaurants', '11–36% lower electricity bill against a control site.', '11 pages', '/wp-content/uploads/2026/09/Case-Sudy-Burger-King.pdf' ),
			array( 'company', 'customers', 'Company', 'Global installed customer base', 'Customers by sector: restaurants, food, manufacturing, laundry and hotels.', '2 pages', '/wp-content/uploads/2026/09/ThermStar-Global-Installed-Customer-Base.pdf' ),
		);

		$docs = array();

		foreach ( $rows as $row ) {
			$docs[] = array(
				'key'   => $row[0],
				'cover' => $row[1] . '.webp',
				'type'  => $row[2],
				'title' => $row[3],
				'desc'  => $row[4],
				'meta'  => $row[5],
				'url'   => $row[6],
			);
		}

		return $docs;
	}

	/**
	 * The chip labels, one "key: Label" per line, as the panel holds them.
	 *
	 * @return string
	 */
	public static function default_chip_map() {
		return "white: White papers\nperf: Performance evidence\nindustry: Industry guides\nproduct: Product literature\ncompany: Company";
	}

	/**
	 * A filter key: lowercase letters, digits and hyphens.
	 *
	 * @param mixed $text Text.
	 * @return string
	 */
	public static function key( $text ) {
		$text = is_scalar( $text ) ? strtolower( trim( (string) $text ) ) : '';

		return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $text ), '-' );
	}

	/**
	 * Read the "key: Label" lines. A line without a colon, or with an empty
	 * side, is skipped rather than guessed at.
	 *
	 * @param mixed $text Textarea value.
	 * @return array<string, string>
	 */
	public static function parse_chip_map( $text ) {
		$map = array();

		foreach ( preg_split( '/\r\n|\r|\n/', is_scalar( $text ) ? (string) $text : '' ) as $line ) {
			$parts = explode( ':', $line, 2 );

			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$key   = self::key( $parts[0] );
			$label = trim( $parts[1] );

			if ( '' !== $key && '' !== $label ) {
				$map[ $key ] = $label;
			}
		}

		return $map;
	}

	/**
	 * Turn the repeater into cards. A row with no title has nothing to show
	 * and is left out; a row with no link stays, as "Coming soon".
	 *
	 * @param mixed $items Repeater value.
	 * @return array
	 */
	public static function build( $items ) {
		$out = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item  = is_array( $item ) ? $item : array();
			$title = self::text( $item, 'title' );

			if ( '' === $title ) {
				continue;
			}

			$cover = isset( $item['cover'] ) && is_array( $item['cover'] ) ? $item['cover'] : array();
			$link  = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();
			$url   = isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '';
			$key   = self::key( self::text( $item, 'key' ) );

			$out[] = array(
				'key'      => '' === $key ? 'other' : $key,
				'cover'    => isset( $cover['url'] ) && is_string( $cover['url'] ) ? trim( $cover['url'] ) : '',
				'cover_id' => isset( $cover['id'] ) ? (int) $cover['id'] : 0,
				'type'     => self::text( $item, 'type' ),
				'title'    => $title,
				'desc'     => self::text( $item, 'desc' ),
				'meta'     => self::text( $item, 'meta' ),
				'url'      => $url,
				'new_tab'  => 'yes' === self::text( $item, 'new_tab' ),
				'download' => 'yes' === self::text( $item, 'download' ),
				'nofollow' => ! empty( $link['nofollow'] ),
			);
		}

		return $out;
	}

	/**
	 * The chips: every key the cards use, in the order they first appear,
	 * labelled from the map. A key with no label is shown capitalised, so a
	 * new key still gets a chip instead of vanishing.
	 *
	 * @param array                $docs Built cards.
	 * @param array<string,string> $map  Key => label.
	 * @return array<string, string>
	 */
	public static function chips( $docs, $map ) {
		$chips = array();

		foreach ( (array) $docs as $doc ) {
			if ( ! isset( $chips[ $doc['key'] ] ) ) {
				$chips[ $doc['key'] ] = isset( $map[ $doc['key'] ] ) ? $map[ $doc['key'] ] : ucfirst( str_replace( '-', ' ', $doc['key'] ) );
			}
		}

		return $chips;
	}

	/**
	 * What a screen reader hears for a card link: the title, then "PDF" and
	 * the meta text, then that it opens a new tab when it does. The card's
	 * own text would read the type label and the description too, which is a
	 * paragraph where a name is wanted.
	 *
	 * @param array $doc Built card.
	 * @return string
	 */
	public static function link_label( $doc ) {
		$parts = array( $doc['title'], 'PDF' . ( '' !== $doc['meta'] ? ', ' . $doc['meta'] : '' ) );

		if ( $doc['new_tab'] ) {
			$parts[] = 'opens in a new tab';
		}

		return implode( ', ', $parts );
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
