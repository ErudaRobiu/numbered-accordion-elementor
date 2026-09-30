<?php
/**
 * Logo elements content helpers.
 *
 * The pure logic behind Element List and Annotated Mark, kept out of the
 * widgets so it can be tested without WordPress or Elementor.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The four elements of the ThermStar mark, their icons, and how the two
 * repeaters become rows and tags.
 */
final class Elements_Content {

	/**
	 * The mark's own file, relative to the plugin root.
	 */
	const LOGO = 'modules/elements/assets/img/thermstar-mark.webp';

	/**
	 * The link group both widgets join out of the box.
	 */
	const GROUP = 'logo-elements';

	/**
	 * The four icons from the wireframe, as SVG.
	 *
	 * @return array<string, string>
	 */
	public static function icons() {
		return array(
			'wind'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 9h11a3 3 0 1 0-3-3M3 14h15a3 3 0 1 1-3 3M3 19h8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
			'flame'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2c1 3 5 5 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2-4.5 0 2 1 3 2 3 0-3-1-5 1-8.5z" fill="currentColor"/></svg>',
			'waves'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 7c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0M3 12c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0M3 17c2-1.5 4-1.5 6 0s4 1.5 6 0 4-1.5 6 0" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
			'sprout' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 21v-8M12 13C12 8 8 6 3 6c0 5 4 7 9 7zM12 11c0-4 3-7 9-7 0 5-4 7-9 7z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		);
	}

	/**
	 * The four elements, verbatim from the old thermstar.com About page, with
	 * where each one points on the mark. Dot positions are in percent of the
	 * 560×520 stage the wireframe drew them on; a tag's height is where its
	 * middle sits.
	 *
	 * @return array<int, array>
	 */
	public static function default_items() {
		return array(
			array(
				'key'    => 'air',
				'icon'   => 'wind',
				'colour' => '#7FC4D6',
				'name'   => 'Air',
				'sub'    => 'Harmony in Motion',
				'text'   => 'The three elegant waves at the base symbolize air. Controlled, balanced, and essential, it reflects the quiet precision of airflow within the system.',
				'corner' => 'bl',
				'tag_y'  => 82.7,
				'dot_x'  => 43.9,
				'dot_y'  => 67.3,
			),
			array(
				'key'    => 'fire',
				'icon'   => 'flame',
				'colour' => '#E8823F',
				'name'   => 'Fire',
				'sub'    => 'Reclaiming Clean Heat',
				'text'   => 'At the center burns a green flame shaped like a leaf. This represents the heart of ThermStar: recovering waste heat and transforming it into reusable heat energy.',
				'corner' => 'tl',
				'tag_y'  => 21.2,
				'dot_x'  => 42.1,
				'dot_y'  => 39.6,
			),
			array(
				'key'    => 'water',
				'icon'   => 'waves',
				'colour' => '#3C8E9C',
				'name'   => 'Water',
				'sub'    => 'Closed-Loop Circulation',
				'text'   => 'The circular arrow represents water, our brine circuit. Like nature, it flows continuously, ensuring thermal efficiency and sustainability.',
				'corner' => 'tr',
				'tag_y'  => 13.5,
				'dot_x'  => 61.8,
				'dot_y'  => 28.1,
			),
			array(
				'key'    => 'earth',
				'icon'   => 'sprout',
				'colour' => '#3E8E4F',
				'name'   => 'Earth',
				'sub'    => 'Restorative and Renewable',
				'text'   => 'Hidden within the flame is a leaf, our nod to the Earth. It reflects our commitment to cleaner, greener energy solutions rooted in sustainability.',
				'corner' => 'br',
				'tag_y'  => 82.7,
				'dot_x'  => 51.4,
				'dot_y'  => 48.8,
			),
		);
	}

	/**
	 * A key or group name: lowercase letters, digits and hyphens.
	 *
	 * @param mixed  $text     Text.
	 * @param string $fallback When nothing survives.
	 * @return string
	 */
	public static function key( $text, $fallback = '' ) {
		$text = is_scalar( $text ) ? strtolower( trim( (string) $text ) ) : '';
		$text = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $text ), '-' );

		return '' === $text ? $fallback : $text;
	}

	/**
	 * A colour for an inline style: a hex value, or nothing.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function colour( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		return preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ? $value : '';
	}

	/**
	 * A percentage, clamped to the stage.
	 *
	 * @param mixed $value    Slider array or number.
	 * @param float $fallback When unreadable.
	 * @return float
	 */
	public static function percent( $value, $fallback ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		if ( ! is_numeric( $value ) ) {
			return (float) $fallback;
		}

		return round( max( 0.0, min( 100.0, (float) $value ) ), 2 );
	}

	/**
	 * The repeater as items both widgets can print. Keys are made unique, as
	 * two items answering to one key would light up together.
	 *
	 * @param mixed $items Repeater value.
	 * @return array
	 */
	public static function build( $items ) {
		$out   = array();
		$taken = array();

		foreach ( is_array( $items ) ? $items : array() as $i => $item ) {
			$item = is_array( $item ) ? $item : array();
			$name = self::text( $item, 'name' );

			if ( '' === $name ) {
				continue;
			}

			$key  = self::key( self::text( $item, 'key' ), self::key( $name, 'item-' . ( $i + 1 ) ) );
			$base = $key;
			$n    = 2;

			while ( isset( $taken[ $key ] ) ) {
				$key = $base . '-' . $n++;
			}

			$taken[ $key ] = true;
			$corner        = self::text( $item, 'corner' );
			$icon          = self::text( $item, 'icon' );

			$out[] = array(
				'key'    => $key,
				'icon'   => isset( self::icons()[ $icon ] ) ? $icon : '',
				'custom' => isset( $item['icon_custom'] ) && is_array( $item['icon_custom'] ) && ! empty( $item['icon_custom']['value'] ) ? $item['icon_custom'] : null,
				'colour' => self::colour( isset( $item['colour'] ) ? $item['colour'] : '' ),
				'name'   => $name,
				'sub'    => self::text( $item, 'sub' ),
				'text'   => self::text( $item, 'text' ),
				'corner' => in_array( $corner, array( 'tl', 'tr', 'bl', 'br' ), true ) ? $corner : 'tl',
				'tag_y'  => self::percent( isset( $item['tag_y'] ) ? $item['tag_y'] : null, 'b' === substr( $corner, 0, 1 ) ? 82.7 : 17.3 ),
				'dot_x'  => self::percent( isset( $item['dot_x'] ) ? $item['dot_x'] : null, 50 ),
				'dot_y'  => self::percent( isset( $item['dot_y'] ) ? $item['dot_y'] : null, 50 ),
			);
		}

		return $out;
	}

	/**
	 * A first guess at a leader line, in stage percent, drawn before the
	 * script measures the real tag: from the tag's inner edge, straight in
	 * by an elbow's width, then to the dot.
	 *
	 * @param array $item Built item.
	 * @return string An SVG path in a 0-100 viewBox.
	 */
	public static function guess_path( $item ) {
		$left  = 'l' === substr( $item['corner'], 1, 1 );
		$edge  = $left ? 16.0 : 84.0;
		$elbow = $left ? $edge + 14.3 : $edge - 14.3;

		return sprintf( 'M%s %s L%s %s L%s %s', $edge, $item['tag_y'], $elbow, $item['tag_y'], $item['dot_x'], $item['dot_y'] );
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
