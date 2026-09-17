<?php
/**
 * The preset catalogue.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Motion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What animations exist, and how each one cuts the text up.
 *
 * Deliberately free of WordPress beyond translation, so the test suite can
 * load it without a WordPress install.
 *
 * The split mode recorded here is the PHP-side copy. The stylesheet declares
 * the same fact as a --eanm-split custom property per preset, and the script
 * reads it from there rather than carrying a third copy. This array exists for
 * the tests and for any future PHP that needs to reason about a preset without
 * a browser.
 */
final class Motion_Presets {

	const SPLIT_NONE  = 'none';
	const SPLIT_WORDS = 'words';
	const SPLIT_CHARS = 'chars';
	const SPLIT_LINES = 'lines';

	/**
	 * Every preset, in the order they appear in the dropdown.
	 *
	 * The keys are frozen: saved Elementor JSON stores them.
	 *
	 * @return array<string, array>
	 */
	public static function all() {
		return array(
			'none'          => array(
				'label' => esc_html__( 'None', 'numbered-accordion' ),
				'split' => self::SPLIT_NONE,
			),
			'words-up'      => array(
				'label' => esc_html__( 'Words Up', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'words-fade'    => array(
				'label' => esc_html__( 'Words Fade', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'chars-cascade' => array(
				'label' => esc_html__( 'Characters Cascade', 'numbered-accordion' ),
				'split' => self::SPLIT_CHARS,
			),
			'chars-flip'    => array(
				'label' => esc_html__( 'Characters Flip', 'numbered-accordion' ),
				'split' => self::SPLIT_CHARS,
			),
			'lines-mask'    => array(
				'label' => esc_html__( 'Lines Reveal', 'numbered-accordion' ),
				'split' => self::SPLIT_LINES,
			),
			'blur-in'       => array(
				'label' => esc_html__( 'Blur In', 'numbered-accordion' ),
				'split' => self::SPLIT_NONE,
			),
			'scale-pop'     => array(
				'label' => esc_html__( 'Scale Pop', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
			'slide-left'    => array(
				'label' => esc_html__( 'Slide In', 'numbered-accordion' ),
				'split' => self::SPLIT_WORDS,
			),
		);
	}

	/**
	 * Value => label, shaped for an Elementor SELECT control.
	 *
	 * @return array<string, string>
	 */
	public static function options() {
		$options = array();

		foreach ( self::all() as $value => $preset ) {
			$options[ $value ] = $preset['label'];
		}

		return $options;
	}

	/**
	 * How a preset cuts the text up.
	 *
	 * Anything unrecognised returns SPLIT_NONE. A preset value that no longer
	 * exists -- a downgrade, a hand-edited layout -- must leave the text alone
	 * rather than guess.
	 *
	 * @param mixed $value Preset value.
	 * @return string
	 */
	public static function split_mode( $value ) {
		if ( ! self::is_valid( $value ) ) {
			return self::SPLIT_NONE;
		}

		$all = self::all();

		return $all[ $value ]['split'];
	}

	/**
	 * Is this a preset this version ships?
	 *
	 * @param mixed $value Candidate.
	 * @return bool
	 */
	public static function is_valid( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		return array_key_exists( $value, self::all() );
	}

	/**
	 * The easing curves offered, as a SELECT's options.
	 *
	 * The values are semantic rather than the curves themselves, so the stored
	 * layout stays readable and a curve can be retuned in CSS later without
	 * touching a single saved page. The stylesheet maps each one to a
	 * cubic-bezier via the .eanm-ease-* class.
	 *
	 * @return array<string, string>
	 */
	public static function easings() {
		return array(
			'out-expo'  => esc_html__( 'Smooth', 'numbered-accordion' ),
			'out-quart' => esc_html__( 'Gentle', 'numbered-accordion' ),
			'out-back'  => esc_html__( 'Overshoot', 'numbered-accordion' ),
			'in-out'    => esc_html__( 'Even', 'numbered-accordion' ),
			'linear'    => esc_html__( 'Linear', 'numbered-accordion' ),
		);
	}
}
