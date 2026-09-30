<?php
/**
 * Build the Award Wall browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/awards-fixture.php > tests/browser/awards.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/awards/class-awards-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/awards/widgets/class-award-wall-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Awards_Fixture extends \ErudaToolkit\Modules\Awards\Widgets\Award_Wall_Widget {

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
	foreach ( \ErudaToolkit\Modules\Awards\Awards_Content::default_items() as $it ) {
		$items[] = array( 'badge' => array( 'url' => '../../' . \ErudaToolkit\Modules\Awards\Awards_Content::BADGE_DIR . $it['file'] ), 'year' => $it['year'], 'name' => $it['name'], 'detail' => $it['detail'], 'dark' => $it['dark'] ? 'yes' : '' );
	}

	$base = array( 'items' => $items, 'show_lead' => 'yes', 'number_mode' => 'count', 'line' => 'international innovation awards for Lepido® technology, {years}', 'animate' => 'yes' );

	$other                  = $base;
	$other['animate']       = '';
	$other['number_mode']   = 'manual';
	$other['number_manual'] = 12;
	$other['items'][7]['link'] = array( 'url' => '#horecava' );

	$wide  = new Awards_Fixture( $base, 'a1' );
	$half  = new Awards_Fixture( $base, 'b2' );
	$extra = new Awards_Fixture( $other, 'c3' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Award Wall — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="../../modules/awards/assets/css/award-wall.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.split{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
#c{max-width:560px}
@media(max-width:1024px){.split{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme. */
ol{padding-left:2em}li{margin-bottom:.5em}p{margin:0 0 1em}
a{color:#c36;text-decoration:underline}img:hover{opacity:.75}
</style>
</head>
<body>
<div class="spacer">Scroll down — the count climbs when the wall arrives</div>
<div class="host">
	<p class="marker">Full width, under the technology tiles</p>
	<div id="a"><?php echo $wide->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host split">
	<div><p class="marker">A half column</p></div>
	<div id="b"><?php echo $half->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">A typed number, a link, no animation, 560px</p>
	<div id="c"><?php echo $extra->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/awards/assets/js/award-wall.js"></script>
</body>
</html>
	<?php
}
