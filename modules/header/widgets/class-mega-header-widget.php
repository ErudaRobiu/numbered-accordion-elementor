<?php
/**
 * Mega Header widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Header\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A sticky bar that frosts on scroll, with mega panels and a phone drawer.
 */
class Mega_Header_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'eanm-mega-header';
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Mega Header', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
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
		return array( 'header', 'nav', 'menu', 'mega', 'sticky', 'navigation', 'frosted', 'glass' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( \ErudaToolkit\Modules\Header\Header_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( \ErudaToolkit\Modules\Header\Header_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_brand_controls();
		$this->register_menu_controls();
		$this->register_cta_controls();
		$this->register_behaviour_controls();
		$this->register_bar_style_controls();
		$this->register_link_style_controls();
		$this->register_panel_style_controls();
		$this->register_cta_style_controls();
	}

	/**
	 * The logo and the name beside it.
	 */
	private function register_brand_controls() {
		$this->start_controls_section(
			'section_brand',
			array( 'label' => esc_html__( 'Brand', 'numbered-accordion' ) )
		);

		$this->add_control(
			'logo',
			array(
				'label' => esc_html__( 'Logo', 'numbered-accordion' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'brand_text',
			array(
				'label'       => esc_html__( 'Name beside the logo', 'numbered-accordion' ),
				'description' => esc_html__( 'Leave this empty when the logo already has the name in it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'brand_link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => home_url( '/' ) ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The menu itself.
	 */
	private function register_menu_controls() {
		$this->start_controls_section(
			'section_menu',
			array( 'label' => esc_html__( 'Menu', 'numbered-accordion' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Menu item', 'numbered-accordion' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'   => esc_html__( 'Link', 'numbered-accordion' ),
				'type'    => Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'has_panel',
			array(
				'label'        => esc_html__( 'Opens a panel', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		/*
		 * One link per line, rather than a repeater inside a repeater.
		 *
		 * Elementor has no nested repeater and is not going to grow one: the
		 * control is backed by a flat array and the panel UI has nowhere to
		 * put a second level. A textarea parsed line by line is what every
		 * mega menu that works in Elementor does, and it has the side benefit
		 * of being pasteable -- a twelve-item panel is one paste rather than
		 * twelve clicks of "add item".
		 */
		$repeater->add_control(
			'panel_links',
			array(
				'label'       => esc_html__( 'Panel links', 'numbered-accordion' ),
				'description' => esc_html__( 'One per line: Label | /url | optional description. The description and the URL can both be left out.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 8,
				'default'     => '',
				'placeholder' => "Waste heat recovery | /services/waste-heat | Capture what the stack throws away\nFeasibility assessment | /services/feasibility",
				'condition'   => array( 'has_panel' => 'yes' ),
			)
		);

		$repeater->add_control(
			'panel_eyebrow',
			array(
				'label'     => esc_html__( 'Panel heading', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array( 'has_panel' => 'yes' ),
				'dynamic'   => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'panel_blurb',
			array(
				'label'     => esc_html__( 'Panel text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'condition' => array( 'has_panel' => 'yes' ),
				'dynamic'   => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'panel_image',
			array(
				'label'     => esc_html__( 'Panel picture', 'numbered-accordion' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'has_panel' => 'yes' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array( 'label' => esc_html__( 'Home', 'numbered-accordion' ) ),
					array( 'label' => esc_html__( 'About us', 'numbered-accordion' ) ),
					array(
						'label'     => esc_html__( 'Services', 'numbered-accordion' ),
						'has_panel' => 'yes',
					),
					array( 'label' => esc_html__( 'Our System', 'numbered-accordion' ) ),
					array( 'label' => esc_html__( 'News', 'numbered-accordion' ) ),
					array( 'label' => esc_html__( 'Support', 'numbered-accordion' ) ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The button on the right.
	 */
	private function register_cta_controls() {
		$this->start_controls_section(
			'section_cta',
			array( 'label' => esc_html__( 'Button', 'numbered-accordion' ) )
		);

		$this->add_control(
			'cta_show',
			array(
				'label'        => esc_html__( 'Show the button', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Get Started Now', 'numbered-accordion' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'cta_show' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'     => esc_html__( 'Link', 'numbered-accordion' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'cta_show' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * How it behaves on scroll and under the pointer.
	 */
	private function register_behaviour_controls() {
		$this->start_controls_section(
			'section_behaviour',
			array( 'label' => esc_html__( 'Behaviour', 'numbered-accordion' ) )
		);

		$this->add_control(
			'fill_mode',
			array(
				'label'       => esc_html__( 'What it does on scroll', 'numbered-accordion' ),
				'description' => esc_html__( 'At the very top the bar is always transparent, so it sits on the hero as though it were not there. This is what happens once the page has moved.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'frost',
				'options'     => array(
					'frost' => esc_html__( 'Frost — blurs whatever is behind it', 'numbered-accordion' ),
					'solid' => esc_html__( 'Fill — a flat colour, no blur', 'numbered-accordion' ),
					'none'  => esc_html__( 'Nothing — stays transparent', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'preview_state',
			array(
				'label'       => esc_html__( 'Preview a state', 'numbered-accordion' ),
				'description' => esc_html__( 'Holds the header in one state so you can see it without scrolling or hovering. It applies on the live page too, so put it back to Normal when you have finished looking.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''      => esc_html__( 'Normal — reacts to scrolling', 'numbered-accordion' ),
					'stuck' => esc_html__( 'Hold it frosted', 'numbered-accordion' ),
					'open'  => esc_html__( 'Hold a panel open', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'menu_anim',
			array(
				'label'       => esc_html__( 'Menu item animation', 'numbered-accordion' ),
				'description' => esc_html__( 'The shuffle is the reference\'s own: every frame is an anagram of the label, locking in one letter at a time from the left, so the word sorts itself out rather than flickering as static. The roll is two copies stacked in a box that clips, one sliding out as the other arrives.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'scramble',
				'options'     => array(
					'scramble' => esc_html__( 'Shuffle the letters', 'numbered-accordion' ),
					'roll'     => esc_html__( 'Roll the whole word', 'numbered-accordion' ),
					'letters'  => esc_html__( 'Roll letter by letter', 'numbered-accordion' ),
					'none'     => esc_html__( 'Just change colour', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'roll_ms',
			array(
				'label'      => esc_html__( 'How fast it rolls', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms', 's' ),
				'range'      => array( 'ms' => array( 'min' => 80, 'max' => 900 ), 's' => array( 'min' => 0, 'max' => 3, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 340 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-roll-ms: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'menu_anim' => array( 'roll', 'letters' ) ),
			)
		);

		$this->add_control(
			'scramble_step',
			array(
				'label'       => esc_html__( 'Time per letter', 'numbered-accordion' ),
				'description' => esc_html__( 'How long each letter takes to lock into place. A whole label settles in this many milliseconds times its length.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 15, 'max' => 140 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 45 ),
				'condition'   => array( 'menu_anim' => 'scramble' ),
			)
		);

		$this->add_control(
			'roll_step',
			array(
				'label'      => esc_html__( 'Delay between letters', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms', 's' ),
				'range'      => array( 'ms' => array( 'min' => 0, 'max' => 120 ), 's' => array( 'min' => 0, 'max' => 3, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-roll-step: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'menu_anim' => 'letters' ),
			)
		);

		$this->add_control(
			'stick_at',
			array(
				'label'       => esc_html__( 'Stays full width for the first', 'numbered-accordion' ),
				'description' => esc_html__( 'The top of the page is always the full-width state, whichever way you were last going.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 400 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 10 ),
				'condition'   => array( 'fill_mode!' => 'none' ),
			)
		);

		$this->add_control(
			'grab',
			array(
				'label'       => esc_html__( 'Reacts after scrolling', 'numbered-accordion' ),
				'description' => esc_html__( 'Scrolling down compacts the bar; scrolling back up gives it back, wherever you are on the page. This is how far you have to keep going one way before it agrees you meant it -- too low and a trackpad makes it flicker between the two.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 10, 'max' => 400 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 60 ),
				'condition'   => array( 'fill_mode!' => 'none' ),
			)
		);

		$this->add_control(
			'hold_space',
			array(
				'label'        => esc_html__( 'Hold space for the bar', 'numbered-accordion' ),
				'description'  => esc_html__( 'On by default for anything but a hero that starts at the very top of the page. Off, the first section runs up underneath the bar; on, the page starts below it.', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'intent',
			array(
				'label'       => esc_html__( 'Pause before a panel opens', 'numbered-accordion' ),
				'description' => esc_html__( 'Stops every panel flashing open in turn as the pointer crosses the bar on its way somewhere else. It only applies to the first panel: moving between two open ones is immediate.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'ms' ),
				'range'       => array( 'ms' => array( 'min' => 0, 'max' => 400 ) ),
				'default'     => array( 'unit' => 'ms', 'size' => 90 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-intent: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'scrim_show',
			array(
				'label'        => esc_html__( 'Blur the page behind a panel', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'scrim_blur',
			array(
				'label'      => esc_html__( 'How much', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-scrim-blur: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'scrim_show' => 'yes' ),
			)
		);

		$this->add_control(
			'scrim_colour',
			array(
				'label'     => esc_html__( 'Tint', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 26, 43, 0.25)',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-scrim-bg: {{VALUE}};' ),
				'condition' => array( 'scrim_show' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The bar: its size, and what the frost looks like.
	 */
	private function register_bar_style_controls() {
		$this->start_controls_section(
			'section_bar_style',
			array(
				'label' => esc_html__( 'Bar', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'pad_y',
			array(
				'label'      => esc_html__( 'Height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 14 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-pad-y: {{SIZE}}{{UNIT}}; --ehdr-pad-y-mobile: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'       => esc_html__( 'Content width', 'numbered-accordion' ),
				'description' => esc_html__( 'The bar always runs the full width of the window, because the frost has to. This caps what sits inside it, so the logo lines up with the words underneath rather than drifting out to the bezel.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 600, 'max' => 2200 ), '%' => array( 'min' => 0, 'max' => 100 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 1440 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'pad_x',
			array(
				'label'       => esc_html__( 'Side padding', 'numbered-accordion' ),
				'description' => esc_html__( 'Line this up with your sections\' own padding, or the logo will not sit above the words under it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 160 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 64 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-pad-x: {{SIZE}}{{UNIT}}; --ehdr-pad-x-mobile: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'float_heading',
			array(
				'label'       => esc_html__( 'Once it frosts', 'numbered-accordion' ),
				'description' => esc_html__( 'The bar comes away from the top edge and narrows into a floating bar, then snaps back to full width the moment a panel opens.', 'numbered-accordion' ),
				'type'        => Controls_Manager::HEADING,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'stuck_gap',
			array(
				'label'      => esc_html__( 'Gap from the top', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-stuck-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'stuck_width_mode',
			array(
				'label'       => esc_html__( 'How wide it becomes', 'numbered-accordion' ),
				'description' => esc_html__( 'Fitting the contents sizes the bar to the logo, the menu and the button -- the thing itself, rather than a box it sits in. A share of the window is the other way, and is the one that goes wrong on a very wide monitor.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'fit',
				'options'     => array(
					'fit'   => esc_html__( 'Fit the contents', 'numbered-accordion' ),
					'share' => esc_html__( 'A share of the window', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'stuck_width',
			array(
				'label'      => esc_html__( 'How much of the window', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array( '%' => array( 'min' => 40, 'max' => 100 ), 'px' => array( 'min' => 320, 'max' => 2400 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 80 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-stuck-width: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'stuck_width_mode' => 'share' ),
			)
		);

		$this->add_control(
			'stuck_inset',
			array(
				'label'       => esc_html__( 'Keep clear of the edges', 'numbered-accordion' ),
				'description' => esc_html__( 'The least space left either side when the bar is sized to its contents, so a wide menu on a narrow window still has air around it.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 32 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-stuck-inset: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'stuck_width_mode' => 'fit' ),
			)
		);

		$this->add_control(
			'pad_x_stuck',
			array(
				'label'      => esc_html__( 'Side padding once frosted', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-pad-x-stuck: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'border_heading',
			array(
				'label'     => esc_html__( 'Border', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'border_width',
			array(
				'label'       => esc_html__( 'Width', 'numbered-accordion' ),
				'description' => esc_html__( 'On all four sides. The width is always reserved and only its colour arrives with the frost, so nothing shifts by a pixel on the frame it appears.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 8 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-bw: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'border_bottom_width',
			array(
				'label'       => esc_html__( 'Bottom only', 'numbered-accordion' ),
				'description' => esc_html__( 'The hairline under a full-width bar. Set the width above for a floating one.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 8 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 1 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-bw-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'bar_radius',
			array(
				'label'      => esc_html__( 'Corner radius once frosted', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-radius-stuck: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'logo_height',
			array(
				'label'      => esc_html__( 'Logo height', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 120 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 44 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-logo: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'logo_height_stuck',
			array(
				'label'       => esc_html__( 'Logo height once frosted', 'numbered-accordion' ),
				'description' => esc_html__( 'Set this smaller than the one above and the bar tightens as you scroll.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 16, 'max' => 120 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 44 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-logo-stuck: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'fill_heading',
			array(
				'label'     => esc_html__( 'The frost', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'fill_mode!' => 'none' ),
			)
		);

		$this->add_control(
			'fill_colour',
			array(
				'label'       => esc_html__( 'Fill', 'numbered-accordion' ),
				'description' => esc_html__( 'Keep this translucent. A solid colour over a blur makes the blur invisible and costs the same to draw.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => 'rgba(255, 255, 255, 0.72)',
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-fill: {{VALUE}}; --ehdr-fill-solid: {{VALUE}};' ),
				'condition'   => array( 'fill_mode!' => 'none' ),
			)
		);

		$this->add_control(
			'blur',
			array(
				'label'      => esc_html__( 'Blur', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-blur: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'fill_mode' => 'frost' ),
			)
		);

		$this->add_control(
			'saturate',
			array(
				'label'       => esc_html__( 'Colour lift', 'numbered-accordion' ),
				'description' => esc_html__( 'Saturates what shows through. A little of this is the difference between frosted glass and grey plastic.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 1, 'max' => 2.5, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 1.4 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-sat: {{SIZE}};' ),
				'condition'   => array( 'fill_mode' => 'frost' ),
			)
		);

		$this->add_control(
			'hairline',
			array(
				'label'     => esc_html__( 'Hairline', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(15, 56, 96, 0.1)',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-hairline: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'sheet',
			array(
				'label'       => esc_html__( 'Panel sheet', 'numbered-accordion' ),
				'description' => esc_html__( 'The colour of an open panel, and of the bar while one is open. This one is opaque on purpose.', 'numbered-accordion' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#FFFFFF',
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-sheet: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'z_index',
			array(
				'label'       => esc_html__( 'Stacking order', 'numbered-accordion' ),
				'description' => esc_html__( 'Raise this if something on the page draws over the header.', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 999,
				'min'         => 1,
				'max'         => 99999,
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-z: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The menu items.
	 */
	private function register_link_style_controls() {
		$this->start_controls_section(
			'section_link_style',
			array(
				'label' => esc_html__( 'Menu items', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'link_typography',
				'selector' => '{{WRAPPER}} .ehdr__link',
			)
		);

		$this->add_control(
			'link_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0F3860',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'link_colour_hover',
			array(
				'label'     => esc_html__( 'Colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#00A55D',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-ink-hover: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => esc_html__( 'Space between items', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-item-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'rule_heading',
			array(
				'label'     => esc_html__( 'The underline', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#00A55D',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'rule_height',
			array(
				'label'      => esc_html__( 'Thickness', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 2 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-rule: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'link_ms',
			array(
				'label'      => esc_html__( 'Hover speed', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms', 's' ),
				'range'      => array( 'ms' => array( 'min' => 60, 'max' => 600 ), 's' => array( 'min' => 0, 'max' => 3, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 200 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-link-ms: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The panels.
	 */
	private function register_panel_style_controls() {
		$this->start_controls_section(
			'section_panel_style',
			array(
				'label' => esc_html__( 'Panels', 'numbered-accordion' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'panel_cols',
			array(
				'label'       => esc_html__( 'Columns', 'numbered-accordion' ),
				'description' => esc_html__( 'Any valid grid-template-columns value. The three parts are the links, the text and the picture.', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '1.1fr 1fr 0.9fr',
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-cols: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'panel_pad',
			array(
				'label'      => esc_html__( 'Padding', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-pad: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'panel_gap',
			array(
				'label'      => esc_html__( 'Space between columns', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 48 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'panel_max',
			array(
				'label'       => esc_html__( 'Content width', 'numbered-accordion' ),
				'description' => esc_html__( 'The panel is full width; this keeps what is inside it lined up with the rest of the page.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 600, 'max' => 2000 ), '%' => array( 'min' => 0, 'max' => 100 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'unit' => 'px', 'size' => 1400 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'muted',
			array(
				'label'     => esc_html__( 'Secondary text', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5B7794',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-muted: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'panel_link_bg',
			array(
				'label'     => esc_html__( 'Row highlight', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0, 165, 93, 0.08)',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-link-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'panel_ms',
			array(
				'label'      => esc_html__( 'Opening speed', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms', 's' ),
				'range'      => array( 'ms' => array( 'min' => 80, 'max' => 800 ), 's' => array( 'min' => 0, 'max' => 3, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'ms', 'size' => 280 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-panel-ms: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'figure_ratio',
			array(
				'label'      => esc_html__( 'Picture shape', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0.6, 'max' => 2.4, 'step' => 0.05 ) ),
				'default'    => array( 'size' => 1.5 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-figure-ratio: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The button, down to the two shadows.
	 */
	private function register_cta_style_controls() {
		$this->start_controls_section(
			'section_cta_style',
			array(
				'label'     => esc_html__( 'Button', 'numbered-accordion' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'cta_show' => 'yes' ),
			)
		);

		/*
		 * The fill, in parts rather than through a background group control.
		 *
		 * The group control was the bug behind "the button colour isn't
		 * applying": it writes background-color and background-image as two
		 * separate declarations, so choosing a flat colour wrote a colour
		 * underneath a gradient that was still sitting on top of it and
		 * nothing appeared to change. Picking Solid here empties the gradient
		 * outright.
		 */
		$this->add_control(
			'cta_fill',
			array(
				'label'   => esc_html__( 'Fill', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'gradient',
				'options' => array(
					'gradient' => esc_html__( 'Gradient', 'numbered-accordion' ),
					'solid'    => esc_html__( 'Solid colour', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'cta_from',
			array(
				'label'     => esc_html__( 'From', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#00A55D',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-from: {{VALUE}};' ),
				'condition' => array( 'cta_fill' => 'gradient' ),
			)
		);

		$this->add_control(
			'cta_to',
			array(
				'label'     => esc_html__( 'To', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#005465',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-to: {{VALUE}};' ),
				'condition' => array( 'cta_fill' => 'gradient' ),
			)
		);

		$this->add_control(
			'cta_angle',
			array(
				'label'      => esc_html__( 'Angle', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'range'      => array( 'deg' => array( 'min' => 0, 'max' => 360 ) ),
				'default'    => array( 'unit' => 'deg', 'size' => 90 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-angle: {{SIZE}}deg;' ),
				'condition'  => array( 'cta_fill' => 'gradient' ),
			)
		);

		$this->add_control(
			'cta_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#00A55D',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-colour: {{VALUE}};' ),
				'condition' => array( 'cta_fill' => 'solid' ),
			)
		);

		$this->add_control(
			'cta_colour_hover',
			array(
				'label'     => esc_html__( 'Colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-colour-hover: {{VALUE}};' ),
				'condition' => array( 'cta_fill' => 'solid' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'cta_typography',
				'selector' => '{{WRAPPER}} .ehdr__cta',
			)
		);

		$this->add_control(
			'cta_ink',
			array(
				'label'     => esc_html__( 'Text colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_ink_hover',
			array(
				'label'     => esc_html__( 'Text colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-ink-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_track',
			array(
				'label'       => esc_html__( 'Letter spacing', 'numbered-accordion' ),
				'description' => esc_html__( 'In em, not per cent. A percentage is not a valid letter-spacing anywhere and browsers drop it without a word, which is why tracking set in per cent never appears to do anything.', 'numbered-accordion' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'em', 'px', 'rem' ),
				'range'       => array( 'em' => array( 'min' => -0.05, 'max' => 0.3, 'step' => 0.005 ), 'px' => array( 'min' => -2, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => -0.05, 'max' => 0.3, 'step' => 0.005 ) ),
				'default'     => array( 'unit' => 'em', 'size' => 0.05 ),
				'selectors'   => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-track: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_border_width',
			array(
				'label'      => esc_html__( 'Border width', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-bw: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_border',
			array(
				'label'     => esc_html__( 'Border colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_border_hover',
			array(
				'label'     => esc_html__( 'Border colour on hover', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-border-hover: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_radius',
			array(
				'label'      => esc_html__( 'Corner radius', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 100 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_pad_y',
			array(
				'label'      => esc_html__( 'Padding, top and bottom', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 40 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 15 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-pad-y: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_pad_x',
			array(
				'label'      => esc_html__( 'Padding, left and right', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 80 ), '%' => array( 'min' => 0, 'max' => 100 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'vw' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 30 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-pad-x: {{SIZE}}{{UNIT}};' ),
			)
		);

		/*
		 * The inner shadow, in four parts.
		 *
		 * This is the thing that makes the pill look moulded rather than
		 * printed, and it is worth the four controls: a dark wash pulled down
		 * from above the top edge, so the top reads as curving away from the
		 * light while the bottom stays bright. The default is the live site's
		 * own -- 2px across, -4px up, 4px of blur at 15% black.
		 */
		$this->add_control(
			'cta_inner_heading',
			array(
				'label'     => esc_html__( 'Inner shadow', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'cta_inner_x',
			array(
				'label'      => esc_html__( 'Across', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => -20, 'max' => 20 ), 'em' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 2 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-inner-x: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_inner_y',
			array(
				'label'      => esc_html__( 'Down', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => -20, 'max' => 20 ), 'em' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => -4 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-inner-y: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_inner_blur',
			array(
				'label'      => esc_html__( 'Blur', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-inner-blur: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_inner_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.15)',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-inner-colour: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'cta_lift_heading',
			array(
				'label'     => esc_html__( 'Drop shadow', 'numbered-accordion' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'cta_lift_y',
			array(
				'label'      => esc_html__( 'Down', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => -20, 'max' => 40 ), 'em' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => -4, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-lift-y: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_lift_blur',
			array(
				'label'      => esc_html__( 'Blur', 'numbered-accordion' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ), 'em' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ), 'rem' => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-lift-blur: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'cta_lift_colour',
			array(
				'label'     => esc_html__( 'Colour', 'numbered-accordion' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.15)',
				'selectors' => array( '{{WRAPPER}} .ehdr' => '--ehdr-cta-lift-colour: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Read a slider's size, with a fallback and a clamp.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Control id.
	 * @param float  $fallback Value when unset.
	 * @param float  $min      Lowest allowed.
	 * @param float  $max      Highest allowed.
	 * @return float
	 */
	private function slider( $settings, $key, $fallback, $min, $max ) {
		$size = isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size']
			? (float) $settings[ $key ]['size']
			: $fallback;

		return max( $min, min( $size, $max ) );
	}

	/**
	 * Turn the panel-links textarea into rows.
	 *
	 * One link per line: `Label | /url | description`. The URL and the
	 * description are both optional, and a line with neither is still a valid
	 * row -- a heading in a list of links is a reasonable thing to want.
	 *
	 * @param string $raw Textarea contents.
	 * @return array<int, array{label: string, url: string, note: string}>
	 */
	private function parse_links( $raw ) {
		$rows = array();

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return $rows;
		}

		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line ) );

			if ( '' === $parts[0] ) {
				continue;
			}

			$rows[] = array(
				'label' => $parts[0],
				'url'   => isset( $parts[1] ) ? $parts[1] : '',
				'note'  => isset( $parts[2] ) ? $parts[2] : '',
			);
		}

		return $rows;
	}

	/**
	 * Print href, target and rel for one of Elementor's URL controls.
	 *
	 * @param array $link Link control value.
	 */
	private function link_attrs( $link ) {
		$url = isset( $link['url'] ) ? $link['url'] : '';

		if ( '' === $url ) {
			return;
		}

		echo ' href="' . esc_url( $url ) . '"';

		if ( ! empty( $link['is_external'] ) ) {
			echo ' target="_blank"';
		}

		$rel = array();

		if ( ! empty( $link['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}

		if ( ! empty( $link['is_external'] ) ) {
			$rel[] = 'noopener';
		}

		if ( $rel ) {
			echo ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
		}
	}

	/**
	 * Print a menu label, in whichever animation was chosen.
	 *
	 * The roll is two copies of the word stacked inside a box that clips: the
	 * visible one slides up and out, the one underneath arrives in its place.
	 * The second copy is aria-hidden so the label is not announced twice.
	 *
	 * Per letter, the copies are per character and each carries its index as
	 * `--i`, which the stylesheet turns into a transition delay so the roll
	 * travels the way the word is read. There the real label is carried once
	 * in a visually-hidden span and every character is hidden from assistive
	 * technology, because a word spelt out one letter per element is read out
	 * one letter at a time.
	 *
	 * @param string $label Label text.
	 * @param string $anim  none | roll | letters | scramble.
	 */
	private function label( $label, $anim ) {
		if ( 'roll' === $anim ) {
			printf(
				'<span class="ehdr__roll"><span>%1$s</span><span aria-hidden="true">%1$s</span></span>',
				esc_html( $label )
			);

			return;
		}

		if ( 'letters' === $anim ) {
			echo '<span class="ehdr__roll"><span class="ehdr__sr">' . esc_html( $label ) . '</span>';

			// preg_split on an empty pattern with UTF-8: a label is not
			// guaranteed to be ASCII and str_split would cut a multi-byte
			// character in half.
			$chars = preg_split( '//u', $label, -1, PREG_SPLIT_NO_EMPTY );
			$chars = is_array( $chars ) ? $chars : array();

			foreach ( $chars as $i => $char ) {
				printf(
					'<span class="ehdr__roll-c" aria-hidden="true" style="--i:%1$d"><span>%2$s</span><span>%2$s</span></span>',
					(int) $i,
					esc_html( $char )
				);
			}

			echo '</span>';

			return;
		}

		if ( 'scramble' === $anim ) {
			printf(
				'<span class="ehdr__scramble" data-ehdr-text="%1$s">%1$s</span>',
				esc_attr( $label )
			);

			return;
		}

		echo esc_html( $label );
	}

	/**
	 * The caret and the arrow, as inline SVG.
	 *
	 * Inline because between them they appear once per menu item and once per
	 * panel row; an icon font or a sprite request for two shapes costs more
	 * than the markup does.
	 *
	 * @param string $which 'caret' or 'arrow'.
	 */
	private function icon( $which ) {
		if ( 'caret' === $which ) {
			echo '<svg class="ehdr__caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5"/></svg>';

			return;
		}

		echo '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10"/><path d="m9 4 4 4-4 4"/></svg>';
	}

	/**
	 * Render.
	 *
	 * Complete without the script: the panels open on hover and on focus in
	 * CSS alone, so a failed script costs the frost and the scrim rather than
	 * the navigation.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = isset( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		$mode = isset( $settings['fill_mode'] ) ? $settings['fill_mode'] : 'frost';
		$mode = in_array( $mode, array( 'frost', 'solid', 'none' ), true ) ? $mode : 'frost';

		$scrim = ! isset( $settings['scrim_show'] ) || 'yes' === $settings['scrim_show'];
		$cta   = ! isset( $settings['cta_show'] ) || 'yes' === $settings['cta_show'];
		$hold  = isset( $settings['hold_space'] ) && 'yes' === $settings['hold_space'];

		$intent  = $this->slider( $settings, 'intent', 90, 0, 400 );
		$stickAt = $this->slider( $settings, 'stick_at', 10, 0, 400 );

		// "Nothing" is a threshold nothing can cross rather than a branch in
		// the script: one less state for the frost to be in.
		if ( 'none' === $mode ) {
			$stickAt = 100000000;
		}

		$ctaText = isset( $settings['cta_text'] ) ? $settings['cta_text'] : '';
		$ctaLink = isset( $settings['cta_link'] ) ? $settings['cta_link'] : array();

		$anim = isset( $settings['menu_anim'] ) ? $settings['menu_anim'] : 'scramble';
		$anim = in_array( $anim, array( 'none', 'roll', 'letters', 'scramble' ), true ) ? $anim : 'scramble';

		$grab = $this->slider( $settings, 'grab', 60, 10, 400 );
		$step = $this->slider( $settings, 'scramble_step', 45, 15, 140 );

		$ctaFill = isset( $settings['cta_fill'] ) && 'solid' === $settings['cta_fill'] ? 'solid' : 'gradient';

		// The preview override. Deliberately a real attribute rather than an
		// editor-only one: it is there to be looked at on the page, not just
		// in the panel. The control says to put it back.
		$preview = isset( $settings['preview_state'] ) ? $settings['preview_state'] : '';
		$preview = in_array( $preview, array( 'stuck', 'open' ), true ) ? $preview : '';
		?>
		<div
			class="ehdr"
			data-ehdr-fill="<?php echo esc_attr( $mode ); ?>"
			data-ehdr-anim="<?php echo esc_attr( $anim ); ?>"
			data-ehdr-cta-fill="<?php echo esc_attr( $ctaFill ); ?>"
			data-ehdr-intent="<?php echo esc_attr( (string) $intent ); ?>"
			data-ehdr-stick-at="<?php echo esc_attr( (string) $stickAt ); ?>"
			data-ehdr-grab="<?php echo esc_attr( (string) $grab ); ?>"
			data-ehdr-scramble-step="<?php echo esc_attr( (string) $step ); ?>"
			<?php echo '' !== $preview ? ' data-ehdr-preview="' . esc_attr( $preview ) . '"' : ''; ?>
		>
			<?php if ( $scrim ) : ?>
				<div class="ehdr__scrim" aria-hidden="true"></div>
			<?php endif; ?>

			<div class="ehdr__bar">
			<div class="ehdr__inner">
				<?php
				$brand     = isset( $settings['brand_link'] ) ? $settings['brand_link'] : array();
				$logoUrl   = isset( $settings['logo']['url'] ) ? $settings['logo']['url'] : '';
				$brandText = isset( $settings['brand_text'] ) ? $settings['brand_text'] : '';
				?>
				<?php if ( '' !== $logoUrl || '' !== $brandText ) : ?>
					<a class="ehdr__brand"<?php $this->link_attrs( $brand ); ?>>
						<?php if ( '' !== $logoUrl ) : ?>
							<img
								class="ehdr__logo"
								src="<?php echo esc_url( $logoUrl ); ?>"
								alt="<?php echo esc_attr( $brandText ? $brandText : get_bloginfo( 'name' ) ); ?>"
								decoding="async"
							/>
						<?php endif; ?>
						<?php if ( '' !== $brandText ) : ?>
							<span class="ehdr__brand-text"><?php echo esc_html( $brandText ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>

				<?php
				/*
				 * The burger sits before the menu in the markup, not after.
				 *
				 * On a phone the menu becomes a drawer positioned under the
				 * bar, and a control that opens something should come before
				 * it in reading order -- otherwise a screen reader meets the
				 * drawer's contents first and the button that opens them last.
				 */
				?>
				<button class="ehdr__burger" type="button" aria-label="<?php echo esc_attr__( 'Menu', 'numbered-accordion' ); ?>">
					<span></span><span></span><span></span>
				</button>

				<nav class="ehdr__nav"><div class="ehdr__nav-inner">
				<ul class="ehdr__menu">
					<?php
					foreach ( $items as $index => $item ) :
						$label = isset( $item['label'] ) ? $item['label'] : '';

						if ( '' === $label ) {
							continue;
						}

						$hasPanel = ! empty( $item['has_panel'] ) && 'yes' === $item['has_panel'];
						$links    = $hasPanel ? $this->parse_links( isset( $item['panel_links'] ) ? $item['panel_links'] : '' ) : array();
						$blurb    = isset( $item['panel_blurb'] ) ? $item['panel_blurb'] : '';
						$eyebrow  = isset( $item['panel_eyebrow'] ) ? $item['panel_eyebrow'] : '';
						$figure   = isset( $item['panel_image']['url'] ) ? $item['panel_image']['url'] : '';

						// A panel with nothing in it is not a panel. Without
						// this an item switched to "opens a panel" and then
						// left empty gets a caret that opens a white strip.
						$hasPanel = $hasPanel && ( $links || '' !== $blurb || '' !== $figure );
						?>
						<li class="ehdr__item">
							<a class="ehdr__link"<?php $this->link_attrs( isset( $item['link'] ) ? $item['link'] : array() ); ?>>
								<?php $this->label( $label, $anim ); ?>
								<?php
								if ( $hasPanel ) {
									$this->icon( 'caret' );
								}
								?>
							</a>

							<?php if ( $hasPanel ) : ?>
								<div class="ehdr__panel">
									<div class="ehdr__panel-inner">
										<?php if ( $links ) : ?>
											<ul class="ehdr__links">
												<?php foreach ( $links as $row ) : ?>
													<li>
														<a class="ehdr__panel-link"<?php echo '' !== $row['url'] ? ' href="' . esc_url( $row['url'] ) . '"' : ''; ?>>
															<span>
																<?php $this->label( $row['label'], 'scramble' === $anim ? 'scramble' : 'none' ); ?>
																<?php if ( '' !== $row['note'] ) : ?>
																	<small><?php echo esc_html( $row['note'] ); ?></small>
																<?php endif; ?>
															</span>
															<?php $this->icon( 'arrow' ); ?>
														</a>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>

										<?php if ( '' !== $blurb || '' !== $eyebrow ) : ?>
											<p class="ehdr__blurb">
												<?php if ( '' !== $eyebrow ) : ?>
													<strong><?php echo esc_html( $eyebrow ); ?></strong>
												<?php endif; ?>
												<?php echo esc_html( $blurb ); ?>
											</p>
										<?php endif; ?>

										<?php if ( '' !== $figure ) : ?>
											<figure class="ehdr__figure">
												<img src="<?php echo esc_url( $figure ); ?>" alt="" loading="lazy" decoding="async" />
											</figure>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						</li>
						<?php
					endforeach;
					?>

				</ul>
				<?php if ( $cta && '' !== $ctaText ) : ?>
					<a class="ehdr__cta ehdr__drawer-cta"<?php $this->link_attrs( $ctaLink ); ?>><?php echo esc_html( $ctaText ); ?></a>
				<?php endif; ?>
				</div></nav>

				<?php if ( $cta && '' !== $ctaText ) : ?>
					<a class="ehdr__cta"<?php $this->link_attrs( $ctaLink ); ?>><?php echo esc_html( $ctaText ); ?></a>
				<?php endif; ?>
			</div>
			</div>
		</div>
		<?php if ( $hold ) : ?>
			<div class="ehdr-spacer" data-ehdr-hold></div>
		<?php endif; ?>
		<?php
	}
}
