<?php
/**
 * Split Explainer content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Explainer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns settings into the small decisions the markup needs.
 */
final class Explainer_Content {

	/**
	 * Is there a step number to show?
	 *
	 * An empty control -- which is the default -- hides the number and closes
	 * the gap the label would otherwise sit after. Whitespace counts as empty,
	 * because a space typed into the field is not a number anybody meant.
	 *
	 * @param mixed $value Raw control value.
	 * @return bool
	 */
	public static function has_step( $value ) {
		return '' !== self::step_text( $value );
	}

	/**
	 * The step number, trimmed.
	 *
	 * @param mixed $value Raw control value.
	 * @return string
	 */
	public static function step_text( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return trim( (string) $value );
	}

	/**
	 * Classes for a step line, so an empty number closes its own gap.
	 *
	 * @param mixed $value Raw control value.
	 * @return string
	 */
	public static function step_classes( $value ) {
		$classes = 'eexp-step';

		if ( ! self::has_step( $value ) ) {
			$classes .= ' eexp-step--bare';
		}

		return $classes;
	}

	/**
	 * A setting as a trimmed string.
	 *
	 * @param mixed  $settings Settings array.
	 * @param string $key      Key.
	 * @return string
	 */
	public static function text( $settings, $key ) {
		if ( ! is_array( $settings ) || ! isset( $settings[ $key ] ) || ! is_scalar( $settings[ $key ] ) ) {
			return '';
		}

		return trim( (string) $settings[ $key ] );
	}

	/**
	 * A repeater control as a list of rows.
	 *
	 * @param mixed  $settings Settings array.
	 * @param string $key      Key.
	 * @return array
	 */
	public static function rows( $settings, $key ) {
		if ( ! is_array( $settings ) || ! isset( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
			return array();
		}

		return $settings[ $key ];
	}

	/**
	 * Split a heading into word spans, ready for the fill to arrive.
	 *
	 * Authored here rather than by splitting innerHTML in the browser: the
	 * trademark sign is its own element, and a naive split eats it.
	 *
	 * Each returned string is already escaped and safe to echo.
	 *
	 * @param mixed $text Heading text.
	 * @return string[] One span per word.
	 */
	public static function heading_words( $text ) {
		if ( ! is_scalar( $text ) ) {
			return array();
		}

		$words = preg_split( '/\s+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );

		if ( ! is_array( $words ) ) {
			return array();
		}

		$spans = array();

		foreach ( $words as $word ) {
			$escaped = function_exists( 'esc_html' )
				? esc_html( $word )
				: htmlspecialchars( $word, ENT_QUOTES, 'UTF-8' );

			$spans[] = '<span>' . preg_replace(
				'/(\x{2122}|\x{00AE})/u',
				'<i class="eexp-tm">$1</i>',
				$escaped
			) . '</span>';
		}

		return $spans;
	}

	/**
	 * Classes for a panel heading, given how it should be filled.
	 *
	 * @param mixed $fill Raw control value.
	 * @return string
	 */
	public static function title_classes( $fill ) {
		$fill    = is_scalar( $fill ) ? (string) $fill : '';
		$classes = 'eexp-heading eexp-panel__title';

		if ( 'gradient' === $fill ) {
			return $classes . ' eexp-panel__title--gradient';
		}

		if ( 'reveal' === $fill ) {
			return $classes . ' eexp-panel__title--reveal eexp-anim';
		}

		return $classes;
	}

	/**
	 * Classes for the footer pill row.
	 *
	 * @param mixed $style Raw control value.
	 * @return string
	 */
	public static function pill_classes( $style ) {
		$style = is_scalar( $style ) ? (string) $style : '';
		$known = array( 'solid', 'glass', 'plain' );

		if ( in_array( $style, $known, true ) ) {
			return 'eexp-loads eexp-loads--' . $style;
		}

		// "dot" is the default and needs no modifier of its own.
		return 'eexp-loads';
	}

	/**
	 * Inline style for one drifting mote.
	 *
	 * Every mote gets its own size, speed, brightness, height and wander. The
	 * spread is deterministic rather than random, so a page looks the same on
	 * every load and this can be asserted -- and the seven sets of values are
	 * chosen to be mutually awkward, so motes do not fall into step with one
	 * another however long they run.
	 *
	 * @param int $index Zero-based mote index.
	 * @param int $total How many motes in total.
	 * @return string
	 */
	public static function mote_style( $index, $total ) {
		$index = max( 0, (int) $index );
		$total = max( 1, (int) $total );

		// top %, size px, seconds, peak opacity, wander px.
		$layers = array(
			array( 26, 8.0, 7.5, 0.92, 14 ),
			array( 41, 4.5, 11.0, 0.5, 9 ),
			array( 57, 6.5, 8.8, 0.78, 18 ),
			array( 72, 9.5, 6.4, 1.0, 11 ),
			array( 34, 5.0, 12.5, 0.55, 21 ),
			array( 63, 7.0, 9.6, 0.82, 7 ),
			array( 48, 3.5, 13.5, 0.45, 15 ),
		);

		$layer = $layers[ $index % count( $layers ) ];

		// Spread the starts across the whole crossing, so the plate is never
		// empty and never shows the whole set entering together.
		$delay = -1 * ( ( $index * $layer[2] ) / max( 2, $total ) );

		return sprintf(
			'top:%d%%;--eexp-mote-size:%spx;--eexp-mote-dur:%ss;--eexp-mote-peak:%s;--eexp-mote-wander:%spx;animation-delay:%ss',
			$layer[0],
			self::trim_zeros( $layer[1] ),
			self::trim_zeros( $layer[2] ),
			self::trim_zeros( $layer[3] ),
			self::trim_zeros( $layer[4] ),
			self::trim_zeros( $delay )
		);
	}

	/**
	 * Format a float without trailing zeros, in a locale-proof way.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	private static function trim_zeros( $number ) {
		$formatted = number_format( (float) $number, 2, '.', '' );
		$formatted = rtrim( rtrim( $formatted, '0' ), '.' );

		return '' === $formatted ? '0' : $formatted;
	}

	/**
	 * How many motes to draw.
	 *
	 * @param mixed $value Raw control value.
	 * @return int
	 */
	public static function mote_count( $value ) {
		$count = is_scalar( $value ) ? (int) $value : 0;

		return max( 0, min( 7, $count ) );
	}

	/**
	 * The sizes hint for a panel image.
	 *
	 * The slab is two columns from 1201px up and one below it, so the image is
	 * about half the container on a desktop and the full width on a phone.
	 *
	 * @return string
	 */
	public static function panel_sizes_attr() {
		return '(max-width: 767px) 92vw, (max-width: 1200px) 88vw, 44vw';
	}
}
