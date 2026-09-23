<?php
/**
 * Build the data table browser fixture from the widget's own render().
 *
 * Generated rather than hand-written, so one command proves the PHP, the
 * stylesheet and the markup together -- and so the fixture cannot drift away
 * from what the widget actually emits, which is how the header fixture ended
 * up asserting a layout the widget had stopped producing.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/table-fixture.php > tests/browser/table.html
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

require_once dirname( __DIR__, 2 ) . '/modules/table/class-table-content.php';
require_once dirname( __DIR__, 2 ) . '/modules/table/widgets/class-data-table-widget.php';

/**
 * The widget with a settings array pushed into it.
 */
class Table_Fixture extends \ErudaToolkit\Modules\Table\Widgets\Data_Table_Widget {

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

/**
 * Turn a list of arrays into repeater rows.
 *
 * @param array $rows Rows.
 * @return array
 */
function rows( $rows ) {
	$out = array();

	foreach ( $rows as $row ) {
		$out[] = array(
			'cell_1' => isset( $row[0] ) ? $row[0] : '',
			'cell_2' => isset( $row[1] ) ? $row[1] : '',
			'cell_3' => isset( $row[2] ) ? $row[2] : '',
		);
	}

	return $out;
}

$widget = new Table_Fixture();

$measurement = array(
	'head_1'    => 'Measured',
	'head_2'    => 'What it tells you',
	'head_3'    => 'Source of the value',
	'show_head' => 'yes',
	'band'      => 'yes',
	'rows'      => rows(
		array(
			array( 'Source air temperature, in and out', 'Energy available, and how much was taken', 'Field sensors' ),
			array( 'Loop supply and return temperature', 'What the circuit is actually carrying', 'Field sensors' ),
			array( 'Fluid flow', 'Circulation against the design case', 'Field sensors' ),
			array( 'Equipment status and alarms', 'Whether it is running, and why not', 'Control logic' ),
			array( 'Operating time and overlap', 'Hours the source and the demand coincide', 'Control logic' ),
			array( 'Calculated thermal energy', 'The figure an incentive process asks for', 'Approved M&V method' ),
		)
	),
);

$two_column = array(
	'head_1'    => 'Stage',
	'head_2'    => 'What happens',
	'head_3'    => '',
	'show_head' => 'yes',
	'band'      => 'yes',
	'caption'   => 'Commissioning runs in four stages, each signed off before the next begins.',
	'rows'      => rows(
		array(
			array( 'Survey', 'The stack is measured over a full production week' ),
			array( 'Sizing', 'The recovery is matched to the demand it will actually meet' ),
			array( 'Install', 'Fitted in a planned shutdown, commissioned before handover' ),
			array( 'Verify', 'Output held against the design case for a full season' ),
		)
	),
);

$headless = array(
	'head_1'    => 'Model',
	'head_2'    => 'Recovery',
	'head_3'    => 'Footprint',
	'show_head' => '',
	'band'      => '',
	'rows'      => rows(
		array(
			array( 'Lepido 1200', 'Up to 60%', '1.4 m²' ),
			array( 'Lepido 2400', 'Up to 58%', '2.1 m²' ),
		)
	),
);

// A row saved while the table was three columns wide, in a table that is now
// two. The third cell must not come back as a column.
$ghost = array(
	'head_1'    => 'Measured',
	'head_2'    => 'What it tells you',
	'head_3'    => '',
	'show_head' => 'yes',
	'band'      => 'yes',
	'rows'      => rows(
		array(
			array( 'Fluid flow', 'Circulation against the design case', 'Field sensors' ),
			array( 'Equipment status', 'Whether it is running, and why not', 'Control logic' ),
		)
	),
);

?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Table fixture</title>
<link rel="stylesheet" href="../../modules/table/assets/css/data-table.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
 body{font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;margin:0;padding:48px 32px 120px;
   color:#1b3a61;background:#fff;max-width:1200px;margin-inline:auto}
 h2{font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
   color:#8ca3bc;margin:56px 0 14px}
 h2:first-of-type{margin-top:0}

 /*
  * A hostile theme, on purpose -- the same block the other fixtures carry.
  * Every one of these is something a real theme or Elementor kit ships, and
  * every one of them lands on a table.
  */
 table{width:auto!important;margin:2em 0;border-collapse:collapse}
 th,td{border:1px solid #ccc;padding:12px;background:#fafafa}
 thead th{background:#eee;color:#333}
 tr:nth-child(odd) td{background:#f0f0f0}
</style></head><body>

<h2>Three columns, banded, with a header row</h2>
<?php echo $widget->markup( $measurement ); // phpcs:ignore ?>

<h2>Two columns, with a caption</h2>
<?php echo $widget->markup( $two_column ); // phpcs:ignore ?>

<h2>No header row</h2>
<?php echo $widget->markup( $headless ); // phpcs:ignore ?>

<h2>A two-column table holding rows that were saved with three cells</h2>
<?php echo $widget->markup( $ghost ); // phpcs:ignore ?>

</body></html>
<?php
}
