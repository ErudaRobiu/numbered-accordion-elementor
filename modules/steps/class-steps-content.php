<?php
/**
 * Process Steps content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Steps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns settings into the small decisions the markup needs, and holds the one
 * table that says what each figure is made of.
 */
final class Steps_Content {

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
	 * A repeater's rows, or nothing.
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
	 * A step with nothing in it is not a step.
	 *
	 * @param array $step One repeater row.
	 * @return bool
	 */
	public static function step_has_content( $step ) {
		return '' !== self::text( $step, 'title' ) || '' !== self::text( $step, 'body' );
	}

	/**
	 * The steps worth drawing.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public static function visible_steps( $settings ) {
		$out = array();

		foreach ( self::rows( $settings, 'steps' ) as $step ) {
			if ( self::step_has_content( $step ) ) {
				$out[] = $step;
			}
		}

		return $out;
	}

	/**
	 * What a step is numbered.
	 *
	 * Typed wins, so a list can start at 03 or use letters. Left blank it
	 * counts, zero-padded to two digits because 01 beside 10 in a column wants
	 * the same width -- which is the same reason the disc uses tabular
	 * figures.
	 *
	 * @param array $step     One repeater row.
	 * @param int   $position Zero-based position in the list.
	 * @return string
	 */
	public static function number( $step, $position ) {
		$typed = self::text( $step, 'number' );

		if ( '' !== $typed ) {
			return $typed;
		}

		$n = $position + 1;

		return $n < 10 ? '0' . $n : (string) $n;
	}

	/**
	 * The figures on offer, and what each one is made of.
	 *
	 * One table, read by the markup and matched by the stylesheet. The parts
	 * carry their own coordinates rather than having them worked out from an
	 * index in CSS, which is not squeamishness: calc() has no modulo operator,
	 * so the first version's `var(--i) % 2` was invalid, every one of those
	 * declarations was dropped, and five sensors rendered stacked on the same
	 * spot looking like one. It failed silently. Coordinates belong where they
	 * can be read.
	 *
	 * `key` marks the one part per figure that carries the accent. Exactly one,
	 * always: an accent spent on three elements out of five is not an accent.
	 *
	 * @return array<string, array>
	 */
	public static function figures() {
		return array(
			'converge' => array(
				'label' => 'Choosing between options',
				'parts' => array(
					array( 'class' => '', 'vars' => array( 'i' => 0 ) ),
					array( 'class' => '', 'vars' => array( 'i' => 1 ), 'key' => true ),
					array( 'class' => '', 'vars' => array( 'i' => 2 ) ),
				),
			),
			'probe'    => array(
				'label' => 'Measuring a surface',
				'deck'  => true,
				'parts' => array(
					array( 'class' => 'estp__plane' ),
					array( 'class' => 'estp__node', 'vars' => array( 'i' => 0, 'x' => -64, 'y' => -30, 'z' => 14 ) ),
					array( 'class' => 'estp__node', 'vars' => array( 'i' => 1, 'x' => -18, 'y' => 18, 'z' => 30 ), 'key' => true ),
					array( 'class' => 'estp__node', 'vars' => array( 'i' => 2, 'x' => 26, 'y' => -24, 'z' => 18 ) ),
					array( 'class' => 'estp__node', 'vars' => array( 'i' => 3, 'x' => 58, 'y' => 26, 'z' => 26 ) ),
					array( 'class' => 'estp__node', 'vars' => array( 'i' => 4, 'x' => -4, 'y' => -2, 'z' => 20 ) ),
				),
			),
			'datum'    => array(
				'label' => 'A baseline across past readings',
				'parts' => array(
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 0, 'z' => 0, 'h' => 44 ) ),
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 1, 'z' => 12, 'h' => 72 ) ),
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 2, 'z' => 0, 'h' => 36 ) ),
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 3, 'z' => 12, 'h' => 64 ) ),
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 4, 'z' => 0, 'h' => 52 ) ),
					array( 'class' => 'estp__bar', 'vars' => array( 'i' => 5, 'z' => 12, 'h' => 80 ) ),
					array( 'class' => 'estp__level', 'key' => true, 'note' => true ),
				),
			),
			'sheets'   => array(
				'label' => 'A stack, and the one on top',
				'parts' => array(
					array( 'class' => '', 'vars' => array( 'i' => 0 ) ),
					array( 'class' => '', 'vars' => array( 'i' => 1 ) ),
					array( 'class' => '', 'vars' => array( 'i' => 2 ) ),
					array( 'class' => '', 'vars' => array( 'i' => 3 ), 'key' => true ),
				),
			),
			'cycle'    => array(
				'label' => 'Something that keeps running',
				'parts' => array(
					array( 'class' => 'estp__ring', 'vars' => array( 'i' => 0 ) ),
					array( 'class' => 'estp__ring', 'vars' => array( 'i' => 1 ) ),
					array( 'class' => '', 'key' => true ),
				),
			),
		);
	}

	/**
	 * The figure ids, for a select control.
	 *
	 * @return string[]
	 */
	public static function figure_ids() {
		return array_keys( self::figures() );
	}

	/**
	 * One figure's definition, falling back to the first rather than to
	 * nothing: a preset id that no longer exists should leave a drawing in
	 * place, not a hole in the page.
	 *
	 * @param string $id Figure id.
	 * @return array
	 */
	public static function figure( $id ) {
		$all = self::figures();

		if ( isset( $all[ $id ] ) ) {
			return $all[ $id ];
		}

		return reset( $all );
	}

	/**
	 * Which figure a step asked for, as an id that exists.
	 *
	 * @param array $step One repeater row.
	 * @return string
	 */
	public static function figure_id( $step ) {
		$asked = self::text( $step, 'figure' );
		$all   = self::figures();

		if ( isset( $all[ $asked ] ) ) {
			return $asked;
		}

		$ids = array_keys( $all );

		return $ids[0];
	}

	/**
	 * A part's custom properties as an inline style.
	 *
	 * Numbers only, and cast on the way out: these land in a `style`
	 * attribute, and a custom property is one of the few places where a
	 * string from settings could otherwise reach CSS unexamined.
	 *
	 * @param array $part One part from the figure table.
	 * @return string Style attribute value, or an empty string.
	 */
	public static function part_style( $part ) {
		if ( ! is_array( $part ) || empty( $part['vars'] ) || ! is_array( $part['vars'] ) ) {
			return '';
		}

		$bits = array();

		foreach ( $part['vars'] as $name => $value ) {
			if ( ! is_numeric( $value ) ) {
				continue;
			}

			$bits[] = '--' . preg_replace( '/[^a-z]/', '', (string) $name ) . ':' . ( 0 + $value );
		}

		return implode( ';', $bits );
	}

	/**
	 * A part's full class list.
	 *
	 * @param array $part One part from the figure table.
	 * @return string
	 */
	public static function part_class( $part ) {
		$classes = 'estp__part';

		if ( ! empty( $part['class'] ) ) {
			$classes .= ' ' . $part['class'];
		}

		if ( ! empty( $part['key'] ) ) {
			$classes .= ' estp__part--key';
		}

		return $classes;
	}
}
