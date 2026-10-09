<?php
/**
 * Assessment Form widget.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Assess\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ErudaToolkit\Modules\Assess\Assess_Content;
use ErudaToolkit\Modules\Assess\Assess_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A three-step heat recovery request: the exhaust, what it carries and where
 * the heat could go, then contact details. After sending, the visitor sees an
 * indicative savings range; the request is saved under Assessments and, when
 * set to, emailed to the team.
 *
 * As a pop-up, place it once (the footer template is the natural home) and
 * every link matching the trigger opens it; without script those links simply
 * go where they point.
 */
class Assessment_Form_Widget extends Widget_Base {

	/**
	 * Widget slug. Never change it: live pages carry it in their saved JSON.
	 *
	 * @return string
	 */
	public function get_name() {
		return Assess_Module::WIDGET;
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Assessment Form', 'numbered-accordion' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		return array( 'form', 'assessment', 'calculator', 'savings', 'estimate', 'popup', 'lead', 'request' );
	}

	/**
	 * Stylesheets to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( Assess_Module::STYLE_HANDLE );
	}

	/**
	 * Scripts to enqueue when this widget is on the page.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( Assess_Module::SCRIPT_HANDLE );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_form', array( 'label' => esc_html__( 'Form', 'numbered-accordion' ) ) );

		$this->add_control(
			'mode',
			array(
				'label'   => esc_html__( 'Show as', 'numbered-accordion' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modal',
				'options' => array(
					'modal'  => esc_html__( 'Pop-up, opened by buttons', 'numbered-accordion' ),
					'inline' => esc_html__( 'On the page', 'numbered-accordion' ),
				),
			)
		);

		$this->add_control(
			'trigger',
			array(
				'label'       => esc_html__( 'Opened by', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '.ts-assess a, a.ts-assess, a[href$="#request"]',
				'description' => esc_html__( 'Links matching this open the pop-up. Give a button the CSS class ts-assess (Advanced tab) to make it one.', 'numbered-accordion' ),
				'condition'   => array( 'mode' => 'modal' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Estimate your heat recovery', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'intro',
			array(
				'label'   => esc_html__( 'Intro', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Four numbers about your exhaust give a first estimate. Not sure? Keep the typical values and we will confirm them with you.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'privacy',
			array(
				'label'   => esc_html__( 'Note under the send button', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'default' => esc_html__( 'We use your details only to follow up on this request. No engineering package needed to start.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_delivery', array( 'label' => esc_html__( 'Requests', 'numbered-accordion' ) ) );

		$this->add_control(
			'delivery',
			array(
				'label'       => esc_html__( 'When someone sends it', 'numbered-accordion' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'send',
				'options'     => array(
					'send' => esc_html__( 'Save it and email the team and the visitor', 'numbered-accordion' ),
					'save' => esc_html__( 'Save it only (testing, no emails)', 'numbered-accordion' ),
				),
				'description' => esc_html__( 'Every request is saved under Assessments in the dashboard either way.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'recipient',
			array(
				'label'   => esc_html__( 'Team email', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'sales@thermstar.com',
			)
		);

		$this->add_control(
			'email_logo',
			array(
				'label'       => esc_html__( 'Logo in the visitor’s email', 'numbered-accordion' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image' ),
				'description' => esc_html__( 'Use a PNG or JPG: Outlook does not show WebP or SVG. Without one, the email starts with the name in text.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'price',
			array(
				'label'       => esc_html__( 'Gas price for the estimate ($ per therm)', 'numbered-accordion' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => Assess_Content::PRICE,
				'min'         => 0.1,
				'max'         => 5,
				'step'        => 0.05,
				'description' => esc_html__( 'Shown to the visitor with the result.', 'numbered-accordion' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_booking', array( 'label' => esc_html__( 'Booking', 'numbered-accordion' ) ) );

		$this->add_control(
			'booking_url',
			array(
				'label'       => esc_html__( 'Booking page', 'numbered-accordion' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => Assess_Module::BOOKING_URL,
				'description' => esc_html__( 'Microsoft Bookings will not open inside another site, so every booking link opens it in a new tab. Leave empty to hide them.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'booking_label',
			array(
				'label'   => esc_html__( 'Button after the estimate', 'numbered-accordion' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Schedule your personalized analysis', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'badge',
			array(
				'label'        => esc_html__( 'Booking button in the corner', 'numbered-accordion' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'A small button fixed to the lower right of every page this widget is on.', 'numbered-accordion' ),
			)
		);

		$this->add_control(
			'badge_label',
			array(
				'label'     => esc_html__( 'Corner button text', 'numbered-accordion' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Schedule time with us', 'numbered-accordion' ),
				'condition' => array( 'badge' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The document this widget is saved in: the page, or the template it sits in.
	 *
	 * @return int
	 */
	private function document_id() {
		$doc = class_exists( '\Elementor\Plugin' ) ? \Elementor\Plugin::$instance->documents->get_current() : null;

		return $doc ? (int) $doc->get_main_id() : (int) get_the_ID();
	}

	/**
	 * A slider with its live value.
	 *
	 * @param string $id    Field.
	 * @param string $label Label.
	 * @param array  $range Min, max, step, default.
	 * @param string $unit  Unit after the value.
	 * @param string $uid   Instance prefix.
	 */
	private function slider( $id, $label, $range, $unit, $uid ) {
		$fid = $uid . '-' . $id;
		?>
		<div class="eas__field eas__field--range">
			<div class="eas__range-head">
				<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $label ); ?></label>
				<output for="<?php echo esc_attr( $fid ); ?>" class="eas__value" data-unit="<?php echo esc_attr( $unit ); ?>"><?php echo esc_html( number_format( $range[3] ) . $unit ); ?></output>
			</div>
			<input class="eas__range" type="range" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $id ); ?>" min="<?php echo esc_attr( $range[0] ); ?>" max="<?php echo esc_attr( $range[1] ); ?>" step="<?php echo esc_attr( $range[2] ); ?>" value="<?php echo esc_attr( $range[3] ); ?>" />
			<div class="eas__range-ends" aria-hidden="true"><span><?php echo esc_html( number_format( $range[0] ) . $unit ); ?></span><span><?php echo esc_html( number_format( $range[1] ) . $unit ); ?></span></div>
		</div>
		<?php
	}

	/**
	 * Tappable choices.
	 *
	 * @param string $name    Field.
	 * @param string $legend  Question.
	 * @param array  $options Key => label.
	 * @param string $type    checkbox or radio.
	 * @param string $uid     Instance prefix.
	 * @param string $checked Preselected key.
	 */
	private function chips( $name, $legend, $options, $type, $uid, $checked = '' ) {
		?>
		<fieldset class="eas__field eas__chips">
			<legend><?php echo esc_html( $legend ); ?></legend>
			<div class="eas__chip-row">
				<?php foreach ( $options as $key => $label ) : ?>
					<label class="eas__chip">
						<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>"<?php checked( $checked, $key ); ?> />
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * A text field.
	 *
	 * @param string $name     Field.
	 * @param string $label    Label.
	 * @param string $type     Input type.
	 * @param string $auto     Autocomplete token.
	 * @param bool   $required Required.
	 * @param string $uid      Instance prefix.
	 */
	private function text_field( $name, $label, $type, $auto, $required, $uid ) {
		$fid = $uid . '-' . $name;
		?>
		<div class="eas__field">
			<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? '' : ' <span class="eas__opt">' . esc_html__( 'optional', 'numbered-accordion' ) . '</span>'; ?></label>
			<input class="eas__input" type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $name ); ?>" autocomplete="<?php echo esc_attr( $auto ); ?>"<?php echo $required ? ' required aria-required="true"' : ''; ?> aria-describedby="<?php echo esc_attr( $fid ); ?>-err" />
			<p class="eas__err" id="<?php echo esc_attr( $fid ); ?>-err" hidden></p>
		</div>
		<?php
	}

	/**
	 * Facility location: required, because ThermStar runs the request against
	 * its own weather data. One tap answers it for a multi-site request.
	 *
	 * @param string $uid Instance prefix.
	 */
	private function location_field( $uid ) {
		$fid = $uid . '-location';
		?>
		<div class="eas__field">
			<label for="<?php echo esc_attr( $fid ); ?>"><?php esc_html_e( 'Facility location', 'numbered-accordion' ); ?></label>
			<div class="eas__location">
				<input class="eas__input" type="text" id="<?php echo esc_attr( $fid ); ?>" name="location" autocomplete="address-level2" placeholder="<?php esc_attr_e( 'City, state or province', 'numbered-accordion' ); ?>" required aria-required="true" aria-describedby="<?php echo esc_attr( $fid ); ?>-hint <?php echo esc_attr( $fid ); ?>-err" />
				<button type="button" class="eas__several" data-eas-several="<?php echo esc_attr( Assess_Content::SEVERAL_SITES ); ?>" aria-pressed="false"><?php echo esc_html( Assess_Content::SEVERAL_SITES ); ?></button>
			</div>
			<p class="eas__hint" id="<?php echo esc_attr( $fid ); ?>-hint"><?php esc_html_e( 'We use it to match your site to local weather data.', 'numbered-accordion' ); ?></p>
			<p class="eas__err" id="<?php echo esc_attr( $fid ); ?>-err" hidden></p>
		</div>
		<?php
	}

	/**
	 * Render the widget on the front end.
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$s     = is_array( $s ) ? $s : array();
		$mode  = ( $s['mode'] ?? 'modal' ) === 'inline' ? 'inline' : 'modal';
		$uid   = 'eas-' . preg_replace( '/[^a-z0-9]/i', '', (string) $this->get_id() );
		$title = trim( (string) ( $s['title'] ?? '' ) );
		$price = is_numeric( $s['price'] ?? null ) ? (float) $s['price'] : Assess_Content::PRICE;
		$edit  = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		$book  = trim( (string) ( $s['booking_url'] ?? '' ) );
		$book_label = trim( (string) ( $s['booking_label'] ?? '' ) );
		$book_label = '' !== $book_label ? $book_label : __( 'Schedule your personalized analysis', 'numbered-accordion' );

		$attrs = sprintf(
			' data-mode="%s" data-endpoint="%s" data-token="%s" data-doc="%d" data-el="%s" data-trigger="%s" data-price="%s"',
			esc_attr( $mode ),
			esc_url( rest_url( Assess_Module::REST_NS . Assess_Module::REST_ROUTE ) ),
			esc_url( rest_url( Assess_Module::REST_NS . Assess_Module::REST_ROUTE . '/token' ) ),
			$this->document_id(),
			esc_attr( $this->get_id() ),
			esc_attr( (string) ( $s['trigger'] ?? '' ) ),
			esc_attr( (string) $price )
		);

		// Logged-in users send a REST nonce so the route knows who they are
		// (editors skip the hourly limit). Never for visitors: pages served
		// to them may be cached, and a cached nonce goes stale.
		if ( is_user_logged_in() ) {
			$attrs .= sprintf( ' data-nonce="%s"', esc_attr( wp_create_nonce( 'wp_rest' ) ) );
		}

		if ( 'modal' === $mode && $edit ) {
			echo '<div class="eas-editor-note">' . esc_html__( 'Assessment Form pop-up: hidden on the page, opened by any Request an Assessment button. Switch "Show as" to "On the page" to preview it here.', 'numbered-accordion' ) . '</div>';
			return;
		}

		if ( 'modal' === $mode ) {
			echo '<dialog class="eas-dialog" id="' . esc_attr( $uid ) . '-dialog" aria-labelledby="' . esc_attr( $uid ) . '-title">';
		}
		?>
		<div class="eas eas--<?php echo esc_attr( $mode ); ?>" id="<?php echo esc_attr( $uid ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>>
			<div class="eas__head">
				<?php if ( 'modal' === $mode ) : ?>
					<button type="button" class="eas__close" data-eas-close aria-label="<?php esc_attr_e( 'Close', 'numbered-accordion' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
				<?php endif; ?>
				<p class="eas__eyebrow"><?php esc_html_e( 'Free opportunity screen', 'numbered-accordion' ); ?></p>
				<?php if ( '' !== $title ) : ?>
					<h2 class="eas__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
				<ol class="eas__steps" aria-label="<?php esc_attr_e( 'Progress', 'numbered-accordion' ); ?>">
					<li class="is-on" data-step-dot="1"><span>1</span><?php esc_html_e( 'Your exhaust', 'numbered-accordion' ); ?></li>
					<li data-step-dot="2"><span>2</span><?php esc_html_e( 'Air and use', 'numbered-accordion' ); ?></li>
					<li data-step-dot="3"><span>3</span><?php esc_html_e( 'Your details', 'numbered-accordion' ); ?></li>
				</ol>
			</div>

			<form class="eas__form" novalidate>
				<section class="eas__step" data-step="1" aria-labelledby="<?php echo esc_attr( $uid ); ?>-s1">
					<h3 class="eas__step-title" id="<?php echo esc_attr( $uid ); ?>-s1" tabindex="-1"><?php esc_html_e( 'Your exhaust', 'numbered-accordion' ); ?></h3>
					<?php if ( ! empty( $s['intro'] ) ) : ?>
						<p class="eas__intro"><?php echo esc_html( $s['intro'] ); ?></p>
					<?php endif; ?>
					<?php
					$this->slider( 'temp', __( 'Exhaust air temperature', 'numbered-accordion' ), Assess_Content::TEMP, '°F', $uid );
					$this->slider( 'cfm', __( 'Airflow', 'numbered-accordion' ), Assess_Content::CFM, ' CFM', $uid );
					$this->slider( 'hours', __( 'Hours running per day', 'numbered-accordion' ), Assess_Content::HOURS, ' h', $uid );
					$this->chips( 'days', __( 'Days per week', 'numbered-accordion' ), array_combine( range( 1, 7 ), range( 1, 7 ) ), 'radio', $uid, (string) Assess_Content::DAYS[3] );
					?>
				</section>

				<section class="eas__step" data-step="2" aria-labelledby="<?php echo esc_attr( $uid ); ?>-s2" hidden>
					<h3 class="eas__step-title" id="<?php echo esc_attr( $uid ); ?>-s2" tabindex="-1"><?php esc_html_e( 'What is in the air, and where could the heat go?', 'numbered-accordion' ); ?></h3>
					<div class="eas__field">
						<label for="<?php echo esc_attr( $uid ); ?>-industry"><?php esc_html_e( 'Industry', 'numbered-accordion' ); ?></label>
						<select class="eas__input" id="<?php echo esc_attr( $uid ); ?>-industry" name="industry" required aria-required="true" aria-describedby="<?php echo esc_attr( $uid ); ?>-industry-err">
							<option value=""><?php esc_html_e( 'Choose one', 'numbered-accordion' ); ?></option>
							<?php foreach ( Assess_Content::industries() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="eas__err" id="<?php echo esc_attr( $uid ); ?>-industry-err" hidden></p>
					</div>
					<?php
					$this->chips( 'contaminants', __( 'In the exhaust (choose any)', 'numbered-accordion' ), Assess_Content::contaminants(), 'checkbox', $uid );
					$this->chips( 'uses', __( 'Heat could go to (choose any)', 'numbered-accordion' ), Assess_Content::uses(), 'checkbox', $uid );
					?>
				</section>

				<section class="eas__step" data-step="3" aria-labelledby="<?php echo esc_attr( $uid ); ?>-s3" hidden>
					<h3 class="eas__step-title" id="<?php echo esc_attr( $uid ); ?>-s3" tabindex="-1"><?php esc_html_e( 'Where should we send your estimate?', 'numbered-accordion' ); ?></h3>
					<div class="eas__grid">
						<?php
						$this->text_field( 'first', __( 'First name', 'numbered-accordion' ), 'text', 'given-name', true, $uid );
						$this->text_field( 'last', __( 'Last name', 'numbered-accordion' ), 'text', 'family-name', true, $uid );
						$this->text_field( 'email', __( 'Work email', 'numbered-accordion' ), 'email', 'email', true, $uid );
						$this->text_field( 'company', __( 'Company', 'numbered-accordion' ), 'text', 'organization', true, $uid );
						$this->text_field( 'role', __( 'Job title', 'numbered-accordion' ), 'text', 'organization-title', false, $uid );
						$this->text_field( 'phone', __( 'Phone', 'numbered-accordion' ), 'tel', 'tel', false, $uid );
						?>
					</div>
					<?php $this->location_field( $uid ); ?>
					<div class="eas__field">
						<label for="<?php echo esc_attr( $uid ); ?>-notes"><?php esc_html_e( 'Anything else about the site', 'numbered-accordion' ); ?> <span class="eas__opt"><?php esc_html_e( 'optional', 'numbered-accordion' ); ?></span></label>
						<textarea class="eas__input" id="<?php echo esc_attr( $uid ); ?>-notes" name="notes" rows="3"></textarea>
					</div>
					<div class="eas__hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>
					<?php if ( ! empty( $s['privacy'] ) ) : ?>
						<p class="eas__privacy"><?php echo esc_html( $s['privacy'] ); ?></p>
					<?php endif; ?>
				</section>

				<p class="eas__status" role="alert" hidden></p>

				<div class="eas__nav">
					<button type="button" class="eas__btn eas__btn--ghost" data-eas-back hidden><?php esc_html_e( 'Back', 'numbered-accordion' ); ?></button>
					<button type="button" class="eas__btn" data-eas-next><?php esc_html_e( 'Next', 'numbered-accordion' ); ?></button>
					<button type="submit" class="eas__btn" data-eas-send hidden><?php esc_html_e( 'Get my estimate', 'numbered-accordion' ); ?></button>
				</div>
			</form>

			<section class="eas__result" hidden aria-labelledby="<?php echo esc_attr( $uid ); ?>-r" tabindex="-1">
				<p class="eas__eyebrow"><?php esc_html_e( 'Your personalized website estimate', 'numbered-accordion' ); ?></p>
				<p class="eas__hello"><?php esc_html_e( 'Hello', 'numbered-accordion' ); ?> <span data-r="name"></span>, <?php esc_html_e( 'thank you for using our waste heat recovery savings calculator! With the ThermStar System™ you could save:', 'numbered-accordion' ); ?></p>
				<h3 class="eas__result-title" id="<?php echo esc_attr( $uid ); ?>-r"><span data-r="therms"></span> <small><?php esc_html_e( 'Therms per year', 'numbered-accordion' ); ?></small></h3>
				<p class="eas__save"><?php esc_html_e( 'If you’re paying a typical', 'numbered-accordion' ); ?> <span data-r="price"></span> <?php esc_html_e( 'per Therm for natural gas,', 'numbered-accordion' ); ?> <strong><?php esc_html_e( 'you could be saving', 'numbered-accordion' ); ?> <span data-r="dollars"></span> <?php esc_html_e( 'per year.', 'numbered-accordion' ); ?></strong></p>
				<div class="eas__next">
					<p><strong><?php esc_html_e( 'The ThermStar System™ recovers thermal energy from particulate-laden exhaust air streams, converting your wasted heat into clean, usable energy.', 'numbered-accordion' ); ?></strong> <?php esc_html_e( 'Dirty Air. Clean Energy.', 'numbered-accordion' ); ?></p>
					<p><?php esc_html_e( 'Your estimate is just the beginning of a process where we evaluate your heat recovery opportunities and can share more ways to save with waste heat recovery across an individual facility or broader site network.', 'numbered-accordion' ); ?>
						<?php if ( 'save' !== ( $s['delivery'] ?? 'send' ) ) : ?>
							<?php esc_html_e( 'A copy is on its way to your inbox.', 'numbered-accordion' ); ?>
						<?php endif; ?>
					</p>
				</div>
				<details class="eas__inputs">
					<summary><?php esc_html_e( 'The inputs and contact details you submitted', 'numbered-accordion' ); ?></summary>
					<table class="eas__table"><tbody data-r="rows"></tbody></table>
					<button type="button" class="eas__again" data-eas-again><?php esc_html_e( 'Need another estimate? Use the savings calculator again', 'numbered-accordion' ); ?></button>
				</details>
				<p class="eas__basis" data-r="basis"></p>
				<p class="eas__basis eas__basis--note"><?php echo esc_html( Assess_Content::WEATHER_NOTE ); ?></p>
				<p class="eas__ready"><?php esc_html_e( 'Ready to explore your savings with the ThermStar System™?', 'numbered-accordion' ); ?></p>
				<div class="eas__nav">
					<?php if ( 'modal' === $mode ) : ?>
						<button type="button" class="eas__btn<?php echo $book ? ' eas__btn--ghost' : ''; ?>" data-eas-close><?php esc_html_e( 'Done', 'numbered-accordion' ); ?></button>
					<?php endif; ?>
					<?php if ( $book ) : ?>
						<a class="eas__btn eas__btn--book" href="<?php echo esc_url( $book ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $book_label ); ?><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 5h5v5M19 5l-8 8M17 14v5H5V7h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="eas__sr"><?php esc_html_e( '(opens in a new tab)', 'numbered-accordion' ); ?></span></a>
					<?php else : ?>
						<a class="eas__btn eas__btn--ghost" href="tel:+18336677359"><?php esc_html_e( 'Call 833 667 7359', 'numbered-accordion' ); ?></a>
					<?php endif; ?>
				</div>
			</section>
		</div>
		<?php
		if ( 'modal' === $mode ) {
			echo '</dialog>';
		}

		if ( $book && 'yes' === ( $s['badge'] ?? '' ) && ! $edit ) {
			$this->badge( $book, trim( (string) ( $s['badge_label'] ?? '' ) ) );
		}
	}

	/**
	 * The booking button fixed to the corner of the page, as the old site's
	 * Calendly badge was. The script moves it to <body> so no transformed
	 * Elementor section can pin it to itself instead of the viewport.
	 *
	 * @param string $url   Booking page.
	 * @param string $label Button text.
	 */
	private function badge( $url, $label ) {
		$label = '' !== $label ? $label : __( 'Schedule time with us', 'numbered-accordion' );
		?>
		<a class="eas-badge" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="15" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 10h17M8 3v4M16 3v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<span><?php echo esc_html( $label ); ?></span>
			<span class="eas__sr"><?php esc_html_e( '(opens in a new tab)', 'numbered-accordion' ); ?></span>
		</a>
		<?php
	}
}
