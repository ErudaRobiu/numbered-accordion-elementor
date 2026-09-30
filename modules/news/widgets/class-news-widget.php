<?php
/**
 * What the four news widgets share.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News\Widgets;

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panel category, assets, and reading a text setting.
 */
abstract class News_Widget extends Widget_Base {

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
		return array( \ErudaToolkit\Modules\News\News_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\News\News_Module::SCRIPT_HANDLE );
	}

	/**
	 * Settings text, trimmed, with a fallback for never-saved controls.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Key.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	protected function word( $settings, $key, $fallback ) {
		if ( ! is_array( $settings ) || ! array_key_exists( $key, $settings ) ) {
			return $fallback;
		}

		return is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
	}

	/**
	 * Is the Elementor editor showing this?
	 *
	 * @return bool
	 */
	protected function editing() {
		return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Categories, for a picker.
	 *
	 * @param string $taxonomy category or post_tag.
	 * @return array<string, string>
	 */
	protected function term_options( $taxonomy ) {
		$options = array();

		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( 'uncategorized' !== $term->slug ) {
						$options[ $term->slug ] = $term->name;
					}
				}
			}
		}

		return $options;
	}
}
