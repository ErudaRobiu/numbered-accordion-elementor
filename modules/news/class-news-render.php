<?php
/**
 * News query and card markup, shared by the widgets and the Load more route.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\News;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads posts and prints cards.
 *
 * One place for both, so a card added by "Load more" is the same markup as
 * one rendered with the page.
 */
final class News_Render {

	/**
	 * Read one page of posts as cards.
	 *
	 * The newest featured post leads the first page, wherever its date puts
	 * it. It is then left out of every later page, so it never appears
	 * twice; `offset` counts only the other posts.
	 *
	 * @param array $args {
	 *     @type int      $per      Page size.
	 *     @type int      $offset   How many non-featured posts are already shown.
	 *     @type string[] $cats     Category slugs; empty for all.
	 *     @type string[] $tags     Tag slugs; empty for all.
	 *     @type int      $featured Featured post id to leave out, or -1 to look it up.
	 * }
	 * @return array{cards: array, more: bool, featured: int, next: int}
	 */
	public static function query( $args ) {
		$per      = News_Content::per_page( isset( $args['per'] ) ? $args['per'] : 9 );
		$offset   = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		$cats     = isset( $args['cats'] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) $args['cats'] ) ) ) : array();
		$tags     = isset( $args['tags'] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) $args['tags'] ) ) ) : array();
		$featured = isset( $args['featured'] ) ? (int) $args['featured'] : -1;
		$base     = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		$tax      = array();

		if ( $cats ) {
			$tax[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => $cats,
			);
		}

		if ( $tags ) {
			$tax[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => $tags,
			);
		}

		if ( $tax ) {
			$base['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$lead = array();

		if ( $featured < 0 ) {
			$featured = 0;
			$found    = get_posts(
				array_merge(
					$base,
					array(
						'posts_per_page' => 1,
						'meta_key'       => 'nw_featured', // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				)
			);

			if ( $found ) {
				$featured = (int) $found[0]->ID;
				$lead     = array( $found[0] );
			}
		}

		$want  = $per - count( $lead );
		$posts = get_posts(
			array_merge(
				$base,
				array(
					'posts_per_page' => $want + 1,
					'offset'         => $offset,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'post__not_in'   => $featured > 0 ? array( $featured ) : array(),
				)
			)
		);

		$more  = count( $posts ) > $want;
		$posts = array_slice( $posts, 0, $want );
		$cards = array();

		foreach ( array_merge( $lead, $posts ) as $post ) {
			$card = self::card_for( $post );

			if ( null !== $card ) {
				$cards[] = $card;
			}
		}

		return array(
			'cards'    => $cards,
			'more'     => $more,
			'featured' => $featured,
			'next'     => $offset + count( $posts ),
		);
	}

	/**
	 * One post as a card.
	 *
	 * @param \WP_Post $post Post.
	 * @return array|null
	 */
	public static function card_for( $post ) {
		$meta = array();

		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( 0 === strpos( $key, 'nw_' ) ) {
				$meta[ $key ] = isset( $values[0] ) ? $values[0] : '';
			}
		}

		$cats  = array();
		$terms = get_the_terms( $post->ID, 'category' );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( 'uncategorized' !== $term->slug ) {
					$cats[] = array( 'slug' => $term->slug, 'name' => $term->name );
				}
			}
		}

		$audio = isset( $meta['nw_audio'] ) ? (int) $meta['nw_audio'] : 0;

		return News_Content::card(
			array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'url'       => get_permalink( $post ),
				'date'      => $post->post_date,
				'excerpt'   => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'cats'      => $cats,
				'meta'      => $meta,
				'audio_url' => $audio > 0 ? (string) wp_get_attachment_url( $audio ) : '',
				'image'     => (string) get_the_post_thumbnail(
					$post,
					'large',
					array(
						'alt'     => '',
						'loading' => 'lazy',
						'sizes'   => '(max-width: 760px) 92vw, (max-width: 1100px) 46vw, 480px',
					)
				),
			)
		);
	}

	/**
	 * The words the cards use that are not the post's own.
	 *
	 * @param array $labels Overrides.
	 * @return array{listen: string, more: string, play: string, pause: string}
	 */
	public static function labels( $labels = array() ) {
		$labels = is_array( $labels ) ? $labels : array();

		return array(
			'listen' => isset( $labels['listen'] ) && '' !== $labels['listen'] ? (string) $labels['listen'] : __( 'Listen on %s', 'numbered-accordion' ),
			'more'   => isset( $labels['more'] ) ? (string) $labels['more'] : __( 'Read more', 'numbered-accordion' ),
			'play'   => __( 'Play', 'numbered-accordion' ),
			'pause'  => __( 'Pause', 'numbered-accordion' ),
		);
	}

	/**
	 * The "Listen on …" text for a card.
	 *
	 * @param array $card   Card.
	 * @param array $labels See labels().
	 * @return string
	 */
	public static function listen_text( $card, $labels ) {
		$where = '' !== $card['source'] ? $card['source'] : (string) wp_parse_url( $card['source_url'], PHP_URL_HOST );

		return false !== strpos( $labels['listen'], '%s' ) ? sprintf( $labels['listen'], $where ) : $labels['listen'];
	}

	/**
	 * The audio player.
	 *
	 * With an audio file it plays in the page: a play button, a waveform that
	 * fills as it plays and can be clicked or arrowed to seek, and the time.
	 * Without one it is a link to the episode's own page, drawn the same way,
	 * so the card looks the same whether or not the file is ours to host.
	 *
	 * The markup can be replaced whole through the `eruda_news_audio_player`
	 * filter ($html, array{src, title, post_id, context, listen_url}).
	 *
	 * @param array  $card    Card.
	 * @param string $context grid or source.
	 * @param array  $labels  See labels().
	 * @return string
	 */
	public static function player( $card, $context, $labels ) {
		$wave = '';

		foreach ( News_Content::waveform( (int) $card['id'] ) as $h ) {
			$wave .= '<i style="--h:' . (int) $h . '%"></i>';
		}

		$bars = '<span class="enws-player__wave" aria-hidden="true"><span class="enws-player__bars">' . $wave . '</span><span class="enws-player__bars enws-player__bars--on">' . $wave . '</span></span>';

		if ( '' !== $card['audio'] ) {
			$html = sprintf(
				'<div class="enws-player" data-audio-src="%1$s" data-audio-title="%2$s" data-post-id="%3$d" data-context="%4$s" data-play="%7$s" data-pause="%8$s">'
				. '<button type="button" class="enws-player__btn" aria-label="%5$s"><span class="enws-player__icon" aria-hidden="true"></span></button>'
				. '<span class="enws-player__track">%6$s<input class="enws-player__seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="%9$s" /></span>'
				. '<span class="enws-player__time" aria-live="off">0:00</span>'
				. '<audio preload="none" src="%1$s"></audio>'
				. '</div>',
				esc_url( $card['audio'] ),
				esc_attr( $card['title'] ),
				(int) $card['id'],
				esc_attr( $context ),
				/* translators: %s: episode title */
				esc_attr( sprintf( __( 'Play: %s', 'numbered-accordion' ), $card['title'] ) ),
				$bars,
				esc_attr( $labels['play'] ),
				esc_attr( $labels['pause'] ),
				/* translators: %s: episode title */
				esc_attr( sprintf( __( 'Position in %s', 'numbered-accordion' ), $card['title'] ) )
			);
		} else {
			$html = sprintf(
				'<a class="enws-player enws-player--link" href="%1$s" target="_blank" rel="noopener" data-context="%2$s" aria-label="%3$s">'
				. '<span class="enws-player__btn" aria-hidden="true"><span class="enws-player__icon"></span></span>'
				. '<span class="enws-player__track">%4$s</span>'
				. '<span class="enws-player__time" aria-hidden="true">↗</span>'
				. '</a>',
				esc_url( $card['source_url'] ),
				esc_attr( $context ),
				/* translators: 1: "Listen on BBC Business Daily", 2: episode title */
				esc_attr( sprintf( __( '%1$s: %2$s (opens in a new tab)', 'numbered-accordion' ), self::listen_text( $card, $labels ), $card['title'] ) ),
				$bars
			);
		}

		/**
		 * Replace the player's markup.
		 *
		 * @param string $html Default markup.
		 * @param array  $args src, title, post_id, context, listen_url.
		 */
		return (string) apply_filters(
			'eruda_news_audio_player',
			$html,
			array(
				'src'        => $card['audio'],
				'title'      => $card['title'],
				'post_id'    => (int) $card['id'],
				'context'    => $context,
				'listen_url' => $card['source_url'],
			)
		);
	}

	/**
	 * One grid card.
	 *
	 * @param array  $card    Card.
	 * @param string $variant audio or image.
	 * @param array  $labels  See labels().
	 * @return string
	 */
	public static function grid_card( $card, $variant, $labels = array() ) {
		$labels = self::labels( $labels );
		$data   = sprintf( ' data-cat="%s"', esc_attr( implode( ' ', $card['cats'] ) ) );
		$pill   = '' !== $card['cat_name'] ? '<span class="enws-pill">' . esc_html( $card['cat_name'] ) . '</span>' : '';
		$date   = '<time datetime="' . esc_attr( $card['datetime'] ) . '">' . esc_html( $card['date'] ) . '</time>';

		ob_start();

		if ( 'audio' === $variant ) {
			$top = trim( $card['cat_name'] . ' · Podcast', ' ·' );
			?>
			<article class="enws-card enws-card--audio"<?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="enws-card__top"><span class="enws-pill enws-pill--dk"><?php echo esc_html( $top ); ?></span><?php echo '' !== $card['source'] ? '<em>' . esc_html( $card['source'] ) . '</em>' : ''; ?></span>
				<h3 class="enws-card__title"><a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h3>
				<?php echo self::player( $card, 'grid', $labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( '' !== $card['quote'] ) : ?>
					<blockquote><?php echo esc_html( '“' . trim( $card['quote'], '“”" ' ) . '”' ); ?><?php echo '' !== $card['credit'] ? '<cite>' . esc_html( $card['credit'] ) . '</cite>' : ''; ?></blockquote>
				<?php endif; ?>
				<span class="enws-card__acts">
					<?php if ( '' !== $card['source_url'] ) : ?>
						<a class="enws-listen" href="<?php echo esc_url( $card['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( self::listen_text( $card, $labels ) ); ?> <span aria-hidden="true">↗</span></a>
					<?php endif; ?>
					<em><?php echo $date; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></em>
				</span>
			</article>
			<?php
		} else {
			?>
			<a class="enws-card enws-card--image" href="<?php echo esc_url( $card['url'] ); ?>"<?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="enws-card__pic"><?php echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup ?><?php echo $pill; // phpcs:ignore ?></span>
				<span class="enws-card__body">
					<strong><?php echo esc_html( $card['title'] ); ?></strong>
					<?php if ( '' !== $card['excerpt'] ) : ?>
						<span class="enws-card__ex"><?php echo esc_html( $card['excerpt'] ); ?></span>
					<?php endif; ?>
					<em><?php echo $date . ' · ' . esc_html( $card['type'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <b aria-hidden="true">↗</b></em>
				</span>
			</a>
			<?php
		}

		return (string) ob_get_clean();
	}

	/**
	 * A run of grid cards, in the order given.
	 *
	 * @param array $cards  Cards.
	 * @param array $labels See labels().
	 * @return string
	 */
	public static function grid_cards( $cards, $labels = array() ) {
		$html     = '';
		$cards    = array_values( (array) $cards );
		$variants = News_Content::variants( $cards );

		foreach ( $cards as $i => $card ) {
			$html .= self::grid_card( $card, $variants[ $i ], $labels );
		}

		return $html;
	}

	/**
	 * One carousel card: always the photo card, with a Podcast pill for a
	 * podcast, and no player.
	 *
	 * @param array  $card Card.
	 * @param string $read "Read" link text.
	 * @return string
	 */
	public static function carousel_card( $card, $read ) {
		ob_start();
		?>
		<a class="enws-car__card" href="<?php echo esc_url( $card['url'] ); ?>">
			<span class="enws-car__img"><?php echo $card['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup ?></span>
			<span class="enws-car__body">
				<span class="enws-car__meta">
					<?php echo '' !== $card['cat_name'] ? '<b>' . esc_html( $card['cat_name'] ) . '</b>' : ''; ?>
					<?php echo 'audio' === News_Content::variants( array( $card ) )[0] ? '<i>' . esc_html__( 'Podcast', 'numbered-accordion' ) . '</i>' : ''; ?>
					<time datetime="<?php echo esc_attr( $card['datetime'] ); ?>"><?php echo esc_html( $card['date'] ); ?></time>
				</span>
				<strong><?php echo esc_html( $card['title'] ); ?></strong>
				<span class="enws-car__go"><?php echo esc_html( $read ); ?> <span aria-hidden="true">→</span></span>
			</span>
		</a>
		<?php
		return (string) ob_get_clean();
	}
}
