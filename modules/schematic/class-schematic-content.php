<?php
/**
 * Flow Schematic content helpers.
 *
 * The column model, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * The diagram is one grid whose columns alternate node, connector, node. Every
 * part of the drawing -- the return path under the middle, the monitoring bar
 * over it, the arrow into the delivery group -- is placed by naming the
 * columns it spans, so nothing in the stylesheet or the markup ever needs a
 * measured coordinate.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Schematic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Works out where each piece of the diagram sits.
 */
final class Schematic_Content {

	/**
	 * Most stages a diagram may carry.
	 *
	 * Past five the row stops being readable at any width worth designing
	 * for, and the stacked layout is the better answer anyway.
	 */
	const MAX_STAGES = 5;

	/**
	 * How many stages there actually are, whatever the repeater holds.
	 *
	 * @param mixed $stages Repeater value.
	 * @return int
	 */
	public static function stage_count( $stages ) {
		$count = is_array( $stages ) ? count( $stages ) : 0;

		return min( $count, self::MAX_STAGES );
	}

	/**
	 * The column a stage sits in, counting stages from one.
	 *
	 * @param int  $index Stage number, one-based.
	 * @param bool $input Is there a source block on the left?
	 * @return int
	 */
	public static function stage_column( $index, $input ) {
		$base = $input ? 3 : 1;

		return $base + ( max( 1, (int) $index ) - 1 ) * 2;
	}

	/**
	 * The column of the connector leaving a stage.
	 *
	 * @param int  $index Stage number, one-based.
	 * @param bool $input Is there a source block on the left?
	 * @return int
	 */
	public static function link_column( $index, $input ) {
		return self::stage_column( $index, $input ) + 1;
	}

	/**
	 * The column the delivery-target group sits in.
	 *
	 * @param int  $stages Number of stages.
	 * @param bool $input  Is there a source block on the left?
	 * @return int
	 */
	public static function outputs_column( $stages, $input ) {
		return self::stage_column( max( 1, (int) $stages ), $input ) + 2;
	}

	/**
	 * How many columns the grid has in total.
	 *
	 * @param int  $stages  Number of stages.
	 * @param bool $input   Source block on the left?
	 * @param bool $outputs Delivery group on the right?
	 * @return int
	 */
	public static function total_columns( $stages, $input, $outputs ) {
		$stages = max( 1, (int) $stages );
		$total  = ( 2 * $stages ) - 1;

		if ( $input ) {
			$total += 2;
		}

		if ( $outputs ) {
			$total += 2;
		}

		return $total;
	}

	/**
	 * The grid-template-columns value for the whole diagram.
	 *
	 * Stages get a floor in pixels so a long label cannot squeeze the node
	 * into a sliver; connectors are free to take what is left, which is what
	 * keeps the drawing feeling airy rather than packed.
	 *
	 * @param int   $stages  Number of stages.
	 * @param bool  $input   Source block on the left?
	 * @param bool  $outputs Delivery group on the right?
	 * @param float $spread  Relative width of the runs between stages.
	 * @return string
	 */
	public static function columns( $stages, $input, $outputs, $spread = 1.4 ) {
		$stages = max( 1, min( (int) $stages, self::MAX_STAGES ) );
		$spread = self::clamp_spread( $spread );
		$tracks = array();

		if ( $input ) {
			$tracks[] = 'minmax(110px, 0.85fr)';
			$tracks[] = '0.9fr';
		}

		for ( $i = 1; $i <= $stages; $i++ ) {
			$tracks[] = 'minmax(150px, 1.05fr)';

			if ( $i < $stages ) {
				$tracks[] = self::number( $spread ) . 'fr';
			}
		}

		if ( $outputs ) {
			$tracks[] = '0.8fr';
			$tracks[] = 'minmax(130px, 0.9fr)';
		}

		return implode( ' ', $tracks );
	}

	/**
	 * Keep the run between stages inside the range that still reads.
	 *
	 * @param mixed $value Raw control value, or a slider array.
	 * @return float
	 */
	public static function clamp_spread( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		if ( ! is_numeric( $value ) ) {
			return 1.4;
		}

		$value = (float) $value;

		if ( $value < 0.4 ) {
			return 0.4;
		}

		if ( $value > 4.0 ) {
			return 4.0;
		}

		return $value;
	}

	/**
	 * Where the boundary stands, as a percentage across the diagram.
	 *
	 * Anything unreadable lands on the middle, which is the one position that
	 * is never wrong.
	 *
	 * @param mixed $value Raw control value, or a slider array.
	 * @return float
	 */
	public static function clamp_percent( $value ) {
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
	 * The span a band covers, given the first and last stage it touches.
	 *
	 * Both the return path and the monitoring bar answer this question, and
	 * both have to survive a client typing the numbers in backwards or naming
	 * a stage that is not there.
	 *
	 * @param mixed $from   First stage, one-based.
	 * @param mixed $to     Last stage, one-based.
	 * @param int   $stages How many stages exist.
	 * @param bool  $input  Source block on the left?
	 * @return array{0:int,1:int} Grid column start and end lines.
	 */
	public static function span( $from, $to, $stages, $input ) {
		$stages = max( 1, min( (int) $stages, self::MAX_STAGES ) );

		$from = is_numeric( $from ) ? (int) $from : 1;
		$to   = is_numeric( $to ) ? (int) $to : $stages;

		$from = max( 1, min( $from, $stages ) );
		$to   = max( 1, min( $to, $stages ) );

		if ( $from > $to ) {
			$swap = $from;
			$from = $to;
			$to   = $swap;
		}

		$start = self::stage_column( $from, $input );
		$end   = self::stage_column( $to, $input ) + 1;

		return array( $start, $end );
	}

	/**
	 * A number as CSS wants it: no locale, no trailing zeroes.
	 *
	 * @param float $value Value.
	 * @return string
	 */
	public static function number( $value ) {
		$out = number_format( (float) $value, 3, '.', '' );
		$out = rtrim( rtrim( $out, '0' ), '.' );

		return '' === $out ? '0' : $out;
	}
}
