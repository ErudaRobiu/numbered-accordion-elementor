<?php
/**
 * Build the industry showcase browser fixture from the widget's own render().
 *
 * Generated rather than hand-written, so the markup being measured is the
 * markup that ships.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/industry-fixture.php > tests/browser/industry.html
 *
 * A server is not required, but the pictures are: they are generated beside
 * the page as SVG files so the frame has something real to crossfade.
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

	require_once dirname( __DIR__, 2 ) . '/modules/industry/class-industry-content.php';
	require_once dirname( __DIR__, 2 ) . '/modules/industry/widgets/class-industry-showcase-widget.php';

	/**
	 * The widget with a settings array pushed into it.
	 */
	class Industry_Fixture extends \ErudaToolkit\Modules\Industry\Widgets\Industry_Showcase_Widget {

		/**
		 * @var array
		 */
		private $fixture = array();

		/**
		 * @param array $settings Settings.
		 * @return void
		 */
		public function set( $settings ) {
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
		public function draw() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}

	$dir = __DIR__ . '/industry-img';

	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0755, true );
	}

	// Six stand-in pictures, each obviously different from the others so that a
	// crossfade between them is visible in a screenshot rather than merely
	// plausible.
	$sectors = array(
		array( 'Industrial Laundry', '#1d3557', '#457b9d' ),
		array( 'Food Manufacturing', '#432818', '#bb9457' ),
		array( 'Pet Food', '#2b2d42', '#8d99ae' ),
		array( 'Foundries', '#6a040f', '#e85d04' ),
		array( 'Manufacturing', '#1b263b', '#778da9' ),
		array( 'Restaurants & Kitchens', '#14213d', '#fca311' ),
	);

	$items = array();

	foreach ( $sectors as $i => $sector ) {
		$file = 'ind-' . $i . '.svg';

		file_put_contents(
			$dir . '/' . $file,
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 800">' .
			'<rect width="600" height="800" fill="' . $sector[1] . '"/>' .
			'<circle cx="300" cy="400" r="200" fill="' . $sector[2] . '"/>' .
			'<text x="300" y="420" text-anchor="middle" font-family="Helvetica,Arial" ' .
			'font-size="44" fill="#fff">' . htmlspecialchars( $sector[0], ENT_QUOTES, 'UTF-8' ) . '</text>' .
			'</svg>'
		);

		$items[] = array(
			'name'      => $sector[0],

			// The fifth industry deliberately has no picture. The script
			// lights a picture by its industry's position, so an item that
			// renders nothing here used to shift every picture after it onto
			// the wrong industry.
			'image'     => 4 === $i ? array() : array( 'url' => 'industry-img/' . $file ),
			'heading'   => 'Heat from ' . strtolower( $sector[0] ) . ', back into the process',
			'body'      => 'Exhaust recovered into makeup air, process water or boiler feedwater, through equipment built for what that airstream actually carries.',
			'link_text' => 'Discover ' . $sector[0],
			'link'      => array( 'url' => '#' . $i ),
		);
	}

	$widget = new Industry_Fixture();
	$widget->set(
		array(
			'items'      => $items,
			'label'      => 'Current focus industries',
			'show_count' => 'yes',
			'pace'       => array( 'unit' => 'vh', 'size' => 85 ),
		)
	);

	$markup = $widget->draw();

	$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Industry showcase — fixture</title>
<link rel="stylesheet" href="../../modules/industry/assets/css/industry.css">
<style>
	/* Elementor's own reset, copied because the widget ships inside it and
	   these two declarations are the ones that fight a picture told to fill
	   its frame. `.elementor img` is a class and an element, so it outranks a
	   single class -- which is how a picture that covered perfectly here ended
	   up letterboxed on the real site. Anything styling an img in this widget
	   has to beat it. */
	.elementor img { height: auto; max-width: 100%; border: none; border-radius: 0; box-shadow: none; }

	/* A deliberately loud theme, standing in for the real one. Every rule here
	   is something themes actually do to buttons and lists, written at the
	   specificity a theme reaches for. The widget has to beat all of it: a
	   name in this list is a line of text, not a control. */
	body button,
	.elementor button {
		background: #c2185b;
		background-image: linear-gradient( #c2185b, #a01548 );
		color: #fff;
		border: 2px solid #fff;
		border-radius: 8px;
		box-shadow: 0 2px 6px rgba(0,0,0,0.4);
		padding: 10px 18px;
		min-height: 44px;
		font-family: Georgia, serif;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.12em;
	}
	body button:hover, body button:focus, .elementor button:hover {
		background: #7b1030;
		border-color: #ff0;
	}
	body ul { list-style: disc; padding-left: 40px; }
	body li { margin: 0 0 8px; }

	/* The widget draws no background of its own, so the fixture supplies the
	   dark section it is built to sit on. */
	body { margin: 0; background: #0b1220; color: #fff;
		font: 16px/1.6 "Helvetica Neue", Helvetica, Arial, sans-serif; }
	.before, .after { padding: 18vh 6vw; color: #64748b; }
	.before p, .after p { max-width: 50ch; }
</style>
</head>
<body class="elementor">
<div class="before"><p>Scroll down. The panel should pin for one screen and the
industries should step past it — names on the left lighting up in turn, the
picture settling in as it changes, the words on the right swapping under it.
Clicking a name should land on that industry rather than on its edge.</p></div>
{$markup}
<div class="after"><p>Past it. The panel should have released.</p></div>
<script src="../../modules/industry/assets/js/industry.js"></script>
</body>
</html>
HTML;

	echo $html;
}
