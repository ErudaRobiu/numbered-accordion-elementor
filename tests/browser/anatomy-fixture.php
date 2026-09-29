<?php
/**
 * Build the Case Anatomy browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/anatomy-fixture.php > tests/browser/anatomy.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/anatomy/class-anatomy-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/anatomy/widgets/class-case-anatomy-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Anatomy_Fixture extends \ErudaToolkit\Modules\Anatomy\Widgets\Case_Anatomy_Widget {

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
		 * The shipped project rows, exactly as the panel would start with them.
		 *
		 * @return array
		 */
		public function shipped_projects() {
			$method = new \ReflectionMethod( $this, 'default_projects' );

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

	$probe = new Anatomy_Fixture();
	$base  = array(
		'projects'      => $probe->shipped_projects(),
		'default_tab'   => 1,
		'tablist_label' => 'Choose a project',
		'highlight'     => 'yes',
		'animate'       => 'yes',
	);

	foreach ( \ErudaToolkit\Modules\Anatomy\Anatomy_Content::default_labels() as $i => $label ) {
		$base[ 'label_' . ( $i + 1 ) ] = $label;
	}

	// Four projects, opening on the third, the result not highlighted, one
	// answer missing, and a project with no logo.
	$other                              = $base;
	$other['default_tab']               = 3;
	$other['highlight']                 = '';
	$other['projects'][1]['answer_3']   = '';
	$other['projects'][]                = array(
		'name'     => 'Burger King',
		'answer_1' => 'Kitchen exhaust heat was thrown away.',
		'answer_6' => '11–36% lower electricity bill.',
	);

	$first  = new Anatomy_Fixture( $base, 'a1' );
	$second = new Anatomy_Fixture( $other, 'b2' );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Case Anatomy — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/anatomy/assets/css/case-anatomy.css">
<style>
body{margin:0;background:#fff;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:1024px){.host{grid-template-columns:1fr}}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's button reset, and the usual image rules. */
[type=button],button{background-color:transparent;border:1px solid #c36;border-radius:3px;color:#c36;display:inline-block;font-size:1rem;font-weight:400;padding:.5rem 1rem;text-align:center;white-space:nowrap}
[type=button]:hover,button:hover,[type=button]:focus,button:focus{background-color:#c36;color:#fff;text-decoration:none}
img{max-width:100%;height:auto}
img:hover{opacity:.75}
ol{padding-left:2em}
p{margin:0 0 1em}
</style>
</head>
<body>
<div class="host">
	<div><p class="marker">Evidence</p><p>Defaults — CWS opens, the result in navy.</p></div>
	<div id="a"><?php echo $first->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<div><p class="marker">Four projects, opens on the third, no highlight, one answer missing, one project without a logo</p></div>
	<div id="b"><?php echo $second->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/anatomy/assets/js/case-anatomy.js"></script>
</body>
</html>
	<?php
}
