<?php
/**
 * Build the Process Stepper browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/process-fixture.php > tests/browser/process.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/process/class-process-content.php';
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

namespace ErudaToolkit\Modules\Process {
	if ( ! class_exists( '\ErudaToolkit\Modules\Process\Process_Module' ) ) {
		/**
		 * Stand-in for the module's handles.
		 */
		class Process_Module {
			const STYLE_HANDLE  = 'eps-process-stepper';
			const SCRIPT_HANDLE = 'eps-process-stepper';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/process/widgets/class-process-stepper-widget.php';

	/**
	 * Render the widget with the settings it is handed.
	 *
	 * @param array  $settings Settings.
	 * @param string $id       Element id.
	 * @return string
	 */
	function eps_fixture( $settings, $id ) {
		$w     = new class() extends \ErudaToolkit\Modules\Process\Widgets\Process_Stepper_Widget {
			/** @var array */
			public $fx = array();
			/** @var string */
			public $fid = '';
			public function __construct() {} // phpcs:ignore
			/** @return array */
			public function get_settings_for_display() {
				return $this->fx;
			}
			/** @return string */
			public function get_id() {
				return $this->fid;
			}
			/** @return string */
			public function out() {
				ob_start();
				$this->render();
				return (string) ob_get_clean();
			}
		};
		$w->fx  = $settings;
		$w->fid = $id;

		return $w->out();
	}

	$items = array();

	foreach ( \ErudaToolkit\Modules\Process\Process_Content::default_items() as $it ) {
		$items[] = array( 'title' => $it['title'], 'text' => $it['text'], 'link' => array( 'url' => $it['link'] ), 'start' => $it['start'] ? 'yes' : '' );
	}

	$base = array(
		'items'          => $items,
		'list_label'     => 'How ThermStar works, step by step',
		'badge'          => 'You start here',
		'fallback_text'  => 'Request an Assessment',
		'fallback_link'  => array( 'url' => '/request-assessment/' ),
		'scroll_below'   => array( 'unit' => 'px', 'size' => 900 ),
		'vertical_below' => array( 'unit' => 'px', 'size' => 560 ),
		'animate'        => 'yes',
	);

	$five                  = $base;
	$five['items']         = array_slice( $items, 0, 5 );
	$five['items'][2]['number'] = 'A';
	$five['items'][4]['title']  = 'A deliberately long stage title that has to wrap without spilling anywhere';
	$five['fallback_text'] = '';
	$five['auto']          = 'yes';
	$five['interval']      = array( 'unit' => 's', 'size' => 2 );
	$five['initial']       = 2;
	$five['animate']       = '';

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Process Stepper — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/process/assets/css/process-stepper.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.band{background:#F5F6F7}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.split{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 44px}
.narrow{max-width:340px}
.flexcol{display:flex;flex-direction:column;align-items:flex-start}
@media(max-width:1024px){.split{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's buttons and links. */
button,[type=button]{display:inline-block;padding:.5rem 1rem;border:1px solid #c36;border-radius:3px;color:#c36;background:transparent;font-size:1rem;text-align:center}
button:hover,button:focus,[type=button]:hover,[type=button]:focus{color:#fff;background-color:#c36;text-decoration:none}
ol{padding-left:2em}li{margin-bottom:.5em;list-style:decimal}p{margin:0 0 1em}
a{color:#c36;text-decoration:underline}a:hover{color:#336}
</style>
</head>
<body>
<div class="spacer">Scroll down — the rail builds in when it arrives</div>
<div class="band">
	<div class="host">
		<p class="marker">Page 28: full width on #F5F6F7</p>
		<div class="flexcol"><div id="a" class="elementor-widget elementor-widget-eps-process-stepper"><div class="elementor-widget-container"><?php echo eps_fixture( $base, 'a1' ); // phpcs:ignore ?></div></div></div>
	</div>
</div>
<div class="host split">
	<div><p class="marker">A half column</p><div id="b"><?php echo eps_fixture( $base, 'b2' ); // phpcs:ignore ?></div></div>
	<div><p class="marker">340px: vertical</p><div id="c" class="narrow"><?php echo eps_fixture( $base, 'c3' ); // phpcs:ignore ?></div></div>
</div>
<div class="host">
	<p class="marker">Five stages, custom number, no fallback, opens on 2, auto-advance every 2s</p>
	<div id="d"><?php echo eps_fixture( $five, 'd4' ); // phpcs:ignore ?></div>
</div>
<div class="spacer">End</div>
<script src="../../modules/process/assets/js/process-stepper.js"></script>
</body>
</html>
	<?php
}
