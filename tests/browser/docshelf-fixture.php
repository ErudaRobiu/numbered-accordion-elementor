<?php
/**
 * Build the Document Shelf browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/docshelf-fixture.php > tests/browser/docshelf.html
 *
 * tests/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

namespace {

	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

	define( 'ERUDA_URL', '../../' );

	require_once dirname( __DIR__ ) . '/stubs/elementor.php';

	foreach ( array( 'esc_html', 'esc_attr', 'esc_url' ) as $fn ) {
		if ( ! function_exists( $fn ) ) {
			eval( "function {$fn}( \$v ) { return htmlspecialchars( (string) \$v, ENT_QUOTES, 'UTF-8' ); }" ); // phpcs:ignore Squiz.PHP.Eval
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

	if ( ! function_exists( 'esc_attr_e' ) ) {
		/**
		 * @param string $text   Text.
		 * @param string $domain Domain.
		 */
		function esc_attr_e( $text, $domain = 'default' ) { // phpcs:ignore
			echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( '__' ) ) {
		/**
		 * @param string $text   Text.
		 * @param string $domain Domain.
		 * @return string
		 */
		function __( $text, $domain = 'default' ) { // phpcs:ignore
			return $text;
		}
	}

	require_once dirname( __DIR__, 2 ) . '/modules/docshelf/class-docshelf-content.php';
}

namespace ErudaToolkit {
	if ( ! class_exists( '\ErudaToolkit\Panel_Category' ) ) {
		/**
		 * Stand-in for the real category.
		 */
		class Panel_Category {
			const SLUG = 'eruda-toolkit';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/docshelf/widgets/class-document-shelf-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class DocShelf_Fixture extends \ErudaToolkit\Modules\DocShelf\Widgets\Document_Shelf_Widget {

		/**
		 * @var array
		 */
		private $fixture = array();

		/**
		 * @var string
		 */
		private $fixture_id = '';

		/**
		 * @param array  $settings Settings.
		 * @param string $id       Element id.
		 */
		public function __construct( $settings = array(), $id = 'a1' ) {
			$this->fixture    = $settings;
			$this->fixture_id = $id;
		}

		/**
		 * @return string
		 */
		public function get_id() {
			return $this->fixture_id;
		}

		/**
		 * @return array
		 */
		public function get_settings_for_display() {
			return $this->fixture;
		}

		/**
		 * The shipped document rows, exactly as the panel would start with them.
		 *
		 * @return array
		 */
		public function shipped_docs() {
			$method = new \ReflectionMethod( $this, 'default_docs' );

			return $method->invoke( $this );
		}

		/**
		 * @return string
		 */
		public function to_html() {
			ob_start();
			$this->render();

			return (string) ob_get_clean();
		}
	}

	$probe = new DocShelf_Fixture();
	$base  = array(
		'docs'          => $probe->shipped_docs(),
		'show_chips'    => 'yes',
		'show_all'      => 'yes',
		'all_label'     => 'All',
		'chip_map'      => \ErudaToolkit\Modules\DocShelf\DocShelf_Content::default_chip_map(),
		'download_text' => 'Download ↓',
		'soon_text'     => 'Coming soon',
		'badge_text'    => 'PDF',
	);

	// No "All", one link in the same tab with a download attribute, and a
	// filter key the label map does not know.
	$other                         = $base;
	$other['show_all']             = '';
	$other['docs'][0]['new_tab']   = '';
	$other['docs'][0]['download']  = 'yes';
	$other['docs'][11]['key']      = 'press kit';
	$other['docs'][2]['link']      = array( 'url' => '' );
	$other['docs'][5]['link']      = array( 'url' => '' );

	$first  = new DocShelf_Fixture( $base, 'a1' );
	$second = new DocShelf_Fixture( $other, 'b2' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Document Shelf — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/docshelf/assets/css/document-shelf.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:760px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's button and link rules, and the usual image ones. */
[type=button],button{background-color:transparent;border:1px solid #c36;border-radius:3px;color:#c36;display:inline-block;font-size:1rem;font-weight:400;padding:.5rem 1rem;text-align:center;white-space:nowrap}
[type=button]:hover,button:hover,[type=button]:focus,button:focus{background-color:#c36;color:#fff;text-decoration:none}
a{color:#c36;text-decoration:underline}a:hover{color:#336}
img{max-width:100%;height:auto}
img:hover{opacity:.75}
</style>
</head>
<body>
<div class="host" id="docs">
	<p class="marker">Page 26 — the shipped shelf</p>
	<div id="a"><?php echo $first->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">No "All", a same-tab download link, a filter with no label, two without a file</p>
	<div id="b"><?php echo $second->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/docshelf/assets/js/document-shelf.js"></script>
</body>
</html>
	<?php
}
