<?php
/**
 * Data Table content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns settings into the small decisions the markup needs.
 */
final class Table_Content {

	/**
	 * How many columns a table may have.
	 *
	 * Three is the shape this was drawn for -- a thing, what it means, and
	 * where it came from -- and it is as many as a phone can stack before the
	 * rows stop reading as rows.
	 */
	const MAX_COLUMNS = 3;

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
	 * The column headings, trimmed, with the empty ones dropped from the end.
	 *
	 * A table is two or three columns depending on whether anybody filled the
	 * third heading in, and that decision is made once here rather than three
	 * times in the markup. Only trailing blanks are dropped: a middle column
	 * left empty is a gap somebody meant to come back to, and silently closing
	 * it would move their data into the wrong heading.
	 *
	 * @param array $settings Widget settings.
	 * @return string[] Headings, in order.
	 */
	public static function headings( $settings ) {
		$out = array();

		for ( $i = 1; $i <= self::MAX_COLUMNS; $i++ ) {
			$out[] = self::text( $settings, 'head_' . $i );
		}

		while ( $out && '' === end( $out ) ) {
			array_pop( $out );
		}

		return array_values( $out );
	}

	/**
	 * How many columns to draw.
	 *
	 * The headings decide it, but a table with the header row switched off has
	 * no headings to decide with -- so the widest row wins instead. Never less
	 * than one, or the markup would emit a table with no columns in it.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	public static function column_count( $settings ) {
		$count = count( self::headings( $settings ) );

		if ( $count > 0 ) {
			return $count;
		}

		foreach ( self::rows( $settings, 'rows' ) as $row ) {
			$count = max( $count, count( self::cells( $row, self::MAX_COLUMNS ) ) );
		}

		return max( 1, $count );
	}

	/**
	 * One row's cells, padded or trimmed to the column count.
	 *
	 * Elementor keeps every field a repeater has ever had, so a row saved when
	 * the table was three columns wide still carries its third cell after the
	 * table has been cut to two. Fitting the row to the table here is what
	 * stops that ghost cell reappearing as a fourth column in the markup.
	 *
	 * With no count given it trims trailing blanks instead, which is how the
	 * column count works out how wide the widest row is.
	 *
	 * @param array    $row     One repeater row.
	 * @param int|null $columns How many cells to return, or null to trim.
	 * @return string[]
	 */
	public static function cells( $row, $columns = null ) {
		$out = array();

		for ( $i = 1; $i <= self::MAX_COLUMNS; $i++ ) {
			$out[] = self::text( $row, 'cell_' . $i );
		}

		if ( null === $columns ) {
			while ( $out && '' === end( $out ) ) {
				array_pop( $out );
			}

			return array_values( $out );
		}

		$columns = max( 0, min( self::MAX_COLUMNS, (int) $columns ) );

		return array_slice( array_pad( array_slice( $out, 0, $columns ), $columns, '' ), 0, $columns );
	}

	/**
	 * Is there anything in this row at all?
	 *
	 * An empty row added and then left alone should not draw a blank stripe
	 * across the table.
	 *
	 * @param array $row One repeater row.
	 * @return bool
	 */
	public static function row_has_content( $row ) {
		foreach ( self::cells( $row ) as $cell ) {
			if ( '' !== $cell ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The rows worth drawing.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public static function visible_rows( $settings ) {
		$out = array();

		foreach ( self::rows( $settings, 'rows' ) as $row ) {
			if ( self::row_has_content( $row ) ) {
				$out[] = $row;
			}
		}

		return $out;
	}

	/**
	 * The label a stacked cell carries on a phone.
	 *
	 * Below the breakpoint the columns become a list, and a value with no
	 * heading beside it is a fact with nothing to attach it to. The heading is
	 * written into the cell as a data attribute and drawn by CSS, so the label
	 * exists only where it is needed and is never read out twice on a desktop.
	 *
	 * Empty when there is no heading to use, which is what the header-row
	 * switch leaves behind.
	 *
	 * @param string[] $headings Column headings.
	 * @param int      $index    Zero-based column index.
	 * @return string
	 */
	public static function cell_label( $headings, $index ) {
		if ( ! is_array( $headings ) || ! isset( $headings[ $index ] ) || ! is_scalar( $headings[ $index ] ) ) {
			return '';
		}

		return trim( (string) $headings[ $index ] );
	}

	/**
	 * A grid template for the table's columns.
	 *
	 * Whatever was typed, if it was typed; otherwise a default that gives the
	 * first two columns the room and lets the last one -- which in practice
	 * holds a short source or status -- take what is left.
	 *
	 * @param array $settings Widget settings.
	 * @param int   $columns  Column count.
	 * @return string
	 */
	public static function template( $settings, $columns ) {
		$typed = self::text( $settings, 'columns' );

		if ( '' !== $typed ) {
			return $typed;
		}

		if ( $columns >= 3 ) {
			return 'minmax(0, 1.05fr) minmax(0, 1.15fr) minmax(0, 0.8fr)';
		}

		if ( 2 === $columns ) {
			return 'minmax(0, 1fr) minmax(0, 1fr)';
		}

		return 'minmax(0, 1fr)';
	}
}
