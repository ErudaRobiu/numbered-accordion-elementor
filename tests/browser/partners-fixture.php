<?php
/**
 * Build the Partner Diagram browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/partners-fixture.php > tests/browser/partners.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/partners/class-partners-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/partners/widgets/class-partner-diagram-widget.php';

	/**
	 * A widget that renders whatever settings it is handed.
	 */
	class Partners_Fixture extends \ErudaToolkit\Modules\Partners\Widgets\Partner_Diagram_Widget {

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

	$img = '../../modules/partners/assets/img/';

	$base = array(
		'background_image' => array( 'url' => $img . 'facility-backdrop.webp' ),
		'blur'             => 'yes',
		'animate'          => 'yes',
		'flow'             => 'yes',
		'join_label'       => 'Partners',
		'aria_label'       => 'How the partners connect: Enjay supplies Lepido to ThermStar, Engenuity and Edgecom support its monitoring and energy data, ThermStar delivers to your facility',
	);

	foreach ( \ErudaToolkit\Modules\Partners\Partners_Content::defaults() as $slot => $card ) {
		$base[ $slot . '_logo' ]  = array( 'url' => '' === $card['logo'] ? '' : $img . $card['logo'] );
		$base[ $slot . '_name' ]  = $card['name'];
		$base[ $slot . '_title' ] = $card['title'];
		$base[ $slot . '_text' ]  = $card['text'];
	}

	// One linked card, so keyboard focus has something to land on.
	$base['core_link'] = array( 'url' => '#system' );

	$still            = $base;
	$still['animate'] = '';
	$still['blur']    = '';

	$animated = new Partners_Fixture( $base );
	$static   = new Partners_Fixture( $still );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Partner Diagram — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700&family=Urbanist:wght@600;700&display=swap">
<link rel="stylesheet" href="../../modules/partners/assets/css/partner-diagram.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.sec{max-width:1240px;margin:0 auto;padding:80px 40px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.copy h2{font:400 52px/1 "Bebas Neue",Impact,sans-serif;text-transform:uppercase;margin:0 0 16px}
.spacer{height:110vh;display:grid;place-items:center;color:#8A97A1}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:1024px){.sec{grid-template-columns:1fr;padding:60px 24px}}
@media(max-width:767px){.sec{padding:40px 16px}}
/* A hostile theme, the same block the other harnesses carry. */
img{max-width:100%;height:auto}
img:hover{opacity:.75}
a:hover{color:red;text-decoration:underline}
figure{margin:0 0 40px}
</style>
</head>
<body>
<div class="spacer">Scroll down — the diagram draws in when it arrives</div>
<section class="sec">
	<div class="copy">
		<p class="marker">Animated, blurred</p>
		<h2>Trusted organizations</h2>
		<p>Every ThermStar System is built on proven technology and specialist partners who each own their part of the work.</p>
	</div>
	<div id="a"><?php echo $animated->to_html(); // phpcs:ignore ?></div>
</section>
<section class="sec">
	<div class="copy">
		<p class="marker">Still, sharp photograph</p>
	</div>
	<div id="b"><?php echo $static->to_html(); // phpcs:ignore ?></div>
</section>
<script src="../../modules/partners/assets/js/partner-diagram.js"></script>
</body>
</html>
	<?php
}
