<?php
/**
 * Press Quotes widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-news-widget.php';

/**
 * What the press said, as frosted cards for a dark section.
 *
 * A repeater is right here: these quotes are fixed history, not a list that
 * grows, so nothing is lost by a placed widget keeping its own copy.
 */
class Press_Quotes_Widget extends News_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'enws-press-quotes';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Press Quotes', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-blockquote';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'press', 'quotes', 'media', 'news', 'testimonial', 'bbc', 'forbes' );
	}

	/**
	 * No script needed.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array();
	}

	/**
	 * The three quotes from the old thermstar.com/news/, verbatim.
	 *
	 * @return array
	 */
	public static function default_quotes() {
		return array(
			array(
				'word'       => 'BBC',
				'word_style' => 'bbc',
				'word_sub'   => '',
				'quote'      => 'There is serious money in kitchen fumes. Enjay is one of a handful of companies that can offer profitable energy recovery from kitchen exhaust.',
				'outlet'     => 'BBC News',
				'link'       => array( 'url' => 'https://www.bbc.com/news/business-65328579' ),
			),
			array(
				'word'       => 'Forbes',
				'word_style' => 'serif',
				'word_sub'   => '',
				'quote'      => 'By deploying the Enjay product, customers use less energy and emit less CO2. Enjay focuses on the ROI, couched in terms of the number of kilowatt hours that customers can save.',
				'outlet'     => 'Forbes',
				'link'       => array( 'url' => '' ),
			),
			array(
				'word'       => 'PREMIER',
				'word_style' => 'heavy',
				'word_sub'   => 'CONSTRUCTION',
				'quote'      => 'Enjay drives kitchen costs down at Burger King and Turtle Bay. Its patented Lepido platform is widely used by hotels, restaurants and schools.',
				'outlet'     => 'Premier Construction',
				'link'       => array( 'url' => '' ),
			),
		);
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_quotes',
			array( 'label' => esc_html__( 'Quotes', 'numbered-accordion' ) )
		);

		$quotes = new Repeater();

		$quotes->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Outlet logo', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'A white or light logo. Empty sets the name in type instead.', 'numbered-accordion' ),
			)
		);

		$quotes->add_control(
			'word',
			array(
				'label' => esc_html__( 'Name in type', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$quotes->add_control(
			'word_style',
			array(
				'label'   => esc_html__( 'Type style', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'plain',
				'options' => array(
					'bbc'   => esc_html__( 'Letter blocks (BBC)', 'numbered-accordion' ),
					'serif' => esc_html__( 'Serif (Forbes)', 'numbered-accordion' ),
					'heavy' => esc_html__( 'Heavy sans (Premier)', 'numbered-accordion' ),
					'plain' => esc_html__( 'Plain', 'numbered-accordion' ),
				),
			)
		);

		$quotes->add_control(
			'word_sub',
			array(
				'label'       => esc_html__( 'Small line under it', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'e.g. CONSTRUCTION.', 'numbered-accordion' ),
			)
		);

		$quotes->add_control(
			'quote',
			array(
				'label' => esc_html__( 'Quote', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 4,
			)
		);

		$quotes->add_control(
			'outlet',
			array(
				'label' => esc_html__( 'Outlet', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$quotes->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link to the piece', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);

		$this->add_control(
			'quotes',
			array(
				'label'       => esc_html__( 'Quotes', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $quotes->get_controls(),
				'default'     => self::default_quotes(),
				'title_field' => '{{{ outlet }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'numbered-accordion' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'selectors'      => array( '{{WRAPPER}} .enws-press' => '--enws-cols: {{VALUE}};' ),
			)
		);

		foreach ( array(
			'card_bg'     => array( esc_html__( 'Card', 'numbered-accordion' ), '--enws-press-bg' ),
			'card_border' => array( esc_html__( 'Card border', 'numbered-accordion' ), '--enws-press-line' ),
			'quote_ink'   => array( esc_html__( 'Quote', 'numbered-accordion' ), '--enws-press-ink' ),
			'outlet_ink'  => array( esc_html__( 'Outlet', 'numbered-accordion' ), '--enws-green-light' ),
		) as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .enws-press' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$quotes   = isset( $settings['quotes'] ) && is_array( $settings['quotes'] ) ? $settings['quotes'] : array();
		$quotes   = array_values( array_filter( $quotes, function ( $q ) {
			return is_array( $q ) && isset( $q['quote'] ) && '' !== trim( (string) $q['quote'] );
		} ) );

		if ( empty( $quotes ) ) {
			return;
		}
		?>
		<div class="enws-press">
			<?php foreach ( $quotes as $q ) : ?>
				<?php
				$outlet = isset( $q['outlet'] ) ? trim( (string) $q['outlet'] ) : '';
				$word   = isset( $q['word'] ) ? trim( (string) $q['word'] ) : '';
				$style  = isset( $q['word_style'] ) && in_array( $q['word_style'], array( 'bbc', 'serif', 'heavy', 'plain' ), true ) ? $q['word_style'] : 'plain';
				$sub    = isset( $q['word_sub'] ) ? trim( (string) $q['word_sub'] ) : '';
				$logo   = isset( $q['logo']['url'] ) ? trim( (string) $q['logo']['url'] ) : '';
				$url    = isset( $q['link']['url'] ) ? trim( (string) $q['link']['url'] ) : '';
				$quote  = trim( (string) $q['quote'], " \t\n\r“”\"" );
				?>
				<figure class="enws-press__q">
					<?php if ( '' !== $logo ) : ?>
						<span class="enws-press__logo"><img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $outlet ); ?>" loading="lazy" /></span>
					<?php elseif ( '' !== $word ) : ?>
						<span class="enws-press__logo enws-press__logo--<?php echo esc_attr( $style ); ?>" role="img" aria-label="<?php echo esc_attr( '' !== $outlet ? $outlet : $word ); ?>">
							<?php
							if ( 'bbc' === $style ) {
								foreach ( preg_split( '//u', $word, -1, PREG_SPLIT_NO_EMPTY ) as $letter ) {
									echo '<i>' . esc_html( $letter ) . '</i>';
								}
							} else {
								echo esc_html( $word );
							}
							echo '' !== $sub ? '<small>' . esc_html( $sub ) . '</small>' : '';
							?>
						</span>
					<?php endif; ?>
					<blockquote><?php echo esc_html( '“' . $quote . '”' ); ?></blockquote>
					<?php if ( '' !== $outlet ) : ?>
						<figcaption>
							<?php if ( '' !== $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $outlet ); ?> <span aria-hidden="true">↗</span></a>
							<?php else : ?>
								<?php echo esc_html( $outlet ); ?>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
