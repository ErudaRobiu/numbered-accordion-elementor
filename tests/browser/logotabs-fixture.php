<?php
/**
 * Build the Customer Logo Tabs browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/logotabs-fixture.php > tests/browser/logotabs.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/logotabs/class-logotabs-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/logotabs/widgets/class-customer-logo-tabs-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class LogoTabs_Fixture extends \ErudaToolkit\Modules\LogoTabs\Widgets\Customer_Logo_Tabs_Widget {

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
		 * The shipped logo rows, exactly as the panel would start with them.
		 *
		 * @return array
		 */
		public function shipped_logos() {
			$method = new \ReflectionMethod( $this, 'default_logos' );

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

	$probe = new LogoTabs_Fixture();
	$base  = array(
		'tabs'          => \ErudaToolkit\Modules\LogoTabs\LogoTabs_Content::default_tabs(),
		'logos'         => $probe->shipped_logos(),
		'default_tab'   => 1,
		'show_all'      => 'yes',
		'all_label'     => 'All',
		'tablist_label' => 'Customers by sector',
		'animate'       => 'yes',
	);

	// A second instance: no "All", opening on the second tab, one linked
	// logo, and one SVG the server cannot measure.
	$other                  = $base;
	$other['show_all']      = '';
	$other['default_tab']   = 2;
	$other['logos'][14]['link'] = array( 'url' => '#burger-king' );
	$other['logos'][]       = array(
		'tab'     => '3',
		'segment' => 'Hotels & venues',
		'image'   => array( 'url' => '../../modules/partners/assets/img/enjay.svg' ),
		'name'    => 'Enjay (SVG, measured by the script)',
	);

	$first  = new LogoTabs_Fixture( $base, 'a1' );
	$second = new LogoTabs_Fixture( $other, 'b2' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customer Logo Tabs — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/logotabs/assets/css/customer-logo-tabs.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's button reset, and the usual image rules. */
[type=button],button{background-color:transparent;border:1px solid #c36;border-radius:3px;color:#c36;display:inline-block;font-size:1rem;font-weight:400;padding:.5rem 1rem;text-align:center;white-space:nowrap}
[type=button]:hover,button:hover,[type=button]:focus,button:focus{background-color:#c36;color:#fff;text-decoration:none}
img{max-width:100%;height:auto}
img:hover{opacity:.75}
ul{padding-left:2em}
li{margin-bottom:.5em}
</style>
</head>
<body>
<div class="host" id="customers">
	<p class="marker">Defaults — Industrial opens, All first</p>
	<div id="a"><?php echo $first->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">No "All" chip, opens on tab 2, a linked logo, an SVG sized by the script</p>
	<div id="b"><?php echo $second->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/logotabs/assets/js/customer-logo-tabs.js"></script>
</body>
</html>
	<?php
}
