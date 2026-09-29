<?php
/**
 * Customer Logo Tabs widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\LogoTabs\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\LogoTabs\LogoTabs_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A customer logo wall split into audience tabs, filtered by segment chips.
 */
class Customer_Logo_Tabs_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eclt-customer-logo-tabs';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Customer Logo Tabs', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-logo';
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
		return array( 'logos', 'customers', 'clients', 'tabs', 'filter', 'wall', 'portfolio' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\LogoTabs\LogoTabs_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\LogoTabs\LogoTabs_Module::SCRIPT_HANDLE );
	}

	/**
	 * Where the bundled logos live.
	 *
	 * @return string
	 */
	private function logo_url() {
		return ( defined( 'ERUDA_URL' ) ? ERUDA_URL : '' ) . LogoTabs_Content::LOGO_DIR;
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_tab_controls();
		$this->register_logo_controls();
		$this->register_tab_style_controls();
		$this->register_chip_style_controls();
		$this->register_wall_style_controls();
	}

	/**
	 * The tabs, which one opens, and the chips.
	 */
	private function register_tab_controls() {
		$this->start_controls_section(
			'section_tabs',
			array( 'label' => esc_html__( 'Tabs', 'numbered-accordion' ) )
		);

		$tabs = new Repeater();

		$tabs->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$tabs->add_control(
			'subline',
			array(
				'label'       => esc_html__( 'Subline', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$tabs->add_control(
			'key',
			array(
				'label'       => esc_html__( 'Link name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'A page address ending in # and this opens the tab, e.g. /customers/#ind. Empty uses the title.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'tabs',
			array(
				'label'       => esc_html__( 'Tabs', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $tabs->get_controls(),
				'default'     => LogoTabs_Content::default_tabs(),
				'title_field' => '{{{ title }}}',
				'max_items'   => LogoTabs_Content::MAX_TABS,
			)
		);

		$this->add_control(
			'default_tab',
			array(
				'label'       => esc_html__( 'Opens on tab', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => LogoTabs_Content::MAX_TABS,
				'default'     => 1,
				'description' => esc_html__( 'Counted from the left. A link name in the address wins over this.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'show_all',
			array(
				'label'        => esc_html__( 'Show the "All" chip', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Off, a tab opens on its first segment. A tab with only one segment never shows chips.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'all_label',
			array(
				'label'     => esc_html__( '"All" chip text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All', 'numbered-accordion' ),
				'condition' => array( 'show_all' => 'yes' ),
			)
		);

		$this->add_control(
			'tablist_label',
			array(
				'label'       => esc_html__( 'Tabs read aloud as', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Customers by sector', 'numbered-accordion' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Fade the tiles in', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'On every tab or chip change, in a quick stagger. Visitors who ask for less motion never get it.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The logos, one flat list.
	 */
	private function register_logo_controls() {
		$this->start_controls_section(
			'section_logos',
			array( 'label' => esc_html__( 'Logos', 'numbered-accordion' ) )
		);

		$this->add_control(
			'logos_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Elementor cannot put a list inside a list, so every logo names its tab and its segment. A tab\'s chips are the segment names its logos use, in the order they first appear. Spell a segment the same way on every logo in it.', 'numbered-accordion' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$options = array();

		for ( $i = 1; $i <= LogoTabs_Content::MAX_TABS; $i++ ) {
			/* translators: %d: tab position */
			$options[ (string) $i ] = sprintf( esc_html__( 'Tab %d', 'numbered-accordion' ), $i );
		}

		$logos = new Repeater();

		$logos->add_control(
			'tab',
			array(
				'label'   => esc_html__( 'Tab', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $options,
				'default' => '1',
			)
		);

		$logos->add_control(
			'segment',
			array(
				'label'       => esc_html__( 'Segment', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$logos->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Transparent and trimmed. It is sized by its shape so wide and square marks look the same weight.', 'numbered-accordion' ),
			)
		);

		$logos->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Organisation name', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => esc_html__( 'Read aloud in place of the logo.', 'numbered-accordion' ),
			)
		);

		$logos->add_control(
			'word',
			array(
				'label'       => esc_html__( 'Wordmark instead', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Until there is a logo file, the name set in type. Ignored once a logo is chosen.', 'numbered-accordion' ),
			)
		);

		$logos->add_control(
			'word_style',
			array(
				'label'     => esc_html__( 'Wordmark type', 'numbered-accordion' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'serif',
				'options'   => array(
					'serif' => esc_html__( 'Serif, spaced', 'numbered-accordion' ),
					'sans'  => esc_html__( 'Bold sans', 'numbered-accordion' ),
				),
				'condition' => array( 'word!' => '' ),
			)
		);

		$logos->add_control(
			'dark',
			array(
				'label'        => esc_html__( 'Dark tile', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'For a white logo.', 'numbered-accordion' ),
			)
		);

		$logos->add_control(
			'width',
			array(
				'label'       => esc_html__( 'Width', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array(
					'px' => array(
						'min' => 16,
						'max' => 200,
					),
				),
				'description' => esc_html__( 'Empty sizes it by eye. Set it only if one logo still looks off.', 'numbered-accordion' ),
			)
		);

		$logos->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);

		$this->add_control(
			'logos',
			array(
				'label'       => esc_html__( 'Logos', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $logos->get_controls(),
				'default'     => $this->default_logos(),
				'title_field' => '{{{ tab }}} · {{{ segment }}} · {{{ name }}}',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The page 24 customer base as repeater rows.
	 *
	 * @return array
	 */
	private function default_logos() {
		$rows = array();

		foreach ( LogoTabs_Content::default_logos() as $logo ) {
			$rows[] = array(
				'tab'        => (string) $logo['tab'],
				'segment'    => $logo['segment'],
				'image'      => array( 'url' => '' === $logo['file'] ? '' : $this->logo_url() . $logo['file'] ),
				'name'       => $logo['name'],
				'word'       => $logo['word'],
				'word_style' => $logo['word_style'],
				'dark'       => $logo['dark'] ? 'yes' : '',
			);
		}

		return $rows;
	}

	/**
	 * Tab colours and type.
	 */
	private function register_tab_style_controls() {
		$this->start_controls_section(
			'section_style_tabs',
			array(
				'label' => esc_html__( 'Tabs', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colours = array(
			'tab_active_bg'      => array( esc_html__( 'Selected background', 'numbered-accordion' ), '--eclt-navy' ),
			'tab_active_subline' => array( esc_html__( 'Selected subline', 'numbered-accordion' ), '--eclt-green-light' ),
			'tab_border'         => array( esc_html__( 'Border', 'numbered-accordion' ), '--eclt-line' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eclt' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_responsive_control(
			'tab_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .eclt' => '--eclt-tab-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tab_title_typography',
				'label'    => esc_html__( 'Title type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .eclt__tab strong',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tab_subline_typography',
				'label'    => esc_html__( 'Subline type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .eclt__tab span',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Chip colours.
	 */
	private function register_chip_style_controls() {
		$this->start_controls_section(
			'section_style_chips',
			array(
				'label' => esc_html__( 'Chips', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'chip_active_bg',
			array(
				'label'     => esc_html__( 'Selected chip', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .eclt' => '--eclt-green: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chip_typography',
				'label'    => esc_html__( 'Chip type', 'numbered-accordion' ),
				'selector' => '{{WRAPPER}} .eclt__chip',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The wall and its tiles.
	 */
	private function register_wall_style_controls() {
		$this->start_controls_section(
			'section_style_wall',
			array(
				'label' => esc_html__( 'Logo wall', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$sizes = array(
			'tile_min'    => array( esc_html__( 'Narrowest tile', 'numbered-accordion' ), '--eclt-min', 80, 320, esc_html__( 'Tiles fill the row and wrap below this width. Phones always get three across.', 'numbered-accordion' ) ),
			'tile_height' => array( esc_html__( 'Tile height', 'numbered-accordion' ), '--eclt-h', 48, 200, esc_html__( 'Phones always get 80px tiles.', 'numbered-accordion' ) ),
			'tile_gap'    => array( esc_html__( 'Gap', 'numbered-accordion' ), '--eclt-gap', 0, 40, esc_html__( 'Phones always get 8px.', 'numbered-accordion' ) ),
			'tile_radius' => array( esc_html__( 'Corner radius', 'numbered-accordion' ), '--eclt-radius', 0, 40, '' ),
		);

		// Not responsive: phones take fixed sizes from the stylesheet, and a
		// per-device value here would be silently beaten by them.
		foreach ( $sizes as $id => $size ) {
			$this->add_control(
				$id,
				array(
					'label'       => $size[0],
					'type'        => Controls_Manager::SLIDER,
					'size_units'  => array( 'px' ),
					'range'       => array( 'px' => array( 'min' => $size[2], 'max' => $size[3] ) ),
					'description' => $size[4],
					'selectors'   => array( '{{WRAPPER}} .eclt' => $size[1] . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		$colours = array(
			'tile_bg'      => array( esc_html__( 'Tile', 'numbered-accordion' ), '--eclt-tile' ),
			'tile_border'  => array( esc_html__( 'Tile border', 'numbered-accordion' ), '--eclt-tile-line' ),
			'tile_dark'    => array( esc_html__( 'Dark tile', 'numbered-accordion' ), '--eclt-dark' ),
			'wordmark_ink' => array( esc_html__( 'Wordmark', 'numbered-accordion' ), '--eclt-word' ),
		);

		foreach ( $colours as $id => $colour ) {
			$this->add_control(
				$id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eclt' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * How wide a logo sits, when it can be known on the server.
	 *
	 * @param array $logo Built logo.
	 * @return int 0 leaves it to the script, which reads the loaded picture.
	 */
	private function logo_width( $logo ) {
		if ( $logo['width'] > 0 ) {
			return $logo['width'];
		}

		$aspect = LogoTabs_Content::bundled_aspect( $logo['url'] );

		if ( $aspect <= 0 && $logo['id'] > 0 && function_exists( 'wp_get_attachment_metadata' ) ) {
			$meta = wp_get_attachment_metadata( $logo['id'] );

			if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				$aspect = (float) $meta['width'] / (float) $meta['height'];
			}
		}

		return LogoTabs_Content::optical_width( $aspect );
	}

	/**
	 * Print one tile.
	 *
	 * @param array $logo   Built logo.
	 * @param bool  $hidden Filtered out by the segment the tab opens on.
	 */
	private function render_tile( $logo, $hidden = false ) {
		$class = 'eclt__tile' . ( $logo['dark'] ? ' eclt__tile--dark' : '' );
		$rel   = array_filter( array( $logo['nofollow'] ? 'nofollow' : '', $logo['external'] ? 'noopener' : '' ) );
		?>
		<li class="<?php echo esc_attr( $class ); ?>" data-seg="<?php echo esc_attr( $logo['segment'] ); ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
			<?php if ( '' !== $logo['link'] ) : ?>
				<a class="eclt__link" href="<?php echo esc_url( $logo['link'] ); ?>"<?php echo $logo['external'] ? ' target="_blank"' : ''; ?><?php echo $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : ''; ?>>
			<?php endif; ?>
			<?php
			if ( '' !== $logo['url'] ) {
				$width = $this->logo_width( $logo );
				printf(
					'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async"%3$s />',
					esc_url( $logo['url'] ),
					esc_attr( $logo['name'] ),
					$width > 0 ? ' style="width:' . (int) $width . 'px"' : ' data-eclt-fit'
				);
			} else {
				printf(
					'<span class="eclt__word eclt__word--%1$s" role="img" aria-label="%2$s">%3$s</span>',
					esc_attr( $logo['word_style'] ),
					esc_attr( '' !== $logo['name'] ? $logo['name'] : $logo['word'] ),
					esc_html( $logo['word'] )
				);
			}
			?>
			<?php if ( '' !== $logo['link'] ) : ?>
				</a>
			<?php endif; ?>
		</li>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$tabs     = LogoTabs_Content::build(
			isset( $settings['tabs'] ) ? $settings['tabs'] : array(),
			isset( $settings['logos'] ) ? $settings['logos'] : array()
		);

		if ( empty( $tabs ) ) {
			return;
		}

		$open     = LogoTabs_Content::default_index( isset( $settings['default_tab'] ) ? $settings['default_tab'] : 1, count( $tabs ) );
		$show_all = isset( $settings['show_all'] ) && 'yes' === $settings['show_all'];
		$all      = isset( $settings['all_label'] ) && is_scalar( $settings['all_label'] ) && '' !== trim( (string) $settings['all_label'] ) ? trim( (string) $settings['all_label'] ) : __( 'All', 'numbered-accordion' );
		$label    = isset( $settings['tablist_label'] ) && is_scalar( $settings['tablist_label'] ) ? trim( (string) $settings['tablist_label'] ) : '';
		$animate  = isset( $settings['animate'] ) && 'yes' === $settings['animate'];
		$uid      = 'eclt-' . ( method_exists( $this, 'get_id' ) ? $this->get_id() : substr( md5( implode( ',', array_column( $tabs, 'key' ) ) ), 0, 7 ) );
		?>
		<div class="eclt<?php echo $animate ? ' eclt--animate' : ''; ?>">
			<div class="eclt__tabs" role="tablist"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
				<?php foreach ( $tabs as $i => $tab ) : ?>
					<button type="button" class="eclt__tab" role="tab"
						id="<?php echo esc_attr( $uid . '-t-' . $tab['key'] ); ?>"
						aria-controls="<?php echo esc_attr( $uid . '-p-' . $tab['key'] ); ?>"
						aria-selected="<?php echo $i === $open ? 'true' : 'false'; ?>"
						tabindex="<?php echo $i === $open ? '0' : '-1'; ?>"
						data-key="<?php echo esc_attr( $tab['key'] ); ?>">
						<strong><?php echo esc_html( $tab['title'] ); ?></strong>
						<?php if ( '' !== $tab['subline'] ) : ?>
							<span><?php echo esc_html( $tab['subline'] ); ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $tabs as $i => $tab ) : ?>
				<?php
				$chips  = count( $tab['segments'] ) > 1;
				$active = $show_all ? 'all' : (string) key( $tab['segments'] );
				?>
				<div class="eclt__panel" role="tabpanel" tabindex="0"
					id="<?php echo esc_attr( $uid . '-p-' . $tab['key'] ); ?>"
					aria-labelledby="<?php echo esc_attr( $uid . '-t-' . $tab['key'] ); ?>"
					<?php echo $i === $open ? '' : 'hidden'; ?>>
					<?php if ( $chips ) : ?>
						<div class="eclt__chips" role="group" aria-label="<?php esc_attr_e( 'Filter by segment', 'numbered-accordion' ); ?>">
							<?php if ( $show_all ) : ?>
								<button type="button" class="eclt__chip" data-seg="all" aria-pressed="true"><?php echo esc_html( $all ); ?></button>
							<?php endif; ?>
							<?php foreach ( $tab['segments'] as $seg => $seg_label ) : ?>
								<button type="button" class="eclt__chip" data-seg="<?php echo esc_attr( $seg ); ?>" aria-pressed="<?php echo $seg === $active ? 'true' : 'false'; ?>"><?php echo esc_html( $seg_label ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<ul class="eclt__wall" role="list">
						<?php
						foreach ( $tab['logos'] as $logo ) {
							// Without "All", a tab opens on its first segment,
							// and the rest are hidden from the server so the
							// whole wall never flashes before the script runs.
							$this->render_tile( $logo, $chips && 'all' !== $active && $logo['segment'] !== $active );
						}
						?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

