<?php
/**
 * Page Transitions module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Transitions;

use ErudaToolkit\Configurable;
use ErudaToolkit\Module;
use ErudaToolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Loaded here rather than in boot(): the settings screen calls
// settings_fields() on a module it has only described, never booted, and that
// call needs the declarations. The file is a pure class with no side effects,
// so requiring it alongside this one costs nothing.
require_once ERUDA_PATH . 'modules/transitions/class-transitions-fields.php';

/**
 * One curtain of columns, used for two jobs.
 *
 * On the first page of a visit the columns are already covering the screen:
 * the site logo wipes in, a bar tracks how loaded the page actually is, and
 * then the columns sweep up. On every internal link after that the same
 * columns rise to cover, a real navigation happens underneath, and they carry
 * on upwards to reveal the new page.
 *
 * Like Smooth Scrolling this has no widget: it is on or off for the whole
 * site. Unlike Smooth Scrolling it has things worth setting per site, so it
 * declares them and the settings screen renders them.
 */
final class Transitions_Module implements Module, Configurable {

	const STYLE_HANDLE  = 'eruda-transitions';
	const SCRIPT_HANDLE = 'eruda-transitions';

	/**
	 * Has the curtain been printed already this request?
	 *
	 * wp_body_open is the right place for it, but a theme that predates it or
	 * forgets to call it would leave the site with a script and no markup, so
	 * wp_footer covers for it. Exactly one of them may win.
	 *
	 * @var bool
	 */
	private $printed = false;

	/**
	 * Module id. Stored, so it never changes.
	 *
	 * @return string
	 */
	public static function id() {
		return 'transitions';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Page Transitions', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Sweeps a curtain of columns between pages, and shows the site logo while the first page of a visit loads. Off in the editor, and off for visitors who have asked for reduced motion.', 'numbered-accordion' );
	}

	/**
	 * Needs nothing but a browser.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return true;
	}

	/**
	 * Nothing to report.
	 *
	 * @return string[]
	 */
	public static function requirement_messages() {
		return array();
	}

	/**
	 * What this module lets a site change.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings_fields() {
		return Transitions_Fields::declarations( self::kit_color() );
	}

	/**
	 * The site's Elementor primary colour, when there is one.
	 *
	 * Saves setting the same colour twice on the sites this is mostly built
	 * for. A site without Elementor, or with a kit that has no primary, just
	 * gets the fallback.
	 *
	 * @return string
	 */
	public static function kit_color() {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return Transitions_Fields::FALLBACK_COLOR;
		}

		$kit_id = (int) get_option( 'elementor_active_kit' );

		if ( $kit_id <= 0 ) {
			return Transitions_Fields::FALLBACK_COLOR;
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) || empty( $settings['system_colors'] ) || ! is_array( $settings['system_colors'] ) ) {
			return Transitions_Fields::FALLBACK_COLOR;
		}

		foreach ( $settings['system_colors'] as $color ) {
			if ( is_array( $color ) && isset( $color['_id'], $color['color'] ) && 'primary' === $color['_id'] ) {
				return (string) $color['color'];
			}
		}

		return Transitions_Fields::FALLBACK_COLOR;
	}

	/**
	 * This module's settings, made safe.
	 *
	 * @return array<string, mixed>
	 */
	public static function options() {
		return Transitions_Fields::options(
			Settings::module_values( self::id() ),
			self::kit_color()
		);
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		if ( is_admin() ) {
			return;
		}

		// Priority 1: the covering rules and the state restorer have to be in
		// the document before anything paints. See head() for why.
		add_action( 'wp_head', array( $this, 'head' ), 1 );
		add_action( 'wp_body_open', array( $this, 'curtain' ) );
		add_action( 'wp_footer', array( $this, 'curtain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Is this a request the curtain has no business being in?
	 *
	 * @return bool
	 */
	private function skip() {
		if ( is_admin() || is_feed() || is_embed() || is_preview() ) {
			return true;
		}

		if ( is_customize_preview() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// The Elementor editor loads the page in a frame and drives its own
		// scrolling and clicking; a curtain intercepting links inside it would
		// make the canvas unusable. The script checks for the frame too, but
		// not printing the markup at all is cheaper and surer.
		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	/**
	 * The part that must not wait for a stylesheet.
	 *
	 * A linked stylesheet and a deferred script both arrive after the browser
	 * has had a chance to paint. If the covering rules are not already in the
	 * document when a visitor lands on a page they clicked through to, they
	 * see the new page for a frame before the curtain re-establishes, which is
	 * the whole thing this is built to prevent. So the rules that hold the
	 * curtain shut, and the few lines that decide whether it should be shut,
	 * are printed inline at the top of the head and nowhere else.
	 */
	public function head() {
		if ( $this->skip() ) {
			return;
		}

		$options = self::options();

		// Only what is needed to hold the curtain shut on the first frame.
		// Everything else waits for the stylesheet. Values come from the
		// custom properties on the element itself, so the colour and the
		// timings are written in exactly one place.
		$critical =
			'.etrn{position:fixed;inset:0;z-index:2147483000;display:none;pointer-events:none;overflow:clip}' .
			'.etrn__cols{display:flex;width:100%;height:100%}' .
			'.etrn__col{flex:1 1 0;height:100%;background:var(--etrn-color,#111111);transform:translateY(100%)}' .
			'html.etrn-covering .etrn,html.etrn-preloading .etrn{display:block}' .
			'html.etrn-covering .etrn__col,html.etrn-preloading .etrn__col{transform:translateY(0);animation:none}' .
			'html.etrn-preloading{overflow:hidden}' .
			'@media (prefers-reduced-motion:reduce){html.etrn-covering .etrn,html.etrn-preloading .etrn{display:none}html.etrn-preloading{overflow:auto}}';

		printf( "<style id=\"eruda-transitions-critical\">%s</style>\n", $critical ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Mirrors the guards in the main script. Deliberately duplicated
		// rather than shared: this has to run before any file can load.
		$boot = "(function(){try{" .
			"var d=document.documentElement,s=window.sessionStorage;" .
			"if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches){return;}" .
			"if(window.self!==window.top){return;}" .
			"if(s.getItem('etrn:covering')==='1'){d.className+=' etrn-covering';return;}" .
			( $options['preloader'] ? "if(!s.getItem('etrn:visited')){d.className+=' etrn-preloading';}" : '' ) .
			"}catch(e){}})();";

		printf( "<script id=\"eruda-transitions-boot\">%s</script>\n", $boot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Print the curtain.
	 *
	 * The markup is server-rendered rather than built by the script so that
	 * the rules above have something to hold shut on the very first frame.
	 */
	public function curtain() {
		if ( $this->printed || $this->skip() ) {
			return;
		}

		$this->printed = true;

		$options = self::options();
		$logo    = $options['preloader'] ? $this->logo( $options['logo'] ) : '';

		// The colour has already been refused unless it is a plain hex value,
		// and the numbers have been clamped to their declared ranges, so
		// nothing here can carry punctuation into the style attribute.
		$style = sprintf(
			'--etrn-color:%s;--etrn-travel:%ss;--etrn-stagger:%ss;--etrn-logo-scale:%s',
			$options['color'],
			(float) $options['travel'],
			(float) $options['stagger'],
			// Typed as a percentage because that is how somebody thinks about
			// it; written as a multiplier because that is what calc() wants
			// against a length.
			round( $options['logo_scale'] / 100, 4 )
		);

		?>
<div class="etrn" aria-hidden="true" style="<?php echo esc_attr( $style ); ?>">
	<div class="etrn__cols">
		<?php for ( $i = 0; $i < $options['columns']; $i++ ) : ?>
			<div class="etrn__col" style="--etrn-i:<?php echo (int) $i; ?>"<?php echo $i >= $options['mobileColumns'] ? ' data-etrn-wide' : ''; ?>></div>
		<?php endfor; ?>
	</div>
		<?php if ( $options['preloader'] ) : ?>
	<div class="etrn__stage">
			<?php if ( '' !== $logo ) : ?>
		<div class="etrn__logo"><span class="etrn__logo-inner"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			<?php endif; ?>
		<div class="etrn__bar"><div class="etrn__fill"></div></div>
	</div>
			<?php if ( $options['percentage'] ) : ?>
	<div class="etrn__count"><span class="etrn__num">0</span>%</div>
			<?php endif; ?>
		<?php endif; ?>
</div>
<noscript><style>.etrn{display:none !important}html.etrn-preloading{overflow:auto !important}</style></noscript>
		<?php
	}

	/**
	 * The preloader's logo, as markup, or an empty string.
	 *
	 * The one chosen for this module wins; otherwise the site falls back to
	 * the logo it already has in the Customizer, so a site that only ever
	 * needs one image never has to pick it twice. Setting one here is for the
	 * case the fallback cannot cover: a curtain in a dark brand colour usually
	 * wants a light version of the mark, and the header wants the other.
	 *
	 * The linking wrapper WordPress puts around a custom logo is stripped —
	 * a link inside a curtain that swallows clicks is a trap.
	 *
	 * @param int $chosen Attachment id from the settings, or 0.
	 * @return string
	 */
	private function logo( $chosen = 0 ) {
		$chosen = (int) $chosen;

		if ( $chosen > 0 && wp_attachment_is_image( $chosen ) ) {
			$markup = wp_get_attachment_image(
				$chosen,
				'full',
				false,
				array(
					'class'    => 'etrn__logo-img',
					'decoding' => 'async',

					// Never lazy: this is the one image on screen, and a
					// lazily loaded one would not start fetching until after
					// the curtain it sits on had been drawn.
					'loading'  => 'eager',
				)
			);

			if ( is_string( $markup ) && '' !== $markup ) {
				return $markup;
			}
		}

		if ( ! function_exists( 'get_custom_logo' ) || ! has_custom_logo() ) {
			return '';
		}

		$markup = get_custom_logo();

		if ( ! is_string( $markup ) || '' === $markup ) {
			return '';
		}

		// Keep the <img>, drop everything wrapping it.
		if ( 1 === preg_match( '/<img[^>]*>/i', $markup, $img ) ) {
			return $img[0];
		}

		return '';
	}

	/**
	 * Load the stylesheet and the script.
	 */
	public function enqueue() {
		if ( $this->skip() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			ERUDA_URL . 'modules/transitions/assets/css/transitions.css',
			array(),
			ERUDA_VERSION
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			ERUDA_URL . 'modules/transitions/assets/js/transitions.js',
			array(),
			ERUDA_VERSION,
			true
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.erudaTransitions = window.erudaTransitions || {}; window.erudaTransitions.options = ' . wp_json_encode( self::options() ) . ';',
			'before'
		);
	}
}
