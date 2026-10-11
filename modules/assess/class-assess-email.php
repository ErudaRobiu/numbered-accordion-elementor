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

		array_push( $lines, '', Assess_Content::basis( $e ), Assess_Content::WEATHER_NOTE, '', 'ThermStar', 'solutions@thermstar.com · 833 667 7359' );

		return implode( "\n", $lines );
	}

	/**
	 * HTML: a plain corporate layout. One 640px white column with a thin navy
	 * rule on top, the logo alone, a two-figure summary, a square navy button,
	 * the answers as a hairline-ruled list and a quiet footer. Tables and
	 * inline styles only, which is what Outlook and Gmail both render
	 * faithfully.
	 *
	 * @param array $d     Clean data.
	 * @param array $e     Estimate.
	 * @param array $links See parts().
	 * @return string
	 */
	public static function html( $d, $e, $links = array() ) {
		$p = self::parts( $d, $e, $links );
		$h = function ( $s ) {
			return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
		};
		$f = array( Assess_Content::class, 'fmt' );

		$font  = 'font-family:\'Helvetica Neue\',Helvetica,Arial,sans-serif;';
		$ink   = '#1F2D3D';
		$para  = 'margin:0 0 16px;' . $font . 'font-size:15px;line-height:1.6;color:' . $ink . ';';
		$label = $font . 'font-size:11px;line-height:1.4;letter-spacing:.08em;text-transform:uppercase;color:' . self::MUTED . ';';
		$small = 'margin:0 0 8px;' . $font . 'font-size:12px;line-height:1.55;color:' . self::MUTED . ';';
		$pad   = 'padding-left:40px;padding-right:40px;';

		$rows = '';
		foreach ( $p['rows'] as $row ) {
			$rows .= '<tr>'
				. '<td class="eml-k" style="padding:11px 16px 11px 0;border-bottom:1px solid ' . self::LINE . ';' . $font . 'font-size:13px;line-height:1.45;color:' . self::MUTED . ';width:44%;vertical-align:top;">' . $h( $row[0] ) . '</td>'
				. '<td style="padding:11px 0;border-bottom:1px solid ' . self::LINE . ';' . $font . 'font-size:14px;line-height:1.45;color:' . $ink . ';vertical-align:top;">' . ( '' !== $row[1] ? $h( $row[1] ) : '&mdash;' ) . '</td>'
				. '</tr>';
		}

		$logo = '' !== $p['logo']
			? '<img src="' . $h( $p['logo'] ) . '" alt="ThermStar" width="150" style="display:block;width:150px;max-width:150px;height:auto;border:0;outline:none;text-decoration:none;">'
			: '<span style="' . $font . 'font-size:20px;font-weight:bold;letter-spacing:-.01em;color:' . self::NAVY . ';">ThermStar</span>';

		$figure = function ( $caption, $value, $unit ) use ( $font, $label ) {
			return '<p style="margin:0 0 6px;' . $label . '">' . $caption . '</p>'
				. '<p style="margin:0;' . $font . 'font-size:26px;line-height:1.2;font-weight:bold;letter-spacing:-.01em;color:' . self::NAVY . ';">' . $value . '</p>'
				. '<p style="margin:4px 0 0;' . $font . 'font-size:13px;line-height:1.4;color:' . self::MUTED . ';">' . $unit . '</p>';
		};

		$button = '' !== $p['book']
			? '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 0;"><tr><td bgcolor="' . self::NAVY . '" style="background:' . self::NAVY . ';border-radius:3px;">'
				. '<a href="' . $h( $p['book'] ) . '" style="display:inline-block;padding:14px 26px;' . $font . 'font-size:15px;line-height:1;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:3px;">Schedule your personalized analysis</a>'
				. '</td></tr></table>'
			: '';

		$again = '' !== $p['again']
			? ' Need another estimate? <a href="' . $h( $p['again'] ) . '" style="color:' . self::GREEN . ';text-decoration:underline;">Click here to use the savings calculator again.</a>'
			: '';

		$preheader = 'Your estimate: ' . $p['therms'] . ' therms a year, about ' . $p['save'] . ' a year at ' . $p['price'] . ' per therm.';

		return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="x-apple-disable-message-reformatting"><title>' . $h( self::SUBJECT ) . '</title>'
			. '<style>@media (max-width:520px){.eml-px{padding-left:24px!important;padding-right:24px!important}.eml-fig{display:block!important;width:100%!important;padding:18px 0!important;border-left:0!important}.eml-fig2{border-top:1px solid ' . self::LINE . '!important}.eml-k{width:42%!important}}</style>'
			. '</head>'
			. '<body style="margin:0;padding:0;background:#F3F4F6;">'
			. '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#F3F4F6;">' . $h( $preheader ) . '</div>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6;"><tr><td align="center" style="padding:32px 12px;">'
			. '<table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:640px;background:#FFFFFF;border-top:4px solid ' . self::NAVY . ';">'

			// Logo.
			. '<tr><td class="eml-px" style="padding-top:32px;padding-bottom:28px;' . $pad . 'border-bottom:1px solid ' . self::LINE . ';">' . $logo . '</td></tr>'

			// Title and greeting.
			. '<tr><td class="eml-px" style="padding-top:36px;' . $pad . '">'
			. '<p style="margin:0 0 10px;' . $label . '">Opportunity Screen</p>'
			. '<h1 style="margin:0 0 24px;' . $font . 'font-size:24px;line-height:1.3;font-weight:bold;letter-spacing:-.01em;color:' . self::NAVY . ';">Your heat recovery estimate</h1>'
			. '<p style="' . $para . '">Hello ' . $h( $p['first'] ) . ',</p>'
			. '<p style="' . $para . '">Thank you for using our waste heat recovery savings calculator! Let’s explore your personalized website estimate.</p>'
			. '<p style="' . $para . 'margin-bottom:20px;">' . $h( $p['first'] ) . ', with the ThermStar System™ you could save:</p>'
			. '</td></tr>'

			// The two figures.
			. '<tr><td class="eml-px" style="' . $pad . '">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid ' . self::LINE . ';border-bottom:1px solid ' . self::LINE . ';">'
			. '<tr><td class="eml-fig" width="50%" valign="top" style="padding:22px 16px 22px 0;">' . $figure( 'Energy', $h( $p['therms'] ), 'Therms per year' ) . '</td>'
			. '<td class="eml-fig eml-fig2" width="50%" valign="top" style="padding:22px 0 22px 20px;border-left:1px solid ' . self::LINE . ';">' . $figure( 'Savings', $h( $p['save'] ), 'per year at ' . $h( $p['price'] ) . ' per Therm' ) . '</td></tr>'
			. '</table>'
			. '</td></tr>'

			// The client's copy.
			. '<tr><td class="eml-px" style="padding-top:24px;' . $pad . '">'
			. '<p style="' . $para . '">If you’re paying a typical ' . $h( $p['price'] ) . ' per Therm for natural gas, you could be saving ' . $h( $p['save'] ) . ' per year.</p>'
			. '<p style="' . $para . '">The ThermStar System™ recovers thermal energy from particulate-laden exhaust air streams, converting your wasted heat into clean, usable energy. Dirty Air. Clean Energy.</p>'
			. '<p style="' . $para . '">Your estimate is just the beginning of a process where we evaluate your heat recovery opportunities and can share more ways to save with waste heat recovery across an individual facility or broader site network.</p>'
			. '<p style="' . $para . 'margin:8px 0 16px;font-weight:bold;color:' . self::NAVY . ';">Ready to explore your savings with the ThermStar System™?</p>'
			. $button
			. '</td></tr>'

			// What they sent.
			. '<tr><td class="eml-px" style="padding-top:40px;' . $pad . '">'
			. '<p style="margin:0 0 8px;' . $label . '">Your submission</p>'
			. '<p style="' . $para . 'font-size:14px;">Here are the inputs and contact details you submitted.' . $again . '</p>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid ' . self::LINE . ';margin:0 0 24px;">' . $rows . '</table>'
			. '<p style="' . $small . '">' . $h( Assess_Content::basis( $e ) ) . '</p>'
			. '<p style="' . $small . 'margin-bottom:36px;">' . $h( Assess_Content::WEATHER_NOTE ) . '</p>'
			. '</td></tr>'
			. '</table>'

			// Footer, outside the white page.
			. '<table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:640px;">'
			. '<tr><td class="eml-px" style="padding:24px 40px 8px;' . $font . 'font-size:12px;line-height:1.7;color:' . self::MUTED . ';">'
			. '<strong style="color:' . self::NAVY . ';">ThermStar</strong> &nbsp;·&nbsp; Dirty Air. Clean Energy.<br>'
			. '<a href="mailto:solutions@thermstar.com" style="color:' . self::MUTED . ';text-decoration:underline;">solutions@thermstar.com</a> &nbsp;·&nbsp; <a href="tel:+18336677359" style="color:' . self::MUTED . ';text-decoration:none;">833 667 7359</a> &nbsp;·&nbsp; <a href="https://thermstar.com/" style="color:' . self::MUTED . ';text-decoration:underline;">thermstar.com</a><br>'
			. 'You are receiving this email because you requested an estimate on thermstar.com.'
			. '</td></tr></table>'
			. '</td></tr></table></body></html>';
	}
}
