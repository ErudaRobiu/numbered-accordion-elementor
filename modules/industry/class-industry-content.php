<?php
/**
 * Industry showcase decisions that do not need WordPress.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Industry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The arithmetic behind the scroll showcase.
 *
 * The widget is a tall section with a pinned inner panel: scrolling through
 * the section walks the list. How tall the section has to be, and which item a
 * given scroll position lands on, are the two decisions that decide whether it
 * feels right, so they live here where they can be tested rather than inside a
 * render method where they cannot.
 */
final class Industry_Content {

	/**
	 * How much scrolling each item is worth, as a fraction of the viewport.
	 *
	 * Below about a half the list flicks past faster than the crossfade can
	 * finish; above about one and a half it feels like the page has stopped
	 * responding.
	 */
	const MIN_PACE = 0.5;
	const MAX_PACE = 1.5;

	/**
	 * Most items a showcase can hold.
	 *
	 * Not a technical limit. Past this the list is taller than the panel it
	 * sits in and the section is long enough that a visitor loses the thread.
	 */
	const MAX_ITEMS = 12;

	/**
	 * Drop items that have nothing to show.
	 *
	 * An item with no name cannot be chosen from the list and an item with no
	 * heading has nothing to say once chosen, so either one is a half-filled
	 * repeater row rather than content. Keeping them would leave gaps in the
	 * list and blank panels in the sequence.
	 *
	 * @param mixed $items Repeater rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function usable( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$usable = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$name    = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
			$heading = isset( $item['heading'] ) ? trim( (string) $item['heading'] ) : '';

			if ( '' === $name || '' === $heading ) {
				continue;
			}

			$usable[] = $item;

			if ( count( $usable ) >= self::MAX_ITEMS ) {
				break;
			}
		}

		return $usable;
	}

	/**
	 * The section's height, in vh.
	 *
	 * One viewport for the pinned panel itself, plus a stretch of scrolling
	 * per item. The panel is pinned for the whole of the extra, so the last
	 * item is on screen for its share of the scroll and not merely for the
	 * instant the section ends.
	 *
	 * @param int   $count Item count.
	 * @param float $pace  Viewports of scroll per item.
	 * @return int Height in vh. 0 when there is nothing to show.
	 */
	public static function height( $count, $pace = 0.85 ) {
		$count = (int) $count;

		if ( $count < 1 ) {
			return 0;
		}

		$pace = (float) $pace;

		if ( $pace < self::MIN_PACE ) {
			$pace = self::MIN_PACE;
		}

		if ( $pace > self::MAX_PACE ) {
			$pace = self::MAX_PACE;
		}

		// A single item has nothing to scroll between, so it is just the panel.
		if ( 1 === $count ) {
			return 100;
		}

		return (int) round( 100 + ( $count * $pace * 100 ) );
	}

	/**
	 * Which item a scroll progress lands on.
	 *
	 * Progress runs 0 to 1 across the section. Multiplying by the count and
	 * flooring would put the last item on screen only at exactly 1, which no
	 * scroll position reliably reaches, so the top of the range is nudged
	 * inside the last band.
	 *
	 * @param float $progress 0 to 1.
	 * @param int   $count    Item count.
	 * @return int Zero-based index.
	 */
	public static function index( $progress, $count ) {
		$count = (int) $count;

		if ( $count < 1 ) {
			return 0;
		}

		$progress = (float) $progress;

		if ( $progress < 0 || ! is_finite( $progress ) ) {
			$progress = 0.0;
		}

		if ( $progress > 1 ) {
			$progress = 1.0;
		}

		$index = (int) floor( $progress * $count * 0.9999 );

		if ( $index < 0 ) {
			$index = 0;
		}

		if ( $index > $count - 1 ) {
			$index = $count - 1;
		}

		return $index;
	}

	/**
	 * Where an item sits within the section, as a progress value.
	 *
	 * The middle of its band rather than its start, so that clicking a name
	 * lands on the item rather than on the boundary where the next one is
	 * about to take over.
	 *
	 * @param int $index Zero-based index.
	 * @param int $count Item count.
	 * @return float 0 to 1.
	 */
	public static function anchor( $index, $count ) {
		$count = (int) $count;

		if ( $count < 1 ) {
			return 0.0;
		}

		$index = max( 0, min( (int) $index, $count - 1 ) );

		return ( $index + 0.5 ) / $count;
	}

	/**
	 * The counter, as it is read out.
	 *
	 * Padded so that the pair does not change width as it counts, which would
	 * make the whole line twitch on every step.
	 *
	 * @param int $index Zero-based index.
	 * @param int $count Item count.
	 * @return string
	 */
	public static function counter( $index, $count ) {
		$count = max( 0, (int) $count );

		if ( 0 === $count ) {
			return '';
		}

		$index = max( 0, min( (int) $index, $count - 1 ) );
		$width = max( 2, strlen( (string) $count ) );

		return sprintf(
			'%s / %s',
			str_pad( (string) ( $index + 1 ), $width, '0', STR_PAD_LEFT ),
			str_pad( (string) $count, $width, '0', STR_PAD_LEFT )
		);
	}
}
