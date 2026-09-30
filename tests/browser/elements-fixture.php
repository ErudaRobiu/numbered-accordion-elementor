<?php
/**
 * Build the Element List and Annotated Mark browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/elements-fixture.php > tests/browser/elements.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/elements/class-elements-content.php';
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

namespace ErudaToolkit\Modules\Elements {
	if ( ! class_exists( '\ErudaToolkit\Modules\Elements\Elements_Module' ) ) {
		/**
		 * Stand-in for the module's handles.
		 */
		class Elements_Module {
			const STYLE_HANDLE  = 'eel-elements';
			const SCRIPT_HANDLE = 'eel-elements';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/elements/widgets/class-element-list-widget.php';
	require_once dirname( __DIR__, 2 ) . '/modules/elements/widgets/class-annotated-mark-widget.php';

	/**
	 * Render a widget class with the settings it is handed.
	 *
	 * @param string $class    Widget class.
	 * @param array  $settings Settings.
	 * @return string
	 */
	function eel_fixture( $class, $settings ) {
		$anon = 'eel_anon_' . md5( $class );

		if ( ! class_exists( $anon ) ) {
			eval( "class {$anon} extends {$class} { public \$fx = array(); public function __construct() {} public function get_settings_for_display() { return \$this->fx; } public function out() { ob_start(); \$this->render(); return (string) ob_get_clean(); } }" ); // phpcs:ignore Squiz.PHP.Eval
		}

		$w     = new $anon();
		$w->fx = $settings;

		return $w->out();
	}

	$defs = \ErudaToolkit\Modules\Elements\Elements_Content::default_items();
	$list = array();
	$mark = array();

	foreach ( $defs as $it ) {
		$list[] = array( 'key' => $it['key'], 'icon' => $it['icon'], 'colour' => $it['colour'], 'name' => $it['name'], 'sub' => $it['sub'], 'text' => $it['text'] );
		$mark[] = array(
			'key'    => $it['key'],
			'icon'   => $it['icon'],
			'colour' => $it['colour'],
			'name'   => $it['name'],
			'corner' => $it['corner'],
			'tag_y'  => array( 'unit' => '%', 'size' => $it['tag_y'] ),
			'dot_x'  => array( 'unit' => '%', 'size' => $it['dot_x'] ),
			'dot_y'  => array( 'unit' => '%', 'size' => $it['dot_y'] ),
		);
	}

	$logo  = array( 'url' => '../../' . \ErudaToolkit\Modules\Elements\Elements_Content::LOGO );
	$l     = array( 'items' => $list, 'link_group' => 'logo-elements' );
	$m     = array( 'items' => $mark, 'link_group' => 'logo-elements', 'logo' => $logo, 'logo_alt' => 'ThermStar logo mark', 'mark_label' => 'The ThermStar mark with its four elements labelled', 'rings' => 'yes', 'glow' => 'yes', 'animate' => 'yes' );
	$lc    = array_merge( $l, array( 'cycle' => 'yes', 'link_group' => 'cycling' ) );
	$mc    = array_merge( $m, array( 'link_group' => 'cycling' ) );
	$mo    = array_merge( $m, array( 'link_group' => 'other', 'animate' => '' ) );
	$LIST  = '\ErudaToolkit\Modules\Elements\Widgets\Element_List_Widget';
	$MARK  = '\ErudaToolkit\Modules\Elements\Widgets\Annotated_Mark_Widget';

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Element List and Annotated Mark — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap">
<link rel="stylesheet" href="../../modules/elements/assets/css/elements.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.split{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
.narrow{max-width:300px}
.flexcol{display:flex;flex-direction:column;align-items:flex-start;max-width:680px;outline:1px solid #e3c4f5}
@media(max-width:1024px){.split{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme. */
ol{padding-left:2em}li{margin-bottom:.5em;list-style:disc}p{margin:0 0 1em}
figure{margin:1em 40px}em{font-style:italic}b{font-weight:900}
a{color:#c36;text-decoration:underline}img:hover{opacity:.75}img{max-width:100%}
</style>
</head>
<body>
<div class="spacer">Scroll down — the mark builds in when it arrives</div>
<div class="host split">
	<div id="a"><p class="marker">Page 28: the list</p><?php echo eel_fixture( $LIST, $l ); // phpcs:ignore ?></div>
	<div id="b"><p class="marker">Page 28: the mark</p><?php echo eel_fixture( $MARK, $m ); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Group "cycling": list steps every 3s, mark stacked under it</p>
	<div id="c"><?php echo eel_fixture( $LIST, $lc ); // phpcs:ignore ?></div>
	<div id="d"><?php echo eel_fixture( $MARK, $mc ); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Group "other", 300px, no entrance: must not react to the others</p>
	<div id="e" class="narrow"><?php echo eel_fixture( $MARK, $mo ); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">A 680px Elementor-style flex column: the mark fills it</p>
	<div class="flexcol"><div id="f" class="elementor-widget elementor-widget-eam-annotated-mark"><div class="elementor-widget-container"><?php echo eel_fixture( $MARK, $mo ); // phpcs:ignore ?></div></div></div>
</div>
<div class="spacer">End</div>
<script src="../../modules/elements/assets/js/elements.js"></script>
</body>
</html>
	<?php
}
