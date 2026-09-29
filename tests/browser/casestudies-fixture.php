<?php
/**
 * Build the Case Studies browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/casestudies-fixture.php > tests/browser/casestudies.html
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

	if ( ! function_exists( 'esc_html_e' ) ) {
		/**
		 * @param string $text   Text.
		 * @param string $domain Domain.
		 */
		function esc_html_e( $text, $domain = 'default' ) { // phpcs:ignore
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

	require_once dirname( __DIR__, 2 ) . '/modules/casestudies/class-casestudies-content.php';
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

	require_once dirname( __DIR__, 2 ) . '/modules/casestudies/widgets/class-case-studies-widget.php';

	use ErudaToolkit\Modules\CaseStudies\CaseStudies_Content;

	/**
	 * The widget, handed its cases instead of querying a database.
	 */
	class CaseStudies_Fixture extends \ErudaToolkit\Modules\CaseStudies\Widgets\Case_Studies_Widget {

		/**
		 * @var array
		 */
		private $fixture = array();

		/**
		 * @var array
		 */
		private $cards = array();

		/**
		 * @param array $settings Settings.
		 * @param array $cards    Cards.
		 */
		public function __construct( $settings, $cards ) {
			$this->fixture = $settings;
			$this->cards   = $cards;
		}

		/**
		 * @return array
		 */
		public function get_settings_for_display() {
			return $this->fixture;
		}

		/**
		 * @param array $settings Settings.
		 * @return array
		 */
		protected function get_cards( $settings ) {
			$cards = $this->cards;
			$only  = isset( $settings['sectors'] ) ? (array) $settings['sectors'] : array();

			if ( $only ) {
				$cards = array_values( array_filter( $cards, function ( $c ) use ( $only ) {
					return (bool) array_intersect( $c['sectors'], $only );
				} ) );
			}

			$limit = isset( $settings['limit'] ) ? (int) $settings['limit'] : 0;

			return $limit > 0 ? array_slice( $cards, 0, $limit ) : $cards;
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

	$logos = array(
		'cws.png'            => '../../modules/logotabs/assets/logos/cws.webp',
		'lantmannen.png'     => '../../modules/logotabs/assets/logos/lantmannen.webp',
		'bruzaholms.svg'     => '../../modules/anatomy/assets/logos/bruzaholms.svg',
		'sports-leisure.png' => '../../modules/logotabs/assets/logos/sports-leisure.webp',
		'burger-king.png'    => '../../modules/logotabs/assets/logos/burger-king.webp',
	);
	$names = CaseStudies_Content::sectors();
	$cards = array();

	foreach ( require __DIR__ . '/casestudies-data.php' as $case ) {
		$card = CaseStudies_Content::card(
			array(
				'title'   => $case['title'],
				'meta'    => $case['meta'],
				'sectors' => array( array( 'slug' => $case['sector'], 'name' => $names[ $case['sector'] ] ) ),
				'pdf_url' => '' === $case['pdf'] ? '' : 'https://example.test/wp-content/uploads/' . $case['pdf'],
			)
		);

		$card['photo_id']  = 0;
		$card['photo_url'] = 'casestudies-img/' . $case['photo'];
		$card['logo_url']  = $logos[ $case['logo'] ];
		$cards[]           = $card;
	}

	$base = array(
		'show_chips'    => 'yes',
		'all_label'     => 'All',
		'show_estimate' => 'yes',
		'estimate_text' => 'Estimate my site',
		'estimate_url'  => '/request-assessment/',
	);

	$industry            = $base;
	$industry['sectors'] = array( 'foundries' );
	$industry['show_chips'] = '';

	$home          = $base;
	$home['limit'] = 3;
	$home['show_chips'] = '';

	$none                = $base;
	$none['sectors']     = array( 'pet-food-manufacturing' );
	$none['empty_text']  = 'Pet food case studies are coming soon.';

	$all       = new CaseStudies_Fixture( $base, $cards );
	$one       = new CaseStudies_Fixture( $industry, $cards );
	$three     = new CaseStudies_Fixture( $home, $cards );
	$nothing   = new CaseStudies_Fixture( $none, $cards );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Case Studies — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/casestudies/assets/css/case-studies.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
@media(max-width:767px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's button and link rules, and the usual image ones. */
[type=button],button{background-color:transparent;border:1px solid #c36;border-radius:3px;color:#c36;display:inline-block;font-size:1rem;font-weight:400;padding:.5rem 1rem;text-align:center;white-space:nowrap}
[type=button]:hover,button:hover,[type=button]:focus,button:focus{background-color:#c36;color:#fff;text-decoration:none}
a{color:#c36}a:hover{color:#336}
img{max-width:100%;height:auto}
img:hover{opacity:.75}
h3{font-size:2em;margin:1em 0}
p{margin:0 0 1em}
</style>
</head>
<body>
<div class="host">
	<p class="marker">Page 25 — every case, chips on</p>
	<div id="a"><?php echo $all->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">An industry page — Foundries only, no chips</p>
	<div id="b"><?php echo $one->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">Home page — first three</p>
	<div id="c"><?php echo $three->to_html(); // phpcs:ignore ?></div>
</div>
<div class="host">
	<p class="marker">A sector with no cases, and an empty-state line</p>
	<div id="d"><?php echo $nothing->to_html(); // phpcs:ignore ?></div>
</div>
<script src="../../modules/casestudies/assets/js/case-studies.js"></script>
</body>
</html>
	<?php
}
