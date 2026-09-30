<?php
/**
 * News Carousel widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News\Widgets;

use Elementor\Controls_Manager;
use ErudaToolkit\Modules\News\News_Content;
use ErudaToolkit\Modules\News\News_Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-news-widget.php';

/**
 * The latest posts as a sideways row of photo cards.
 */
class News_Carousel_Widget extends News_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'enws-news-carousel';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'News Carousel', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'news', 'posts', 'carousel', 'slider', 'latest', 'blog' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_posts',
			array( 'label' => esc_html__( 'Posts', 'numbered-accordion' ) )
		);

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'How many', 'numbered-accordion' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 8,
			)
		);

		$this->add_control(
			'cats',
			array(
				'label'       => esc_html__( 'Only these categories', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->term_options( 'category' ),
			)
		);

		$this->add_control(
			'tags',
			array(
				'label'       => esc_html__( 'Only these topics', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->term_options( 'post_tag' ),
			)
		);

		$this->add_control(
			'read_text',
			array(
				'label'   => esc_html__( 'Card link text', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Read', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'track_label',
			array(
				'label'   => esc_html__( 'Row read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Latest posts', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_controls',
			array( 'label' => esc_html__( 'Arrows and link', 'numbered-accordion' ) )
		);

		$this->add_control(
			'arrows',
			array(
				'label'       => esc_html__( 'Arrows', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'bar',
				'options'     => array(
					'bar'      => esc_html__( 'Above the cards, on the right', 'numbered-accordion' ),
					'external' => esc_html__( 'My own buttons in this section', 'numbered-accordion' ),
					'none'     => esc_html__( 'None', 'numbered-accordion' ),
				),
				'description' => esc_html__( '"My own buttons": give any two buttons in the same section the CSS classes enws-car-prev and enws-car-next (Advanced → CSS Classes), e.g. beside the heading.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'all_text',
			array(
				'label'     => esc_html__( '"All posts" link', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All posts', 'numbered-accordion' ),
				'condition' => array( 'arrows' => 'bar' ),
			)
		);

		$this->add_control(
			'all_url',
			array(
				'label'     => esc_html__( 'Goes to', 'numbered-accordion' ),
				'type'      => Controls_Manager::URL,
				'default'   => array( 'url' => '/news/' ),
				'condition' => array( 'arrows' => 'bar' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Row', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'visible',
			array(
				'label'           => esc_html__( 'Cards in view', 'numbered-accordion' ),
				'type'            => Controls_Manager::NUMBER,
				'min'             => 1,
				'max'             => 6,
				'step'            => 0.1,
				'default'         => 3.2,
				'tablet_default'  => 2.2,
				'mobile_default'  => 1.1,
				'description'     => esc_html__( 'A fraction shows the edge of the next card, which says "there is more".', 'numbered-accordion' ),
				'selectors'       => array( '{{WRAPPER}} .enws-car-wrap' => '--enws-visible: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .enws-car-wrap' => '--enws-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The posts. Protected so the tests can hand it cards.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	protected function cards( $settings ) {
		$page = News_Render::query(
			array(
				'per'      => News_Content::per_page( isset( $settings['count'] ) ? $settings['count'] : 8 ),
				'offset'   => 0,
				'cats'     => isset( $settings['cats'] ) ? (array) $settings['cats'] : array(),
				'tags'     => isset( $settings['tags'] ) ? (array) $settings['tags'] : array(),
				// Newest first, plainly: the carousel has no big tile.
				'featured' => 0,
			)
		);

		return $page['cards'];
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$cards    = $this->cards( $settings );

		if ( empty( $cards ) ) {
			if ( $this->editing() ) {
				echo '<p class="enws-empty">' . esc_html__( 'No published posts match yet.', 'numbered-accordion' ) . '</p>';
			}
			return;
		}

		$arrows = $this->word( $settings, 'arrows', 'bar' );
		$read   = $this->word( $settings, 'read_text', __( 'Read', 'numbered-accordion' ) );
		$all    = $this->word( $settings, 'all_text', __( 'All posts', 'numbered-accordion' ) );
		$url    = isset( $settings['all_url'] ) && is_array( $settings['all_url'] ) && isset( $settings['all_url']['url'] ) ? trim( (string) $settings['all_url']['url'] ) : '/news/';
		?>
		<div class="enws-car-wrap" data-arrows="<?php echo esc_attr( $arrows ); ?>">
			<?php if ( 'bar' === $arrows ) : ?>
				<div class="enws-car__bar">
					<button type="button" class="enws-car__btn" data-dir="-1" aria-label="<?php esc_attr_e( 'Previous posts', 'numbered-accordion' ); ?>"><span aria-hidden="true">←</span></button>
					<button type="button" class="enws-car__btn" data-dir="1" aria-label="<?php esc_attr_e( 'Next posts', 'numbered-accordion' ); ?>"><span aria-hidden="true">→</span></button>
					<?php if ( '' !== $all && '' !== $url ) : ?>
						<a class="enws-car__all" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $all ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="enws-car" tabindex="0" role="region" aria-label="<?php echo esc_attr( $this->word( $settings, 'track_label', __( 'Latest posts', 'numbered-accordion' ) ) ); ?>">
				<?php
				foreach ( $cards as $card ) {
					echo News_Render::carousel_card( $card, $read ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in News_Render
				}
				?>
			</div>
		</div>
		<?php
	}
}
