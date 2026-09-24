<?php
/**
 * Build the page transitions browser fixture from the module's own output.
 *
 * Generated rather than hand-written, for the same reason the steps fixture
 * is: the markup being measured has to be the markup that ships. Here it
 * matters more than usual, because the thing under test is the relationship
 * between what the module prints into the head and what it prints into the
 * body — hand-copying either would test a fiction.
 *
 * A transition needs somewhere to go, so this writes a small site rather than
 * one page. Usage, from the repository root:
 *
 *   php tests/browser/transitions-fixture.php
 *   python3 -m http.server 8732
 *   open http://localhost:8732/tests/browser/transitions/one.html
 *
 * A server is required: file:// gives every page a null origin, and
 * sessionStorage is per origin, so the covering flag would never survive the
 * hop and every page would look like a first visit.
 *
 * tests/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

namespace {

	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
	define( 'ERUDA_PATH', dirname( __DIR__, 2 ) . '/' );
	define( 'ERUDA_URL', '../../../' );
	define( 'ERUDA_VERSION', 'fixture' );

	$GLOBALS['eruda_fixture_options'] = array();

	foreach ( array( 'esc_attr', 'esc_html', 'esc_url' ) as $fn ) {
		if ( ! function_exists( $fn ) ) {
			eval( 'function ' . $fn . '( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, "UTF-8" ); }' ); // phpcs:ignore
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		/**
		 * @param string $text   Text.
		 * @param string $domain Domain.
		 * @return string
		 */
		function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	// The module touches WordPress only through these, and every one of them
	// has an answer that means "an ordinary front-end page request".
	$stubs = array(
		'is_admin'            => 'return false;',
		'is_feed'             => 'return false;',
		'is_embed'            => 'return false;',
		'is_preview'          => 'return false;',
		'is_customize_preview' => 'return false;',
		'did_action'          => 'return 0;',
		'get_option'          => 'return array();',
		'get_post_meta'       => 'return array();',
		'add_action'          => 'return true;',
		'wp_enqueue_style'    => 'return true;',
		'wp_enqueue_script'   => 'return true;',
		'wp_add_inline_script' => 'return true;',
		'has_custom_logo'     => 'return true;',
	);

	foreach ( $stubs as $name => $body ) {
		if ( ! function_exists( $name ) ) {
			eval( 'function ' . $name . '() { ' . $body . ' }' ); // phpcs:ignore
		}
	}

	if ( ! function_exists( 'get_custom_logo' ) ) {
		/**
		 * A stand-in wordmark, wrapped the way WordPress wraps the real one so
		 * the module's unwrapping is exercised rather than bypassed.
		 *
		 * Deliberately a file rather than a data URI. A real site's logo is a
		 * media library file and therefore an HTTP request that has usually
		 * not arrived when the curtain goes up; a data URI is there instantly
		 * and hides every bug that depends on the difference. One of them
		 * shipped.
		 *
		 * @return string
		 */
		function get_custom_logo() {
			return '<a href="/" class="custom-logo-link"><img class="custom-logo" ' .
				'src="logo.svg" width="260" height="48" alt="Fixture" /></a>';
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		/**
		 * @param mixed $data Data.
		 * @return string
		 */
		function wp_json_encode( $data ) {
			return json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	require_once dirname( __DIR__, 2 ) . '/includes/interface-module.php';
	require_once dirname( __DIR__, 2 ) . '/includes/interface-configurable.php';
	require_once dirname( __DIR__, 2 ) . '/includes/class-fields.php';
	require_once dirname( __DIR__, 2 ) . '/modules/transitions/class-transitions-module.php';
}

namespace ErudaToolkit {

	/**
	 * Settings stub. The fixture writes what it wants read into a global.
	 */
	final class Settings {

		/**
		 * @param string $id Module id.
		 * @return array<string, mixed>
		 */
		public static function module_values( $id ) {
			return isset( $GLOBALS['eruda_fixture_options'] ) ? $GLOBALS['eruda_fixture_options'] : array();
		}
	}
}

namespace {

	use ErudaToolkit\Modules\Transitions\Transitions_Module;

	$out = __DIR__ . '/transitions';

	if ( ! is_dir( $out ) ) {
		mkdir( $out, 0755, true );
	}

	file_put_contents(
		$out . '/logo.svg',
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 48">' .
		'<text x="0" y="36" font-family="Helvetica,Arial" font-size="38" fill="#fff">Fixture</text>' .
		'</svg>'
	);

	/**
	 * Capture a method's echoed output.
	 *
	 * @param callable $fn Callable.
	 * @return string
	 */
	function capture( $fn ) {
		ob_start();
		$fn();
		return (string) ob_get_clean();
	}

	$pages = array(
		'one'   => array( 'Page One', '#0042ff' ),
		'two'   => array( 'Page Two', '#0042ff' ),
		'three' => array( 'Page Three', '#0042ff' ),
	);

	foreach ( $pages as $slug => $page ) {
		$GLOBALS['eruda_fixture_options'] = array( 'color' => $page[1] );

		// A fresh instance per page: the print-once guard is per request, and
		// a shared one would silently drop the curtain on pages two and three.
		$module = new Transitions_Module();

		$head    = capture( array( $module, 'head' ) );
		$curtain = capture( array( $module, 'curtain' ) );
		$options = json_encode( Transitions_Module::options() ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$links = '';

		foreach ( array_keys( $pages ) as $other ) {
			$links .= sprintf(
				'<a href="%s.html"%s>%s</a> ',
				$other,
				$other === $slug ? ' aria-current="page"' : '',
				ucfirst( $other )
			);
		}

		$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$page[0]} — transitions fixture</title>
{$head}
<link rel="stylesheet" href="../../../modules/transitions/assets/css/transitions.css">
<style>
	body { margin: 0; background: #0b0b0d; color: #f4f4f5; font: 16px/1.6 system-ui, sans-serif; }
	main { max-width: 720px; margin: 0 auto; padding: 18vh 24px 40vh; }
	h1 { font-size: clamp( 32px, 7vw, 64px ); margin: 0 0 24px; letter-spacing: -0.02em; }
	nav { display: flex; gap: 16px; margin-bottom: 40px; }
	a { color: #7aa2ff; }
	a[aria-current] { color: #f4f4f5; text-decoration: none; }
	.swatch { height: 240px; margin: 32px 0; background: linear-gradient( 120deg, #1a1a20, #26262e ); }
	.out { color: #9aa0a6; }
</style>
</head>
<body>
{$curtain}
<main>
	<nav>{$links}</nav>
	<h1>{$page[0]}</h1>
	<p>Click between the three pages. The columns should rise to cover, the
	navigation should happen underneath, and the columns should carry on
	upwards to reveal the next page — one continuous movement, never a
	reversal.</p>
	<p><a href="https://example.com" class="out">An external link</a> and
	<a href="two.html" class="no-transition">one marked no-transition</a>
	should both be left alone.</p>
	<div class="swatch"></div>
	<p>The first page of a session shows the preloader. Clear session storage
	to see it again.</p>
</main>
<script>window.erudaTransitions = { options: {$options} };</script>
<script src="../../../modules/transitions/assets/js/transitions.js"></script>
</body>
</html>
HTML;

		file_put_contents( $out . '/' . $slug . '.html', $html );
		echo 'wrote tests/browser/transitions/' . $slug . ".html\n";
	}
}
