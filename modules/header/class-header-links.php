<?php
/**
 * Mega Header link helpers.
 *
 * The pure logic behind every list of links in the header's panels, kept out
 * of the widget so it can be tested without WordPress or Elementor. See
 * tests/run.php.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Header;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns typed lines, or a WordPress menu, into panel link rows.
 *
 * A row is always `array( label, url, note, new_tab )`, whichever end it came
 * from, so the markup has one shape to render.
 */
final class Header_Links {

	/**
	 * Read a panel's typed list.
	 *
	 * The forms below all describe the same link, and the point of accepting
	 * all of them is that nobody should have to remember which one this
	 * control wants:
	 *
	 *     Waste heat recovery | /services/waste-heat | Capture the stack
	 *     Waste heat recovery | /services/waste-heat
	 *     Waste heat recovery | waste-heat-recovery     (a page slug)
	 *     Waste heat recovery | #412                    (a page ID)
	 *     /services/waste-heat | Waste heat recovery    (either way round)
	 *     /services/waste-heat                          (label from the page)
	 *     Waste heat recovery                           (a heading, no link)
	 *
	 * A slug or an ID is handed to `$resolver`, which is where WordPress turns
	 * it into a permalink and a title -- so a line can be typed the way the
	 * page is known rather than the way its URL happens to read today, and it
	 * keeps working after the page is moved. A pipe inside a label is written
	 * `\|`. A `^` on the end of the target opens it in a new tab, which is
	 * assumed anyway for a URL on somebody else's host.
	 *
	 * @param string        $raw       Textarea contents.
	 * @param callable|null $resolver  Given a slug or `#id`, returns
	 *                                 array( 'url' => string, 'label' => string ).
	 * @param string        $home_host The site's own host, for deciding what
	 *                                 counts as an outside link. Skipped when
	 *                                 empty.
	 * @return array<int, array{label: string, url: string, note: string, new_tab: bool}>
	 */
	public static function parse( $raw, $resolver = null, $home_host = '' ) {
		$rows = array();

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return $rows;
		}

		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$fields = self::fields( $line );
			$first  = isset( $fields[0] ) ? $fields[0] : '';
			$second = isset( $fields[1] ) ? $fields[1] : '';
			$note   = isset( $fields[2] ) ? $fields[2] : '';

			// Whichever of the first two fields is unmistakably a link target
			// is the link target. Only a full URL, a path, an anchor or a
			// scheme qualifies: a bare word is a label, because "Services" and
			// a slug called "services" are the same characters.
			if ( self::is_target( $first ) && ! self::is_target( $second ) ) {
				$label  = $second;
				$target = $first;
			} elseif ( 1 === count( $fields ) && self::is_slug_path( $first ) ) {
				// One field, and it is written the way a slug is written
				// rather than the way a label is. `waste-heat-recovery` is a
				// page; `Services` is a heading, even on a site with a page
				// called services -- a word on its own is the one case where
				// guessing would turn somebody's heading into a link.
				$label  = '';
				$target = $first;
			} else {
				$label  = $first;
				$target = $second;
			}

			$row = self::row( $label, $target, $note, $resolver, $home_host );

			if ( null !== $row ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Build one row, or null when there is nothing to show.
	 *
	 * @param string        $label     Label as typed, possibly empty.
	 * @param string        $target    Target as typed, possibly empty.
	 * @param string        $note      Description as typed.
	 * @param callable|null $resolver  Slug and ID resolver.
	 * @param string        $home_host The site's own host.
	 * @return array{label: string, url: string, note: string, new_tab: bool}|null
	 */
	private static function row( $label, $target, $note, $resolver, $home_host ) {
		$label   = trim( (string) $label );
		$target  = trim( (string) $target );
		$new_tab = false;

		if ( '' !== $target && '^' === substr( $target, -1 ) ) {
			$new_tab = true;
			$target  = rtrim( substr( $target, 0, -1 ) );
		}

		$url   = '';
		$title = '';

		if ( '' !== $target ) {
			if ( self::is_reference( $target ) ) {
				if ( is_callable( $resolver ) ) {
					$found = call_user_func( $resolver, $target );

					if ( is_array( $found ) ) {
						$url   = isset( $found['url'] ) ? (string) $found['url'] : '';
						$title = isset( $found['label'] ) ? (string) $found['label'] : '';
					} elseif ( is_string( $found ) ) {
						$url = $found;
					}
				}

				// A slug nothing answered to still becomes the path it reads
				// like. Dropping it would turn the line into a heading, which
				// looks deliberate and is the harder mistake to spot.
				if ( '' === $url && 0 !== strpos( $target, '#' ) ) {
					$url = '/' . ltrim( $target, '/' );
				}
			} elseif ( preg_match( '#^www\.#i', $target ) ) {
				$url = 'https://' . $target;
			} else {
				$url = $target;
			}
		}

		if ( '' === $label ) {
			$label = '' !== $title ? $title : self::label_from_target( $target );
		}

		if ( '' === $label ) {
			$label = $url;
		}

		if ( '' === $label ) {
			return null;
		}

		if ( ! $new_tab ) {
			$new_tab = self::is_outside( $url, $home_host );
		}

		return array(
			'label'   => $label,
			'url'     => $url,
			'note'    => trim( (string) $note ),
			'new_tab' => $new_tab,
		);
	}

	/**
	 * Split a line on its pipes, honouring `\|` as a literal one.
	 *
	 * @param string $line One line.
	 * @return array<int, string>
	 */
	private static function fields( $line ) {
		$parts = preg_split( '/(?<!\\\\)\|/', $line );

		if ( ! is_array( $parts ) ) {
			return array( $line );
		}

		$out = array();

		foreach ( $parts as $part ) {
			$out[] = trim( str_replace( '\|', '|', $part ) );
		}

		return $out;
	}

	/**
	 * Whether a field is a link target rather than a label.
	 *
	 * @param string $value Field.
	 * @return bool
	 */
	public static function is_target( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return false;
		}

		if ( '/' === $value[0] || '#' === $value[0] || '?' === $value[0] ) {
			return true;
		}

		return (bool) preg_match( '#^((https?:)?//|www\.|mailto:|tel:|sms:)#i', $value );
	}

	/**
	 * Whether a target is a page slug or a `#123` page ID, and so needs
	 * looking up rather than printing.
	 *
	 * @param string $value Target.
	 * @return bool
	 */
	public static function is_reference( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return false;
		}

		if ( preg_match( '/^#\d+$/', $value ) ) {
			return true;
		}

		// A slug path: words, hyphens and slashes, no scheme, no host, no
		// query. `/about/team` is a path the browser can use as it stands;
		// `about/team` is a slug this site has to be asked about.
		return (bool) preg_match( '#^[a-z0-9][a-z0-9_-]*(/[a-z0-9][a-z0-9_-]*)*/?$#i', $value );
	}

	/**
	 * Whether a field is written the way a slug is: lower-level words joined
	 * by hyphens, underscores or slashes, with no spaces. A single word does
	 * not qualify, on purpose -- see the note where this is used.
	 *
	 * @param string $value Field.
	 * @return bool
	 */
	public static function is_slug_path( $value ) {
		$value = trim( (string) $value );

		return '' !== $value && (bool) preg_match( '#^[a-z0-9]+(?:[-_/][a-z0-9]+)+/?$#i', $value );
	}

	/**
	 * A readable label for a link that was given without one.
	 *
	 * @param string $target Target as typed.
	 * @return string
	 */
	public static function label_from_target( $target ) {
		$target = trim( (string) $target );

		if ( '' === $target ) {
			return '';
		}

		if ( 0 === stripos( $target, 'mailto:' ) ) {
			return substr( $target, 7 );
		}

		if ( 0 === stripos( $target, 'tel:' ) ) {
			return substr( $target, 4 );
		}

		if ( '#' === $target[0] ) {
			$target = substr( $target, 1 );
		} else {
			$target = preg_replace( '#^(https?:)?//[^/]*#i', '', $target );
			$target = preg_replace( '/[?#].*$/', '', $target );
		}

		$target = trim( (string) $target, '/' );

		if ( '' === $target ) {
			return '';
		}

		$parts = explode( '/', $target );
		$slug  = (string) end( $parts );
		$slug  = preg_replace( '/\.[a-z0-9]{2,5}$/i', '', $slug );
		$slug  = str_replace( array( '-', '_', '+', '%20' ), ' ', $slug );
		$slug  = trim( (string) preg_replace( '/\s+/', ' ', $slug ) );

		if ( '' === $slug ) {
			return '';
		}

		return ucwords( $slug );
	}

	/**
	 * Whether a URL leaves the site, and so wants a new tab.
	 *
	 * `www.` is ignored on either side: a site reached both ways is one site,
	 * and marking its own links as outside ones is worse than missing an
	 * outside link.
	 *
	 * @param string $url       Finished URL.
	 * @param string $home_host The site's own host.
	 * @return bool
	 */
	public static function is_outside( $url, $home_host ) {
		$home_host = strtolower( trim( (string) $home_host ) );

		if ( '' === $home_host || ! preg_match( '#^((https?:)?//)#i', (string) $url ) ) {
			return false;
		}

		$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );

		if ( '' === $host ) {
			return false;
		}

		$strip = '/^www\./';

		return preg_replace( $strip, '', $host ) !== preg_replace( $strip, '', $home_host );
	}

	/**
	 * Read a panel's links out of a WordPress menu.
	 *
	 * The items arrive normalised -- id, parent, label, url, note, new_tab --
	 * so this stays testable and WordPress's objects stay in the widget.
	 *
	 * @param array<int, array<string, mixed>> $items Normalised menu items.
	 * @param string                           $ref   `menu:<id>` for a whole
	 *                                                menu's top level, or
	 *                                                `item:<menu>:<id>` for one
	 *                                                item's children.
	 * @return array<int, array{label: string, url: string, note: string, new_tab: bool}>
	 */
	public static function rows_from_menu_items( $items, $ref ) {
		$parent = self::parent_from_ref( $ref );
		$rows   = array();

		if ( null === $parent || ! is_array( $items ) ) {
			return $rows;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_parent = isset( $item['parent'] ) ? (int) $item['parent'] : 0;

			if ( $item_parent !== $parent ) {
				continue;
			}

			$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';

			if ( '' === $label ) {
				continue;
			}

			$rows[] = array(
				'label'   => $label,
				'url'     => isset( $item['url'] ) ? trim( (string) $item['url'] ) : '',
				'note'    => isset( $item['note'] ) ? trim( (string) $item['note'] ) : '',
				'new_tab' => ! empty( $item['new_tab'] ),
			);
		}

		return $rows;
	}

	/**
	 * The menu item id whose children a reference asks for. Zero means the
	 * menu's own top level; null means the reference is not one.
	 *
	 * @param string $ref Reference.
	 * @return int|null
	 */
	public static function parent_from_ref( $ref ) {
		$ref = trim( (string) $ref );

		if ( preg_match( '/^menu:(\d+)$/', $ref ) ) {
			return 0;
		}

		if ( preg_match( '/^item:(\d+):(\d+)$/', $ref, $m ) ) {
			return (int) $m[2];
		}

		return null;
	}

	/**
	 * The menu id a reference belongs to, or 0 when it has none.
	 *
	 * @param string $ref Reference.
	 * @return int
	 */
	public static function menu_from_ref( $ref ) {
		$ref = trim( (string) $ref );

		if ( preg_match( '/^(?:menu|item):(\d+)/', $ref, $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}
}
