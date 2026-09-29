<?php
/**
 * Case Anatomy content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Anatomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every case answers the same six questions; this holds them and the page 25
 * answers, and turns settings into tabs and tiles.
 */
final class Anatomy_Content {

	/**
	 * Where the bundled logos live, relative to the plugin root.
	 */
	const LOGO_DIR = 'modules/anatomy/assets/logos/';

	/**
	 * The most projects the tab row holds before the logos get too small.
	 */
	const MAX_PROJECTS = 4;

	/**
	 * How many questions each case answers. The last is the result.
	 */
	const QUESTIONS = 6;

	/**
	 * The six questions, shared by every project.
	 *
	 * @return string[]
	 */
	public static function default_labels() {
		return array(
			'The challenge',
			'Source conditions',
			'Where the heat goes',
			'The solution',
			'Operating period',
			'Approved result',
		);
	}

	/**
	 * The three page 25 projects. Every answer is taken from its case PDF.
	 *
	 * @return array<int, array{file: string, name: string, dark: bool, answers: string[], link: string, link_text: string}>
	 */
	public static function default_projects() {
		return array(
			array(
				'file'      => 'cws.webp',
				'name'      => 'CWS Workwear',
				'dark'      => false,
				'answers'   => array(
					'Hot, very moist finisher exhaust was vented straight outside.',
					'Finisher exhaust at about 80°C, heavy with moisture.',
					'Pre-heats process water for two tunnel washers.',
					'Lepido® added to the finisher exhaust.',
					'Six-month trial from early summer 2023.',
					'63% less gas per kg of laundry (0.019 → 0.007 kWh/kg).',
				),
				'link'      => 'https://norrelinc.com/wp-content/uploads/2026/03/Case-Study-CWS-laundry.pdf',
				'link_text' => 'Read the CWS case (PDF)',
			),
			array(
				'file'      => 'lantmannen.webp',
				'name'      => 'Lantmännen',
				'dark'      => false,
				'answers'   => array(
					'Clogging risk had ruled out heat recovery on the production line.',
					'9.7 m³/s at 40°C with grease, soot and moisture, 20 h a day.',
					'Supply air for the production hall, through a closed run-around loop.',
					'Two Lepido® L50 units in the exhaust duct.',
					'A 2022 project in Laholm, Sweden.',
					'614,900 kWh a year recovered, 99% of the heat demand.',
				),
				'link'      => 'https://norrelinc.com/wp-content/uploads/2026/03/Case-Study-Laholm-Food-production.pdf',
				'link_text' => 'Read the Lantmännen case (PDF)',
			),
			array(
				'file'      => 'bruzaholms.svg',
				'name'      => 'Bruzaholms',
				'dark'      => true,
				'answers'   => array(
					'Conventional exchangers clogged, needed weekly maintenance and were removed.',
					'About 4 m³/s at 30°C from the sand cooler, with particles, moisture and binders.',
					'Heat the site used to get from an oil-fired boiler.',
					'Lepido® in the sand reclamation duct, with no pre-filter.',
					'Installed spring 2025, results after three months.',
					'95 kW recovered, 85% of the heating need, about 90 t less fossil CO₂ a year.',
				),
				'link'      => 'https://norrelinc.com/wp-content/uploads/2026/03/Case-Study-Bruzaholms-Foundry.pdf',
				'link_text' => 'Read the Bruzaholms case (PDF)',
			),
		);
	}

	/**
	 * The labels as saved, one per question. An emptied label stays empty;
	 * one never saved falls back to the default.
	 *
	 * @param array $settings Widget settings.
	 * @return string[]
	 */
	public static function labels( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$defaults = self::default_labels();
		$labels   = array();

		for ( $i = 1; $i <= self::QUESTIONS; $i++ ) {
			$key      = 'label_' . $i;
			$labels[] = array_key_exists( $key, $settings ) ? self::scalar( $settings[ $key ] ) : $defaults[ $i - 1 ];
		}

		return $labels;
	}

	/**
	 * Turn the projects repeater into tabs with their tiles.
	 *
	 * A project with no logo and no name has nothing to put on its tab and is
	 * left out. A question with no answer is left out of that project, but the
	 * rest keep their numbers: 04 is always the solution, whatever is missing
	 * around it.
	 *
	 * @param mixed    $projects  Repeater value.
	 * @param string[] $labels    The six labels.
	 * @param bool     $highlight Mark the last question as the result.
	 * @return array<int, array{name: string, logo: string, logo_id: int, dark: bool, link: string, link_text: string, external: bool, nofollow: bool, tiles: array}>
	 */
	public static function build( $projects, $labels, $highlight ) {
		$projects = is_array( $projects ) ? array_values( $projects ) : array();
		$out      = array();

		foreach ( $projects as $project ) {
			// Capped after empty rows are dropped, so a blank row left in the
			// repeater does not cost a real project its tab.
			if ( count( $out ) >= self::MAX_PROJECTS ) {
				break;
			}

			$project = is_array( $project ) ? $project : array();
			$logo    = isset( $project['logo'] ) && is_array( $project['logo'] ) ? $project['logo'] : array();
			$url     = isset( $logo['url'] ) && is_string( $logo['url'] ) ? trim( $logo['url'] ) : '';
			$name    = self::text( $project, 'name' );

			if ( '' === $url && '' === $name ) {
				continue;
			}

			$tiles = array();

			for ( $i = 1; $i <= self::QUESTIONS; $i++ ) {
				$answer = self::text( $project, 'answer_' . $i );

				if ( '' === $answer ) {
					continue;
				}

				$tiles[] = array(
					'number' => sprintf( '%02d', $i ),
					'label'  => isset( $labels[ $i - 1 ] ) ? (string) $labels[ $i - 1 ] : '',
					'answer' => $answer,
					'result' => $highlight && self::QUESTIONS === $i,
				);
			}

			$link = isset( $project['link'] ) && is_array( $project['link'] ) ? $project['link'] : array();

			$out[] = array(
				'name'      => $name,
				'logo'      => $url,
				'logo_id'   => isset( $logo['id'] ) ? (int) $logo['id'] : 0,
				'dark'      => 'yes' === self::text( $project, 'dark' ),
				'link'      => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
				'link_text' => self::text( $project, 'link_text' ),
				'external'  => ! empty( $link['is_external'] ),
				'nofollow'  => ! empty( $link['nofollow'] ),
				'tiles'     => $tiles,
			);
		}

		return $out;
	}

	/**
	 * Which project opens first, as an index. Out of range lands on the first.
	 *
	 * @param mixed $value Control value, counted from one.
	 * @param int   $count How many projects there are.
	 * @return int
	 */
	public static function default_index( $value, $count ) {
		$index = is_numeric( $value ) ? (int) $value - 1 : 0;

		return $index >= 0 && $index < $count ? $index : 0;
	}

	/**
	 * A trimmed text setting, or an empty string.
	 *
	 * @param array  $row Row.
	 * @param string $key Key.
	 * @return string
	 */
	private static function text( $row, $key ) {
		return isset( $row[ $key ] ) ? self::scalar( $row[ $key ] ) : '';
	}

	/**
	 * A trimmed string from a scalar, or an empty one.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function scalar( $value ) {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
