<?php
/**
 * Award Wall widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Awards\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Awards\Awards_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Award badges in a grid beside a count that climbs as it scrolls into view.
 *
 * A repeater is right here: awards are added one at a time, rarely, and the
 * count and year range follow the list on their own.
 */
class Award_Wall_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eaw-award-wall';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Award Wall', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-price-list';
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
		return array( 'awards', 'badges', 'prizes', 'recognition', 'logos', 'about' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Awards\Awards_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Awards\Awards_Module::SCRIPT_HANDLE );
	}

	/**
	 * Where the bundled badges live.
	 *
	 * @return string
	 */
	private function badge_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . Awards_Content::BADGE_DIR;
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_awards',
			array( 'label' => esc_html__( 'Awards', 'numbered-accordion' ) )
		);

		$awards = new Repeater();

		$awards->add_control(
			'badge',
			array(
				'label'       => esc_html__( 'Badge', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Trimmed, transparent, about 240px.', 'numbered-accordion' ),
			)
		);

		foreach ( array(
			'year'   => esc_html__( 'Year', 'numbered-accordion' ),
			'name'   => esc_html__( 'Award', 'numbered-accordion' ),
			'detail' => esc_html__( 'Detail', 'numbered-accordion' ),
		) as $id => $label ) {
			$awards->add_control(
				$id,
				array(
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'label_block' => 'year' !== $id,
				)
			);
		}

		$awards->add_control(
			'dark',
			array(
				'label'        => esc_html__( 'Dark badge', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'For a white badge: it gets a dark chip the size of the badge itself.', 'numbered-accordion' ),
			)
		);

		$awards->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);

		$defaults = array();

		foreach ( Awards_Content::default_items() as $item ) {
			$defaults[] = array(
				'badge'  => array( 'url' => $this->badge_url() . $item['file'] ),
				'year'   => $item['year'],
				'name'   => $item['name'],
				'detail' => $item['detail'],
				'dark'   => $item['dark'] ? 'yes' : '',
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Awards', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $awards->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ year }}} · {{{ name }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_lead',
			array( 'label' => esc_html__( 'The number', 'numbered-accordion' ) )
		);

		$this->add_control(
			'show_lead',
			array(
				'label'        => esc_html__( 'Show it', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'number_mode',
			array(
				'label'       => esc_html__( 'Number', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'count',
				'options'     => array(
					'count'  => esc_html__( 'How many awards are listed', 'numbered-accordion' ),
					'manual' => esc_html__( 'Type it', 'numbered-accordion' ),
				),
				'description' => esc_html__( 'Counting means adding an award updates the number.', 'numbered-accordion' ),
				'condition'   => array( 'show_lead' => 'yes' ),
			)
		);

		$this->add_control(
			'number_manual',
			array(
				'label'     => esc_html__( 'The number', 'numbered-accordion' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'default'   => 8,
				'condition' => array(
					'show_lead'   => 'yes',
					'number_mode' => 'manual',
				),
			)
		);

		$this->add_control(
			'line',
			array(
				'label'       => esc_html__( 'Line under it', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'international innovation awards for Lepido® technology, {years}',
				'description' => esc_html__( '{years} becomes the earliest to latest year listed, e.g. 2018–2022. {count} becomes the number.', 'numbered-accordion' ),
				'condition'   => array( 'show_lead' => 'yes' ),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Count up and fade in', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Once, on scroll. Not in the editor, never for visitors who ask for less motion.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * Style controls.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Wall', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'       => esc_html__( 'Columns when wide', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '4',
				'options'     => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
				'description' => esc_html__( 'Below about 640px of its own width the wall always goes to two.', 'numbered-accordion' ),
				'selectors'   => array( '{{WRAPPER}} .eaw' => '--eaw-cols: {{VALUE}};' ),
			)
		);

		foreach ( array(
			'badge_h' => array( esc_html__( 'Badge area height', 'numbered-accordion' ), '--eaw-badge-h', 60, 200 ),
			'gap'     => array( esc_html__( 'Gap', 'numbered-accordion' ), '--eaw-gap', 0, 32 ),
			'radius'  => array( esc_html__( 'Card corner radius', 'numbered-accordion' ), '--eaw-radius', 0, 32 ),
		) as $id => $size ) {
			$this->add_control(
				$id,
				array(
					'label'      => $size[0],
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => $size[2], 'max' => $size[3] ) ),
					'selectors'  => array( '{{WRAPPER}} .eaw' => $size[1] . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		foreach ( array(
			'panel'  => array( esc_html__( 'Panel', 'numbered-accordion' ), '--eaw-panel' ),
			'card'   => array( esc_html__( 'Card', 'numbered-accordion' ), '--eaw-card' ),
			'border' => array( esc_html__( 'Borders', 'numbered-accordion' ), '--eaw-line' ),
			'green'  => array( esc_html__( 'Number and years', 'numbered-accordion' ), '--eaw-green' ),
			'ink'    => array( esc_html__( 'Line and names', 'numbered-accordion' ), '--eaw-ink' ),
			'muted'  => array( esc_html__( 'Details', 'numbered-accordion' ), '--eaw-muted' ),
			'chip'   => array( esc_html__( 'Dark badge chip', 'numbered-accordion' ), '--eaw-chip' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eaw' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'number_type' => array( esc_html__( 'Number type', 'numbered-accordion' ), '.eaw__num' ),
			'line_type'   => array( esc_html__( 'Line type', 'numbered-accordion' ), '.eaw__line' ),
			'year_type'   => array( esc_html__( 'Year type', 'numbered-accordion' ), '.eaw__year' ),
			'name_type'   => array( esc_html__( 'Award type', 'numbered-accordion' ), '.eaw__name' ),
			'detail_type' => array( esc_html__( 'Detail type', 'numbered-accordion' ), '.eaw__detail' ),
		) as $id => $group ) {
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $id,
					'label'    => $group[0],
					'selector' => '{{WRAPPER}} ' . $group[1],
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Print a badge.
	 *
	 * @param array $card Card.
	 */
	private function render_badge( $card ) {
		/* translators: %s: award name */
		$alt = sprintf( __( '%s badge', 'numbered-accordion' ), $card['name'] );

		if ( $card['badge_id'] > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			$image = wp_get_attachment_image( $card['badge_id'], 'medium', false, array( 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async' ) );

			if ( '' !== $image ) {
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}
		}

		if ( '' !== $card['badge'] ) {
			printf( '<img src="%s" alt="%s" loading="lazy" decoding="async" />', esc_url( $card['badge'] ), esc_attr( $alt ) );
		}
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$cards    = Awards_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );

		if ( empty( $cards ) ) {
			return;
		}

		$lead    = isset( $settings['show_lead'] ) && 'yes' === $settings['show_lead'];
		$animate = isset( $settings['animate'] ) && 'yes' === $settings['animate'];
		$number  = Awards_Content::number( isset( $settings['number_mode'] ) ? $settings['number_mode'] : 'count', isset( $settings['number_manual'] ) ? $settings['number_manual'] : null, count( $cards ) );
		$line    = Awards_Content::line( isset( $settings['line'] ) ? $settings['line'] : '', Awards_Content::year_range( $cards ), $number );
		?>
		<div class="eaw-wrap">
			<div class="eaw<?php echo $animate ? ' eaw--animate' : ''; ?><?php echo $lead ? '' : ' eaw--no-lead'; ?>">
				<?php if ( $lead ) : ?>
					<p class="eaw__lead"><b class="eaw__num" data-to="<?php echo (int) $number; ?>"><?php echo (int) $number; ?></b><?php echo '' !== $line ? ' <span class="eaw__line">' . esc_html( $line ) . '</span>' : ''; ?></p>
				<?php endif; ?>
				<ol class="eaw__grid">
					<?php foreach ( $cards as $i => $card ) : ?>
						<?php
						$tag   = '' !== $card['link'] ? 'a' : 'div';
						$rel   = array_filter( array( $card['nofollow'] ? 'nofollow' : '', $card['external'] ? 'noopener' : '' ) );
						$attrs = '' !== $card['link']
							? ' href="' . esc_url( $card['link'] ) . '"' . ( $card['external'] ? ' target="_blank"' : '' ) . ( $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '' )
							: '';
						?>
						<li class="eaw__item" style="--eaw-i:<?php echo (int) $i; ?>">
							<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="eaw__card<?php echo $card['dark'] ? ' is-dark' : ''; ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped parts ?>>
								<span class="eaw__badge"><?php $this->render_badge( $card ); ?></span>
								<?php echo '' !== $card['year'] ? '<span class="eaw__year">' . esc_html( $card['year'] ) . '</span>' : ''; ?>
								<strong class="eaw__name"><?php echo esc_html( $card['name'] ); ?></strong>
								<?php echo '' !== $card['detail'] ? '<span class="eaw__detail">' . esc_html( $card['detail'] ) . '</span>' : ''; ?>
							</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
		<?php
	}
}
