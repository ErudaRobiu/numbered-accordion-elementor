<?php
/**
 * Build the Image Compare browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/compare-fixture.php > tests/browser/compare.html
 *
 * tests/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

namespace {

	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

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

	require_once dirname( __DIR__, 2 ) . '/modules/compare/class-compare-content.php';
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

namespace ErudaToolkit\Modules\Compare {
	if ( ! class_exists( '\ErudaToolkit\Modules\Compare\Compare_Module' ) ) {
		/**
		 * Stand-in for the module, for its asset handles.
		 */
		final class Compare_Module {
			const STYLE_HANDLE  = 'ecmp-image-compare';
			const SCRIPT_HANDLE = 'ecmp-image-compare';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/compare/widgets/class-image-compare-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Compare_Fixture extends \ErudaToolkit\Modules\Compare\Widgets\Image_Compare_Widget {

		/**
		 * Settings to render.
		 *
		 * @var array
		 */
		private $fixture = array();

		/**
		 * @param array $settings Settings.
		 */
		public function __construct( $settings = array() ) {
			$this->fixture = $settings;
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

	$images = getenv( 'ECMP_FIXTURE_IMAGES' );
	$images = false === $images || '' === $images ? 'images/' : $images;

	$assets = getenv( 'ECMP_FIXTURE_ASSETS' );
	$assets = false === $assets || '' === $assets ? '../../modules/compare/assets/' : $assets;

	$base = array(
		'before_image'   => array( 'url' => $images . 'roof-before.webp' ),
		'after_image'    => array( 'url' => $images . 'roof-after.webp' ),
		'before_label'   => 'Before',
		'after_label'    => 'After',
		'orientation'    => 'horizontal',
		'move_on'        => 'drag',
		'start_position' => array(
			'unit' => '%',
			'size' => 50,
		),
	);

	$vertical                = $base;
	$vertical['orientation'] = 'vertical';
	$vertical['before_label'] = 'Without Lepido';
	$vertical['after_label']  = 'With Lepido';

	$edge                   = $base;
	$edge['start_position'] = array(
		'unit' => '%',
		'size' => 8,
	);

	$one_picture               = $base;
	$one_picture['after_image'] = array( 'url' => '' );

	$horizontal = new Compare_Fixture( $base );
	$upright    = new Compare_Fixture( $vertical );
	$near_edge  = new Compare_Fixture( $edge );
	$half_set   = new Compare_Fixture( $one_picture );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Image Compare — browser fixture</title>
<link rel="stylesheet" href="<?php echo esc_attr( $assets ); ?>css/image-compare.css">
<style>
body{margin:0;background:#08121C;font:16px/1.6 system-ui,sans-serif;color:#fff}
.host{max-width:900px;margin:0 auto;padding:40px 24px}
.marker{color:#4DCF8D;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
/* A hostile theme, the same block the other harnesses carry. */
img{max-width:100%;height:auto}
</style>
</head>
<body>
<div class="host">
	<p class="marker">Side to side, drag</p>
	<div id="a"><?php echo $horizontal->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Up and down</p>
	<div id="b"><?php echo $upright->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Starting at 8% — the label should step out of the way</p>
	<div id="c"><?php echo $near_edge->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Only one picture set — renders nothing</p>
	<div id="d"><?php echo $half_set->to_html(); // phpcs:ignore ?></div>
</div>
<script src="<?php echo esc_attr( $assets ); ?>js/image-compare.js"></script>
</body>
</html>
	<?php
}
