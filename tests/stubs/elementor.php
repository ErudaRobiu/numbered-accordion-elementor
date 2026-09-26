<?php
/**
 * Just enough Elementor to load a widget class.
 *
 * Not a mock of Elementor and not trying to be. A widget file cannot even be
 * parsed without the base class it extends, and the parts of a widget worth
 * testing -- how it turns settings into markup, how it parses what someone
 * typed -- do not touch Elementor at all. These stubs exist so those parts can
 * be reached; every method here is one a widget calls at registration time,
 * and none of them are called by the tests.
 *
 * tests/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

namespace Elementor;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	/**
	 * Stand-in for the widget base class.
	 */
	class Widget_Base {

		/**
		 * Elementor passes data and args; nothing here needs either.
		 *
		 * @param array $data Element data.
		 * @param array $args Element args.
		 */
		public function __construct( $data = array(), $args = null ) {}

		/**
		 * Control registration is a no-op: the real one needs the whole
		 * controls stack, and no test asks a widget to build its panel.
		 */
		protected function register_controls() {}

		/**
		 * @param string $id   Section id.
		 * @param array  $args Section args.
		 */
		protected function start_controls_section( $id, $args = array() ) {}

		/**
		 * End of a section.
		 */
		protected function end_controls_section() {}

		/**
		 * @param string $id   Control id.
		 * @param array  $args Control args.
		 */
		protected function add_control( $id, $args = array() ) {}

		/**
		 * @param string $id   Control id.
		 * @param array  $args Control args.
		 */
		protected function add_responsive_control( $id, $args = array() ) {}

		/**
		 * @param string $type Group control type.
		 * @param array  $args Group control args.
		 */
		protected function add_group_control( $type, $args = array() ) {}

		/**
		 * @return array
		 */
		public function get_settings_for_display() {
			return array();
		}
	}
}

if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
	/**
	 * The control type constants a widget names.
	 */
	class Controls_Manager {
		const TEXT       = 'text';
		const TEXTAREA   = 'textarea';
		const NUMBER     = 'number';
		const SLIDER     = 'slider';
		const COLOR      = 'color';
		const MEDIA      = 'media';
		const URL        = 'url';
		const SELECT     = 'select';
		const SELECT2    = 'select2';
		const SWITCHER   = 'switcher';
		const HEADING    = 'heading';
		const REPEATER   = 'repeater';
		const TAB_STYLE  = 'style';
		const TAB_CONTENT = 'content';
	}
}

if ( ! class_exists( '\Elementor\Repeater' ) ) {
	/**
	 * A repeater that collects nothing.
	 */
	class Repeater {

		/**
		 * @param string $id   Control id.
		 * @param array  $args Control args.
		 */
		public function add_control( $id, $args = array() ) {}

		/**
		 * @return array
		 */
		public function get_controls() {
			return array();
		}
	}
}

if ( ! class_exists( '\Elementor\Group_Control_Typography' ) ) {
	/**
	 * Group control stand-in.
	 */
	class Group_Control_Typography {
		/**
		 * @return string
		 */
		public static function get_type() {
			return 'typography';
		}
	}
}

if ( ! class_exists( '\Elementor\Group_Control_Background' ) ) {
	/**
	 * Group control stand-in.
	 */
	class Group_Control_Background {
		/**
		 * @return string
		 */
		public static function get_type() {
			return 'background';
		}
	}
}
