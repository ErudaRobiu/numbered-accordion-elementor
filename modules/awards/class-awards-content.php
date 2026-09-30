<?php
/**
 * Award Wall content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Awards;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The eight Lepido awards, and how the repeater becomes the wall.
 */
final class Awards_Content {

	/**
	 * Where the bundled badges live, relative to the plugin root.
	 */
	const BADGE_DIR = 'modules/awards/assets/badges/';

	/**
	 * The awards as norrelinc.com/awards/ lists them. The Nordic badge itself
	 * reads "2017 Finalist" where the page says 2019; 2019 stands until the
	 * client says otherwise.
	 *
	 * @return array<int, array{file: string, year: string, name: string, detail: string, dark: bool}>
	 */
	public static function default_items() {
		$rows = array(
			array( 'wwf', '2018', 'WWF Climate Solver', 'Finalist', false ),
			array( 'nordic', '2019', 'Nordic Cleantech Open', 'Finalist', false ),
			array( 'cleantech100', '2019', 'Global Cleantech 100', 'Top 100 company', false ),
			array( 'perpetuum', '2020', 'Perpetuum Energy Efficiency Prize', 'Germany', true ),
			array( 'solar-impulse', '2020', 'Solar Impulse Efficient Solution', 'Switzerland', false ),
			array( 'postcode', '2020', 'Postcode Lotteries Green Challenge', 'Germany', false ),
			array( 'set', '2020', 'SET Award', '#SET100 · Germany', false ),
			array( 'horecava', '2022', 'Horecava Innovation Award', 'Winner · Netherlands', false ),
		);
		$out  = array();

		foreach ( $rows as $r ) {
			$out[] = array(
				'file'   => $r[0] . '.webp',
				'year'   => $r[1],
				'name'   => $r[2],
				'detail' => $r[3],
				'dark'   => $r[4],
			);
		}

		return $out;
	}

	/**
	 * Turn the repeater into cards. A card needs a name.
	 *
	 * @param mixed $items Repeater value.
	 * @return array
	 */
	public static function build( $items ) {
		$out = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item = is_array( $item ) ? $item : array();
			$name = self::text( $item, 'name' );

			if ( '' === $name ) {
				continue;
			}

			$badge = isset( $item['badge'] ) && is_array( $item['badge'] ) ? $item['badge'] : array();
			$link  = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();

			$out[] = array(
				'badge'    => isset( $badge['url'] ) && is_string( $badge['url'] ) ? trim( $badge['url'] ) : '',
				'badge_id' => isset( $badge['id'] ) ? (int) $badge['id'] : 0,
				'year'     => self::text( $item, 'year' ),
				'name'     => $name,
				'detail'   => self::text( $item, 'detail' ),
				'dark'     => 'yes' === self::text( $item, 'dark' ),
				'link'     => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
				'external' => ! empty( $link['is_external'] ),
				'nofollow' => ! empty( $link['nofollow'] ),
			);
		}

		return $out;
	}

	/**
	 * The earliest and latest year, as "2018–2022", or one year, or nothing.
	 * Anything in a year field that is not a four-digit year is ignored.
	 *
	 * @param array $cards Built cards.
	 * @return string
	 */
	public static function year_range( $cards ) {
		$years = array();

		foreach ( (array) $cards as $card ) {
			if ( is_array( $card ) && isset( $card['year'] ) && preg_match( '/\b(19|20)\d{2}\b/', (string) $card['year'], $m ) ) {
				$years[] = (int) $m[0];
			}
		}

		if ( ! $years ) {
			return '';
		}

		$from = min( $years );
		$to   = max( $years );

		// Word joiners either side of the dash, so a narrow line never breaks
		// the range into "2018–" and "2022".
		return $from === $to ? (string) $from : $from . "\u{2060}–\u{2060}" . $to;
	}

	/**
	 * The big number: the count of awards, or a number typed in.
	 *
	 * @param string $mode   count or manual.
	 * @param mixed  $manual The typed number.
	 * @param int    $count  How many awards there are.
	 * @return int
	 */
	public static function number( $mode, $manual, $count ) {
		if ( 'manual' === $mode && is_numeric( $manual ) ) {
			return max( 0, (int) $manual );
		}

		return max( 0, (int) $count );
	}

	/**
	 * The line under the number, with {years} and {count} filled in. A range
	 * token with nothing to fill leaves no dangling comma behind it.
	 *
	 * @param mixed  $text  Line text.
	 * @param string $years Year range.
	 * @param int    $count Count.
	 * @return string
	 */
	public static function line( $text, $years, $count ) {
		$text = is_scalar( $text ) ? (string) $text : '';

		if ( '' === $years ) {
			$text = preg_replace( '/[,·\-–]?\s*\{years\}/u', '', $text );
		}

		return trim( str_replace( array( '{years}', '{count}' ), array( $years, (string) $count ), $text ) );
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
