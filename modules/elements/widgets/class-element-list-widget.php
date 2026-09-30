<?php
/**
 * Element List widget.
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
 * The elements as a list: a tinted icon tile, the name and what it stands
 * for. Pointing at a row lights the same element in every linked widget.
 */
class Element_List_Widget extends Elements_Widget {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eel-element-list';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Element List', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-bullet-list';
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'elements', 'logo', 'list', 'linked', 'legend', 'about' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Elements', 'numbered-accordion' ) )
		);

		$items = new Repeater();
		$this->add_item_basics( $items );

		$items->add_control(
			'sub',
			array(
				'label'       => esc_html__( 'After the name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$items->add_control(
			'text',
			array(
				'label' => esc_html__( 'Description', 'numbered-accordion' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 3,
			)
		);

		$defaults = array();

		foreach ( Elements_Content::default_items() as $item ) {
			$defaults[] = array(
				'key'    => $item['key'],
				'icon'   => $item['icon'],
				'colour' => $item['colour'],
				'name'   => $item['name'],
				'sub'    => $item['sub'],
				'text'   => $item['text'],
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Elements', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ name }}} ({{{ key }}})',
			)
		);

		$this->add_link_group_control();

		$this->add_control(
			'cycle',
			array(
				'label'        => esc_html__( 'Step through them when idle', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Lights each element in turn every 3 seconds, in every linked widget, until the visitor points at one. Never for visitors who ask for less motion.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'List', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'tile',
			array(
				'label'      => esc_html__( 'Tile size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 96 ) ),
				'selectors'  => array( '{{WRAPPER}} .eel' => '--eel-tile: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'tile_radius',
			array(
				'label'      => esc_html__( 'Tile corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'selectors'  => array( '{{WRAPPER}} .eel' => '--eel-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'tint',
			array(
				'label'      => esc_html__( 'Tile tint', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .eel' => '--eel-tint: {{SIZE}}%;' ),
			)
		);

		foreach ( array(
			'divider' => array( esc_html__( 'Dividers', 'numbered-accordion' ), '--eel-line' ),
			'ink'     => array( esc_html__( 'Name', 'numbered-accordion' ), '--eel-ink' ),
			'muted'   => array( esc_html__( 'Subtitle and description', 'numbered-accordion' ), '--eel-muted' ),
			'active'  => array( esc_html__( 'Active name', 'numbered-accordion' ), '--eel-active' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eel' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'name_type' => array( esc_html__( 'Name type', 'numbered-accordion' ), '.eel__name' ),
			'sub_type'  => array( esc_html__( 'Subtitle type', 'numbered-accordion' ), '.eel__sub' ),
			'text_type' => array( esc_html__( 'Description type', 'numbered-accordion' ), '.eel__text' ),
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
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$items    = Elements_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );

		if ( empty( $items ) ) {
			return;
		}

		$cycle = isset( $settings['cycle'] ) && 'yes' === $settings['cycle'];
		?>
		<div class="eel-wrap">
			<ol class="eel" data-link-group="<?php echo esc_attr( $this->group( $settings ) ); ?>"<?php echo $cycle ? ' data-cycle="3000"' : ''; ?>>
				<?php foreach ( $items as $item ) : ?>
					<li class="eel__row" data-key="<?php echo esc_attr( $item['key'] ); ?>" tabindex="0"<?php echo '' !== $item['colour'] ? ' style="--eel-c:' . esc_attr( $item['colour'] ) . '"' : ''; ?>>
						<span class="eel__tile" aria-hidden="true"><?php echo $this->icon( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG or Elementor's icon markup ?></span>
						<span class="eel__body">
							<span class="eel__head">
								<b class="eel__name"><?php echo esc_html( $item['name'] ); ?></b>
								<?php if ( '' !== $item['sub'] ) : ?>
									<em class="eel__sub"><span class="eel__dash" aria-hidden="true">– </span><?php echo esc_html( $item['sub'] ); ?></em>
								<?php endif; ?>
							</span>
							<?php echo '' !== $item['text'] ? '<span class="eel__text">' . esc_html( $item['text'] ) . '</span>' : ''; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php
	}
}
