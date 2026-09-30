<?php
/**
 * Post Source Box widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News\Widgets;

use Elementor\Controls_Manager;
use ErudaToolkit\Modules\News\News_Render;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-news-widget.php';

/**
 * For the single post template: where the post came from, its audio, and the
 * page it leads on to.
 */
class Post_Source_Widget extends News_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'enws-post-source';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Source Box', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-link';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'news', 'post', 'source', 'related', 'podcast', 'audio', 'single' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_words',
			array( 'label' => esc_html__( 'Wording', 'numbered-accordion' ) )
		);

		$this->add_control(
			'note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'For the single post template. It reads the post\'s News item fields: the source, the audio file and the Related page. In the editor it previews the newest post.', 'numbered-accordion' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		foreach ( array(
			'by_text'      => array( esc_html__( 'Before the source', 'numbered-accordion' ), esc_html__( 'Originally published by', 'numbered-accordion' ) ),
			'read_text'    => array( esc_html__( 'Button (%s is the source)', 'numbered-accordion' ), esc_html__( 'Read on %s', 'numbered-accordion' ) ),
			'listen_text'  => array( esc_html__( 'Above the player', 'numbered-accordion' ), esc_html__( 'Listen', 'numbered-accordion' ) ),
			'related_text' => array( esc_html__( 'Above the related page', 'numbered-accordion' ), esc_html__( 'Related', 'numbered-accordion' ) ),
		) as $id => $word ) {
			$this->add_control(
				$id,
				array(
					'label'       => $word[0],
					'type'        => Controls_Manager::TEXT,
					'label_block' => true,
					'default'     => $word[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * The post being shown, or in the editor the newest post.
	 *
	 * @return \WP_Post|null
	 */
	protected function current_post() {
		$post = get_post();

		if ( $post && 'post' === $post->post_type ) {
			return $post;
		}

		if ( $this->editing() || ( function_exists( 'is_preview' ) && is_preview() ) ) {
			$latest = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish' ) );
			return $latest ? $latest[0] : null;
		}

		return null;
	}

	/**
	 * What the box shows for a post. Protected so the tests can hand it one.
	 *
	 * @return array{card: array, source_url: string, related: array|null}|null
	 */
	protected function box() {
		$post = $this->current_post();

		if ( ! $post ) {
			return null;
		}

		$card    = News_Render::card_for( $post );
		$rel_id  = (int) get_post_meta( $post->ID, 'nw_related', true );
		$related = null;

		if ( $rel_id > 0 && 'publish' === get_post_status( $rel_id ) ) {
			$related = array(
				'title'   => get_the_title( $rel_id ),
				'url'     => get_permalink( $rel_id ),
				'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $rel_id ) ), 22 ),
			);
		}

		return array(
			'card'       => $card,
			'source_url' => trim( (string) get_post_meta( $post->ID, 'nw_source_url', true ) ),
			'related'    => $related,
		);
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$box      = $this->box();

		if ( ! $box || ! $box['card'] ) {
			return;
		}

		$card    = $box['card'];
		$source  = '' !== $card['source'] ? $card['source'] : ( '' !== $box['source_url'] ? (string) wp_parse_url( $box['source_url'], PHP_URL_HOST ) : '' );
		$has_src = '' !== $box['source_url'];
		$has_aud = '' !== $card['audio'];

		if ( ! $has_src && ! $has_aud && ! $box['related'] ) {
			return;
		}

		$read = $this->word( $settings, 'read_text', __( 'Read on %s', 'numbered-accordion' ) );
		?>
		<aside class="enws-src">
			<?php if ( $has_src ) : ?>
				<div class="enws-src__from">
					<p><?php echo esc_html( $this->word( $settings, 'by_text', __( 'Originally published by', 'numbered-accordion' ) ) ); ?> <b><?php echo esc_html( $source ); ?></b></p>
					<a class="enws-src__btn" href="<?php echo esc_url( $box['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( false !== strpos( $read, '%s' ) ? sprintf( $read, $source ) : $read ); ?> <span aria-hidden="true">↗</span></a>
				</div>
			<?php endif; ?>

			<?php if ( $has_aud ) : ?>
				<div class="enws-src__listen">
					<?php $listen = $this->word( $settings, 'listen_text', __( 'Listen', 'numbered-accordion' ) ); ?>
					<?php echo '' !== $listen ? '<span class="enws-src__eyebrow">' . esc_html( $listen ) . '</span>' : ''; ?>
					<?php echo News_Render::audio_slot( $card, 'source' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in News_Render ?>
				</div>
			<?php endif; ?>

			<?php if ( $box['related'] ) : ?>
				<a class="enws-src__rel" href="<?php echo esc_url( $box['related']['url'] ); ?>">
					<span class="enws-src__eyebrow"><?php echo esc_html( $this->word( $settings, 'related_text', __( 'Related', 'numbered-accordion' ) ) ); ?></span>
					<strong><?php echo esc_html( $box['related']['title'] ); ?></strong>
					<?php echo '' !== $box['related']['excerpt'] ? '<span>' . esc_html( $box['related']['excerpt'] ) . '</span>' : ''; ?>
					<i aria-hidden="true">→</i>
				</a>
			<?php endif; ?>
		</aside>
		<?php
	}
}
