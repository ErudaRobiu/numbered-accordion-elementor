<?php
/**
 * Company Chain content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Chain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page 28 chain, and how the repeater becomes cards.
 */
final class Chain_Content {

	/**
	 * Enjay makes it, Norrel distributes it, your facility is served. From
	 * the old About page and the content doc, verbatim.
	 *
	 * @return array<int, array{label: string, name: string, sub: string, highlight: bool}>
	 */
	public static function default_items() {
		return array(
			array(
				'label'     => 'Develops & makes',
				'name'      => 'Enjay Systems',
				'sub'       => 'Lepido® · Sweden',
				'highlight' => false,
			),
			array(
				'label'     => 'Exclusive North American distributor',
				'name'      => 'Norrel Inc.',
				'sub'       => 'DBA ThermStar System',
				'highlight' => true,
			),
			array(
				'label'     => 'Serves',
				'name'      => 'Your facility',
				'sub'       => 'United States & Canada',
				'highlight' => false,
			),
		);
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

			$logo = isset( $item['logo'] ) && is_array( $item['logo'] ) ? $item['logo'] : array();
			$link = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();

			$out[] = array(
				'label'     => self::text( $item, 'label' ),
				'name'      => $name,
				'sub'       => self::text( $item, 'sub' ),
				'highlight' => 'yes' === self::text( $item, 'highlight' ),
				'logo'      => isset( $logo['url'] ) && is_string( $logo['url'] ) ? trim( $logo['url'] ) : '',
				'link'      => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
				'external'  => ! empty( $link['is_external'] ),
				'nofollow'  => ! empty( $link['nofollow'] ),
			);
		}

		return $out;
	}

	/**
	 * The row's columns: each card a share, the highlighted one a little
	 * wider, with an arrow's width between each pair.
	 *
	 * @param array $cards Built cards.
	 * @return string A grid-template-columns value.
	 */
	public static function columns( $cards ) {
		$cols = array();

		foreach ( array_values( (array) $cards ) as $i => $card ) {
			if ( $i > 0 ) {
				$cols[] = 'auto';
			}

			$cols[] = $card['highlight'] ? 'minmax(0, 1.15fr)' : 'minmax(0, 1fr)';
		}

		return implode( ' ', $cols );
	}

	/**
	 * The container width below which the cards stack, clamped.
	 *
	 * @param mixed $value Slider value or number.
	 * @return int 240 to 1200; 520 when unreadable.
	 */
	public static function stack_at( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		$px = is_numeric( $value ) ? (int) $value : 520;

		return max( 240, min( 1200, $px ) );
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
