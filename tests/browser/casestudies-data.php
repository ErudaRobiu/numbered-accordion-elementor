<?php
/**
 * The five page 25 case studies, as they are entered in the dashboard.
 *
 * One list for the browser fixture and for bin/seed-case-studies.php, so the
 * test and the site cannot drift apart. Every figure comes from the case's
 * own PDF; the confidential Pancake Factory study is deliberately absent.
 *
 * @package ErudaToolkit
 */

return array(
	array(
		'title'  => 'CWS Workwear',
		'order'  => 10,
		'sector' => 'industrial-laundry',
		'photo'  => 'case-laundry.webp',
		'alt'    => 'Rows of industrial washers in a laundry.',
		'logo'   => 'cws.png',
		'pdf'    => 'Case-Study-CWS-laundry.pdf',
		'meta'   => array(
			'cs_location'     => 'Den Bosch, Netherlands',
			'cs_status'       => 'published',
			'cs_figure'       => '63%',
			'cs_figure_label' => 'less gas on the tunnel washers',
			'cs_basis'        => 'gas per kg of laundry, before vs after · 6-month trial, 2023',
			'cs_source'       => 'Finisher exhaust',
			'cs_use'          => 'washer process water',
			'cs_link_text'    => 'Read the CWS case (PDF)',
			'cs_logo_dark'    => '0',
		),
	),
	array(
		'title'  => 'Lantmännen',
		'order'  => 20,
		'sector' => 'food-manufacturing',
		'photo'  => 'case-food.webp',
		'alt'    => 'Pancakes on a production line.',
		'logo'   => 'lantmannen.png',
		'pdf'    => 'Case-Study-Laholm-Food-production.pdf',
		'meta'   => array(
			'cs_location'     => 'Laholm, Sweden',
			'cs_status'       => 'published',
			'cs_figure'       => '99%',
			'cs_figure_label' => "of the site's heat demand covered",
			'cs_basis'        => '614,900 of 616,600 kWh/yr · 2 × Lepido L50 · 2022',
			'cs_source'       => 'Production-line exhaust with grease and soot',
			'cs_use'          => 'production-hall supply air',
			'cs_link_text'    => 'Read the Lantmännen case (PDF)',
			'cs_logo_dark'    => '0',
		),
	),
	array(
		'title'  => 'Bruzaholms',
		'order'  => 30,
		'sector' => 'foundries',
		'photo'  => 'case-foundry.webp',
		'alt'    => 'Lepido unit installed in a foundry.',
		'logo'   => 'bruzaholms.svg',
		'pdf'    => 'Case-Study-Bruzaholms-Foundry.pdf',
		'meta'   => array(
			'cs_location'     => 'Småland, Sweden',
			'cs_status'       => 'published',
			'cs_figure'       => '85%',
			'cs_figure_label' => 'of the heating need, once met by an oil boiler',
			'cs_basis'        => '95 kW recovered · first 3 months of data, 2025',
			'cs_source'       => 'Sand cooler exhaust',
			'cs_use'          => 'heat for the site',
			'cs_link_text'    => 'Read the Bruzaholms case (PDF)',
			'cs_logo_dark'    => '1',
		),
	),
	array(
		'title'  => 'Sports & Leisure Group',
		'order'  => 40,
		'sector' => 'manufacturing',
		'photo'  => 'case-polymer.webp',
		'alt'    => 'A heat recovery module in a manufacturing plant (illustrative).',
		'logo'   => 'sports-leisure.png',
		'pdf'    => '',
		'meta'   => array(
			'cs_location'    => 'Polymer production',
			'cs_status'      => 'ongoing',
			'cs_status_note' => 'Results are published when the study closes.',
			'cs_link_url'    => '/manufacturing/',
			'cs_link_text'   => 'See manufacturing applications',
			'cs_logo_dark'   => '0',
		),
	),
	array(
		'title'  => 'Burger King UK',
		'order'  => 90,
		'sector' => 'restaurants-commercial-kitchens',
		'photo'  => 'case-qsr.webp',
		'alt'    => 'Lepido unit on a restaurant rooftop.',
		'logo'   => 'burger-king.png',
		'pdf'    => 'Case-Sudy-Burger-King.pdf',
		'meta'   => array(
			'cs_location'     => 'United Kingdom',
			'cs_status'       => 'published',
			'cs_figure'       => '11–36%',
			'cs_figure_label' => 'lower electricity bill',
			'cs_basis'        => 'electric heater use vs a control site without Lepido · 2024',
			'cs_source'       => 'Kitchen exhaust',
			'cs_use'          => 'make-up air, electric heater off',
			'cs_link_text'    => 'Read the Burger King case (PDF)',
			'cs_logo_dark'    => '0',
		),
	),
);
