<?php
/**
 * Journey Timeline content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Journey;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page 28 milestones, and how the repeater becomes rows.
 */
final class Journey_Content {

	/**
	 * The three milestones, verbatim from the content doc (page 28) and
	 * Scott Preston's answers. The last is where the story is now.
	 *
	 * @return array<int, array{year: string, title: string, text: string, current: bool}>
	 */
	public static function default_items() {
		return array(
			array(
				'year'    => '2016',
				'title'   => 'Invented in Sweden',
				'text'    => 'Built for restaurant and commercial kitchen exhaust, where grease and moisture beat conventional exchangers.',
				'current' => false,
			),
			array(
				'year'    => '2021',
				'title'   => 'Into industry',
				'text'    => 'Food production, industrial laundry, foundries and other high-fouling processes.',
				'current' => false,
			),
			array(
				'year'    => '2025',
				'title'   => 'North America',
				'text'    => 'Norrel Inc. launches the ThermStar System for the United States and Canada.',
				'current' => true,
			),
		);
	}

	/**
	 * Turn the repeater into rows. A row needs a year or a title to show;
	 * anything else is left out.
	 *
	 * @param mixed $items Repeater value.
	 * @return array<int, array{year: string, title: string, text: string, current: bool, link: string, external: bool, nofollow: bool}>
	 */
	public static function build( $items ) {
		$out = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item  = is_array( $item ) ? $item : array();
			$year  = self::text( $item, 'year' );
			$title = self::text( $item, 'title' );

			if ( '' === $year && '' === $title ) {
				continue;
			}

			$link = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();

			$out[] = array(
				'year'     => $year,
				'title'    => $title,
				'text'     => self::text( $item, 'text' ),
				'current'  => 'yes' === self::text( $item, 'current' ),
				'link'     => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
				'external' => ! empty( $link['is_external'] ),
				'nofollow' => ! empty( $link['nofollow'] ),
			);
		}

		return $out;
	}

	/**
	 * The line's draw time, in milliseconds, clamped to something that still
	 * reads as a line being drawn.
	 *
	 * @param mixed $value Slider value or number.
	 * @return int 300 to 4000; 1200 when unreadable.
	 */
	public static function duration( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		$ms = is_numeric( $value ) ? (int) $value : 1200;

		return max( 300, min( 4000, $ms ) );
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
