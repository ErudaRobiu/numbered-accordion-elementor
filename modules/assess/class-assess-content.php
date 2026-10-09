<?php
/**
 * Assessment Form content helpers.
 *
 * The pure logic behind the form, kept out of the widget and the REST route
 * so it can be tested without WordPress or Elementor: the choices offered,
 * the savings estimate, and cleaning what a visitor sends.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Assess;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Choices, estimate and cleaning.
 */
final class Assess_Content {

	/**
	 * Where saved requests live.
	 */
	const POST_TYPE = 'eas_request';

	/**
	 * Slider ranges: min, max, step, default.
	 */
	const TEMP  = array( 80, 450, 1, 230 );
	const CFM   = array( 500, 20000, 250, 4000 );
	const HOURS = array( 1, 24, 1, 16 );
	const DAYS  = array( 1, 7, 1, 5 );

	/**
	 * Estimate assumptions, stated on the result so nothing is hidden.
	 *
	 * Recovered heat is 1.08 × CFM × (exhaust °F − 60 °F) × a recovery share,
	 * over the operating hours of 52 weeks. It displaces fuel burned at 80%
	 * efficiency; 1 therm is 100,000 BTU and releases about 5.3 kg of CO2.
	 * The share depends on where the heat goes (see shares()).
	 */
	const SINK_F       = 60;
	const BURNER       = 0.80;
	const KG_CO2_THERM = 5.3;
	const PRICE        = 1.0;

	/**
	 * What a visitor types when the request covers more than one facility.
	 */
	const SEVERAL_SITES = 'Several sites';

	/**
	 * ThermStar's wording, shown with every estimate.
	 */
	const WEATHER_NOTE = 'Opportunity Screen results are not adjusted for ambient weather of the location selected.';

	/**
	 * Low and high recovery share for each place the heat could go, as set
	 * by ThermStar (5 Oct 2026). Several picked: the lowest low and the
	 * highest high of those. The client's space heating figure already allows
	 * for the season, so nothing else trims it.
	 *
	 * @return array<string, float[]>
	 */
	public static function shares() {
		return array(
			'process-air'   => array( 0.50, 0.70 ),
			'makeup-air'    => array( 0.30, 0.50 ),
			'process-water' => array( 0.30, 0.50 ),
			'boiler'        => array( 0.45, 0.65 ),
			'space'         => array( 0.20, 0.40 ),
			'unsure'        => array( 0.30, 0.50 ),
		);
	}

	/**
	 * The recovery share range for the uses picked. None picked counts as
	 * "Not sure yet".
	 *
	 * @param array $uses Use keys.
	 * @return float[] Low, high.
	 */
	public static function share( $uses ) {
		$all  = self::shares();
		$hits = array_values( array_intersect_key( $all, array_flip( array_map( 'strval', (array) $uses ) ) ) );

		if ( ! $hits ) {
			return $all['unsure'];
		}

		return array( min( array_column( $hits, 0 ) ), max( array_column( $hits, 1 ) ) );
	}

	/**
	 * Industries offered, keyed for the record.
	 *
	 * @return array<string, string>
	 */
	public static function industries() {
		return array(
			'industrial-laundry'              => 'Industrial laundry',
			'food-manufacturing'              => 'Food manufacturing',
			'pet-food-manufacturing'          => 'Pet food manufacturing',
			'foundries'                       => 'Foundry',
			'manufacturing'                   => 'Manufacturing',
			'restaurants-commercial-kitchens' => 'Restaurant or commercial kitchen',
			'other'                           => 'Something else',
		);
	}

	/**
	 * What the exhaust carries.
	 *
	 * @return array<string, string>
	 */
	public static function contaminants() {
		return array(
			'lint'     => 'Lint',
			'grease'   => 'Grease',
			'dust'     => 'Dust or particles',
			'fibers'   => 'Fibers',
			'soot'     => 'Soot',
			'humidity' => 'High humidity',
			'unsure'   => 'Not sure',
		);
	}

	/**
	 * Where recovered heat could go.
	 *
	 * @return array<string, string>
	 */
	public static function uses() {
		return array(
			'process-air'   => 'Process air',
			'makeup-air'    => 'Makeup air',
			'process-water' => 'Process or wash water',
			'boiler'        => 'Boiler feedwater',
			'space'         => 'Space heating',
			'unsure'        => 'Not sure yet',
		);
	}

	/**
	 * A number held to a range.
	 *
	 * @param mixed $value Value.
	 * @param array $range Min, max, step, default.
	 * @return float
	 */
	public static function clamp( $value, $range ) {
		if ( ! is_numeric( $value ) ) {
			return (float) $range[3];
		}

		return (float) max( $range[0], min( $range[1], (float) $value ) );
	}

	/**
	 * The indicative savings range.
	 *
	 * @param array $in    temp (°F), cfm, hours (per day), days (per week), uses (keys).
	 * @param float $price Gas price in dollars per therm.
	 * @return array{mmbtu: int[], therms: int[], dollars: int[], tco2: float[], hours: int, price: float, share: int[]}
	 */
	public static function estimate( $in, $price ) {
		$temp  = self::clamp( $in['temp'] ?? null, self::TEMP );
		$cfm   = self::clamp( $in['cfm'] ?? null, self::CFM );
		$hours = self::clamp( $in['hours'] ?? null, self::HOURS );
		$days  = self::clamp( $in['days'] ?? null, self::DAYS );
		$uses  = is_array( $in['uses'] ?? null ) ? $in['uses'] : array();
		$price = is_numeric( $price ) && $price > 0 ? (float) $price : self::PRICE;
		$range = self::share( $uses );

		$per_hour = 1.08 * $cfm * max( 0, $temp - self::SINK_F );
		$year     = $hours * $days * 52;

		$out = array( 'mmbtu' => array(), 'therms' => array(), 'dollars' => array(), 'tco2' => array() );

		foreach ( $range as $share ) {
			$mmbtu  = $per_hour * $share * $year / 1000000;
			$therms = $mmbtu * 10 / self::BURNER;

			$out['mmbtu'][]   = (int) self::round2( $mmbtu );
			$out['therms'][]  = (int) self::round2( $therms );
			$out['dollars'][] = (int) self::round2( $therms * $price );
			$out['tco2'][]    = round( $therms * self::KG_CO2_THERM / 1000, 1 );
		}

		$out['hours'] = (int) $year;
		$out['price'] = $price;
		$out['share'] = array( (int) round( $range[0] * 100 ), (int) round( $range[1] * 100 ) );

		return $out;
	}

	/**
	 * Round to two significant figures, so an estimate doesn't pretend to
	 * a precision it lacks: 14,873 reads 15,000.
	 *
	 * @param float $n Number.
	 * @return float
	 */
	public static function round2( $n ) {
		if ( $n <= 0 ) {
			return 0;
		}

		$scale = pow( 10, floor( log10( $n ) ) - 1 );

		return round( $n / $scale ) * $scale;
	}

	/**
	 * What a visitor sent, cleaned. Returns the clean values and any field
	 * errors, keyed by field.
	 *
	 * @param array $raw Request body.
	 * @return array{data: array, errors: array<string, string>}
	 */
	public static function clean( $raw ) {
		$raw  = is_array( $raw ) ? $raw : array();
		$text = function ( $key, $max = 200 ) use ( $raw ) {
			$v = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? trim( wp_strip_all_tags( (string) $raw[ $key ] ) ) : '';
			return mb_substr( preg_replace( '/\s+/', ' ', $v ), 0, $max );
		};
		$pick = function ( $key, $allowed ) use ( $raw ) {
			$v = isset( $raw[ $key ] ) ? (array) $raw[ $key ] : array();
			return array_values( array_intersect( array_map( 'strval', $v ), array_keys( $allowed ) ) );
		};

		$data = array(
			'temp'         => self::clamp( $raw['temp'] ?? null, self::TEMP ),
			'cfm'          => self::clamp( $raw['cfm'] ?? null, self::CFM ),
			'hours'        => self::clamp( $raw['hours'] ?? null, self::HOURS ),
			'days'         => self::clamp( $raw['days'] ?? null, self::DAYS ),
			'industry'     => array_key_exists( $text( 'industry' ), self::industries() ) ? $text( 'industry' ) : '',
			'contaminants' => $pick( 'contaminants', self::contaminants() ),
			'uses'         => $pick( 'uses', self::uses() ),
			'first'        => $text( 'first', 80 ),
			'last'         => $text( 'last', 80 ),
			'email'        => sanitize_email( $text( 'email', 190 ) ),
			'company'      => $text( 'company', 160 ),
			'role'         => $text( 'role', 120 ),
			'phone'        => $text( 'phone', 40 ),
			'location'     => $text( 'location', 160 ),
			'notes'        => mb_substr( isset( $raw['notes'] ) && is_scalar( $raw['notes'] ) ? trim( wp_strip_all_tags( (string) $raw['notes'] ) ) : '', 0, 2000 ),
			'source'       => esc_url_raw( $text( 'source', 500 ) ),
		);

		$errors = array();

		if ( '' === $data['first'] ) {
			$errors['first'] = 'Enter your first name.';
		}
		if ( '' === $data['last'] ) {
			$errors['last'] = 'Enter your last name.';
		}
		if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
			$errors['email'] = 'Enter an email address like name@company.com.';
		}
		if ( '' === $data['company'] ) {
			$errors['company'] = 'Enter your company name.';
		}
		if ( '' === $data['industry'] ) {
			$errors['industry'] = 'Choose your industry.';
		}
		if ( '' === $data['location'] ) {
			$errors['location'] = 'Enter the facility\'s city and state or province, or choose ' . self::SEVERAL_SITES . '.';
		}

		return array( 'data' => $data, 'errors' => $errors );
	}

	/**
	 * What the visitor sent, as labelled rows: the table under their estimate
	 * on screen and in their email, in ThermStar's order and wording.
	 *
	 * @param array $d Clean data.
	 * @return array<int, string[]> Label, value.
	 */
	public static function rows( $d ) {
		$names = function ( $keys, $all ) {
			return implode( ', ', array_map( function ( $k ) use ( $all ) { return $all[ $k ]; }, (array) $keys ) );
		};

		return array(
			array( 'Exhaust Air Temperature (°F)', self::fmt( $d['temp'] ) . '°F' ),
			array( 'Exhaust Airflow (CFM)', self::fmt( $d['cfm'] ) ),
			array( 'Hours of Operation per Day', self::fmt( $d['hours'] ) ),
			array( 'Days of Operation per Week', self::fmt( $d['days'] ) ),
			array( 'Heat Usage Type', $names( $d['uses'], self::uses() ) ),
			array( 'Industry Type', self::industries()[ $d['industry'] ] ?? '' ),
			array( 'What’s in the Air?', $names( $d['contaminants'], self::contaminants() ) ),
			array( 'Name', trim( $d['first'] . ' ' . $d['last'] ) ),
			array( 'Company', $d['company'] ),
			array( 'Email', $d['email'] ),
			array( 'Site Location', $d['location'] ),
			array( 'Job Title', $d['role'] ),
		);
	}

	/**
	 * How the estimate was worked out, in the words the result screen uses.
	 *
	 * @param array $e Estimate.
	 * @return string
	 */
	public static function basis( $e ) {
		$share = isset( $e['share'] ) && 2 === count( (array) $e['share'] ) ? $e['share'] : array( 30, 50 );

		return 'Indicative. Heat = 1.08 × airflow × (exhaust temperature − ' . self::SINK_F . ' °F), with ' . $share[0] . '% to ' . $share[1] . '% of it recovered for the uses you chose, displacing fuel burned at ' . (int) ( self::BURNER * 100 ) . '% efficiency. The free screen replaces these assumptions with your real data.';
	}

	/**
	 * A start token: when the form was handed out, signed so a bot cannot
	 * make one up. The browser's own clock is never trusted for this.
	 *
	 * @param int    $now Unix time.
	 * @param string $key Secret.
	 * @return string
	 */
	public static function token( $now, $key ) {
		return (int) $now . '.' . substr( hash_hmac( 'sha256', 'eas|' . (int) $now, (string) $key ), 0, 32 );
	}

	/**
	 * Seconds since a start token was handed out, or null if it is forged
	 * or malformed.
	 *
	 * @param mixed  $token Token as sent.
	 * @param int    $now   Unix time.
	 * @param string $key   Secret.
	 * @return int|null
	 */
	public static function token_age( $token, $now, $key ) {
		if ( ! is_string( $token ) || ! preg_match( '/^(\d{9,11})\.[0-9a-f]{32}$/', $token, $m ) ) {
			return null;
		}

		return hash_equals( self::token( (int) $m[1], $key ), $token ) ? (int) $now - (int) $m[1] : null;
	}

	/**
	 * Spam a person would not send: a web address in a name, company or job
	 * title, or a note that is mostly links.
	 *
	 * @param array $d Clean data.
	 * @return bool
	 */
	public static function is_spam( $d ) {
		$link = '~https?://|www\.|\[url~i';

		foreach ( array( 'first', 'last', 'company', 'role', 'location' ) as $key ) {
			if ( preg_match( $link, (string) ( $d[ $key ] ?? '' ) ) ) {
				return true;
			}
		}

		return preg_match_all( $link, (string) ( $d['notes'] ?? '' ) ) > 2;
	}

	/**
	 * A number for people: 15000 reads "15,000".
	 *
	 * @param float $n Number.
	 * @return string
	 */
	public static function fmt( $n ) {
		return number_format( (float) $n, ( floor( $n ) == $n ) ? 0 : 1 ); // phpcs:ignore Universal.Operators.StrictComparisons
	}

	/**
	 * The request as plain lines, for the team email and the saved record.
	 *
	 * @param array $d  Clean data.
	 * @param array $e  Estimate.
	 * @return string
	 */
	public static function summary( $d, $e ) {
		$names = function ( $keys, $all ) {
			return $keys ? implode( ', ', array_map( function ( $k ) use ( $all ) { return $all[ $k ]; }, $keys ) ) : 'Not given';
		};

		$lines = array(
			'Name: ' . $d['first'] . ' ' . $d['last'],
			'Email: ' . $d['email'],
			'Company: ' . $d['company'],
			'Role: ' . ( $d['role'] ?: 'Not given' ),
			'Phone: ' . ( $d['phone'] ?: 'Not given' ),
			'Facility location: ' . ( $d['location'] ?: 'Not given' ),
			'Industry: ' . ( self::industries()[ $d['industry'] ] ?? 'Not given' ),
			'',
			'Exhaust temperature: ' . self::fmt( $d['temp'] ) . ' °F',
			'Airflow: ' . self::fmt( $d['cfm'] ) . ' CFM',
			'Operation: ' . self::fmt( $d['hours'] ) . ' h/day, ' . self::fmt( $d['days'] ) . ' days/week',
			'In the exhaust: ' . $names( $d['contaminants'], self::contaminants() ),
			'Heat could go to: ' . $names( $d['uses'], self::uses() ),
			'',
			'Indicative estimate: ' . self::fmt( $e['mmbtu'][0] ) . '–' . self::fmt( $e['mmbtu'][1] ) . ' MMBtu/yr, $' . self::fmt( $e['dollars'][0] ) . '–$' . self::fmt( $e['dollars'][1] ) . '/yr at $' . number_format( $e['price'], 2 ) . '/therm, ' . self::fmt( $e['tco2'][0] ) . '–' . self::fmt( $e['tco2'][1] ) . ' t CO2/yr (' . ( $e['share'][0] ?? '' ) . '–' . ( $e['share'][1] ?? '' ) . '% of the exhaust heat recovered)',
			self::WEATHER_NOTE,
			'',
			'Notes: ' . ( $d['notes'] ?: 'None' ),
			'Sent from: ' . ( $d['source'] ?: 'Unknown page' ),
		);

		return implode( "\n", $lines );
	}
}
