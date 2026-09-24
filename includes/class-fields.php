<?php
/**
 * Declared-field reading and sanitising.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a module's field declarations into usable values.
 *
 * Kept free of WordPress so it can be tested without it, and so the settings
 * screen has one place to trust rather than a sanitiser per module. A module
 * says what its options are; this decides what a stored or submitted value is
 * allowed to become.
 *
 * Every path returns something usable. A missing, corrupt or hostile value
 * falls back to the declared default rather than to null, because the caller
 * is about to render a curtain across somebody's site with it.
 */
final class Fields {

	/**
	 * Read stored values against declarations.
	 *
	 * @param array<int, array<string, mixed>> $declarations Field declarations.
	 * @param mixed                            $stored       Whatever was in the option.
	 * @return array<string, mixed> Value per field id.
	 */
	public static function values( $declarations, $stored ) {
		$stored = is_array( $stored ) ? $stored : array();
		$values = array();

		foreach ( self::valid( $declarations ) as $field ) {
			$id             = $field['id'];
			$values[ $id ]  = array_key_exists( $id, $stored )
				? self::cast( $field, $stored[ $id ] )
				: $field['default'];
		}

		return $values;
	}

	/**
	 * Sanitise a submitted set.
	 *
	 * Rebuilt from the declarations rather than from the submitted keys, the
	 * same way the module switches are: an unchecked checkbox is absent from
	 * the POST body entirely, and anything not declared is not ours.
	 *
	 * @param array<int, array<string, mixed>> $declarations Field declarations.
	 * @param mixed                            $submitted    Raw submitted value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $declarations, $submitted ) {
		$submitted = is_array( $submitted ) ? $submitted : array();
		$clean     = array();

		foreach ( self::valid( $declarations ) as $field ) {
			$id = $field['id'];

			if ( 'checkbox' === $field['type'] ) {
				$clean[ $id ] = ! empty( $submitted[ $id ] );
				continue;
			}

			$clean[ $id ] = array_key_exists( $id, $submitted )
				? self::cast( $field, $submitted[ $id ] )
				: $field['default'];
		}

		return $clean;
	}

	/**
	 * Coerce one value to its declared type.
	 *
	 * @param array<string, mixed> $field Declaration.
	 * @param mixed                $value Raw value.
	 * @return mixed
	 */
	private static function cast( $field, $value ) {
		switch ( $field['type'] ) {
			case 'color':
				$color = self::color( $value );
				return null === $color ? $field['default'] : $color;

			case 'number':
				if ( is_bool( $value ) || ! is_numeric( $value ) ) {
					return $field['default'];
				}

				$number = (float) $value;

				if ( isset( $field['min'] ) && $number < $field['min'] ) {
					$number = (float) $field['min'];
				}

				if ( isset( $field['max'] ) && $number > $field['max'] ) {
					$number = (float) $field['max'];
				}

				// A whole-numbered step means the field is counting things, so
				// hand back an int and let a caller compare it without
				// surprises. Compared as a float on purpose: casting the
				// remainder to int would read 0.01 as zero and quietly round
				// every fractional field to whole numbers.
				if ( isset( $field['step'] ) && abs( fmod( (float) $field['step'], 1.0 ) ) < 0.000001 ) {
					return (int) round( $number );
				}

				return $number;

			case 'media':
				// An attachment id. Zero means nothing chosen, which is a
				// valid state, so it is the floor rather than an error.
				if ( is_bool( $value ) || ! is_numeric( $value ) ) {
					return $field['default'];
				}

				return max( 0, (int) $value );

			case 'checkbox':
			default:
				return ! empty( $value );
		}
	}

	/**
	 * Normalise a CSS hex colour.
	 *
	 * Shorthand is expanded so callers never have to handle both forms, and
	 * anything that is not a plain hex colour is refused outright: this value
	 * is printed into a style attribute.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null Lowercased #rrggbb, or null when unusable.
	 */
	public static function color( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = strtolower( trim( $value ) );

		if ( 1 === preg_match( '/^#([0-9a-f]{3})$/', $value, $short ) ) {
			$chars = str_split( $short[1] );
			return '#' . $chars[0] . $chars[0] . $chars[1] . $chars[1] . $chars[2] . $chars[2];
		}

		if ( 1 === preg_match( '/^#([0-9a-f]{6})$/', $value ) ) {
			return $value;
		}

		return null;
	}

	/**
	 * Drop declarations that are missing what every field needs.
	 *
	 * A module with a malformed declaration loses that one field rather than
	 * taking the settings screen down with it.
	 *
	 * @param mixed $declarations Declarations.
	 * @return array<int, array<string, mixed>>
	 */
	public static function valid( $declarations ) {
		if ( ! is_array( $declarations ) ) {
			return array();
		}

		$valid = array();

		foreach ( $declarations as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( empty( $field['id'] ) || ! is_string( $field['id'] ) ) {
				continue;
			}

			if ( ! array_key_exists( 'default', $field ) ) {
				continue;
			}

			if ( empty( $field['type'] ) || ! in_array( $field['type'], array( 'color', 'number', 'checkbox', 'media' ), true ) ) {
				continue;
			}

			$valid[] = $field;
		}

		return $valid;
	}
}
