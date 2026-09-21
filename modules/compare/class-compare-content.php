<?php
/**
 * Image Compare content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Compare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns settings into the numbers the markup needs.
 */
final class Compare_Content {

	/**
	 * Where the divider starts, as a percentage.
	 *
	 * Anything unreadable lands on the middle, which is the one position that
	 * is never wrong.
	 *
	 * @param mixed $value Raw control value, or a slider array.
	 * @return float
	 */
	public static function clamp_position( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		if ( ! is_numeric( $value ) ) {
			return 50.0;
		}

		$value = (float) $value;

		if ( $value < 0 ) {
			return 0.0;
		}

		if ( $value > 100 ) {
			return 100.0;
		}

		return $value;
	}

	/**
	 * Is this the up-and-down one?
	 *
	 * @param mixed $value Raw control value.
	 * @return bool
	 */
	public static function is_vertical( $value ) {
		return 'vertical' === ( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * The clip that hides the part of the second picture not yet revealed.
	 *
	 * Horizontal reveals from the left, so the second picture is cut away on
	 * its left edge; vertical reveals from the top.
	 *
	 * @param mixed $position    Raw position.
	 * @param mixed $orientation Raw orientation.
	 * @return string
	 */
	public static function clip_inset( $position, $orientation ) {
		$pos = self::format( self::clamp_position( $position ) );

		return self::is_vertical( $orientation )
			? sprintf( 'inset(%s%% 0 0 0)', $pos )
			: sprintf( 'inset(0 0 0 %s%%)', $pos );
	}

	/**
	 * The custom properties the frame carries.
	 *
	 * @param mixed $position    Raw position.
	 * @param mixed $orientation Raw orientation.
	 * @return string
	 */
	public static function frame_style( $position, $orientation ) {
		/*
		 * --ecmp-n is the same number without its unit. CSS cannot mix a
		 * percentage with plain numbers inside clamp(), so the label fade --
		 * which is arithmetic on the position -- needs a unitless copy or the
		 * whole declaration is thrown away.
		 */
		$pos = self::format( self::clamp_position( $position ) );

		return sprintf(
			'--ecmp-pos:%s%%;--ecmp-n:%s;--ecmp-clip:%s',
			$pos,
			$pos,
			self::clip_inset( $position, $orientation )
		);
	}

	/**
	 * Classes for the root.
	 *
	 * @param mixed $orientation Raw orientation.
	 * @param mixed $move_on     Raw interaction mode.
	 * @return string
	 */
	public static function root_classes( $orientation, $move_on ) {
		$classes = 'ecmp';

		if ( self::is_vertical( $orientation ) ) {
			$classes .= ' ecmp--vertical';
		}

		if ( 'hover' === ( is_scalar( $move_on ) ? (string) $move_on : '' ) ) {
			$classes .= ' ecmp--hover';
		}

		return $classes;
	}

	/**
	 * Format a number for CSS without a trailing zero or a comma.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	private static function format( $number ) {
		$formatted = number_format( (float) $number, 2, '.', '' );
		$formatted = rtrim( rtrim( $formatted, '0' ), '.' );

		return '' === $formatted ? '0' : $formatted;
	}

	/**
	 * What the range input announces to a screen reader.
	 *
	 * @param mixed $before Label on the first picture.
	 * @param mixed $after  Label on the second.
	 * @return string
	 */
	public static function slider_label( $before, $after ) {
		$before = is_scalar( $before ) ? trim( (string) $before ) : '';
		$after  = is_scalar( $after ) ? trim( (string) $after ) : '';

		if ( '' !== $before && '' !== $after ) {
			return sprintf(
				/* translators: 1: label on the first picture, 2: label on the second */
				__( 'Reveal %1$s or %2$s', 'numbered-accordion' ),
				$before,
				$after
			);
		}

		return __( 'Compare the two pictures', 'numbered-accordion' );
	}
}
