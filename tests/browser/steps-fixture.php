<?php
/**
 * Build the process steps browser fixture from the widget's own render().
 *
 * Generated rather than hand-written, so the markup being measured is the
 * markup that ships. The first version of this page was hand-written, and the
 * figure markup and the widget drifted apart twice inside an hour.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/steps-fixture.php > tests/browser/steps.html
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
		eval( 'function ' . $fn . '( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, "UTF-8" ); }' ); // phpcs:ignore
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

require_once dirname( __DIR__, 2 ) . '/modules/steps/class-steps-content.php';
require_once dirname( __DIR__, 2 ) . '/modules/steps/widgets/class-process-steps-widget.php';

/**
 * The widget with a settings array pushed into it.
 */
class Steps_Fixture extends \ErudaToolkit\Modules\Steps\Widgets\Process_Steps_Widget {

	/**
	 * @var array
	 */
	public $feed = array();

	/**
	 * @return array
	 */
	public function get_settings_for_display() {
		return $this->feed;
	}

	/**
	 * @param array $settings Settings to render with.
	 * @return string
	 */
	public function markup( $settings ) {
		$this->feed = $settings;

		ob_start();
		( new \ReflectionMethod( $this, 'render' ) )->invoke( $this );

		return (string) ob_get_clean();
	}
}

$widget = new Steps_Fixture();

$main = $widget->markup( array(
	'title_tag' => 'h3',
	'steps'     => array(
		array(
			'title'  => 'Agree the method',
			'body'   => 'Which calculation, which inputs, which baseline — before anything is installed.',
			'figure' => 'converge',
		),
		array(
			'title'  => 'Instrument to it',
			'body'   => 'Sensors placed and rated for the accuracy the method assumes.',
			'figure' => 'probe',
		),
		array(
			'title'  => 'Establish the baseline',
			'body'   => 'What the facility used before, over a period the program will accept.',
			'figure' => 'datum',
			'note'   => 'Baseline',
		),
		array(
			'title'  => 'Report the period',
			'body'   => 'Operating data connected to the energy delivered, for the reporting period agreed.',
			'figure' => 'sheets',
		),
	),
) );

// The fifth figure, and a typed number that is not a number.
$extra = $widget->markup( array(
	'steps' => array(
		array(
			'title'  => 'Keep it running',
			'body'   => 'Output held against the design case for a full season, then reported again.',
			'figure' => 'cycle',
			'number' => '05',
		),
	),
) );

?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Process Steps fixture</title>
<link rel="stylesheet" href="../../modules/steps/assets/css/process-steps.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
 body{font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;margin:0;color:#1b3a61;
   /* The grey the widget has to hold up on. It carries no background of its
      own, so every bit of contrast has to come out of the figures. */
   background:#f2f4f6}
 .wrap{max-width:1180px;margin:0 auto;padding:0 64px}
 .lede{padding:96px 0 8px}
 .lede h1{font-size:clamp(2rem,4.2vw,3.25rem);line-height:1.05;margin:0 0 22px;
   font-weight:800;letter-spacing:-0.02em;text-transform:uppercase}
 .lede p{max-width:56ch;color:#55708b;font-size:1.0625rem;margin:0 0 64px}
 .after{padding:80px 0 140px;color:#8aa0b6;font-size:.875rem}
 .pad{height:70vh}
 h3{font-size:inherit}

 /* A hostile theme, on purpose -- the same block the other fixtures carry. */
 img{max-width:100%;height:auto}
 .elementor a{color:#8a8a8a !important}

 /* This is what genuinely flattens it: overflow on the scene itself. An
    ancestor with overflow:hidden does nothing, which was measured. */
 .flatten .estp__scene{overflow:hidden}

 /* And the dark case, since the widget is meant to sit on any section. */
 .dark{background:#0f2237;padding:70px 0}
 .dark .estp{--estp-ink:#eaf2f8;--estp-body:#9db6cc;--estp-face:rgba(255,255,255,0.1);
   --estp-hairline:rgba(255,255,255,0.28);--estp-shadow:rgba(0,0,0,0.5);
   --estp-key-face:rgba(77,190,126,0.16);--estp-accent:#4dbe7e;
   --estp-ring:rgba(255,255,255,0.4)}
</style></head><body class="elementor">

<div class="wrap">
 <div class="lede">
  <h1>Measurement and verification of energy savings</h1>
  <p>Recovered thermal energy is calculated from approved sensor inputs and methods. The
     methodology, sensor accuracy, baseline and reporting period should match the project
     and any applicable program rules.</p>
 </div>

 <?php echo $main; // phpcs:ignore ?>

 <div class="after">An incentive programme decides what it will accept. Agreeing the method first is what keeps the later report usable.</div>
</div>

<div class="pad"></div>

<div class="wrap"><?php echo $extra; // phpcs:ignore ?></div>

<div class="dark"><div class="wrap"><?php echo $extra; // phpcs:ignore ?></div></div>

<div class="wrap flatten"><?php echo $extra; // phpcs:ignore ?></div>

<div class="pad"></div>

<script src="../../modules/steps/assets/js/process-steps.js"></script>
</body></html>
<?php
}
