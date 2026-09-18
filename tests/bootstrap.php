<?php
/**
 * Test bootstrap.
 *
 * Loads the pure parts of the plugin with just enough of WordPress stubbed to
 * exercise them. There is no WordPress here and there is not meant to be: the
 * classes under test were written to keep their decision-making free of it.
 *
 * Both files below reference WordPress only inside method bodies that these
 * tests never call, so requiring them needs nothing but a defined ABSPATH.
 *
 * @package ErudaToolkit
 */

define( 'ABSPATH', __DIR__ );

/**
 * Recursive add-slashes, matching WordPress's behaviour on strings, arrays
 * and other scalars.
 *
 * @param mixed $value Value to slash.
 * @return mixed
 */
function wp_slash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_slash', $value );
	}

	if ( is_string( $value ) ) {
		return addslashes( $value );
	}

	return $value;
}

/**
 * Pass-through translation stub.
 *
 * @param string $text   Text.
 * @param string $domain Text domain.
 * @return string
 */
function __( $text, $domain = 'default' ) { // phpcs:ignore
	return $text;
}

/**
 * Escaping translation stub. Escaping is WordPress's job and is not what
 * these tests are about; the pass-through keeps assertions readable.
 *
 * @param string $text   Text.
 * @param string $domain Text domain.
 * @return string
 */
function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
	return $text;
}

/**
 * Filter stub. Returns the value untouched, which is what an unhooked filter
 * does. A test that needs a hooked filter sets $GLOBALS['eruda_test_filters'].
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Value.
 * @return mixed
 */
function apply_filters( $hook, $value ) { // phpcs:ignore
	if ( isset( $GLOBALS['eruda_test_filters'][ $hook ] ) ) {
		return call_user_func( $GLOBALS['eruda_test_filters'][ $hook ], $value );
	}

	return $value;
}

require_once dirname( __DIR__ ) . '/includes/interface-module.php';
require_once dirname( __DIR__ ) . '/includes/class-toolkit.php';
require_once dirname( __DIR__ ) . '/includes/class-panel-category.php';
require_once dirname( __DIR__ ) . '/modules/duplicator/class-duplicator.php';
require_once dirname( __DIR__ ) . '/modules/impact/class-impact-content.php';
require_once dirname( __DIR__ ) . '/modules/motion/class-motion-presets.php';
require_once dirname( __DIR__ ) . '/modules/motion/class-motion-controls.php';
require_once dirname( __DIR__ ) . '/modules/smoothscroll/class-smoothscroll-module.php';
