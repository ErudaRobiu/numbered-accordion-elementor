<?php
/**
 * Annotated Mark widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Elements\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use ErudaToolkit\Modules\Elements\Elements_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-elements-widget.php';

/**
 * The logo with a labelled tag for each element, joined by a leader line to
 * the part of the mark it describes. Pointing at a tag lights the same
 * element in every linked widget.
 */
class Annotated_Mark_Widget extends Elements_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eam-annotated-mark';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Annotated Mark', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-hotspot';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'logo', 'annotated', 'hotspots', 'labels', 'elements', 'linked', 'about' );
	}

	/**
	 * Where the bundled mark lives.
	 *
	 * @return string
	 */
	private function logo_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . Elements_Content::LOGO;
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_mark',
			array( 'label' => esc_html__( 'Mark', 'numbered-accordion' ) )
		);

		$this->add_control(
			'logo',
			array(
				'label'   => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => $this->logo_url() ),
			)
		);

		$this->add_control(
			'logo_alt',
			array(
				'label'   => esc_html__( 'Logo read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'ThermStar logo mark', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'logo_width',
			array(
				'label'      => esc_html__( 'Logo width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 20, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .eam' => '--eam-logo: {{SIZE}}%;' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'       => esc_html__( 'Max width', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%' ),
				'range'       => array( 'px' => array( 'min' => 240, 'max' => 1200 ) ),
				'description' => esc_html__( 'Empty fills the column. The mark stays centred.', 'numbered-accordion' ),
				'selectors'   => array( '{{WRAPPER}} .eam' => '--eam-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Labels', 'numbered-accordion' ) )
		);

		$items = new Repeater();
		$this->add_item_basics( $items );

		$items->add_control(
			'corner',
			array(
				'label'   => esc_html__( 'Label sits', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'tl',
				'options' => array(
					'tl' => esc_html__( 'Top left', 'numbered-accordion' ),
					'tr' => esc_html__( 'Top right', 'numbered-accordion' ),
					'bl' => esc_html__( 'Bottom left', 'numbered-accordion' ),
					'br' => esc_html__( 'Bottom right', 'numbered-accordion' ),
				),
			)
		);

		foreach ( array(
			'tag_y' => esc_html__( 'Label height (%)', 'numbered-accordion' ),
			'dot_x' => esc_html__( 'Dot across (%)', 'numbered-accordion' ),
			'dot_y' => esc_html__( 'Dot down (%)', 'numbered-accordion' ),
		) as $id => $label ) {
			$items->add_control(
				$id,
				array(
					'label'      => $label,
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( '%' ),
					'range'      => array( '%' => array( 'min' => 0, 'max' => 100, 'step' => 0.1 ) ),
				)
			);
		}

		$defaults = array();

		foreach ( Elements_Content::default_items() as $item ) {
			$defaults[] = array(
				'key'    => $item['key'],
				'icon'   => $item['icon'],
				'colour' => $item['colour'],
				'name'   => $item['name'],
				'corner' => $item['corner'],
				'tag_y'  => array( 'unit' => '%', 'size' => $item['tag_y'] ),
				'dot_x'  => array( 'unit' => '%', 'size' => $item['dot_x'] ),
				'dot_y'  => array( 'unit' => '%', 'size' => $item['dot_y'] ),
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Labels', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ name }}} ({{{ key }}})',
			)
		);

		$this->add_link_group_control();

		$this->add_control(
			'mark_label',
			array(
				'label'   => esc_html__( 'Read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'The ThermStar mark with its four elements labelled', 'numbered-accordion' ),
			)
		);

		foreach ( array(
			'rings'   => array( esc_html__( 'Dashed rings', 'numbered-accordion' ), 'yes' ),
			'glow'    => array( esc_html__( 'Soft glow', 'numbered-accordion' ), 'yes' ),
			'animate' => array( esc_html__( 'Build in on scroll', 'numbered-accordion' ), 'yes' ),
		) as $id => $switch ) {
			$this->add_control(
				$id,
				array(
					'label'        => $switch[0],
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => $switch[1],
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Labels and lines', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tag_type',
				'label'    => esc_html__( 'Label type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .eam__tag',
			)
		);

		foreach ( array(
			'line'   => array( esc_html__( 'Lines', 'numbered-accordion' ), '--eam-line' ),
			'active' => array( esc_html__( 'Active: label, line, dot', 'numbered-accordion' ), '--eam-active' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eam' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'dot',
			array(
				'label'      => esc_html__( 'Dot size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 6, 'max' => 24 ) ),
				'selectors'  => array( '{{WRAPPER}} .eam' => '--eam-dot: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$items    = Elements_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );
		$logo     = isset( $settings['logo'] ) && is_array( $settings['logo'] ) && isset( $settings['logo']['url'] ) ? trim( (string) $settings['logo']['url'] ) : '';
		$alt      = isset( $settings['logo_alt'] ) && is_scalar( $settings['logo_alt'] ) ? trim( (string) $settings['logo_alt'] ) : '';
		$label    = isset( $settings['mark_label'] ) && is_scalar( $settings['mark_label'] ) ? trim( (string) $settings['mark_label'] ) : '';
		$on       = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		if ( '' === $logo && empty( $items ) ) {
			return;
		}

		$classes = 'eam' . ( $on( 'rings' ) ? ' eam--rings' : '' ) . ( $on( 'glow' ) ? ' eam--glow' : '' ) . ( $on( 'animate' ) ? ' eam--animate' : '' );
		?>
		<div class="eam-wrap">
			<figure class="<?php echo esc_attr( $classes ); ?>" data-link-group="<?php echo esc_attr( $this->group( $settings ) ); ?>"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
				<?php if ( $on( 'rings' ) ) : ?>
					<svg class="eam__rings" viewBox="0 0 560 520" aria-hidden="true" focusable="false"><circle cx="280" cy="260" r="200"/><circle cx="280" cy="260" r="245"/></svg>
				<?php endif; ?>

				<?php if ( '' !== $logo ) : ?>
					<img class="eam__logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $alt ); ?>" decoding="async" />
				<?php endif; ?>

				<svg class="eam__lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
					<?php foreach ( $items as $i => $item ) : ?>
						<path data-key="<?php echo esc_attr( $item['key'] ); ?>" class="eam__line eam__line--<?php echo esc_attr( substr( $item['corner'], 1, 1 ) ); ?>" style="--eam-i:<?php echo (int) $i; ?>" d="<?php echo esc_attr( Elements_Content::guess_path( $item ) ); ?>" vector-effect="non-scaling-stroke"/>
					<?php endforeach; ?>
				</svg>

				<?php foreach ( $items as $i => $item ) : ?>
					<span class="eam__dot" data-key="<?php echo esc_attr( $item['key'] ); ?>" style="left:<?php echo esc_attr( $item['dot_x'] ); ?>%;top:<?php echo esc_attr( $item['dot_y'] ); ?>%;--eam-i:<?php echo (int) $i; ?>" aria-hidden="true"></span>
				<?php endforeach; ?>

				<?php foreach ( $items as $i => $item ) : ?>
					<span class="eam__tag eam__tag--<?php echo esc_attr( $item['corner'] ); ?>" data-key="<?php echo esc_attr( $item['key'] ); ?>" tabindex="0" style="top:<?php echo esc_attr( $item['tag_y'] ); ?>%;--eam-i:<?php echo (int) $i; ?><?php echo '' !== $item['colour'] ? ';--eam-c:' . esc_attr( $item['colour'] ) : ''; ?>">
						<span class="eam__ic" aria-hidden="true"><?php echo $this->icon( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG or Elementor's icon markup ?></span><?php echo esc_html( $item['name'] ); ?>
					</span>
				<?php endforeach; ?>
			</figure>
		</div>
		<?php
	}
}
