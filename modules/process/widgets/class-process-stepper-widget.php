<?php
/**
 * Process Stepper widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Process\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Process\Process_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Numbered stages on a rail, a green line filling up to the one picked, and
 * a panel under the rail that explains it, its caret under the stage. Scrolls
 * sideways inside itself when space runs short, and turns into a vertical
 * stepper whose picked row opens in place when shorter still.
 *
 * A repeater is right here: a process is a short fixed list.
 */
class Process_Stepper_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eps-process-stepper';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Process Stepper', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-time-line';
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
		return array( 'process', 'steps', 'stepper', 'stages', 'how it works', 'timeline', 'rail', 'services' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Process\Process_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Process\Process_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_items',
			array( 'label' => esc_html__( 'Stages', 'numbered-accordion' ) )
		);

		$items = new Repeater();

		$items->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'numbered-accordion' ),
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

		$items->add_control(
			'number',
			array(
				'label'       => esc_html__( 'Number', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Empty counts for you: 01, 02, 03.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'numbered-accordion' ),
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => '' ),
				'description' => esc_html__( 'Optional. Without one, the fallback button shows instead.', 'numbered-accordion' ),
			)
		);

		$items->add_control(
			'link_text',
			array(
				'label'       => esc_html__( 'Link text', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => Process_Content::LINK_TEXT,
			)
		);

		$items->add_control(
			'start',
			array(
				'label'        => esc_html__( 'Start here', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'Solid green, with the start badge above it.', 'numbered-accordion' ),
			)
		);

		$defaults = array();

		foreach ( Process_Content::default_items() as $item ) {
			$defaults[] = array(
				'title' => $item['title'],
				'text'  => $item['text'],
				'link'  => array( 'url' => $item['link'] ),
				'start' => $item['start'] ? 'yes' : '',
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Stages', 'numbered-accordion' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_control(
			'list_label',
			array(
				'label'   => esc_html__( 'Read aloud as', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'How ThermStar works, step by step', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_settings',
			array( 'label' => esc_html__( 'Settings', 'numbered-accordion' ) )
		);

		$this->add_control(
			'badge',
			array(
				'label'   => esc_html__( 'Start badge', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'You start here', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'initial',
			array(
				'label'       => esc_html__( 'Open on stage', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'description' => esc_html__( 'Empty opens on the start stage.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'fallback_text',
			array(
				'label'       => esc_html__( 'Fallback button', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Request an Assessment', 'numbered-accordion' ),
				'description' => esc_html__( 'A quieter button for stages without their own link, so the panel stays balanced. Empty lets the text run the full width.', 'numbered-accordion' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'fallback_link',
			array(
				'label'   => esc_html__( 'Fallback link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '/services/thermal-energy-opportunity-screen/' ),
			)
		);

		$this->add_control(
			'auto',
			array(
				'label'        => esc_html__( 'Auto-advance', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
				'description'  => esc_html__( 'Steps through the stages while in view, until the visitor points at one. Not in the editor, and never for visitors who ask for less motion.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'interval',
			array(
				'label'      => esc_html__( 'Every', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array( 's' => array( 'min' => 2, 'max' => 15, 'step' => 0.5 ) ),
				'default'    => array( 'unit' => 's', 'size' => 4 ),
				'condition'  => array( 'auto' => 'yes' ),
			)
		);

		$this->add_control(
			'scroll_below',
			array(
				'label'       => esc_html__( 'Scroll sideways below', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 300, 'max' => 1600, 'step' => 10 ) ),
				'default'     => array( 'unit' => 'px', 'size' => Process_Content::SCROLL_BELOW ),
				'description' => esc_html__( 'The widget\'s own width, not the screen\'s.', 'numbered-accordion' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'vertical_below',
			array(
				'label'      => esc_html__( 'Go vertical below', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 1200, 'step' => 10 ) ),
				'default'    => array( 'unit' => 'px', 'size' => Process_Content::VERTICAL_BELOW ),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Build in on scroll', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'description'  => esc_html__( 'Once: the line draws across and the stages land one by one. The panel also fades its text on each change.', 'numbered-accordion' ),
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
			'section_style_rail',
			array(
				'label' => esc_html__( 'Rail', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'size',
			array(
				'label'      => esc_html__( 'Circle size', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 96 ) ),
				'selectors'  => array( '{{WRAPPER}} .eps' => '--eps-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		foreach ( array(
			'line'     => array( esc_html__( 'Line', 'numbered-accordion' ), '--eps-line' ),
			'fill'     => array( esc_html__( 'Progress fill', 'numbered-accordion' ), '--eps-fill' ),
			'border'   => array( esc_html__( 'Circle border', 'numbered-accordion' ), '--eps-border' ),
			'circle'   => array( esc_html__( 'Circle', 'numbered-accordion' ), '--eps-circle' ),
			'num'      => array( esc_html__( 'Number', 'numbered-accordion' ), '--eps-num' ),
			'selected' => array( esc_html__( 'Selected, start and done', 'numbered-accordion' ), '--eps-sel' ),
			'halo'     => array( esc_html__( 'Halo', 'numbered-accordion' ), '--eps-halo' ),
			'label'    => array( esc_html__( 'Labels', 'numbered-accordion' ), '--eps-label' ),
			'label_on' => array( esc_html__( 'Selected label', 'numbered-accordion' ), '--eps-label-on' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eps' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		foreach ( array(
			'num_type'   => array( esc_html__( 'Number type', 'numbered-accordion' ), '.eps__dot' ),
			'label_type' => array( esc_html__( 'Label type', 'numbered-accordion' ), '.eps__label' ),
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

		$this->start_controls_section(
			'section_style_panel',
			array(
				'label' => esc_html__( 'Panel', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		foreach ( array(
			'panel_bg'     => array( esc_html__( 'Background', 'numbered-accordion' ), '--eps-panel' ),
			'panel_border' => array( esc_html__( 'Border', 'numbered-accordion' ), '--eps-panel-line' ),
			'panel_num'    => array( esc_html__( 'Number', 'numbered-accordion' ), '--eps-panel-num' ),
			'panel_title'  => array( esc_html__( 'Title', 'numbered-accordion' ), '--eps-title' ),
			'panel_text'   => array( esc_html__( 'Description', 'numbered-accordion' ), '--eps-text' ),
			'button'       => array( esc_html__( 'Button', 'numbered-accordion' ), '--eps-button' ),
		) as $id => $colour ) {
			$this->add_control(
				'colour_' . $id,
				array(
					'label'     => $colour[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} .eps' => $colour[1] . ': {{VALUE}};' ),
				)
			);
		}

		$this->add_control(
			'panel_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .eps' => '--eps-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'panel_shadow',
				'selector' => '{{WRAPPER}} .eps__panel',
			)
		);

		foreach ( array(
			'pn_type'     => array( esc_html__( 'Panel number type', 'numbered-accordion' ), '.eps__pn' ),
			'title_type'  => array( esc_html__( 'Title type', 'numbered-accordion' ), '.eps__title' ),
			'text_type'   => array( esc_html__( 'Description type', 'numbered-accordion' ), '.eps__desc' ),
			'button_type' => array( esc_html__( 'Button type', 'numbered-accordion' ), '.eps__link' ),
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
	 * A stage's button: its own link, else the fallback, else nothing.
	 *
	 * @param array $stage    Built stage.
	 * @param array $fallback Fallback link and text.
	 * @return string
	 */
	private function action( $stage, $fallback ) {
		$link  = '' !== $stage['link']['url'] ? $stage['link'] : ( '' !== $fallback['text'] && '' !== $fallback['url'] ? $fallback : null );
		$quiet = '' === $stage['link']['url'];

		if ( ! $link ) {
			return '';
		}

		$rel = array_filter( array( $link['nofollow'] ? 'nofollow' : '', $link['external'] ? 'noopener' : '' ) );

		return sprintf(
			'<a class="eps__link%s" href="%s"%s%s>%s</a>',
			$quiet ? ' eps__link--quiet' : '',
			esc_url( $link['url'] ),
			$link['external'] ? ' target="_blank"' : '',
			$rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '',
			esc_html( $quiet ? $fallback['text'] : $stage['link_text'] )
		);
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings = is_array( $settings ) ? $settings : array();
		$stages   = Process_Content::build( isset( $settings['items'] ) ? $settings['items'] : array() );

		if ( empty( $stages ) ) {
			return;
		}

		$text     = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
		};
		$on       = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};
		$sel      = Process_Content::initial( isset( $settings['initial'] ) ? $settings['initial'] : null, $stages );
		$count    = count( $stages );
		$breaks   = Process_Content::breakpoints(
			isset( $settings['scroll_below'] ) ? $settings['scroll_below'] : null,
			isset( $settings['vertical_below'] ) ? $settings['vertical_below'] : null
		);
		$fallback = Process_Content::link( isset( $settings['fallback_link'] ) ? $settings['fallback_link'] : '' );
		$badge    = $text( 'badge' );
		$label    = $text( 'list_label' );
		$id       = 'eps-' . preg_replace( '/[^a-z0-9]/i', '', (string) $this->get_id() );

		$fallback['text'] = $text( 'fallback_text' );

		$classes = 'eps' . ( $on( 'animate' ) ? ' eps--animate' : '' ) . ( 1 === $count ? ' eps--single' : '' );
		$data    = sprintf( ' data-scroll-below="%d" data-vertical-below="%d"', $breaks[0], $breaks[1] );

		if ( $on( 'auto' ) ) {
			$data .= sprintf( ' data-auto="%d"', Process_Content::interval( isset( $settings['interval'] ) ? $settings['interval'] : 4 ) );
		}
		?>
		<div class="eps-wrap">
			<div class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $id ); ?>" data-mode="h"<?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integers ?> style="--eps-n:<?php echo (int) $count; ?>;--eps-k:<?php echo (int) $sel; ?>">
				<div class="eps__scroller">
					<ol class="eps__rail"<?php echo '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ''; ?>>
						<?php foreach ( $stages as $i => $stage ) : ?>
							<?php
							$state = ( $stage['start'] ? ' is-start' : '' ) . ( $i === $sel ? ' is-sel' : '' ) . ( $i < $sel ? ' is-done' : '' );
							?>
							<li class="eps__item<?php echo esc_attr( $state ); ?>" style="--eps-i:<?php echo (int) $i; ?>">
								<button type="button" class="eps__stage" id="<?php echo esc_attr( $id . '-s' . $i ); ?>" aria-controls="<?php echo esc_attr( $id . '-p' . $i . ' ' . $id . '-b' . $i ); ?>" tabindex="<?php echo $i === $sel ? '0' : '-1'; ?>"<?php echo $i === $sel ? ' aria-current="step"' : ''; ?>>
									<span class="eps__dot"><?php echo esc_html( $stage['number'] ); ?></span>
									<span class="eps__name"><span class="eps__label"><?php echo esc_html( $stage['title'] ); ?></span><?php if ( $stage['start'] && '' !== $badge ) : ?> <span class="eps__badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?></span>
								</button>
								<div class="eps__body" id="<?php echo esc_attr( $id . '-b' . $i ); ?>">
									<div class="eps__inner">
										<?php if ( '' !== $stage['text'] ) : ?>
											<p class="eps__desc"><?php echo esc_html( $stage['text'] ); ?></p>
										<?php endif; ?>
										<?php echo $this->action( $stage, $fallback ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
				<div class="eps__panel" aria-live="polite">
					<?php foreach ( $stages as $i => $stage ) : ?>
						<?php $action = $this->action( $stage, $fallback ); ?>
						<div class="eps__slide<?php echo '' === $action ? ' eps__slide--wide' : ''; ?>" id="<?php echo esc_attr( $id . '-p' . $i ); ?>" data-i="<?php echo (int) $i; ?>"<?php echo $i === $sel ? '' : ' hidden'; ?>>
							<b class="eps__pn" aria-hidden="true"><?php echo esc_html( $stage['number'] ); ?></b>
							<div class="eps__copy">
								<strong class="eps__title"><?php echo esc_html( $stage['title'] ); ?></strong>
								<?php if ( '' !== $stage['text'] ) : ?>
									<p class="eps__desc"><?php echo esc_html( $stage['text'] ); ?></p>
								<?php endif; ?>
							</div>
							<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php // Pick the layout before first paint, so a phone never sees the desktop rail flash. ?>
			<script>(function(r){var w=r&&r.clientWidth;if(w){r.setAttribute('data-mode',w<+r.getAttribute('data-vertical-below')?'v':w<+r.getAttribute('data-scroll-below')?'scroll':'h');}}(document.currentScript&&document.currentScript.previousElementSibling));</script>
		</div>
		<?php
	}
}
