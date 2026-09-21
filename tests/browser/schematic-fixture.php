<?php
/**
 * Build the Flow Schematic browser fixture from the widget's own render().
 *
 * The thing worth proving here is that the PHP emits a grid whose columns line
 * up -- the connectors landing between the nodes, the return path sitting
 * under the stages it names, the monitoring bar over them. None of that can be
 * checked by reading the markup, because the markup only names columns; the
 * browser is what turns them into positions.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/schematic-fixture.php > tests/browser/schematic.html
 *
 * tests/ is excluded from the release zip, so none of this ships.
 *
 * @package ErudaToolkit
 */

namespace {

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );

require_once dirname( __DIR__ ) . '/stubs/elementor.php';

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

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( $text ) { // phpcs:ignore
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( $text ) { // phpcs:ignore
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function esc_attr__( $text, $domain = 'default' ) { // phpcs:ignore
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( $url ) { // phpcs:ignore
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

require_once dirname( __DIR__, 2 ) . '/modules/schematic/class-schematic-content.php';
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

namespace ErudaToolkit\Modules\Schematic {
	if ( ! class_exists( '\ErudaToolkit\Modules\Schematic\Schematic_Module' ) ) {
		/**
		 * Stand-in for the module, for its asset handle.
		 */
		final class Schematic_Module {
			const STYLE_HANDLE = 'efs-flow-schematic';
		}
	}
}

namespace {

	require_once dirname( __DIR__, 2 ) . '/modules/schematic/widgets/class-flow-schematic-widget.php';

	/**
	 * Everything a fixture widget needs on top of the real one.
	 */
	trait Fixture_Widget {

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
		 * Render attributes, enough for the links a node may carry.
		 *
		 * @var array
		 */
		private $attributes = array();

		/**
		 * @param string $key   Element key.
		 * @param array  $link  Link control value.
		 */
		public function add_link_attributes( $key, $link ) {
			if ( ! empty( $link['url'] ) ) {
				$this->attributes[ $key ]['href'][] = $link['url'];
			}

			if ( ! empty( $link['is_external'] ) ) {
				$this->attributes[ $key ]['target'][] = '_blank';
			}
		}

		/**
		 * @param string $key Element key.
		 */
		public function print_render_attribute_string( $key ) {
			if ( empty( $this->attributes[ $key ] ) ) {
				return;
			}

			$out = array();

			foreach ( $this->attributes[ $key ] as $name => $values ) {
				$out[] = sprintf( '%s="%s"', $name, esc_attr( implode( ' ', $values ) ) );
			}

			echo implode( ' ', $out ); // phpcs:ignore
		}

		/**
		 * Render and return, rather than print.
		 *
		 * @return string
		 */
		public function to_html() {
			ob_start();
			$this->render();

			return (string) ob_get_clean();
		}
	}

	/**
	 * The schematic, rendering whatever settings it is handed.
	 */
	class Schematic_Fixture extends \ErudaToolkit\Modules\Schematic\Widgets\Flow_Schematic_Widget {
		use Fixture_Widget;
	}

	$assets = getenv( 'EFS_FIXTURE_ASSETS' );
	$assets = false === $assets || '' === $assets ? '../../modules/schematic/assets/' : $assets;

	// The ThermStar loop: the case the widget was drawn for.
	$loop = array(
		'stages'       => array(
			array(
				'title'      => 'Lepido®',
				'eyebrow'    => 'Source unit · PRG®',
				'meta'       => 'finless · staggered tube',
				'num'        => '01',
				'variant'    => 'accent',
				'flow_label' => 'heat transfer fluid, warmed',
				'flow_style' => 'over',
				'flow_tone'  => 'flow',
				'link'       => array( 'url' => '#lepido' ),
			),
			array(
				'title'      => 'HeatCore Hx™',
				'eyebrow'    => 'Delivery unit',
				'meta'       => 'one — or several',
				'num'        => '03',
				'variant'    => 'standard',
				'flow_label' => '',
				'flow_style' => 'over',
				'flow_tone'  => 'flow',
			),
		),
		'source_show'  => 'yes',
		'source_title' => 'Process',
		'source_meta'  => 'oven · washer · fryer',
		'source_label' => 'grease · lint · soot',
		'source_style' => 'chip',
		'outs_show'    => 'yes',
		'outs'         => array(
			array( 'text' => 'Process / makeup air' ),
			array( 'text' => 'Process water' ),
			array( 'text' => 'Boiler feedwater' ),
		),
		'outs_foot'    => '…and four more',
		'ret_show'     => 'yes',
		'ret_label'    => 'returned to the source unit, cooled',
		'ret_markers'  => 'pump-box',
		'ret_from'     => 1,
		'ret_to'       => 2,
		'mon_show'     => 'yes',
		'mon_label'    => 'Power Intelligence',
		'mon_from'     => 1,
		'mon_to'       => 2,
		'div_show'     => 'yes',
		'div_label'    => 'The two fluids never mix',
		'zone_left'    => 'Contaminated side',
		'zone_right'   => 'Sealed circuit — clean side',
		'frame'        => 'yes',
		'spread'       => array( 'size' => 1.4 ),
	);

	// Three stages, no source, no targets — the other shape the grid has to
	// hold without any of the optional bands propping it up.
	$bare = array(
		'stages'      => array(
			array(
				'title'      => 'Intake',
				'meta'       => 'ambient',
				'variant'    => 'quiet',
				'flow_label' => 'raw',
				'flow_style' => 'chip',
				'flow_tone'  => 'quiet',
			),
			array(
				'title'      => 'Treatment',
				'eyebrow'    => 'Stage two',
				'variant'    => 'accent',
				'num'        => '02',
				'flow_label' => 'conditioned',
				'flow_style' => 'chip',
				'flow_tone'  => 'flow',
			),
			array(
				'title'   => 'Output',
				'meta'    => 'to the plant',
				'variant' => 'dashed',
			),
		),
		'source_show' => '',
		'outs_show'   => '',
		'ret_show'    => '',
		'mon_show'    => '',
		'div_show'    => '',
		'zone_left'   => '',
		'zone_right'  => '',
		'frame'       => 'yes',
		'spread'      => array( 'size' => 1 ),
	);

	// One stage and nothing else. The degenerate case that used to be where a
	// grid built out of spans falls apart.
	$single = array(
		'stages'      => array(
			array(
				'title'   => 'The only stage',
				'variant' => 'standard',
			),
		),
		'source_show' => 'yes',
		'source_title' => 'In',
		'source_meta' => '',
		'source_label' => '',
		'outs_show'   => 'yes',
		'outs'        => array( array( 'text' => 'Out' ) ),
		'ret_show'    => 'yes',
		'mon_show'    => 'yes',
		'mon_label'   => 'Watched',
		'div_show'    => '',
		'zone_left'   => '',
		'zone_right'  => '',
		'frame'       => '',
	);

	// The same diagram as finished artwork, which is the mode that pans.
	$art = array(
		'art'     => array( 'url' => 'schematic-art.svg' ),
		'art_alt' => 'Contaminated process exhaust enters a Lepido source unit; a sealed circuit carries the recovered energy to a HeatCore Hx delivery unit.',
		'art_min' => array(
			'unit' => 'px',
			'size' => 1100,
		),
		'frame'   => '',
	);

	$loop_widget   = new Schematic_Fixture( $loop );
	$art_widget    = new Schematic_Fixture( $art );
	$bare_widget   = new Schematic_Fixture( $bare );
	$single_widget = new Schematic_Fixture( $single );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Flow Schematic — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?php echo esc_attr( $assets ); ?>css/flow-schematic.css">
<style>
/* The kit's job, not the widget's: the family and the page surface. */
body{margin:0;background:#060c11;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif}
.host{padding:60px 40px;max-width:1600px;margin-inline:auto}
.marker{max-width:1600px;margin:0 auto;padding:0 40px;color:#4dcf8d;
  font:600 12px/1 "Plus Jakarta Sans",system-ui,sans-serif;letter-spacing:.09em;text-transform:uppercase}
/* A narrow column at a wide window — the case a media query cannot see. */
.host--column{max-width:760px;margin-inline:0}
.host--narrow{max-width:560px;margin-inline:0}
</style>
</head>
<body>

<p class="marker">The loop — every band switched on</p>
<div class="host"><?php echo $loop_widget->to_html(); // phpcs:ignore ?></div>

<p class="marker">Three stages, no source, no targets, no bands</p>
<div class="host"><?php echo $bare_widget->to_html(); // phpcs:ignore ?></div>

<p class="marker">One stage, unframed</p>
<div class="host"><?php echo $single_widget->to_html(); // phpcs:ignore ?></div>

<p class="marker">The loop again, in a 760px column</p>
<div class="host host--column"><?php echo $loop_widget->to_html(); // phpcs:ignore ?></div>

<p class="marker">Artwork mode — no frame, pans on a narrow screen</p>
<div class="host" style="--efs-art-min:1100px"><?php echo $art_widget->to_html(); // phpcs:ignore ?></div>

<p class="marker">Artwork mode in a 560px column — the slider appears</p>
<div class="host host--narrow" style="--efs-art-min:1100px"><?php echo $art_widget->to_html(); // phpcs:ignore ?></div>

<script src="<?php echo esc_attr( $assets ); ?>js/flow-schematic.js"></script>

</body>
</html>
	<?php
}
