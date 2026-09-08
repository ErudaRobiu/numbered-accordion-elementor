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

require_once dirname( __DIR__ ) . '/includes/class-toolkit.php';
require_once dirname( __DIR__ ) . '/modules/duplicator/class-duplicator.php';
