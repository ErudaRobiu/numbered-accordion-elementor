<?php
/**
 * What the Page Transitions module lets a site change.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Transitions;

use ErudaToolkit\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The module's option declarations, and the shape the front end wants them in.
 *
 * Kept apart from the module class so the decisions here can be tested without
 * WordPress, the same way Motion_Presets and Impact_Content are.
 *
 * Field ids are stored in the database. They never change.
 */
final class Transitions_Fields {

	/**
	 * Fallback curtain colour.
	 *
	 * Near-black rather than pure black: on an OLED screen a pure-black
	 * curtain against a dark page is invisible, and the transition reads as a
	 * stutter rather than a movement.
	 */
	const FALLBACK_COLOR = '#111111';

	/**
	 * Below this width the curtain drops to four columns.
	 *
	 * Six columns on a phone are each about sixty pixels wide, which reads as
	 * a shimmer rather than a sweep.
	 */
	const MOBILE_BREAKPOINT = 478;

	/**
	 * Most columns a phone ever shows.
	 */
	const MOBILE_COLUMNS = 4;

	/**
	 * The declarations.
	 *
	 * @param string $default_color Colour to offer when nothing is stored.
	 *                              The module passes the Elementor kit's
	 *                              primary when there is one.
	 * @return array<int, array<string, mixed>>
	 */
	public static function declarations( $default_color = self::FALLBACK_COLOR ) {
		$color = Fields::color( $default_color );

		return array(
			array(
				'id'      => 'color',
				'label'   => esc_html__( 'Curtain colour', 'numbered-accordion' ),
				'type'    => 'color',
				'default' => null === $color ? self::FALLBACK_COLOR : $color,
				'help'    => esc_html__( 'Defaults to the site\'s Elementor primary colour.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'columns',
				'label'   => esc_html__( 'Columns', 'numbered-accordion' ),
				'type'    => 'number',
				'default' => 6,
				'min'     => 1,
				'max'     => 12,
				'step'    => 1,
				'help'    => esc_html__( 'Phones show at most four, whatever this says.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'travel',
				'label'   => esc_html__( 'Column travel', 'numbered-accordion' ),
				'type'    => 'number',
				'default' => 0.18,
				'min'     => 0.05,
				'max'     => 1.5,
				'step'    => 0.01,
				'help'    => esc_html__( 'Seconds for one column to cross the screen.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'stagger',
				'label'   => esc_html__( 'Stagger', 'numbered-accordion' ),
				'type'    => 'number',
				'default' => 0.05,
				'min'     => 0,
				'max'     => 0.5,
				'step'    => 0.01,
				'help'    => esc_html__( 'Seconds between one column starting and the next.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'preloader',
				'label'   => esc_html__( 'Preloader', 'numbered-accordion' ),
				'type'    => 'checkbox',
				'default' => true,
				'help'    => esc_html__( 'Show the logo and progress bar on the first page of a visit.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'logo',
				'label'   => esc_html__( 'Preloader logo', 'numbered-accordion' ),
				'type'    => 'media',
				'default' => 0,
				'help'    => esc_html__( 'Falls back to the site logo. Set one here when the curtain needs a different version of the mark to the header does.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'minimum',
				'label'   => esc_html__( 'Minimum preloader time', 'numbered-accordion' ),
				'type'    => 'number',
				'default' => 600,
				'min'     => 0,
				'max'     => 5000,
				'step'    => 50,
				'help'    => esc_html__( 'Milliseconds. Stops the preloader flashing on a fast page.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'maximum',
				'label'   => esc_html__( 'Maximum preloader time', 'numbered-accordion' ),
				'type'    => 'number',
				'default' => 4000,
				'min'     => 500,
				'max'     => 15000,
				'step'    => 100,
				'help'    => esc_html__( 'Milliseconds. The page is revealed at this point whether or not it is ready.', 'numbered-accordion' ),
			),
			array(
				'id'      => 'percentage',
				'label'   => esc_html__( 'Percentage', 'numbered-accordion' ),
				'type'    => 'checkbox',
				'default' => true,
				'help'    => esc_html__( 'Count up in the corner while the page loads.', 'numbered-accordion' ),
			),
		);
	}

	/**
	 * Stored values, made safe and made consistent.
	 *
	 * @param mixed  $stored        Whatever is in the option for this module.
	 * @param string $default_color Colour to fall back to.
	 * @return array<string, mixed>
	 */
	public static function options( $stored, $default_color = self::FALLBACK_COLOR ) {
		$values = Fields::values( self::declarations( $default_color ), $stored );

		// A maximum below the minimum would hold the page forever at one end
		// or reveal it instantly at the other, depending on which check ran
		// first. Neither is what anybody typed, so give the minimum way.
		if ( $values['maximum'] < $values['minimum'] ) {
			$values['minimum'] = $values['maximum'];
		}

		$values['mobileColumns'] = min( self::MOBILE_COLUMNS, $values['columns'] );
		$values['breakpoint']    = self::MOBILE_BREAKPOINT;

		// What the last column's animation finishes at, which is how long a
		// cover or a reveal actually takes. The script waits on the animation
		// itself and uses this only to size its fallback timer.
		$values['sweep'] = $values['travel'] + ( $values['stagger'] * ( $values['columns'] - 1 ) );

		return $values;
	}
}
