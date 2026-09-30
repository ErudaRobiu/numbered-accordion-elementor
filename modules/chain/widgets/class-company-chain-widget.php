<?php
/**
 * Company Chain widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Chain\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Chain\Chain_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Who makes it, who brings it, who it serves: cards joined by arrows, in a row
 * when there is room and stacked with downward arrows when there is not.
 *
 * A repeater is right here: a short, fixed list.
 */
class Company_Chain_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'echn-company-chain';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Company Chain', 'numbered-accordion' );
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
		return array( 'chain', 'companies', 'supply', 'distributor', 'flow', 'about', 'arrows' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Chain\Chain_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Chain\Chain_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Cards', 'numbered-accordion' ) )
		);

		$items = new Repeater();

		foreach ( array(
			'label' => array( esc_html__( 'Label', 'numbered-accordion' ), Controls_Manager::TEXT ),
			'name'  => array( esc_html__( 'Name', 'numbered-accordion' ), Controls_Manager::TEXT ),
			'sub'   => array( esc_html__( 'Under the name', 'numbered-accordion' ), Controls_Manager::TEXT ),
		) as $id => $field ) {
			$items->add_control(
				$id,
				array(
					'label'       => $field[0],
					'type'        => $field[1],
					'label_block' => true,
				)
			);
		}

		$items->add_control(
			'highlight',
			array(
				'label'        => esc_html__( 'Highlight', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'Navy, a little wider, and the last to land.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'logo',
			array(
				'label'       => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Optional, above the label. On the highlighted card use a white logo.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);

		$defaults = array();

		foreach ( Chain_Content::default_items() as $item ) {
			$defaults[] = array(
				'label'     => $item['label'],
				'name'      => $item['name'],
				'sub'       => $item['sub'],
				'highlight' => $item['highlight'] ? 'yes' : '',
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Cards', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ name }}}',
			)
		);

		$this->add_control(
			'chain_label',
			array(
				'label'   => esc_html__( 'Read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'How the companies connect', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_behaviour',
			array( 'label' => esc_html__( 'Layout and motion', 'numbered-accordion' ) )
		);

		$this->add_control(
			'stack_at',
			array(
				'label'       => esc_html__( 'Stack below', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 240, 'max' => 1200, 'step' => 10 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 520 ),
				'description' => esc_html__( 'The widget\'s own width, not the screen\'s: in a half column it stacks sooner. On phones it always stacks.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'arrow_style',
			array(
				'label'   => esc_html__( 'Arrow', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'arrow',
				'options' => array(
					'arrow'   => esc_html__( 'Line and head', 'numbered-accordion' ),
					'chevron' => esc_html__( 'Chevron', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Build in on scroll', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Once: each card, then the arrow to the next. Not in the editor, never for visitors who ask for less motion.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'glow',
			array(
				'label'        => esc_html__( 'Glow on the highlighted card', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'A slow, soft green glow that keeps going.', 'numbered-accordion' ),
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
				'label' => esc_html__( 'Cards', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		foreach ( array(
			'radius'  => array( esc_html__( 'Corner radius', 'numbered-accordion' ), '--echn-radius', 0, 32 ),
			'padding' => array( esc_html__( 'Padding', 'numbered-accordion' ), '--echn-pad', 6, 40 ),
			'gap'     => array( esc_html__( 'Gap', 'numbered-accordion' ), '--echn-gap', 0, 32 ),
		) as $id => $size ) {
			$this->add_responsive_control(
				$id,
				array(
					'label'      => $size[0],
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => $size[2], 'max' => $size[3] ) ),
					'selectors'  => array( '{{WRAPPER}} .echn' => $size[1] . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		foreach ( array(
			'card_bg'      => array( esc_html__( 'Card', 'numbered-accordion' ), '--echn-card' ),
			'card_border'  => array( esc_html__( 'Border', 'numbered-accordion' ), '--echn-line' ),
			'ink'          => array( esc_html__( 'Name', 'numbered-accordion' ), '--echn-ink' ),
			'muted'        => array( esc_html__( 'Label and sub-line', 'numbered-accordion' ), '--echn-muted' ),
			'hi_bg'        => array( esc_html__( 'Highlighted card', 'numbered-accordion' ), '--echn-hi' ),
			'hi_label'     => array( esc_html__( 'Highlighted label', 'numbered-accordion' ), '--echn-hi-label' ),
			'hi_ink'       => array( esc_html__( 'Highlighted name', 'numbered-accordion' ), '--echn-hi-ink' ),
			'hi_sub'       => array( esc_html__( 'Highlighted sub-line', 'numbered-accordion' ), '--echn-hi-sub' ),
			'arrow_colour' => array( esc_html__( 'Arrows and hover', 'numbered-accordion' ), '--echn-green' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .echn' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'label_type' => array( esc_html__( 'Label type', 'numbered-accordion' ), '.echn__label' ),
			'name_type'  => array( esc_html__( 'Name type', 'numbered-accordion' ), '.echn__name' ),
			'sub_type'   => array( esc_html__( 'Sub-line type', 'numbered-accordion' ), '.echn__sub' ),
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
	 * One arrow.
	 *
	 * @param string $style arrow or chevron.
	 * @param int    $step  Its place in the entrance.
	 * @return string
	 */
	private function arrow( $style, $step ) {
		$svg = 'chevron' === $style
			? '<svg viewBox="0 0 16 24" aria-hidden="true" focusable="false"><path d="M4 3l9 9-9 9" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			: '<svg viewBox="0 0 30 16" aria-hidden="true" focusable="false"><path d="M1 8h24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M20 2.5L27 8l-7 5.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		return '<span class="echn__arrow echn__arrow--' . esc_attr( $style ) . '" style="--echn-i:' . (int) $step . '" aria-hidden="true">' . $svg . '</span>';
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$cards    = Chain_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );

		if ( empty( $cards ) ) {
			return;
		}

		$animate = isset( $settings['animate'] ) && 'yes' === $settings['animate'];
		$glow    = isset( $settings['glow'] ) && 'yes' === $settings['glow'];
		$style   = isset( $settings['arrow_style'] ) && 'chevron' === $settings['arrow_style'] ? 'chevron' : 'arrow';
		$label   = isset( $settings['chain_label'] ) && is_scalar( $settings['chain_label'] ) ? trim( (string) $settings['chain_label'] ) : '';
		$logos   = count( array_filter( array_column( $cards, 'logo' ) ) ) > 0;
		$steps   = 2 * count( $cards ) - 1;
		$classes = 'echn' . ( $animate ? ' echn--animate' : '' ) . ( $glow ? ' echn--glow' : '' ) . ( $logos ? ' echn--logos' : '' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" role="list" data-stack-at="<?php echo (int) Chain_Content::stack_at( isset( $settings['stack_at'] ) ? $settings['stack_at'] : 520 ); ?>" style="--echn-cols:<?php echo esc_attr( Chain_Content::columns( $cards ) ); ?>;--echn-n:<?php echo (int) $steps; ?>"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
			<?php foreach ( $cards as $i => $card ) : ?>
				<?php
				if ( $i > 0 ) {
					echo $this->arrow( $style, 2 * $i - 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup
				}

				$tag   = '' !== $card['link'] ? 'a' : 'div';
				$rel   = array_filter( array( $card['nofollow'] ? 'nofollow' : '', $card['external'] ? 'noopener' : '' ) );
				$attrs = '' !== $card['link']
					? ' href="' . esc_url( $card['link'] ) . '"' . ( $card['external'] ? ' target="_blank"' : '' ) . ( $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '' )
					: '';
				?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="echn__card<?php echo $card['highlight'] ? ' is-hi' : ''; ?>" role="listitem" style="--echn-i:<?php echo (int) ( 2 * $i ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped parts ?>>
					<span class="echn__logo"><?php echo '' !== $card['logo'] ? '<img src="' . esc_url( $card['logo'] ) . '" alt="" loading="lazy" decoding="async" />' : ''; ?></span>
					<small class="echn__label"><?php echo esc_html( $card['label'] ); ?></small>
					<b class="echn__name"><?php echo esc_html( $card['name'] ); ?></b>
					<span class="echn__sub"><?php echo esc_html( $card['sub'] ); ?></span>
				</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
