<?php
/**
 * Partner Diagram widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Partners\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Partners\Partners_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Who supplies what to whom: three cards joined by arrows, two partners hung
 * off the middle one, over a blurred photograph.
 */
class Partner_Diagram_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'epdg-partner-diagram';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Partner Diagram', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-flow';
	}

	/**
	 * Panel categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \ErudaToolkit\Panel_Category::SLUG );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'partners', 'diagram', 'suppliers', 'flow', 'logos', 'trusted', 'organizations' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Partners\Partners_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Partners\Partners_Module::SCRIPT_HANDLE );
	}

	/**
	 * Where the bundled logos and photograph live.
	 *
	 * @return string
	 */
	private function asset_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . 'modules/partners/assets/img/';
	}

	/**
	 * The factory drawn for the facility card when it has no logo.
	 *
	 * @return string
	 */
	private function facility_icon() {
		return '<svg class="epdg__icon" viewBox="0 0 48 40" aria-hidden="true" focusable="false"><path d="M3 37V19l11 7v-7l11 7v-7l11 7V5h8v32z" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path class="epdg__smoke" d="M40 5c0-3 3-3 3-5" fill="none" stroke-width="2" stroke-linecap="round"/><rect x="9" y="29" width="5" height="4" fill="currentColor"/><rect x="20" y="29" width="5" height="4" fill="currentColor"/><rect x="31" y="29" width="5" height="4" fill="currentColor"/></svg>';
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_diagram_controls();

		foreach ( Partners_Content::defaults() as $slot => $card ) {
			$this->register_card_controls( $slot, $card );
		}

		$this->register_frame_style_controls();
		$this->register_card_style_controls();
		$this->register_line_style_controls();
	}

	/**
	 * The backdrop and the motion.
	 */
	private function register_diagram_controls() {
		$this->start_controls_section(
			'section_diagram',
			array( 'label' => esc_html__( 'Diagram', 'numbered-accordion' ) )
		);

		$this->add_control(
			'background_image',
			array(
				'label'       => esc_html__( 'Background photograph', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => $this->asset_url() . 'facility-backdrop.webp' ),
				'description' => esc_html__( 'Blurred, a small file is plenty: nobody sees its detail. Empty leaves a pale blue ground.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'blur',
			array(
				'label'        => esc_html__( 'Blur the photograph', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'blur_amount',
			array(
				'label'      => esc_html__( 'Blur', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 2,
						'max' => 60,
					),
				),
				'condition'  => array( 'blur' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .epdg' => '--epdg-blur: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Animate', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Cards rise in turn as the diagram scrolls into view, then the lines draw in. Visitors who ask their device for less motion always get it still.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'flow',
			array(
				'label'        => esc_html__( 'Heat flowing along the arrows', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'animate' => 'yes' ),
				'description'  => esc_html__( 'A small green pulse travels supplier to system to facility on a slow loop. It pauses while the diagram is off screen.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'join_label',
			array(
				'label'       => esc_html__( 'Partners label on phones', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Partners', 'numbered-accordion' ),
				'description' => esc_html__( 'On a phone the diagram becomes one column, and this heads the two partner cards in place of the bracket.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'aria_label',
			array(
				'label'       => esc_html__( 'Description read aloud', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => esc_html__( 'How the partners connect: Enjay supplies Lepido to ThermStar, Engenuity and Edgecom support its monitoring and energy data, ThermStar delivers to your facility', 'numbered-accordion' ),
				'description' => esc_html__( 'A screen reader cannot see the arrows, so say in one sentence what they show.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * One card's content.
	 *
	 * @param string $slot Slot.
	 * @param array  $card Its defaults.
	 */
	private function register_card_controls( $slot, $card ) {
		$this->start_controls_section(
			'section_card_' . $slot,
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- labels come from a fixed list.
			array( 'label' => esc_html__( $card['label'], 'numbered-accordion' ) )
		);

		$this->add_control(
			$slot . '_logo',
			array(
				'label'       => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' === $card['logo'] ? '' : $this->asset_url() . $card['logo'] ),
				'description' => 'site' === $slot
					? esc_html__( 'Empty draws a factory.', 'numbered-accordion' )
					: ( 'core' === $slot
						? esc_html__( 'This card is dark, so use the white version of the logo.', 'numbered-accordion' )
						: esc_html__( 'A transparent SVG or PNG.', 'numbered-accordion' ) ),
			)
		);

		$this->add_responsive_control(
			$slot . '_logo_width',
			array(
				'label'      => esc_html__( 'Logo width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 240,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .epdg__card--' . str_replace( '_', '-', $slot ) => '--epdg-logo-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		if ( 'site' !== $slot ) {
			$this->add_control(
				$slot . '_name',
				array(
					'label'       => esc_html__( 'Organisation name', 'numbered-accordion' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => $card['name'],
					'description' => esc_html__( 'Read aloud in place of the logo.', 'numbered-accordion' ),
				)
			);
		}

		$this->add_control(
			$slot . '_title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => $card['title'],
			)
		);

		$this->add_control(
			$slot . '_text',
			array(
				'label'   => esc_html__( 'Subtext', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => $card['text'],
			)
		);

		$this->add_control(
			$slot . '_link',
			array(
				'label'       => esc_html__( 'Link', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Optional. With a link the whole card is clickable and can be reached by keyboard.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The outer card and its photograph.
	 */
	private function register_frame_style_controls() {
		$this->start_controls_section(
			'section_style_frame',
			array(
				'label' => esc_html__( 'Frame', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'frame_padding',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .epdg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .epdg' => '--epdg-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'veil_start',
			array(
				'label'     => esc_html__( 'White wash, top', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-veil-a: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'veil_end',
			array(
				'label'     => esc_html__( 'White wash, bottom', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-veil-b: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The cards.
	 */
	private function register_card_style_controls() {
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'core_background',
			array(
				'label'     => esc_html__( 'Core card', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-navy: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'core_text',
			array(
				'label'     => esc_html__( 'Core card subtext', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-green-light: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_title_colour',
			array(
				'label'     => esc_html__( 'Titles on the glass cards', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-ink: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .epdg__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Subtext type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .epdg__text, {{WRAPPER}} .epdg__join span',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Arrows and connectors.
	 */
	private function register_line_style_controls() {
		$this->start_controls_section(
			'section_style_lines',
			array(
				'label' => esc_html__( 'Arrows', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_colour',
			array(
				'label'     => esc_html__( 'Arrows and pulse', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-green: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bracket_colour',
			array(
				'label'     => esc_html__( 'Partner bracket', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .epdg' => '--epdg-bracket: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Print a card's logo.
	 *
	 * @param string $slot Slot.
	 * @param array  $card Normalised card.
	 */
	private function render_logo( $slot, $card ) {
		if ( '' === $card['logo_url'] ) {
			if ( 'site' === $slot ) {
				echo $this->facility_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			return;
		}

		// Through the media library where possible, for its srcset. The alt
		// is always the name typed here, because a logo's filename-derived
		// library alt ("enjay-logo-black-2") is worse than none.
		if ( $card['logo_id'] > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			$image = wp_get_attachment_image(
				$card['logo_id'],
				'medium',
				false,
				array(
					'alt'      => $card['name'],
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);

			if ( '' !== $image ) {
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}
		}
		?>
		<img src="<?php echo esc_url( $card['logo_url'] ); ?>" alt="<?php echo esc_attr( $card['name'] ); ?>" loading="lazy" decoding="async" />
		<?php
	}

	/**
	 * Print one card.
	 *
	 * @param array  $settings Settings.
	 * @param string $slot     Slot.
	 */
	private function render_card( $settings, $slot ) {
		$card = Partners_Content::card( $settings, $slot );
		$tag  = '' === $card['link'] ? 'div' : 'a';
		$rel  = array();

		if ( $card['nofollow'] ) {
			$rel[] = 'nofollow';
		}

		if ( $card['external'] ) {
			$rel[] = 'noopener';
		}
		?>
		<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="<?php echo esc_attr( Partners_Content::card_class( $slot ) ); ?>"
			<?php if ( 'a' === $tag ) : ?>
				href="<?php echo esc_url( $card['link'] ); ?>"
				<?php if ( $card['external'] ) : ?>target="_blank"<?php endif; ?>
				<?php if ( $rel ) : ?>rel="<?php echo esc_attr( implode( ' ', $rel ) ); ?>"<?php endif; ?>
			<?php endif; ?>>
			<span class="epdg__logo"><?php $this->render_logo( $slot, $card ); ?></span>
			<?php if ( '' !== $card['title'] ) : ?>
				<b class="epdg__title"><?php echo esc_html( $card['title'] ); ?></b>
			<?php endif; ?>
			<?php if ( '' !== $card['text'] ) : ?>
				<span class="epdg__text"><?php echo esc_html( $card['text'] ); ?></span>
			<?php endif; ?>
		</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
	}

	/**
	 * Print an arrow between two cards of the top row.
	 *
	 * @param string $which 'in' (supplier to system) or 'out' (system to facility).
	 */
	private function render_arrow( $which ) {
		?>
		<span class="epdg__arrow epdg__arrow--<?php echo esc_attr( $which ); ?>" aria-hidden="true"><span class="epdg__line"></span><span class="epdg__pulse"></span></span>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$background = isset( $settings['background_image'] ) && is_array( $settings['background_image'] ) && isset( $settings['background_image']['url'] ) ? $settings['background_image']['url'] : '';
		$join       = isset( $settings['join_label'] ) && is_scalar( $settings['join_label'] ) ? trim( (string) $settings['join_label'] ) : '';
		$aria       = isset( $settings['aria_label'] ) && is_scalar( $settings['aria_label'] ) ? trim( (string) $settings['aria_label'] ) : '';
		$classes    = Partners_Content::root_classes(
			isset( $settings['blur'] ) ? $settings['blur'] : '',
			isset( $settings['animate'] ) ? $settings['animate'] : '',
			isset( $settings['flow'] ) ? $settings['flow'] : ''
		);
		?>
		<figure class="<?php echo esc_attr( $classes ); ?>"<?php echo '' !== $aria ? ' aria-label="' . esc_attr( $aria ) . '"' : ''; ?>>
			<span class="epdg__backdrop" aria-hidden="true" style="<?php echo esc_attr( Partners_Content::backdrop_style( $background ) ); ?>"></span>

			<div class="epdg__row">
				<?php
				$this->render_card( $settings, 'source' );
				$this->render_arrow( 'in' );
				$this->render_card( $settings, 'core' );
				$this->render_arrow( 'out' );
				$this->render_card( $settings, 'site' );
				?>
			</div>

			<div class="epdg__stems" aria-hidden="true"><i class="epdg__stem epdg__stem--up"></i><i class="epdg__stem epdg__stem--across"></i><i class="epdg__stem epdg__stem--left"></i><i class="epdg__stem epdg__stem--right"></i></div>

			<?php if ( '' !== $join ) : ?>
				<div class="epdg__join"><span><?php echo esc_html( $join ); ?></span></div>
			<?php endif; ?>

			<div class="epdg__sup">
				<?php
				$this->render_card( $settings, 'partner_one' );
				$this->render_card( $settings, 'partner_two' );
				?>
			</div>
		</figure>
		<?php
	}
}
