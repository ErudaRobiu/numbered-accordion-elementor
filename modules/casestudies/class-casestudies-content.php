<?php
/**
 * Case Studies content helpers.
 *
 * The pure logic, kept out of the module and the widget so it can be tested
 * without WordPress, Elementor or ACF. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\CaseStudies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The post type, the sectors, the edit form, and how a saved case becomes a
 * card.
 */
final class CaseStudies_Content {

	const POST_TYPE = 'case_study';
	const TAXONOMY  = 'case_sector';

	/**
	 * The sectors, in the order a visitor should meet them: industrial first.
	 * The slugs match the industry page URLs and the assessment form's
	 * ?sector= values, so one word joins all three.
	 *
	 * @return array<string, string> Slug => name.
	 */
	public static function sectors() {
		return array(
			'industrial-laundry'              => 'Industrial laundry',
			'food-manufacturing'              => 'Food production',
			'pet-food-manufacturing'          => 'Pet food',
			'foundries'                       => 'Foundries',
			'manufacturing'                   => 'Manufacturing',
			'restaurants-commercial-kitchens' => 'Restaurants',
		);
	}

	/**
	 * The edit form, as an ACF local field group.
	 *
	 * Defined here rather than clicked together in wp-admin, so it is the same
	 * on staging and live, ships with the plugin, and cannot be broken from
	 * the ACF screens. Field names are the post meta keys the widget reads,
	 * so the front end never needs ACF itself.
	 *
	 * Validation carries the content rules: a published result needs its
	 * basis of measurement, and there is deliberately no money field --
	 * customer economics stay private.
	 *
	 * @return array
	 */
	public static function field_group() {
		$published = array( array( array( 'field' => 'field_ecs_status', 'operator' => '==', 'value' => 'published' ) ) );
		$ongoing   = array( array( array( 'field' => 'field_ecs_status', 'operator' => '==', 'value' => 'ongoing' ) ) );

		return array(
			'key'                   => 'group_ecs_case_card',
			'title'                 => 'Case study card',
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'menu_order'            => 0,
			'active'                => true,
			'show_in_rest'          => 1,
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => self::POST_TYPE,
					),
				),
			),
			'fields'                => array(
				array(
					'key'       => 'field_ecs_help',
					'label'     => 'How this card works',
					'name'      => '',
					'type'      => 'message',
					'message'   => 'The title above is the customer\'s name. The photo is the <strong>Card photo</strong> box on the right. Cards appear in the Case Studies widget in <strong>Order</strong> (lowest first, also on the right): industrial cases first, restaurants last. Use only figures that appear in the linked PDF.',
					'new_lines' => '',
					'esc_html'  => 0,
				),
				array(
					'key'           => 'field_ecs_sector',
					'label'         => 'Sector',
					'name'          => 'cs_sector',
					'type'          => 'taxonomy',
					'instructions'  => 'Decides which filter chip shows this card, the tag on the card, and which industry form the "Estimate my site" button opens.',
					'required'      => 1,
					'taxonomy'      => self::TAXONOMY,
					'field_type'    => 'radio',
					'add_term'      => 0,
					'save_terms'    => 1,
					'load_terms'    => 1,
					'return_format' => 'id',
					'allow_null'    => 0,
				),
				array(
					'key'          => 'field_ecs_location',
					'label'        => 'Location',
					'name'         => 'cs_location',
					'type'         => 'text',
					'instructions' => 'After the sector on the tag line, e.g. "Den Bosch, Netherlands".',
					'maxlength'    => 60,
				),
				array(
					'key'           => 'field_ecs_status',
					'label'         => 'Status',
					'name'          => 'cs_status',
					'type'          => 'button_group',
					'instructions'  => 'A published case shows its headline figure. An ongoing one shows "Now measuring" instead.',
					'choices'       => array(
						'published' => 'Results published',
						'ongoing'   => 'Ongoing study',
					),
					'default_value' => 'published',
					'return_format' => 'value',
					'layout'        => 'horizontal',
				),
				array(
					'key'               => 'field_ecs_figure',
					'label'             => 'Headline figure',
					'name'              => 'cs_figure',
					'type'              => 'text',
					'instructions'      => 'The big green number, e.g. "63%" or "11–36%". It must appear in the linked PDF.',
					'required'          => 1,
					'maxlength'         => 12,
					'wrapper'           => array( 'width' => '30' ),
					'conditional_logic' => $published,
				),
				array(
					'key'               => 'field_ecs_figure_label',
					'label'             => 'What the figure means',
					'name'              => 'cs_figure_label',
					'type'              => 'text',
					'instructions'      => 'The line under the number, e.g. "less gas on the tunnel washers".',
					'required'          => 1,
					'maxlength'         => 70,
					'wrapper'           => array( 'width' => '70' ),
					'conditional_logic' => $published,
				),
				array(
					'key'               => 'field_ecs_basis',
					'label'             => 'Basis of measurement',
					'name'              => 'cs_basis',
					'type'              => 'text',
					'instructions'      => 'Required for every published figure. Shown as "Basis: …", e.g. "gas per kg of laundry, before vs after · 6-month trial, 2023". No costs or savings in money.',
					'required'          => 1,
					'maxlength'         => 110,
					'conditional_logic' => $published,
				),
				array(
					'key'               => 'field_ecs_status_note',
					'label'             => 'Note under the progress track',
					'name'              => 'cs_status_note',
					'type'              => 'text',
					'default_value'     => 'Results are published when the study closes.',
					'maxlength'         => 90,
					'conditional_logic' => $ongoing,
				),
				array(
					'key'          => 'field_ecs_source',
					'label'        => 'Heat from',
					'name'         => 'cs_source',
					'type'         => 'text',
					'instructions' => 'Left of the arrow, e.g. "Finisher exhaust". Leave both sides empty to hide the line.',
					'maxlength'    => 70,
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_ecs_use',
					'label'        => 'Heat to',
					'name'         => 'cs_use',
					'type'         => 'text',
					'instructions' => 'Right of the arrow, e.g. "washer process water".',
					'maxlength'    => 70,
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_ecs_logo',
					'label'         => 'Customer logo',
					'name'          => 'cs_logo',
					'type'          => 'image',
					'instructions'  => 'Transparent PNG or SVG. It sits on a small white badge on the photo.',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_ecs_logo_dark',
					'label'         => 'Dark logo badge',
					'name'          => 'cs_logo_dark',
					'type'          => 'true_false',
					'instructions'  => 'For a pale logo that disappears on white.',
					'ui'            => 1,
					'default_value' => 0,
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'               => 'field_ecs_pdf',
					'label'             => 'Case study PDF',
					'name'              => 'cs_pdf',
					'type'              => 'file',
					'instructions'      => 'One card, one PDF. It opens in a new tab.',
					'return_format'     => 'id',
					'library'           => 'all',
					'mime_types'        => 'pdf',
					'conditional_logic' => $published,
				),
				array(
					'key'          => 'field_ecs_link_url',
					'label'        => 'Link instead of a PDF',
					'name'         => 'cs_link_url',
					'type'         => 'url',
					'instructions' => 'Used only when there is no PDF, e.g. an ongoing study pointing at its industry page.',
				),
				array(
					'key'           => 'field_ecs_link_text',
					'label'         => 'Link text',
					'name'          => 'cs_link_text',
					'type'          => 'text',
					'instructions'  => 'e.g. "Read the CWS case (PDF)". Empty uses "Read the case (PDF)", or "Learn more" for a plain link.',
					'maxlength'     => 60,
				),
			),
		);
	}

	/**
	 * Turn one case's saved values into what the card prints.
	 *
	 * @param array $raw {
	 *     @type string $title    Post title: the customer.
	 *     @type array  $meta     Post meta, key => single value.
	 *     @type array  $sectors  Assigned sectors, each array{slug: string, name: string}.
	 *     @type string $pdf_url  URL of the attached PDF, if any.
	 * }
	 * @return array|null Null when the case has nothing a card can show.
	 */
	public static function card( $raw ) {
		$raw     = is_array( $raw ) ? $raw : array();
		$meta    = isset( $raw['meta'] ) && is_array( $raw['meta'] ) ? $raw['meta'] : array();
		$sectors = isset( $raw['sectors'] ) && is_array( $raw['sectors'] ) ? array_values( $raw['sectors'] ) : array();
		$get     = function ( $key ) use ( $meta ) {
			return isset( $meta[ $key ] ) && is_scalar( $meta[ $key ] ) ? trim( (string) $meta[ $key ] ) : '';
		};

		$status = 'ongoing' === $get( 'cs_status' ) ? 'ongoing' : 'published';
		$figure = $get( 'cs_figure' );

		// A published card is its figure. Without one there is no card, only
		// a photo and a logo claiming a result nobody can see.
		if ( 'published' === $status && '' === $figure ) {
			return null;
		}

		$pdf  = isset( $raw['pdf_url'] ) && is_string( $raw['pdf_url'] ) ? trim( $raw['pdf_url'] ) : '';
		$link = 'published' === $status && '' !== $pdf ? $pdf : $get( 'cs_link_url' );
		$tag  = array_filter( array( isset( $sectors[0]['name'] ) ? (string) $sectors[0]['name'] : '', $get( 'cs_location' ) ) );

		return array(
			'title'        => isset( $raw['title'] ) && is_scalar( $raw['title'] ) ? trim( (string) $raw['title'] ) : '',
			'status'       => $status,
			'tag'          => implode( ' · ', $tag ),
			'figure'       => $figure,
			'figure_label' => $get( 'cs_figure_label' ),
			'basis'        => $get( 'cs_basis' ),
			'status_note'  => $get( 'cs_status_note' ),
			'source'       => $get( 'cs_source' ),
			'use'          => $get( 'cs_use' ),
			'logo_id'      => (int) $get( 'cs_logo' ),
			'logo_dark'    => in_array( $get( 'cs_logo_dark' ), array( '1', 'yes', 'true' ), true ),
			'link'         => $link,
			'link_is_pdf'  => '' !== $link && $link === $pdf,
			'link_text'    => '' !== $get( 'cs_link_text' ) ? $get( 'cs_link_text' ) : ( '' !== $link && $link === $pdf ? 'Read the case (PDF)' : 'Learn more' ),
			'sectors'      => array_values( array_filter( array_map( function ( $s ) {
				return isset( $s['slug'] ) ? (string) $s['slug'] : '';
			}, $sectors ) ) ),
			'sector_names' => array_values( array_filter( array_map( function ( $s ) {
				return isset( $s['name'] ) ? (string) $s['name'] : '';
			}, $sectors ) ) ),
		);
	}

	/**
	 * The chips for a set of cards: every sector the cards use, in the order
	 * the cards first use them. The cards are in menu order, industrial first,
	 * so the chips follow.
	 *
	 * @param array $cards Built cards.
	 * @return array<string, string> Slug => name.
	 */
	public static function chips( $cards ) {
		$chips = array();

		foreach ( (array) $cards as $card ) {
			foreach ( $card['sectors'] as $i => $slug ) {
				if ( ! isset( $chips[ $slug ] ) ) {
					$chips[ $slug ] = isset( $card['sector_names'][ $i ] ) ? $card['sector_names'][ $i ] : $slug;
				}
			}
		}

		return $chips;
	}

	/**
	 * The "Estimate my site" address: the form page with the card's sector,
	 * so the form opens on the right industry.
	 *
	 * @param mixed $base  Form page URL.
	 * @param mixed $slug  Sector slug.
	 * @return string Empty when there is no form page.
	 */
	public static function estimate_url( $base, $slug ) {
		$base = is_string( $base ) ? trim( $base ) : '';
		$slug = is_string( $slug ) ? trim( $slug ) : '';

		if ( '' === $base ) {
			return '';
		}

		if ( '' === $slug ) {
			return $base;
		}

		$hash = '';

		if ( false !== strpos( $base, '#' ) ) {
			list( $base, $hash ) = explode( '#', $base, 2 );
			$hash = '#' . $hash;
		}

		return $base . ( false === strpos( $base, '?' ) ? '?' : '&' ) . 'sector=' . rawurlencode( $slug ) . $hash;
	}
}
