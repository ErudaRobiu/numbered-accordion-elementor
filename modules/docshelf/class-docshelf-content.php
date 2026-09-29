<?php
/**
 * Document Shelf content helpers.
 *
 * The pure logic, kept out of the widget so it can be tested without
 * WordPress or Elementor. See tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\DocShelf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Documents post type, its filters and form, the page 26 documents it
 * was seeded with, and how a saved document becomes a card.
 */
final class DocShelf_Content {

	const POST_TYPE = 'resource_document';
	const TAXONOMY  = 'document_type';

	/**
	 * Where the bundled covers live, relative to the plugin root.
	 */
	const COVER_DIR = 'modules/docshelf/assets/covers/';

	/**
	 * The URL hash that opens a filter is this plus the filter key:
	 * /resources/#docs-perf.
	 */
	const HASH_PREFIX = 'docs-';

	/**
	 * The page 26 documents, as they were first entered: the seed for
	 * bin/seed-documents.php and the browser fixture. The site's own list is
	 * the Documents post type; this only starts it.
	 *
	 * The Pancake Factory case study (confidential footer) and the Scott
	 * Preston document (internal) are deliberately not here.
	 *
	 * @return array<int, array{key: string, cover: string, type: string, title: string, desc: string, meta: string, url: string}>
	 */
	public static function default_docs() {
		$rows = array(
			array( 'white', 'overview', 'White paper', 'Waste heat recovery for commercial and industrial facilities', 'Fundamentals, dirty-exhaust challenges, PRG and system architecture. By Scott J. Preston, CEM.', '12 pages · May 2026', '/wp-content/uploads/2026/09/ThermStar_White_Paper_FINAL.pdf' ),
			array( 'white', 'technical', 'White paper', 'ThermStar System technical white paper', 'Written for mechanical, energy and HVAC design engineers.', '5 pages', '/wp-content/uploads/2026/09/ThermStar-Technical-Whitepaper-Final.pdf' ),
			array( 'product', 'brochure', 'Product literature', 'ThermStar System brochure', 'The turnkey system at a glance: Lepido®, HeatCore™, sensors and controls.', 'Brochure', '/wp-content/uploads/2026/09/ThermStar-System-Brochure.pdf' ),
			array( 'industry', 'food', 'Industry guide', 'ThermStar System for food production', 'Oven, fryer and dryer exhaust, and where that heat can go.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Food-Production.pdf' ),
			array( 'industry', 'laundry', 'Industry guide', 'ThermStar System for industrial laundry', 'Lint-laden dryer, washer and finisher exhaust, plus the CWS result.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Industrial-Laundry.pdf' ),
			array( 'industry', 'petfood', 'Industry guide', 'ThermStar System for pet food', 'Dryer, oven and cooker exhaust in pet food plants.', 'Industry one-pager', '/wp-content/uploads/2026/09/ThermStar-System-Pet-Food.pdf' ),
			array( 'perf', '22month', 'Performance study', '22-month field validation of Lepido®', 'Kitchen exhaust, no pre-filter, no cleaning. Effectiveness above 98%.', '4 pages', '/wp-content/uploads/2026/09/22_Month_Long_Term_Lepido_White_Paper_FINAL.pdf' ),
			array( 'perf', 'cs-cws', 'Case study', 'CWS Workwear, industrial laundry', '63% less gas on the tunnel washers. Den Bosch, Netherlands.', '15 pages', '/wp-content/uploads/2026/09/Case-Study-CWS-laundry.pdf' ),
			array( 'perf', 'cs-laholm', 'Case study', 'Lantmännen, food production', "99% of the site's heat demand covered. Laholm, Sweden.", '8 pages', '/wp-content/uploads/2026/09/Case-Study-Laholm-Food-production.pdf' ),
			array( 'perf', 'cs-bruz', 'Case study', 'Bruzaholms, foundry', '85% of the heating need from sand cooler exhaust. Småland, Sweden.', '7 pages', '/wp-content/uploads/2026/09/Case-Study-Bruzaholms-Foundry.pdf' ),
			array( 'perf', 'cs-bk', 'Comparative study', 'Burger King UK, restaurants', '11–36% lower electricity bill against a control site.', '11 pages', '/wp-content/uploads/2026/09/Case-Sudy-Burger-King.pdf' ),
			array( 'company', 'customers', 'Company', 'Global installed customer base', 'Customers by sector: restaurants, food, manufacturing, laundry and hotels.', '2 pages', '/wp-content/uploads/2026/09/ThermStar-Global-Installed-Customer-Base.pdf' ),
		);

		$docs = array();

		foreach ( $rows as $row ) {
			$docs[] = array(
				'key'   => $row[0],
				'cover' => $row[1] . '.webp',
				'type'  => $row[2],
				'title' => $row[3],
				'desc'  => $row[4],
				'meta'  => $row[5],
				'url'   => $row[6],
			);
		}

		return $docs;
	}

	/**
	 * The filters, in the order a visitor should meet them. The slugs are the
	 * URL hash keys (#docs-perf), so links from other pages keep working.
	 *
	 * @return array<string, string> Slug => chip label.
	 */
	public static function filters() {
		return array(
			'white'    => 'White papers',
			'product'  => 'Product literature',
			'industry' => 'Industry guides',
			'perf'     => 'Performance evidence',
			'company'  => 'Company',
		);
	}

	/**
	 * A filter key: lowercase letters, digits and hyphens.
	 *
	 * @param mixed $text Text.
	 * @return string
	 */
	public static function key( $text ) {
		$text = is_scalar( $text ) ? strtolower( trim( (string) $text ) ) : '';

		return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $text ), '-' );
	}

	/**
	 * The edit form, as an ACF local field group.
	 *
	 * Defined in code so it is the same on staging and live and cannot be
	 * broken from the ACF screens. Field names are the post meta keys the
	 * widget reads, so the front end never needs ACF itself.
	 *
	 * @return array
	 */
	public static function field_group() {
		return array(
			'key'                   => 'group_edoc_document',
			'title'                 => 'Document',
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
					'key'       => 'field_edoc_help',
					'label'     => 'How this card works',
					'name'      => '',
					'type'      => 'message',
					'message'   => 'The title above is the card\'s title. The cover is the <strong>Cover</strong> box on the right: page 1 of the document, about 600px wide. Cards appear on the Document Shelf in <strong>Order</strong> (lowest first, also on the right).',
					'new_lines' => '',
					'esc_html'  => 0,
				),
				array(
					'key'           => 'field_edoc_filter',
					'label'         => 'Filter',
					'name'          => 'doc_filter',
					'type'          => 'taxonomy',
					'instructions'  => 'Which chip shows this document.',
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
					'key'          => 'field_edoc_type',
					'label'        => 'Type label',
					'name'         => 'doc_type',
					'type'         => 'text',
					'instructions' => 'The small green line above the title, e.g. "White paper" or "Case study".',
					'maxlength'    => 40,
					'wrapper'      => array( 'width' => '40' ),
				),
				array(
					'key'          => 'field_edoc_meta',
					'label'        => 'Meta',
					'name'         => 'doc_meta',
					'type'         => 'text',
					'instructions' => 'After the PDF badge, e.g. "12 pages · May 2026".',
					'maxlength'    => 40,
					'wrapper'      => array( 'width' => '60' ),
				),
				array(
					'key'          => 'field_edoc_desc',
					'label'        => 'Description',
					'name'         => 'doc_desc',
					'type'         => 'textarea',
					'instructions' => 'One or two short sentences.',
					'rows'         => 2,
					'maxlength'    => 160,
					'new_lines'    => '',
				),
				array(
					'key'           => 'field_edoc_file',
					'label'         => 'PDF',
					'name'          => 'doc_file',
					'type'          => 'file',
					'instructions'  => 'Upload the PDF here, or put a link in the next field instead.',
					'return_format' => 'id',
					'library'       => 'all',
					'mime_types'    => 'pdf',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_edoc_url',
					'label'        => 'Or a link',
					'name'         => 'doc_url',
					'type'         => 'url',
					'instructions' => 'e.g. a SharePoint link, so the file can be updated there. Used instead of the PDF when filled. With neither, the card shows "Coming soon".',
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_edoc_new_tab',
					'label'         => 'Open in a new tab',
					'name'          => 'doc_new_tab',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_edoc_download',
					'label'         => 'Download instead of open',
					'name'          => 'doc_download',
					'type'          => 'true_false',
					'instructions'  => 'Only works for an uploaded PDF, not a SharePoint link.',
					'ui'            => 1,
					'default_value' => 0,
					'wrapper'       => array( 'width' => '50' ),
				),
			),
		);
	}

	/**
	 * Turn one saved document into a card.
	 *
	 * @param array $raw {
	 *     @type string $title     Post title.
	 *     @type array  $meta      Post meta, key => single value.
	 *     @type array  $filters   Assigned filters, each array{slug: string, name: string}.
	 *     @type string $file_url  URL of the uploaded PDF, if any.
	 *     @type int    $cover_id  Cover attachment id.
	 *     @type string $cover_url Cover URL, when there is no attachment.
	 * }
	 * @return array|null Null when there is no title to show.
	 */
	public static function card( $raw ) {
		$raw     = is_array( $raw ) ? $raw : array();
		$meta    = isset( $raw['meta'] ) && is_array( $raw['meta'] ) ? $raw['meta'] : array();
		$filters = isset( $raw['filters'] ) && is_array( $raw['filters'] ) ? array_values( $raw['filters'] ) : array();
		$get     = function ( $key ) use ( $meta ) {
			return isset( $meta[ $key ] ) && is_scalar( $meta[ $key ] ) ? trim( (string) $meta[ $key ] ) : '';
		};
		$title   = isset( $raw['title'] ) && is_scalar( $raw['title'] ) ? trim( (string) $raw['title'] ) : '';

		if ( '' === $title ) {
			return null;
		}

		$file   = isset( $raw['file_url'] ) && is_string( $raw['file_url'] ) ? trim( $raw['file_url'] ) : '';
		$link   = $get( 'doc_url' );
		$url    = '' !== $link ? $link : $file;
		$key    = isset( $filters[0]['slug'] ) ? self::key( $filters[0]['slug'] ) : '';
		$on     = array( '1', 'yes', 'true' );

		return array(
			'key'      => '' === $key ? 'other' : $key,
			'key_name' => isset( $filters[0]['name'] ) && '' !== trim( (string) $filters[0]['name'] ) ? trim( (string) $filters[0]['name'] ) : 'Other',
			'cover'    => isset( $raw['cover_url'] ) && is_string( $raw['cover_url'] ) ? trim( $raw['cover_url'] ) : '',
			'cover_id' => isset( $raw['cover_id'] ) ? (int) $raw['cover_id'] : 0,
			'type'     => $get( 'doc_type' ),
			'title'    => $title,
			'desc'     => $get( 'doc_desc' ),
			'meta'     => $get( 'doc_meta' ),
			'url'      => $url,
			// A never-saved switch is on: the form's default.
			'new_tab'  => ! isset( $meta['doc_new_tab'] ) || in_array( $get( 'doc_new_tab' ), $on, true ),
			// The download attribute only works on this site's own file.
			'download' => in_array( $get( 'doc_download' ), $on, true ) && '' === $link && '' !== $file,
			'nofollow' => false,
		);
	}

	/**
	 * The chips: every filter the cards use, in the order they first appear.
	 * The documents are in Order, so the chips follow it, and a filter with
	 * no documents never gets a chip.
	 *
	 * @param array $docs Built cards.
	 * @return array<string, string>
	 */
	public static function chips( $docs ) {
		$chips = array();

		foreach ( (array) $docs as $doc ) {
			if ( ! isset( $chips[ $doc['key'] ] ) ) {
				$chips[ $doc['key'] ] = isset( $doc['key_name'] ) ? $doc['key_name'] : ucfirst( $doc['key'] );
			}
		}

		return $chips;
	}

	/**
	 * What a screen reader hears for a card link: the title, then "PDF" and
	 * the meta text, then that it opens a new tab when it does. The card's
	 * own text would read the type label and the description too, which is a
	 * paragraph where a name is wanted.
	 *
	 * @param array $doc Built card.
	 * @return string
	 */
	public static function link_label( $doc ) {
		$parts = array( $doc['title'], 'PDF' . ( '' !== $doc['meta'] ? ', ' . $doc['meta'] : '' ) );

		if ( $doc['new_tab'] ) {
			$parts[] = 'opens in a new tab';
		}

		return implode( ', ', $parts );
	}
}
