<?php
/**
 * Impact grid content parsing.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Impact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns what an editor typed into the shape the widget renders.
 *
 * Deliberately free of both WordPress and Elementor. Everything here is a pure
 * function of its arguments, which is what lets tests/run.php exercise it
 * without a WordPress install and without Elementor's Widget_Base existing.
 */
final class Impact_Content {

	/**
	 * Characters that mark a line as a sub-bullet of the line above it.
	 *
	 * Hyphen, asterisk, bullet, en dash, em dash. Editors reach for all five,
	 * and which one they picked should not change what they get.
	 */
	const SUB_MARKERS = '-*\x{2022}\x{2013}\x{2014}';

	/**
	 * Parse a checklist textarea into items and their sub-bullets.
	 *
	 * Elementor has no nested repeater, so the nesting on card 6 of the source
	 * artwork -- a check item carrying two plain bullets -- has to come from
	 * the text itself. One item per line; a line opening with a marker nests
	 * under the most recent top-level item.
	 *
	 * @param mixed $text Raw textarea value.
	 * @return array<int, array{text: string, sub: string[]}>
	 */
	public static function parse_list( $text ) {
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return array();
		}

		$items = array();

		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				continue;
			}

			$is_sub = (bool) preg_match( '/^[' . self::SUB_MARKERS . ']\s*(.*)$/u', $line, $matches );

			if ( $is_sub ) {
				$line = trim( $matches[1] );

				// A marker with nothing after it is punctuation, not content.
				if ( '' === $line ) {
					continue;
				}
			}

			/*
			 * A sub-bullet with nothing above it to nest under is promoted to
			 * a top-level item rather than discarded. Someone who indented
			 * every line still sees all of their text on the page; silently
			 * eating it would look like the widget had lost their content.
			 */
			if ( $is_sub && ! empty( $items ) ) {
				$items[ count( $items ) - 1 ]['sub'][] = $line;
				continue;
			}

			$items[] = array(
				'text' => $line,
				'sub'  => array(),
			);
		}

		return $items;
	}

	/**
	 * Can this figure be animated from zero?
	 *
	 * The answer is decided here, in PHP, and handed to the browser as a data
	 * attribute. The script never re-derives it, so there is one rule rather
	 * than two that can drift apart.
	 *
	 * Anything that is not a plain number -- a range, an approximation, a
	 * trailing plus, a percentage -- is left exactly as typed. Counting up to
	 * a value the widget had to guess at is worse than not counting at all.
	 *
	 * @param mixed $value Figure as the editor typed it.
	 * @return bool
	 */
	public static function is_countable( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return false;
		}

		// Grouped: 1,234 or 165,000,000 or 1,234.56. Groups must be exact,
		// so a typo like 1,23,456 is treated as text and left static.
		if ( preg_match( '/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value ) ) {
			return true;
		}

		// Ungrouped: 400 or 26.5.
		return (bool) preg_match( '/^\d+(\.\d+)?$/', $value );
	}

	/**
	 * Format a card's badge number.
	 *
	 * @param int    $position Zero-based position among rendered cards.
	 * @param string $format   One of 'plain', 'pad', 'none'.
	 * @param int    $start    Number the first card carries.
	 * @return string Empty string when numbering is switched off.
	 */
	public static function format_number( $position, $format, $start ) {
		if ( 'none' === $format ) {
			return '';
		}

		$value = (int) $start + (int) $position;

		if ( 'pad' === $format ) {
			return str_pad( (string) $value, 2, '0', STR_PAD_LEFT );
		}

		return (string) $value;
	}
}
