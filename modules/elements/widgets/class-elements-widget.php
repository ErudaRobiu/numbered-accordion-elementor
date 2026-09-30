<?php
/**
 * What Element List and Annotated Mark share.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Elements\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Elements\Elements_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panel category, assets, the link group, and printing an icon.
 */
abstract class Elements_Widget extends Widget_Base {

	/**
	 * Panel categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \ErudaToolkit\Panel_Category::SLUG );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Elements\Elements_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Elements\Elements_Module::SCRIPT_HANDLE );
	}

	/**
	 * The link group control.
	 */
	protected function add_link_group_control() {
		$this->add_control(
			'link_group',
			array(
				'label'       => esc_html__( 'Link group', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => Elements_Content::GROUP,
				'description' => esc_html__( 'Widgets with the same group light up together: point at "Fire" in one and "Fire" lights up in the others, wherever they sit on the page. Items match by their key.', 'numbered-accordion' ),
			)
		);
	}

	/**
	 * The fields both repeaters start with: key, icon, colour, name.
	 *
	 * @param \Elementor\Repeater $items Repeater.
	 */
	protected function add_item_basics( $items ) {
		$items->add_control(
			'key',
			array(
				'label'       => esc_html__( 'Key', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'The same key in both widgets links the two, e.g. fire.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'wind',
				'options' => array(
					'wind'   => esc_html__( 'Wind', 'numbered-accordion' ),
					'flame'  => esc_html__( 'Flame', 'numbered-accordion' ),
					'waves'  => esc_html__( 'Waves', 'numbered-accordion' ),
					'sprout' => esc_html__( 'Sprout', 'numbered-accordion' ),
					''       => esc_html__( 'None', 'numbered-accordion' ),
				),
			)
		);

		$items->add_control(
			'icon_custom',
			array(
				'label'       => esc_html__( 'Or your own icon', 'numbered-accordion' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'description' => esc_html__( 'Used instead of the one above when set. An uploaded SVG works.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'colour',
			array(
				'label' => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'  => Controls_Manager::COLOR,
			)
		);

		$items->add_control(
			'name',
			array(
				'label' => esc_html__( 'Name', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
	}

	/**
	 * Print an item's icon: the chosen Elementor icon, or a built-in one.
	 *
	 * @param array $item Built item.
	 * @return string
	 */
	protected function icon( $item ) {
		if ( $item['custom'] && class_exists( '\Elementor\Icons_Manager' ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $item['custom'], array( 'aria-hidden' => 'true' ) );
			$html = (string) ob_get_clean();

			if ( '' !== $html ) {
				return $html;
			}
		}

		$icons = Elements_Content::icons();

		return '' !== $item['icon'] && isset( $icons[ $item['icon'] ] ) ? $icons[ $item['icon'] ] : '';
	}

	/**
	 * The group name, cleaned.
	 *
	 * @param array $settings Settings.
	 * @return string
	 */
	protected function group( $settings ) {
		return Elements_Content::key( isset( $settings['link_group'] ) ? $settings['link_group'] : Elements_Content::GROUP, Elements_Content::GROUP );
	}
}
