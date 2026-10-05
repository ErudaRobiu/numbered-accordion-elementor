<?php
/**
 * The estimate email the visitor gets.
 *
 * ThermStar's own copy (5 Oct 2026), word for word, with the visitor's
 * numbers and answers filled in. Built without WordPress so it can be tested
 * on its own; the module sends it.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Assess;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The HTML email and its plain text twin.
 */
final class Assess_Email {

	const NAVY  = '#0F3961';
	const GREEN = '#07864F';
	const MUTED = '#5A6F84';
	const LINE  = '#DDE2E6';

	/**
	 * Subject line.
	 */
	const SUBJECT = 'Your ThermStar waste heat recovery estimate';

	/**
	 * The figures and links the copy needs.
	 *
	 * @param array $d     Clean data.
	 * @param array $e     Estimate.
	 * @param array $links book (booking page), again (calculator), logo (image), all optional.
	 * @return array
	 */
	private static function parts( $d, $e, $links ) {
		$f = array( Assess_Content::class, 'fmt' );

		return array(
			'first'  => $d['first'],
			'therms' => $f( $e['therms'][0] ) . ' to ' . $f( $e['therms'][1] ),
			'price'  => '$' . number_format( (float) $e['price'], 2 ),
			'save'   => '$' . $f( $e['dollars'][0] ) . ' to $' . $f( $e['dollars'][1] ),
			'book'   => (string) ( $links['book'] ?? '' ),
			'again'  => (string) ( $links['again'] ?? '' ),
			'logo'   => (string) ( $links['logo'] ?? '' ),
			'rows'   => Assess_Content::rows( $d ),
		);
	}

	/**
	 * Plain text, for mail apps that will not show HTML.
	 *
	 * @param array $d     Clean data.
	 * @param array $e     Estimate.
	 * @param array $links See parts().
	 * @return string
	 */
	public static function text( $d, $e, $links = array() ) {
		$p = self::parts( $d, $e, $links );

		$lines = array(
			'Hello ' . $p['first'] . ',',
			'',
			'Thank you for using our waste heat recovery savings calculator! Let’s explore your personalized website estimate.',
			'',
			$p['first'] . ', with the ThermStar System™ you could save:',
			'',
			'  • ' . $p['therms'] . ' Therms per year',
			'',
			'If you’re paying a typical ' . $p['price'] . ' per Therm for natural gas, you could be saving ' . $p['save'] . ' per year.',
			'',
			'The ThermStar System™ recovers thermal energy from particulate-laden exhaust air streams, converting your wasted heat into clean, usable energy. Dirty Air. Clean Energy.',
			'',
			'Your estimate is just the beginning of a process where we evaluate your heat recovery opportunities and can share more ways to save with waste heat recovery across an individual facility or broader site network.',
			'',
			'Ready to explore your savings with the ThermStar System™?',
		);

		if ( '' !== $p['book'] ) {
			array_push( $lines, 'SCHEDULE YOUR PERSONALIZED ANALYSIS: ' . $p['book'] );
		}

		array_push( $lines, '', 'Here are the inputs and contact details you submitted.' . ( '' !== $p['again'] ? ' Need another estimate? Use the savings calculator again: ' . $p['again'] : '' ), '' );

		foreach ( $p['rows'] as $row ) {
			$lines[] = $row[0] . ': ' . $row[1];
		}

		array_push( $lines, '', Assess_Content::WEATHER_NOTE, '', 'ThermStar', 'solutions@thermstar.com · 833 667 7359' );

		return implode( "\n", $lines );
	}

	/**
	 * HTML: one centred 600px column of tables with inline styles, which is
	 * what Outlook and Gmail both render faithfully.
	 *
	 * @param array $d     Clean data.
	 * @param array $e     Estimate.
	 * @param array $links See parts().
	 * @return string
	 */
	public static function html( $d, $e, $links = array() ) {
		$p    = self::parts( $d, $e, $links );
		$h    = function ( $s ) {
			return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
		};
		$font = 'font-family:Arial,Helvetica,sans-serif;';
		$para = 'margin:0 0 18px;' . $font . 'font-size:16px;line-height:1.55;color:' . self::NAVY . ';';

		$rows = '';
		foreach ( $p['rows'] as $i => $row ) {
			$bg    = $i % 2 ? '#FFFFFF' : '#F5F6F7';
			$rows .= '<tr>'
				. '<td style="padding:10px 14px;border-top:1px solid ' . self::LINE . ';background:' . $bg . ';' . $font . 'font-size:14px;color:' . self::MUTED . ';width:52%;">' . $h( $row[0] ) . '</td>'
				. '<td style="padding:10px 14px;border-top:1px solid ' . self::LINE . ';background:' . $bg . ';' . $font . 'font-size:14px;color:' . self::NAVY . ';font-weight:bold;">' . ( '' !== $row[1] ? $h( $row[1] ) : '&mdash;' ) . '</td>'
				. '</tr>';
		}

		$head = '' !== $p['logo']
			? '<img src="' . $h( $p['logo'] ) . '" alt="ThermStar" height="44" style="display:block;height:44px;width:auto;border:0;">'
			: '<span style="' . $font . 'font-size:22px;font-weight:bold;color:' . self::NAVY . ';">ThermStar</span>';

		$button = '' !== $p['book']
			? '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;"><tr><td style="border-radius:999px;background:' . self::GREEN . ';">'
				. '<a href="' . $h( $p['book'] ) . '" style="display:inline-block;padding:15px 28px;' . $font . 'font-size:15px;font-weight:bold;letter-spacing:.04em;color:#FFFFFF;text-decoration:none;border-radius:999px;">SCHEDULE YOUR PERSONALIZED ANALYSIS</a>'
				. '</td></tr></table>'
			: '';

		$again = '' !== $p['again']
			? ' Need another estimate? <a href="' . $h( $p['again'] ) . '" style="color:' . self::GREEN . ';font-weight:bold;">Click here to use the savings calculator again.</a>'
			: '';

		return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $h( self::SUBJECT ) . '</title></head>'
			. '<body style="margin:0;padding:0;background:#EEF1F3;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF1F3;"><tr><td align="center" style="padding:28px 12px;">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#FFFFFF;border-radius:16px;">'
			. '<tr><td style="padding:28px 36px 20px;border-bottom:3px solid ' . self::GREEN . ';">' . $head . '</td></tr>'
			. '<tr><td style="padding:32px 36px 8px;">'
			. '<p style="' . $para . '">Hello <strong style="color:' . self::GREEN . ';">' . $h( $p['first'] ) . '</strong>,</p>'
			. '<p style="' . $para . '">Thank you for using our waste heat recovery savings calculator! Let’s explore your personalized website estimate.</p>'
			. '<p style="' . $para . 'margin-bottom:10px;"><strong style="color:' . self::GREEN . ';">' . $h( $p['first'] ) . '</strong>, with the ThermStar System™ you could save:</p>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;"><tr><td style="padding:18px 20px;background:#07864F12;border-left:4px solid ' . self::GREEN . ';border-radius:8px;' . $font . 'font-size:22px;font-weight:bold;color:' . self::NAVY . ';">' . $h( $p['therms'] ) . ' Therms per year</td></tr></table>'
			. '<p style="' . $para . '">If you’re paying a typical ' . $h( $p['price'] ) . ' per Therm for natural gas, <strong>you could be saving ' . $h( $p['save'] ) . ' per year.</strong></p>'
			. '<p style="' . $para . '"><strong>The ThermStar System™ recovers thermal energy from particulate-laden exhaust air streams, converting your wasted heat into clean, usable energy</strong>. Dirty Air. Clean Energy.</p>'
			. '<p style="' . $para . '">Your estimate is just the beginning of a process where we evaluate your heat recovery opportunities and can share more ways to save with waste heat recovery across an individual facility or broader site network.</p>'
			. '<p style="' . $para . '"><strong>Ready to explore your savings with the ThermStar System™?</strong></p>'
			. $button
			. '<p style="' . $para . '">Here are the inputs and contact details you submitted.' . $again . '</p>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;border:1px solid ' . self::LINE . ';border-collapse:collapse;">'
			. '<tr><th align="left" style="padding:10px 14px;background:' . self::NAVY . ';' . $font . 'font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#FFFFFF;">Field</th><th align="left" style="padding:10px 14px;background:' . self::NAVY . ';' . $font . 'font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#FFFFFF;">Value</th></tr>'
			. $rows
			. '</table>'
			. '<p style="margin:0 0 28px;' . $font . 'font-size:13px;line-height:1.5;font-style:italic;color:' . self::MUTED . ';">' . $h( Assess_Content::WEATHER_NOTE ) . '</p>'
			. '</td></tr>'
			. '<tr><td style="padding:20px 36px 28px;border-top:1px solid ' . self::LINE . ';' . $font . 'font-size:13px;line-height:1.6;color:' . self::MUTED . ';">'
			. '<strong style="color:' . self::NAVY . ';">ThermStar</strong> · Dirty Air. Clean Energy.<br>'
			. '<a href="mailto:solutions@thermstar.com" style="color:' . self::GREEN . ';">solutions@thermstar.com</a> · <a href="tel:+18336677359" style="color:' . self::GREEN . ';">833 667 7359</a>'
			. '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}
