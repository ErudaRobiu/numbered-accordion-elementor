<?php
/**
 * Build the Assessment Form browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/assess-fixture.php > tests/browser/assess.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/assess/class-assess-content.php';
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
	foreach ( array(
		'rest_url'     => 'function rest_url( $p = "" ) { return "/wp-json/" . ltrim( $p, "/" ); }',
		'checked'      => 'function checked( $a, $b = true, $echo = true ) { $r = ( (string) $a === (string) $b ) ? " checked=\'checked\'" : ""; if ( $echo ) { echo $r; } return $r; }',
		'esc_html_e'   => 'function esc_html_e( $t, $d = "default" ) { echo htmlspecialchars( $t, ENT_QUOTES, "UTF-8" ); }',
		'get_the_ID'   => 'function get_the_ID() { return 42; }',
		'sanitize_email' => 'function sanitize_email( $e ) { return trim( $e ); }',
	) as $fn => $code ) {
		if ( ! function_exists( $fn ) ) { eval( $code ); } // phpcs:ignore Squiz.PHP.Eval
	}
}

namespace ErudaToolkit\Modules\Assess {
	if ( ! class_exists( '\ErudaToolkit\Modules\Assess\Assess_Module' ) ) {
		/**
		 * Stand-in for the module's constants.
		 */
		class Assess_Module {
			const STYLE_HANDLE  = 'eas-assessment-form';
			const SCRIPT_HANDLE = 'eas-assessment-form';
			const REST_NS       = 'eruda/v1';
			const REST_ROUTE    = '/assessment';
			const WIDGET        = 'eas-assessment-form';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/assess/widgets/class-assessment-form-widget.php';

	/**
	 * Render the widget with the settings it is handed.
	 *
	 * @param array  $settings Settings.
	 * @param string $id       Element id.
	 * @return string
	 */
	function eas_fixture( $settings, $id ) {
		$w = new class() extends \ErudaToolkit\Modules\Assess\Widgets\Assessment_Form_Widget {
			/** @var array */
			public $fx = array();
			/** @var string */
			public $fid = '';
			public function __construct() {} // phpcs:ignore
			/** @return array */
			public function get_settings_for_display() { return $this->fx; }
			/** @return string */
			public function get_id() { return $this->fid; }
			/** @return string */
			public function out() { ob_start(); $this->render(); return (string) ob_get_clean(); }
		};
		$w->fx  = $settings;
		$w->fid = $id;
		return $w->out();
	}

	$base = array(
		'title'     => 'Estimate your heat recovery',
		'intro'     => 'Four numbers about your exhaust give a first estimate. Not sure? Keep the typical values and we will confirm them with you.',
		'privacy'   => 'We use your details only to follow up on this request. No engineering package needed to start.',
		'delivery'  => 'send',
		'recipient' => 'solutions@thermstar.com',
		'price'     => 0.9,
		'trigger'   => '.ts-assess a, a.ts-assess, a[href$="#request"]',
	);
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Assessment Form — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/assess/assets/css/assessment-form.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:40px}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 18px}
.row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:40px}
.elementor-button{display:inline-block;padding:12px 22px;border-radius:99px;background:#07864F;color:#fff;text-decoration:none}
@media(max-width:767px){.host{padding:20px 16px}}
/* A hostile theme: Hello Elementor's buttons, inputs and lists. */
button,[type=button],[type=submit]{display:inline-block;padding:.5rem 1rem;border:1px solid #c36;border-radius:3px;color:#c36;background:transparent;font-size:1rem}
button:hover,button:focus,[type=button]:hover,[type=submit]:hover{color:#fff;background-color:#c36}
input,select,textarea{border:2px solid #666;border-radius:0;padding:4px;font-size:12px}
label{font-weight:400;font-size:12px}ol{padding-left:2em}li{list-style:decimal}
a{color:#c36}
</style>
</head>
<body>
<div class="host">
	<p class="marker">Buttons that open the pop-up</p>
	<div class="row">
		<div class="elementor-widget ts-assess" id="btn1"><a class="elementor-button" href="/services/thermal-energy-opportunity-screen/">Request an Assessment</a></div>
		<a class="elementor-button" id="btn2" href="/services/thermal-energy-opportunity-screen/#request">Get Started Now</a>
		<a class="elementor-button" id="plain" href="#plain-target">A normal link</a>
	</div>
	<p class="marker">Inline on a page</p>
	<div id="inline"><?php echo eas_fixture( array_merge( $base, array( 'mode' => 'inline' ) ), 'inl1' ); // phpcs:ignore ?></div>
	<p id="plain-target" style="height:40vh">Plain link target</p>
</div>
<div id="modal"><?php echo eas_fixture( array_merge( $base, array( 'mode' => 'modal' ) ), 'mod1' ); // phpcs:ignore ?></div>
<script>
/* Stand-in for the page-transition module: takes every link click that is not already handled. */
window.__navigated = [];
document.addEventListener('click', function (e) {
	if (e.defaultPrevented) return;
	var a = e.target.closest && e.target.closest('a');
	if (!a || !a.href || a.getAttribute('href').charAt(0) === '#') return;
	e.preventDefault();
	window.__navigated.push(a.getAttribute('href'));
});
</script>
<script src="../../modules/assess/assets/js/assessment-form.js"></script>
</body>
</html>
	<?php
}
