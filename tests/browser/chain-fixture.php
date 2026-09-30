<?php
/**
 * Build the Company Chain browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/chain-fixture.php > tests/browser/chain.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/chain/class-chain-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/chain/widgets/class-company-chain-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Chain_Fixture extends \ErudaToolkit\Modules\Chain\Widgets\Company_Chain_Widget {

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
		 * @return string
		 */
		public function to_html() {
			ob_start();
			$this->render();

			return (string) ob_get_clean();
		}
	}

	$items = array();
	foreach ( \ErudaToolkit\Modules\Chain\Chain_Content::default_items() as $it ) {
		$items[] = array( 'label' => $it['label'], 'name' => $it['name'], 'sub' => $it['sub'], 'highlight' => $it['highlight'] ? 'yes' : '' );
	}

	$base = array( 'items' => $items, 'animate' => 'yes', 'glow' => '', 'arrow_style' => 'arrow', 'stack_at' => array( 'unit' => 'px', 'size' => 520 ), 'chain_label' => 'How the companies connect' );

	$fancy                = $base;
	$fancy['animate']     = '';
	$fancy['glow']        = 'yes';
	$fancy['arrow_style'] = 'chevron';
	$fancy['items'][0]['logo'] = array( 'url' => '../../modules/partners/assets/img/enjay.svg' );
	$fancy['items'][1]['logo'] = array( 'url' => '../../modules/partners/assets/img/thermstar-white.png' );
	$fancy['items'][1]['link'] = array( 'url' => '#norrel' );

	$half   = new Chain_Fixture( $base, 'a1' );
	$narrow = new Chain_Fixture( $base, 'b2' );
	$extras = new Chain_Fixture( $fancy, 'c3' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Company Chain — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/chain/assets/css/company-chain.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.host{max-width:1240px;margin:0 auto;padding:60px 40px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
#b{max-width:480px}
@media(max-width:1024px){.host{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
a{color:#c36;text-decoration:underline}
img:hover{opacity:.75}
</style>
</head>
<body>
<div class="spacer">Scroll down — the chain builds when it arrives</div>
<div class="host">
	<div><p class="marker">The company</p><h2 style="font:700 40px/1.1 system-ui;margin:0">About Norrel Inc</h2></div>
	<div id="a"><?php echo $half->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<div><p class="marker">A 480px column — stacks</p></div>
	<div id="b"><?php echo $narrow->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<div><p class="marker">Logos, a link, glow, chevrons, no animation</p></div>
	<div id="c"><?php echo $extras->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/chain/assets/js/company-chain.js"></script>
</body>
</html>
	<?php
}
