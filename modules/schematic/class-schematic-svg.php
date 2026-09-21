<?php
/**
 * Inlining an SVG safely.
 *
 * An SVG referenced with <img> is loaded as a document of its own. That
 * isolation is the point of it -- but it also means the file cannot reach the
 * page's @font-face rules, so a diagram lettered in the site's typeface
 * silently falls back to Helvetica or Arial for every visitor. Measured:
 * rendering the same file through <img> with the webfont on the page and
 * without it produces byte-identical pixels, and both differ from the same
 * file inlined.
 *
 * So the markup is printed into the page instead. That gives up the browser's
 * isolation, which is exactly what this class has to replace: the file is
 * parsed, walked, and rebuilt from an allowlist before any of it reaches the
 * document.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Schematic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reduces an SVG to drawing instructions.
 */
final class Schematic_Svg {

	/**
	 * Largest file worth inlining, in bytes.
	 *
	 * Past this the markup costs more in the document than the typeface is
	 * worth, and it belongs in an <img> again.
	 */
	const MAX_BYTES = 262144;

	/**
	 * Elements that may draw.
	 *
	 * Everything absent is dropped with its subtree -- script and
	 * foreignObject most of all, since both can carry code.
	 *
	 * @var string[]
	 */
	private static $elements = array(
		'svg', 'g', 'defs', 'title', 'desc', 'symbol', 'use',
		'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
		'text', 'tspan', 'textPath',
		'marker', 'clipPath', 'mask', 'pattern',
		'linearGradient', 'radialGradient', 'stop',
		'filter', 'feGaussianBlur', 'feOffset', 'feMerge', 'feMergeNode',
		'feColorMatrix', 'feBlend', 'feFlood', 'feComposite',
	);

	/**
	 * Attributes that may survive, beyond the presentation ones matched below.
	 *
	 * @var string[]
	 */
	private static $attributes = array(
		'viewbox', 'xmlns', 'width', 'height', 'preserveaspectratio', 'class',
		'id', 'role', 'aria-labelledby', 'aria-label', 'aria-hidden',
		'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
		'd', 'points', 'transform', 'offset', 'gradientunits', 'gradienttransform',
		'patternunits', 'patterncontentunits', 'maskunits', 'clippathunits',
		'markerwidth', 'markerheight', 'markerunits', 'orient', 'refx', 'refy',
		'dx', 'dy', 'rotate', 'textlength', 'lengthadjust', 'startoffset',
		'filterunits', 'result', 'in', 'in2', 'stddeviation', 'mode', 'type', 'values',
	);

	/**
	 * Read a file and return markup safe to print, or an empty string.
	 *
	 * @param string $path  Absolute path to the file.
	 * @param string $class Class to put on the root element.
	 * @return string
	 */
	public static function inline( $path, $class = '' ) {
		if ( ! is_string( $path ) || '' === $path || ! is_readable( $path ) ) {
			return '';
		}

		if ( 'svg' !== strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
			return '';
		}

		$size = filesize( $path );

		if ( false === $size || $size > self::MAX_BYTES ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$markup = file_get_contents( $path );

		if ( false === $markup || '' === trim( $markup ) ) {
			return '';
		}

		return self::sanitise( $markup, $class );
	}

	/**
	 * Rebuild markup from the allowlist.
	 *
	 * @param string $markup Raw file contents.
	 * @param string $class  Class to put on the root element.
	 * @return string
	 */
	public static function sanitise( $markup, $class = '' ) {
		if ( ! class_exists( '\DOMDocument' ) ) {
			return '';
		}

		// loadXML() throws on an empty string rather than failing to parse it.
		if ( ! is_string( $markup ) || '' === trim( $markup ) ) {
			return '';
		}

		// An entity the parser expands is an entity that can read a file off
		// the server, so they are never expanded.
		$previous = libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		$doc->preserveWhiteSpace = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$loaded = $doc->loadXML( $markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $doc->documentElement ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			return '';
		}

		$root = $doc->documentElement; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		if ( 'svg' !== strtolower( $root->nodeName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			return '';
		}

		self::scrub( $root );

		if ( '' !== $class ) {
			$existing = trim( $root->getAttribute( 'class' ) . ' ' . $class );
			$root->setAttribute( 'class', $existing );
		}

		// width and height give the file its ratio when it is an <img>;
		// inlined they only fight the stylesheet for the size.
		$root->removeAttribute( 'width' );
		$root->removeAttribute( 'height' );

		$out = $doc->saveXML( $root );

		return is_string( $out ) ? $out : '';
	}

	/**
	 * Walk a node, dropping anything not on the allowlist.
	 *
	 * @param \DOMNode $node Node.
	 */
	private static function scrub( $node ) {
		foreach ( iterator_to_array( $node->childNodes ) as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			if ( XML_COMMENT_NODE === $child->nodeType || XML_PI_NODE === $child->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$node->removeChild( $child );
				continue;
			}

			if ( XML_ELEMENT_NODE !== $child->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				continue;
			}

			if ( ! self::allowed_element( $child->nodeName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				$node->removeChild( $child );
				continue;
			}

			self::scrub_attributes( $child );
			self::scrub( $child );
		}
	}

	/**
	 * Is this element one that draws?
	 *
	 * @param string $name Element name.
	 * @return bool
	 */
	private static function allowed_element( $name ) {
		foreach ( self::$elements as $allowed ) {
			if ( 0 === strcasecmp( $name, $allowed ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strip every attribute that is not plainly about drawing.
	 *
	 * @param \DOMElement $el Element.
	 */
	private static function scrub_attributes( $el ) {
		foreach ( iterator_to_array( $el->attributes ) as $attr ) {
			$name  = strtolower( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$value = (string) $attr->nodeValue; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

			// Every handler, however it is spelled.
			if ( 0 === strpos( $name, 'on' ) ) {
				$el->removeAttribute( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				continue;
			}

			// A reference may point inside this file and nowhere else.
			if ( 'href' === $name || 'xlink:href' === $name ) {
				if ( 0 !== strpos( trim( $value ), '#' ) ) {
					$el->removeAttribute( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				}
				continue;
			}

			if ( false !== stripos( $value, 'javascript:' ) || false !== stripos( $value, 'data:text/html' ) ) {
				$el->removeAttribute( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				continue;
			}

			if ( 'style' === $name ) {
				// url() in a style is a fetch, and a fetch is the thing being
				// prevented.
				if ( false !== stripos( $value, 'url(' ) || false !== stripos( $value, 'expression' ) ) {
					$el->removeAttribute( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				}
				continue;
			}

			if ( self::allowed_attribute( $name ) ) {
				continue;
			}

			$el->removeAttribute( $attr->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		}
	}

	/**
	 * Is this attribute about drawing?
	 *
	 * @param string $name Lowercased attribute name.
	 * @return bool
	 */
	public static function allowed_attribute( $name ) {
		if ( in_array( $name, self::$attributes, true ) ) {
			return true;
		}

		// The presentation attributes, which are many and all hyphenated
		// lowercase: fill, stroke-width, font-family, letter-spacing and so on.
		return (bool) preg_match(
			'/^(fill|stroke|font|text|letter|word|opacity|color|display|visibility|overflow|clip|mask|marker|shape|paint|vector|dominant|alignment|baseline|writing|direction|unicode|glyph|stop|flood|lighting|filter|pointer|cursor|image)(-[a-z]+)*$/',
			$name
		);
	}
}
