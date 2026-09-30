<?php
/**
 * Build the News browser fixture from the widget's own render().
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/news-fixture.php > tests/browser/news.html
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

	require_once dirname( __DIR__, 2 ) . '/modules/news/class-news-content.php';
	foreach ( array( 'esc_attr_e', 'esc_html_e' ) as $fn ) {
		if ( ! function_exists( $fn ) ) {
			eval( "function {$fn}( \$t, \$d = 'default' ) { echo htmlspecialchars( (string) \$t, ENT_QUOTES, 'UTF-8' ); }" ); // phpcs:ignore Squiz.PHP.Eval
		}
	}

	if ( ! function_exists( 'apply_filters' ) ) {
		/**
		 * @param string $hook  Hook.
		 * @param mixed  $value Value.
		 * @return mixed
		 */
		function apply_filters( $hook, $value ) { // phpcs:ignore
			return $value;
		}
	}

	if ( ! function_exists( 'wp_parse_url' ) ) {
		/**
		 * @param string $url       URL.
		 * @param int    $component Component.
		 * @return mixed
		 */
		function wp_parse_url( $url, $component = -1 ) { // phpcs:ignore
			return parse_url( $url, $component ); // phpcs:ignore
		}
	}

	if ( ! function_exists( 'sanitize_title' ) ) {
		/**
		 * @param string $t Text.
		 * @return string
		 */
		function sanitize_title( $t ) { // phpcs:ignore
			return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $t ) ), '-' );
		}
	}

	require_once dirname( __DIR__, 2 ) . '/modules/news/class-news-render.php';

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

namespace ErudaToolkit\Modules\News {
	if ( ! class_exists( '\ErudaToolkit\Modules\News\News_Module' ) ) {
		/**
		 * Stand-in for the module, for its handles.
		 */
		final class News_Module {
			const STYLE_HANDLE  = 'enws-news';
			const SCRIPT_HANDLE = 'enws-news';
			const REST_NS       = 'eruda/v1';
			const REST_ROUTE    = '/news-grid';
		}
	}
}

namespace {

	use ErudaToolkit\Modules\News\News_Content;
	use ErudaToolkit\Modules\News\News_Render;

	foreach ( array( 'news-grid', 'news-carousel', 'press-quotes', 'post-source' ) as $w ) {
		require_once dirname( __DIR__, 2 ) . '/modules/news/widgets/class-' . $w . '-widget.php';
	}

	/**
	 * The four seed posts as the query hands them over: featured first.
	 *
	 * @return array
	 */
	function news_seed_cards() {
		$img  = function ( $f ) {
			return '<img src="news-img/' . $f . '.webp" alt="" loading="lazy" />';
		};
		$rows = array(
			array( 1, 'Why there is serious money in kitchen fumes', '2023-05-02 09:00:00', 'media', 'Media', array( 'nw_featured' => '1', 'nw_source_name' => 'BBC News' ), '', 'p27-bbc-article', 'On the windy roof of a Burger King in Malmö Sweden…' ),
			array( 2, 'The ROI of Waste Heat Recovery: Proven, Reliable, and Achievable in Three Years or Better', '2025-11-20 16:18:06', 'insights', 'Insights', array(), '', 'p27-post-2271', 'Across North America, industrial facilities lose enormous amounts of heat every day through hot, particulate-laden exhaust.' ),
			array( 3, 'Welcome to Norrel™: Reclaim Waste Heat. Energy Savings Delivered.', '2025-10-27 18:05:33', 'updates', 'Updates', array(), '', 'p27-post-488', 'Is your business wasting valuable energy because traditional heat recovery systems can’t handle your hot, particulate-laden, and humid exhaust?' ),
			array( 4, 'Recycling heat from kitchens to keep restaurants warm', '2023-05-02 08:00:00', 'media', 'Media', array( 'nw_source_name' => 'BBC Business Daily', 'nw_audio_quote' => 'We were looking at ways in which we could reduce our costs and become more sustainable, and this was at the forefront of what we\'d found.', 'nw_audio_credit' => 'Matt Manfield, facilities manager, Turtle Bay (UK)' ), 'news-img/silence.mp3', 'p27-bbc-podcast', '' ),
		);
		$out  = array();

		foreach ( $rows as $r ) {
			$out[] = News_Content::card(
				array(
					'id'        => $r[0],
					'title'     => $r[1],
					'url'       => '#post-' . $r[0],
					'date'      => $r[2],
					'excerpt'   => $r[8],
					'cats'      => array( array( 'slug' => $r[3], 'name' => $r[4] ) ),
					'meta'      => $r[5],
					'audio_url' => $r[6],
					'image'     => $img( $r[7] ),
				)
			);
		}

		return $out;
	}

	/**
	 * The grid, handed its first page.
	 */
	class News_Grid_Fixture extends \ErudaToolkit\Modules\News\Widgets\News_Grid_Widget {
		/** @var array */
		private $s;
		/** @var array */
		private $page;
		/** @var string */
		private $rest;

		/**
		 * @param array  $s    Settings.
		 * @param array  $page First page.
		 * @param string $rest Load more URL.
		 */
		public function __construct( $s, $page, $rest = '' ) {
			$this->s    = $s;
			$this->page = $page;
			$this->rest = $rest;
		}

		/** @return array */
		public function get_settings_for_display() {
			return $this->s;
		}

		/**
		 * @param array $settings Settings.
		 * @return array
		 */
		protected function scope( $settings ) {
			return array( 'cats' => array(), 'tags' => array(), 'archive' => false );
		}

		/**
		 * @param array $settings Settings.
		 * @param array $scope    Scope.
		 * @return array
		 */
		protected function first_page( $settings, $scope ) {
			return $this->page;
		}

		/**
		 * @param array $scope Scope.
		 * @return array
		 */
		protected function chip_terms( $scope ) {
			return array( array( 'slug' => 'media', 'name' => 'Media' ), array( 'slug' => 'updates', 'name' => 'Updates' ), array( 'slug' => 'insights', 'name' => 'Insights' ) );
		}

		/** @return string */
		protected function rest_url() {
			return $this->rest;
		}

		/** @return string */
		public function html() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}

	/**
	 * The carousel, handed its cards.
	 */
	class News_Carousel_Fixture extends \ErudaToolkit\Modules\News\Widgets\News_Carousel_Widget {
		/** @var array */
		private $s;
		/** @var array */
		private $c;

		/**
		 * @param array $s Settings.
		 * @param array $c Cards.
		 */
		public function __construct( $s, $c ) {
			$this->s = $s;
			$this->c = $c;
		}

		/** @return array */
		public function get_settings_for_display() {
			return $this->s;
		}

		/**
		 * @param array $settings Settings.
		 * @return array
		 */
		protected function cards( $settings ) {
			return $this->c;
		}

		/** @return string */
		public function html() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}

	/**
	 * Press quotes with their defaults.
	 */
	class Press_Fixture extends \ErudaToolkit\Modules\News\Widgets\Press_Quotes_Widget {
		/** @return array */
		public function get_settings_for_display() {
			return array( 'quotes' => self::default_quotes() );
		}

		/** @return string */
		public function html() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}

	/**
	 * The source box, handed a post.
	 */
	class Source_Fixture extends \ErudaToolkit\Modules\News\Widgets\Post_Source_Widget {
		/** @var array */
		private $b;

		/**
		 * @param array $b Box.
		 */
		public function __construct( $b ) {
			$this->b = $b;
		}

		/** @return array */
		public function get_settings_for_display() {
			return array();
		}

		/** @return array */
		protected function box() {
			return $this->b;
		}

		/** @return string */
		public function html() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}

	$seed = news_seed_cards();

	// A second archive with more posts than one load, to prove Load more:
	// the first three, then the next three from "the server".
	$extra = array();
	foreach ( array( 5, 6, 7 ) as $n ) {
		$c           = $seed[2];
		$c['id']     = $n;
		$c['title']  = 'Older update ' . $n;
		$c['url']    = '#post-' . $n;
		$c['cat']    = 'updates';
		$extra[]     = $c;
	}
	file_put_contents( __DIR__ . '/news-more.json', json_encode( array( 'html' => News_Render::grid_cards( $extra, false ), 'more' => false, 'next' => 5 ) ) ); // phpcs:ignore

	$grid  = new News_Grid_Fixture( array( 'per_page' => 9 ), array( 'cards' => $seed, 'more' => false, 'featured' => 1, 'next' => 3 ) );
	$paged = new News_Grid_Fixture( array( 'per_page' => 3, 'show_next' => 'yes', 'next_chip' => 'Go deeper', 'next_heading' => 'Looking for white papers?', 'next_text' => 'The Resource Library has them all.', 'next_button' => 'Resource Library', 'next_link' => array( 'url' => '/resources/' ) ), array( 'cards' => array_slice( $seed, 0, 3 ), 'more' => true, 'featured' => 1, 'next' => 2 ), 'news-more.json' );
	$car   = new News_Carousel_Fixture( array( 'arrows' => 'bar', 'all_text' => 'All posts', 'all_url' => array( 'url' => '/news/' ) ), array( $seed[1], $seed[0], $seed[2], $seed[3], $seed[1], $seed[2] ) );
	$ext   = new News_Carousel_Fixture( array( 'arrows' => 'external' ), array( $seed[1], $seed[0], $seed[2], $seed[3], $seed[1], $seed[2] ) );
	$press = new Press_Fixture();
	$src   = new Source_Fixture( array( 'card' => $seed[3], 'source_url' => 'https://www.bbc.co.uk/programmes/w3ct4w8v', 'related' => array( 'title' => 'Restaurants & Commercial Kitchens', 'url' => '/restaurants-commercial-kitchens/', 'excerpt' => 'Grease-laden kitchen exhaust, recovered for make-up air.' ) ) );

	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>News — browser fixture</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Urbanist:wght@700&display=swap">
<link rel="stylesheet" href="../../modules/news/assets/css/news.css">
<style>
body{margin:0;background:#F5F6F7;font:16px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;color:#0F3961}
.host{max-width:1240px;margin:0 auto;padding:60px 40px}
.dark{background:linear-gradient(180deg,#07131C,#0A2A26)}
.marker{color:#07864F;font:700 12px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;margin:0 0 14px}
.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
@media(max-width:760px){.host{padding:32px 16px}}
/* A hostile theme: Hello Elementor's button and link rules, and the usual image ones. */
[type=button],button{background-color:transparent;border:1px solid #c36;border-radius:3px;color:#c36;display:inline-block;font-size:1rem;font-weight:400;padding:.5rem 1rem;text-align:center;white-space:nowrap}
[type=button]:hover,button:hover,[type=button]:focus,button:focus{background-color:#c36;color:#fff;text-decoration:none}
a{color:#c36;text-decoration:underline}a:hover{color:#336}
img{max-width:100%;height:auto}
img:hover{opacity:.75}
blockquote{margin:1em 2em;font-style:italic;border-left:4px solid #c36}
h3{font-size:2em}
</style>
</head>
<body>
<section class="host" id="posts">
	<p class="marker">News Grid — the four seed posts</p>
	<div id="a"><?php echo $grid->html(); // phpcs:ignore ?></div>
</section>
<section class="host">
	<p class="marker">News Grid — three per load, Load more, Go deeper tile</p>
	<div id="b"><?php echo $paged->html(); // phpcs:ignore ?></div>
</section>
<section class="host" style="background:#fff">
	<p class="marker">News Carousel — built-in arrows</p>
	<div id="c"><?php echo $car->html(); // phpcs:ignore ?></div>
</section>
<section class="host" id="ext">
	<div class="head"><p class="marker">News Carousel — my own buttons</p><span><span class="elementor-widget enws-car-prev"><a href="#" class="elementor-button">Prev</a></span> <span class="elementor-widget enws-car-next"><a href="#" class="elementor-button">Next</a></span></span></div>
	<div id="d"><?php echo $ext->html(); // phpcs:ignore ?></div>
</section>
<section class="host dark">
	<p class="marker">Press Quotes</p>
	<div id="e"><?php echo $press->html(); // phpcs:ignore ?></div>
</section>
<section class="host">
	<p class="marker">Post Source Box</p>
	<div id="f" style="max-width:720px"><?php echo $src->html(); // phpcs:ignore ?></div>
</section>
<script src="../../modules/news/assets/js/news.js"></script>
</body>
</html>
	<?php
}
