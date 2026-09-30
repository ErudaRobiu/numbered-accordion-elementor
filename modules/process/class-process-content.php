<?php
/**
 * Process Stepper content helpers.
 *
 * The pure logic behind the widget, kept out of it so it can be tested
 * without WordPress or Elementor.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Process;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The eight ThermStar stages, and how the repeater becomes stages.
 */
final class Process_Content {

	/**
	 * Default wording for a stage's own link.
	 */
	const LINK_TEXT = 'Learn more →';

	/**
	 * Default breakpoints, in pixels of the widget's own width.
	 */
	const SCROLL_BELOW   = 900;
	const VERTICAL_BELOW = 560;

	/**
	 * The eight stages, each line condensed from the approved service pages.
	 *
	 * @return array<int, array>
	 */
	public static function default_items() {
		return array(
			array( 'title' => 'Opportunity screening', 'text' => 'Where heat leaves your facility, and where it could serve a useful load.', 'link' => '/thermal-energy-opportunity-screen/', 'start' => true ),
			array( 'title' => 'Technical data review', 'text' => 'Exhaust conditions, operating hours and the receiving demand, reviewed together.', 'link' => '/technical-data-review/', 'start' => false ),
			array( 'title' => 'Preliminary assessment', 'text' => 'Recoverable energy, delivery options and early project economics.', 'link' => '', 'start' => false ),
			array( 'title' => 'Configuration', 'text' => 'A system concept built around your source, your demand and your site.', 'link' => '/configuration-implementation-verification/', 'start' => false ),
			array( 'title' => 'Proposal', 'text' => 'ThermStar’s supply and services, and the interfaces your team owns.', 'link' => '', 'start' => false ),
			array( 'title' => 'Implementation coordination', 'text' => 'Working with your engineers and contractors on delivery and integration.', 'link' => '', 'start' => false ),
			array( 'title' => 'Commissioning', 'text' => 'Controls, sensors and operating sequences checked and confirmed.', 'link' => '', 'start' => false ),
			array( 'title' => 'Measurement', 'text' => 'Recovered energy documented after startup, with reporting for incentives.', 'link' => '/thermstar-power-intelligence/', 'start' => false ),
		);
	}

	/**
	 * A link setting as url, new tab and nofollow. Takes Elementor's URL
	 * control array or a bare string.
	 *
	 * @param mixed $value Value.
	 * @return array{url: string, external: bool, nofollow: bool}
	 */
	public static function link( $value ) {
		if ( is_array( $value ) ) {
			return array(
				'url'      => isset( $value['url'] ) && is_scalar( $value['url'] ) ? trim( (string) $value['url'] ) : '',
				'external' => ! empty( $value['is_external'] ),
				'nofollow' => ! empty( $value['nofollow'] ),
			);
		}

		return array(
			'url'      => is_scalar( $value ) ? trim( (string) $value ) : '',
			'external' => false,
			'nofollow' => false,
		);
	}

	/**
	 * The repeater as stages. Untitled rows are skipped; numbers count the
	 * stages kept, unless a row types its own.
	 *
	 * @param mixed  $items     Repeater value.
	 * @param string $link_text Default link wording.
	 * @return array
	 */
	public static function build( $items, $link_text = self::LINK_TEXT ) {
		$out = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item  = is_array( $item ) ? $item : array();
			$title = self::text( $item, 'title' );

			if ( '' === $title ) {
				continue;
			}

			$number = self::text( $item, 'number' );
			$link   = self::link( isset( $item['link'] ) ? $item['link'] : '' );
			$text   = self::text( $item, 'link_text' );

			$out[] = array(
				'number'    => '' !== $number ? $number : sprintf( '%02d', count( $out ) + 1 ),
				'title'     => $title,
				'text'      => self::text( $item, 'text' ),
				'link'      => $link,
				'link_text' => '' !== $text ? $text : $link_text,
				'start'     => isset( $item['start'] ) && ( true === $item['start'] || 'yes' === $item['start'] ),
			);
		}

		return $out;
	}

	/**
	 * Which stage opens first, zero-based. A typed stage number wins; else the
	 * first stage marked as the start; else the first.
	 *
	 * @param mixed $value  The "initial stage" setting, 1-based, empty for auto.
	 * @param array $stages Built stages.
	 * @return int
	 */
	public static function initial( $value, $stages ) {
		$count = count( $stages );

		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		if ( is_numeric( $value ) && (int) $value >= 1 && $count ) {
			return min( (int) $value, $count ) - 1;
		}

		foreach ( $stages as $i => $stage ) {
			if ( $stage['start'] ) {
				return (int) $i;
			}
		}

		return 0;
	}

	/**
	 * The auto-advance interval in milliseconds, kept to a readable pace.
	 *
	 * @param mixed $value Seconds, or a slider array.
	 * @return int
	 */
	public static function interval( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		$seconds = is_numeric( $value ) ? (float) $value : 4.0;

		return (int) round( max( 2.0, min( 15.0, $seconds ) ) * 1000 );
	}

	/**
	 * The two breakpoints, sane and in order: vertical below never above
	 * scroll below.
	 *
	 * @param mixed $scroll   Slider array or number.
	 * @param mixed $vertical Slider array or number.
	 * @return array{0: int, 1: int}
	 */
	public static function breakpoints( $scroll, $vertical ) {
		$scroll   = self::px( $scroll, self::SCROLL_BELOW );
		$vertical = self::px( $vertical, self::VERTICAL_BELOW );

		return array( max( $scroll, $vertical ), $vertical );
	}

	/**
	 * A pixel setting, clamped.
	 *
	 * @param mixed $value    Slider array or number.
	 * @param int   $fallback When unreadable.
	 * @return int
	 */
	private static function px( $value, $fallback ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['size'] ) ? $value['size'] : null;
		}

		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}

		return (int) max( 0, min( 2400, round( (float) $value ) ) );
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
