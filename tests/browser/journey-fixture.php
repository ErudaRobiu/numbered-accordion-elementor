<?php
/**
 * Build the Journey Timeline browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/journey-fixture.php > tests/browser/journey.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/journey/class-journey-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/journey/widgets/class-journey-timeline-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Journey_Fixture extends \ErudaToolkit\Modules\Journey\Widgets\Journey_Timeline_Widget {

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
	foreach ( \ErudaToolkit\Modules\Journey\Journey_Content::default_items() as $it ) {
		$items[] = array( 'year' => $it['year'], 'title' => $it['title'], 'text' => $it['text'], 'current' => $it['current'] ? 'yes' : '' );
	}

	$base = array( 'items' => $items, 'animate' => 'yes', 'pulse' => 'yes', 'duration' => array( 'unit' => 'ms', 'size' => 1200 ), 'list_label' => 'Timeline' );

	// Rows of very different heights, a link, and no animation.
	$uneven             = $base;
	$uneven['animate']  = '';
	$uneven['items'][1]['text'] = 'Food production, industrial laundry, foundries and other high-fouling processes. A much longer description on this row, so that it wraps onto several lines and the row is far taller than its circle.';
	$uneven['items'][0]['link'] = array( 'url' => '#story' );

	$first  = new Journey_Fixture( $base, 'a1' );
	$second = new Journey_Fixture( $uneven, 'b2' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Journey Timeline — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="../../modules/journey/assets/css/journey-timeline.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:1024px){.host{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme: list bullets and margins, underlined links. */
ol{padding-left:2em;margin:1em 0}li{margin-bottom:.5em}
a{color:#c36;text-decoration:underline}
</style>
</head>
<body>
<div class="spacer">Scroll down — the line draws when the timeline arrives</div>
<div class="host">
	<div><p class="marker">Our story</p><h2 style="font:400 52px/1 'Bebas Neue';text-transform:uppercase;margin:0">From a Swedish kitchen roof to North American plants</h2></div>
	<div id="a"><?php echo $first->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<div><p class="marker">Uneven rows, a link, no animation</p></div>
	<div id="b"><?php echo $second->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/journey/assets/js/journey-timeline.js"></script>
</body>
</html>
	<?php
}
