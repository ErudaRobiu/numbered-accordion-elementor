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
	 * Inline style for one drifting mote.
	 *
	 * The motes are decoration over the flow artwork. Their positions are
	 * spread deterministically rather than randomly so that a page looks the
	 * same on every load, and so this can be asserted.
	 *
	 * @param int $index Zero-based mote index.
	 * @param int $total How many motes in total.
	 * @return string
	 */
	public static function mote_style( $index, $total ) {
		$index = max( 0, (int) $index );
		$total = max( 1, (int) $total );

		$tops      = array( 26, 41, 57, 72, 34, 63, 48 );
		$durations = array( 7.5, 9.0, 8.2, 10.0, 11.0, 8.8, 9.6 );

		$top      = $tops[ $index % count( $tops ) ];
		$duration = $durations[ $index % count( $durations ) ];
		$delay    = round( ( $index * 1.45 ), 2 );

		return sprintf(
			'top:%d%%;animation-duration:%ss;animation-delay:%ss',
			$top,
			self::trim_zeros( $duration ),
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
