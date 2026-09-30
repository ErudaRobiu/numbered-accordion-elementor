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
	 * Recovered heat is 1.08 × CFM × (exhaust °F − 60 °F) × a recovery share
	 * of 40% to 60%, over the operating hours of 52 weeks. It displaces fuel
	 * burned at 80% efficiency; 1 therm is 100,000 BTU and releases about
	 * 5.3 kg of CO2. Space heating only runs half the year.
	 */
	const SINK_F        = 60;
	const SHARE_LOW     = 0.40;
	const SHARE_HIGH    = 0.60;
	const BURNER        = 0.80;
	const KG_CO2_THERM  = 5.3;
	const SEASONAL      = 0.5;

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
	 * @return array{mmbtu: int[], dollars: int[], tco2: float[], hours: int, price: float}
	 */
	public static function estimate( $in, $price ) {
		$temp  = self::clamp( $in['temp'] ?? null, self::TEMP );
		$cfm   = self::clamp( $in['cfm'] ?? null, self::CFM );
		$hours = self::clamp( $in['hours'] ?? null, self::HOURS );
		$days  = self::clamp( $in['days'] ?? null, self::DAYS );
		$uses  = is_array( $in['uses'] ?? null ) ? $in['uses'] : array();
		$price = is_numeric( $price ) && $price > 0 ? (float) $price : 0.9;

		$per_hour = 1.08 * $cfm * max( 0, $temp - self::SINK_F );
		$year     = $hours * $days * 52;

		// Space heating on its own only has a use for about half the year.
		if ( array( 'space' ) === array_values( array_intersect( $uses, array_keys( self::uses() ) ) ) ) {
			$year *= self::SEASONAL;
		}

		$out = array( 'mmbtu' => array(), 'dollars' => array(), 'tco2' => array() );

		foreach ( array( self::SHARE_LOW, self::SHARE_HIGH ) as $share ) {
			$mmbtu  = $per_hour * $share * $year / 1000000;
			$therms = $mmbtu * 10 / self::BURNER;

			$out['mmbtu'][]   = (int) self::round2( $mmbtu );
			$out['dollars'][] = (int) self::round2( $therms * $price );
			$out['tco2'][]    = round( $therms * self::KG_CO2_THERM / 1000, 1 );
		}

		$out['hours'] = (int) $year;
		$out['price'] = $price;

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

		return array( 'data' => $data, 'errors' => $errors );
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
			'Indicative estimate: ' . self::fmt( $e['mmbtu'][0] ) . '–' . self::fmt( $e['mmbtu'][1] ) . ' MMBtu/yr, $' . self::fmt( $e['dollars'][0] ) . '–$' . self::fmt( $e['dollars'][1] ) . '/yr at $' . number_format( $e['price'], 2 ) . '/therm, ' . self::fmt( $e['tco2'][0] ) . '–' . self::fmt( $e['tco2'][1] ) . ' t CO2/yr',
			'',
			'Notes: ' . ( $d['notes'] ?: 'None' ),
			'Sent from: ' . ( $d['source'] ?: 'Unknown page' ),
		);

		return implode( "\n", $lines );
	}
}
