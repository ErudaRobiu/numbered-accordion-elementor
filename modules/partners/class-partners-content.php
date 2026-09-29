<?php
/**
 * Partner Diagram content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Partners;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five cards, what they say out of the box, and how settings become markup.
 */
final class Partners_Content {

	/**
	 * The cards, in reading order. The layout is fixed -- a supplier, the
	 * system, the facility, and two partners hanging off the system -- so these
	 * are named slots rather than a repeater: a sixth card would have nowhere
	 * to go.
	 *
	 * @var string[]
	 */
	const SLOTS = array( 'source', 'core', 'site', 'partner_one', 'partner_two' );

	/**
	 * What each card says when nobody has touched it: the approved wording from
	 * the page 24 wireframe, so a freshly dropped widget is already right.
	 *
	 * Logo files are relative to the module's assets/img/ folder. An empty file
	 * on the facility card is deliberate: it falls back to the drawn factory.
	 *
	 * @return array<string, array{label: string, logo: string, name: string, title: string, text: string, width: int}>
	 */
	public static function defaults() {
		return array(
			'source'      => array(
				'label' => 'Technology card',
				'logo'  => 'enjay.svg',
				'name'  => 'Enjay Systems',
				'title' => 'Technology',
				'text'  => 'Lepido® heat exchanger · Sweden',
				'width' => 104,
			),
			'core'        => array(
				'label' => 'Core card',
				'logo'  => 'thermstar-white.png',
				'name'  => 'ThermStar',
				'title' => 'The ThermStar System',
				'text'  => 'Integration, controls, Power Intelligence',
				'width' => 128,
			),
			'site'        => array(
				'label' => 'Facility card',
				'logo'  => '',
				'name'  => '',
				'title' => 'Your facility',
				'text'  => 'Recovered heat put back into your process',
				'width' => 46,
			),
			'partner_one' => array(
				'label' => 'First partner card',
				'logo'  => 'engenuity.png',
				'name'  => 'Engenuity Systems',
				'title' => 'Monitoring platform',
				'text'  => 'eViewIoT powers ThermStar Power Intelligence',
				'width' => 112,
			),
			'partner_two' => array(
				'label' => 'Second partner card',
				'logo'  => 'edgecom.svg',
				'name'  => 'Edgecom Energy',
				'title' => 'Energy management',
				'text'  => 'AI energy optimization for industrial sites',
				'width' => 120,
			),
		);
	}

	/**
	 * The class that places a card. Both partners share one look.
	 *
	 * @param string $slot Slot.
	 * @return string
	 */
	public static function card_class( $slot ) {
		$slot = in_array( $slot, self::SLOTS, true ) ? $slot : 'source';

		if ( 0 === strpos( $slot, 'partner_' ) ) {
			return 'epdg__card epdg__card--partner epdg__card--' . str_replace( '_', '-', $slot );
		}

		return 'epdg__card epdg__card--' . $slot;
	}

	/**
	 * One card's settings, read defensively.
	 *
	 * Elementor hands back whatever was saved, which for a page built before a
	 * control existed is nothing at all -- so every field has a fallback, and a
	 * text field somebody emptied on purpose stays empty.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $slot     Slot.
	 * @return array{logo_url: string, logo_id: int, name: string, title: string, text: string, link: string, external: bool, nofollow: bool}
	 */
	public static function card( $settings, $slot ) {
		$settings = is_array( $settings ) ? $settings : array();
		$logo     = isset( $settings[ $slot . '_logo' ] ) && is_array( $settings[ $slot . '_logo' ] ) ? $settings[ $slot . '_logo' ] : array();
		$link     = isset( $settings[ $slot . '_link' ] ) && is_array( $settings[ $slot . '_link' ] ) ? $settings[ $slot . '_link' ] : array();

		return array(
			'logo_url' => isset( $logo['url'] ) && is_string( $logo['url'] ) ? trim( $logo['url'] ) : '',
			'logo_id'  => isset( $logo['id'] ) ? (int) $logo['id'] : 0,
			'name'     => self::text( $settings, $slot . '_name' ),
			'title'    => self::text( $settings, $slot . '_title' ),
			'text'     => self::text( $settings, $slot . '_text' ),
			'link'     => isset( $link['url'] ) && is_string( $link['url'] ) ? trim( $link['url'] ) : '',
			'external' => ! empty( $link['is_external'] ),
			'nofollow' => ! empty( $link['nofollow'] ),
		);
	}

	/**
	 * Classes for the root.
	 *
	 * @param mixed $blur    Switcher value: 'yes' blurs the photograph.
	 * @param mixed $animate Switcher value: 'yes' draws the diagram in.
	 * @param mixed $flow    Switcher value: 'yes' runs the heat pulse.
	 * @return string
	 */
	public static function root_classes( $blur, $animate, $flow ) {
		$classes = 'epdg';

		if ( self::on( $blur ) ) {
			$classes .= ' epdg--blur';
		}

		if ( self::on( $animate ) ) {
			$classes .= ' epdg--animate';

			// The pulse is part of the motion, so switching motion off stops
			// it too, whatever its own switch says.
			if ( self::on( $flow ) ) {
				$classes .= ' epdg--flow';
			}
		}

		return $classes;
	}

	/**
	 * The backdrop photograph as an inline style.
	 *
	 * The URL is quoted and has its quotes and backslashes stripped, because it
	 * lands inside url("...") in an attribute and a stray quote would end the
	 * declaration -- or the attribute. The caller escapes it for the attribute
	 * on top of this.
	 *
	 * @param mixed $url Picture URL.
	 * @return string Empty when there is no picture.
	 */
	public static function backdrop_style( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';

		if ( '' === $url ) {
			return '';
		}

		$url = str_replace( array( '"', "'", '\\', "\n", "\r" ), '', $url );

		return 'background-image:url("' . $url . '")';
	}

	/**
	 * Is a switcher on?
	 *
	 * @param mixed $value Switcher value.
	 * @return bool
	 */
	private static function on( $value ) {
		return 'yes' === ( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * A trimmed text setting, or an empty string.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Key.
	 * @return string
	 */
	private static function text( $settings, $key ) {
		return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
	}
}
