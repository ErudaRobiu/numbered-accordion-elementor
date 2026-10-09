<?php
/**
 * Assessment Form module.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit\Modules\Assess;

use ErudaToolkit\Elementor_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Assessment Form widget, the route it posts to, and the admin
 * list every request is saved in.
 */
final class Assess_Module extends Elementor_Module {

	const STYLE_HANDLE  = 'eas-assessment-form';
	const SCRIPT_HANDLE = 'eas-assessment-form';
	const REST_NS       = 'eruda/v1';
	const REST_ROUTE    = '/assessment';
	const WIDGET        = 'eas-assessment-form';
	const BOOKING_URL   = 'https://bookings.cloud.microsoft/book/IntroductoryCall@thermstar.com/';

	/**
	 * Requests one visitor may send in an hour.
	 */
	const PER_HOUR = 5;

	/**
	 * Estimate emails the whole site may send to visitors in an hour, so the
	 * form can never be used to flood other people's inboxes. Past it the
	 * request is still saved and the team still hears about it.
	 */
	const VISITOR_MAILS_PER_HOUR = 40;

	/**
	 * Seconds a person needs at least, and the longest a form may sit open.
	 */
	const MIN_SECONDS = 4;
	const MAX_SECONDS = 86400;

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public static function id() {
		return 'assess';
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public static function label() {
		return esc_html__( 'Assessment Form', 'numbered-accordion' );
	}

	/**
	 * Module description.
	 *
	 * @return string
	 */
	public static function description() {
		return esc_html__( 'Adds an "Assessment Form" widget: a three-step heat recovery request with an indicative savings estimate, as a pop-up any button can open or inline on a page. Every request is saved under Assessments and can be emailed to your team.', 'numbered-accordion' );
	}

	/**
	 * Hook everything up.
	 */
	public function boot() {
		require_once ERUDA_PATH . 'modules/assess/class-assess-content.php';
		require_once ERUDA_PATH . 'modules/assess/class-assess-email.php';

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		if ( is_admin() ) {
			add_filter( 'manage_' . Assess_Content::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
			add_action( 'manage_' . Assess_Content::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
			add_action( 'add_meta_boxes_' . Assess_Content::POST_TYPE, array( $this, 'meta_box' ) );
		}
	}

	/**
	 * Saved requests: private, listed in the admin, never on the site.
	 */
	public function register_post_type() {
		register_post_type(
			Assess_Content::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Assessments', 'numbered-accordion' ),
					'singular_name' => __( 'Assessment request', 'numbered-accordion' ),
					'edit_item'     => __( 'Assessment request', 'numbered-accordion' ),
					'search_items'  => __( 'Search requests', 'numbered-accordion' ),
					'not_found'     => __( 'No assessment requests yet.', 'numbered-accordion' ),
					'all_items'     => __( 'All requests', 'numbered-accordion' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'menu_position'       => 24,
				'menu_icon'           => 'dashicons-clipboard',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * POST /wp-json/eruda/v1/assessment
	 *
	 * Public by nature: anyone may ask for an assessment. Abuse is kept down
	 * by a hidden field bots fill in, a signed start token with a minimum time
	 * on the form, a check that the email's domain takes mail, a link filter,
	 * a limit per visitor per hour and a site-wide cap on visitor emails. The
	 * recipient, price and send mode are read from the widget as saved in
	 * Elementor, never from the request, so the route cannot be pointed at
	 * anyone else's inbox.
	 *
	 * GET /wp-json/eruda/v1/assessment/token hands out the start token; the
	 * form asks for it when it loads, so a cached page still gets a fresh one.
	 */
	public function register_route() {
		register_rest_route(
			self::REST_NS,
			self::REST_ROUTE,
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'handle' ),
			)
		);

		register_rest_route(
			self::REST_NS,
			self::REST_ROUTE . '/token',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$response = new \WP_REST_Response( array( 'token' => Assess_Content::token( time(), wp_salt( 'nonce' ) ) ), 200 );
					$response->header( 'Cache-Control', 'no-store, max-age=0' );
					return $response;
				},
			)
		);
	}

	/**
	 * Whether an email address's domain can receive mail at all.
	 *
	 * @param string $email Address.
	 * @return bool
	 */
	private static function domain_takes_mail( $email ) {
		$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );

		if ( '' === $domain || ! function_exists( 'checkdnsrr' ) ) {
			return true; // Cannot check here: let it through rather than lose a real request.
		}

		return checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' ) || checkdnsrr( $domain, 'AAAA' );
	}

	/**
	 * Find the widget's saved settings.
	 *
	 * @param int    $doc Document (page or template) id.
	 * @param string $el  Element id.
	 * @return array|null
	 */
	public static function widget_settings( $doc, $el ) {
		if ( ! $doc || ! $el || ! class_exists( '\Elementor\Plugin' ) ) {
			return null;
		}

		$data = json_decode( (string) get_post_meta( (int) $doc, '_elementor_data', true ), true );
		$find = function ( $els ) use ( &$find, $el ) {
			foreach ( (array) $els as $e ) {
				if ( ( $e['id'] ?? '' ) === $el && self::WIDGET === ( $e['widgetType'] ?? '' ) ) {
					return (array) ( $e['settings'] ?? array() );
				}
				if ( ! empty( $e['elements'] ) ) {
					$hit = $find( $e['elements'] );
					if ( null !== $hit ) {
						return $hit;
					}
				}
			}
			return null;
		};

		return $find( $data );
	}

	/**
	 * Take one request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( $request ) {
		$body = (array) $request->get_json_params();

		// Bots fill the hidden field, forge or skip the start token, or submit
		// faster than anyone can read three steps. Answer them as if it
		// worked, so they learn nothing.
		$fake = new \WP_REST_Response( array( 'ok' => true, 'estimate' => null ), 200 );
		$age  = Assess_Content::token_age( $body['token'] ?? null, time(), wp_salt( 'nonce' ) );

		if ( ! empty( $body['website'] ) || null === $age || $age < self::MIN_SECONDS ) {
			return $fake;
		}

		if ( $age > self::MAX_SECONDS ) {
			return new \WP_REST_Response( array( 'ok' => false, 'message' => 'This form has been open too long. Reload the page and try again.' ), 400 );
		}

		$settings = self::widget_settings( (int) ( $body['doc'] ?? 0 ), (string) ( $body['el'] ?? '' ) );

		if ( null === $settings ) {
			return new \WP_REST_Response( array( 'ok' => false, 'message' => 'This form is out of date. Reload the page and try again.' ), 400 );
		}

		$key  = 'eas_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$sent = (int) get_transient( $key );

		// The site's own editors test the form over and over; the hourly
		// limit is for the public. (WordPress only knows who they are when
		// the page sent its REST nonce, which it does for logged-in users.)
		$staff = current_user_can( 'edit_posts' );

		if ( ! $staff && $sent >= self::PER_HOUR ) {
			return new \WP_REST_Response( array( 'ok' => false, 'message' => 'Too many requests from this connection. Please email solutions@thermstar.com or call 833 667 7359.' ), 429 );
		}

		$clean = Assess_Content::clean( $body );

		if ( ! isset( $clean['errors']['email'] ) && ! self::domain_takes_mail( $clean['data']['email'] ) ) {
			$clean['errors']['email'] = 'That email address can’t receive mail. Check the part after the @.';
		}

		if ( $clean['errors'] ) {
			return new \WP_REST_Response( array( 'ok' => false, 'errors' => $clean['errors'] ), 422 );
		}

		if ( Assess_Content::is_spam( $clean['data'] ) ) {
			return $fake;
		}

		$d        = $clean['data'];
		$price    = isset( $settings['price']['size'] ) ? (float) $settings['price']['size'] : ( is_numeric( $settings['price'] ?? null ) ? (float) $settings['price'] : Assess_Content::PRICE );
		$estimate = Assess_Content::estimate( $d, $price );
		$summary  = Assess_Content::summary( $d, $estimate );

		$post_id = wp_insert_post(
			array(
				'post_type'    => Assess_Content::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $d['company'] . ' — ' . $d['first'] . ' ' . $d['last'],
				'post_content' => '',
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_eas_data', $d );
			update_post_meta( $post_id, '_eas_estimate', $estimate );
			update_post_meta( $post_id, '_eas_summary', $summary );
		}

		if ( ! $staff ) {
			set_transient( $key, $sent + 1, HOUR_IN_SECONDS );
		}

		$mode = ( $settings['delivery'] ?? 'send' ) === 'save' ? 'save' : 'send';
		$to   = sanitize_email( (string) ( $settings['recipient'] ?? '' ) );
		$mail = array( 'team' => false, 'visitor' => false );

		if ( 'send' === $mode && is_email( $to ) ) {
			$mail['team'] = wp_mail(
				$to,
				sprintf( 'Assessment request: %s (%s)', $d['company'], Assess_Content::industries()[ $d['industry'] ] ?? 'industry not given' ),
				$summary . "\n\nSaved in WordPress under Assessments.",
				array( 'Reply-To: ' . $d['first'] . ' ' . $d['last'] . ' <' . $d['email'] . '>' )
			);
			$cap = (int) get_transient( 'eas_visitor_mails' );

			if ( $staff || $cap < self::VISITOR_MAILS_PER_HOUR ) {
				$mail['visitor'] = self::send_visitor_email( $d, $estimate, $settings, $to );
				if ( ! $staff ) {
					set_transient( 'eas_visitor_mails', $cap + 1, HOUR_IN_SECONDS );
				}
			}
		}

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_eas_mail', array( 'mode' => $mode, 'to' => $to ) + $mail );
		}

		return new \WP_REST_Response( array( 'ok' => true, 'estimate' => $estimate, 'rows' => Assess_Content::rows( $d ) ), 200 );
	}

	/**
	 * Email the visitor their estimate: ThermStar's HTML letter, with the
	 * plain text version alongside for mail apps that will not show HTML.
	 *
	 * @param array  $d        Clean data.
	 * @param array  $e        Estimate.
	 * @param array  $settings Saved widget settings.
	 * @param string $team     Team address, for replies.
	 * @return bool
	 */
	private static function send_visitor_email( $d, $e, $settings, $team ) {
		$logo  = (int) ( $settings['email_logo']['id'] ?? 0 );
		$links = array(
			'book'  => self::booking_url( $settings ),
			'again' => home_url( '/#assessment' ),
			'logo'  => $logo ? (string) wp_get_attachment_image_url( $logo, 'large' ) : '',
		);
		$text  = Assess_Email::text( $d, $e, $links );
		$alt   = function ( $mailer ) use ( $text ) {
			$mailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		};

		add_action( 'phpmailer_init', $alt );
		$sent = wp_mail(
			$d['email'],
			Assess_Email::SUBJECT,
			Assess_Email::html( $d, $e, $links ),
			array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ThermStar <' . $team . '>' )
		);
		remove_action( 'phpmailer_init', $alt );

		return $sent;
	}

	/**
	 * The booking page the widget links to. A widget saved before the
	 * setting existed has no key and gets the default; an emptied one gets none.
	 *
	 * @param array $settings Saved widget settings.
	 * @return string
	 */
	public static function booking_url( $settings ) {
		if ( ! array_key_exists( 'booking_url', (array) $settings ) ) {
			return self::BOOKING_URL;
		}

		return esc_url_raw( trim( (string) $settings['booking_url'] ) );
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public function columns( $cols ) {
		return array(
			'cb'           => $cols['cb'] ?? '',
			'title'        => __( 'Request', 'numbered-accordion' ),
			'eas_email'    => __( 'Email', 'numbered-accordion' ),
			'eas_industry' => __( 'Industry', 'numbered-accordion' ),
			'eas_estimate' => __( 'Estimate / year', 'numbered-accordion' ),
			'eas_mail'     => __( 'Emailed', 'numbered-accordion' ),
			'date'         => __( 'Received', 'numbered-accordion' ),
		);
	}

	/**
	 * Admin list cells.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Post.
	 */
	public function column( $col, $post_id ) {
		$d = (array) get_post_meta( $post_id, '_eas_data', true );
		$e = (array) get_post_meta( $post_id, '_eas_estimate', true );
		$m = (array) get_post_meta( $post_id, '_eas_mail', true );

		switch ( $col ) {
			case 'eas_email':
				echo esc_html( $d['email'] ?? '' );
				break;
			case 'eas_industry':
				echo esc_html( Assess_Content::industries()[ $d['industry'] ?? '' ] ?? '' );
				break;
			case 'eas_estimate':
				if ( ! empty( $e['dollars'] ) ) {
					echo esc_html( '$' . Assess_Content::fmt( $e['dollars'][0] ) . '–$' . Assess_Content::fmt( $e['dollars'][1] ) );
				}
				break;
			case 'eas_mail':
				echo esc_html( 'save' === ( $m['mode'] ?? '' ) ? __( 'No (saved only)', 'numbered-accordion' ) : ( ! empty( $m['team'] ) ? __( 'Yes', 'numbered-accordion' ) : __( 'Failed', 'numbered-accordion' ) ) );
				break;
		}
	}

	/**
	 * The request, read-only, on its edit screen.
	 */
	public function meta_box() {
		add_meta_box(
			'eas-request',
			__( 'What they sent', 'numbered-accordion' ),
			function ( $post ) {
				echo '<pre style="white-space:pre-wrap;font:13px/1.6 -apple-system,BlinkMacSystemFont,sans-serif;margin:0">' . esc_html( (string) get_post_meta( $post->ID, '_eas_summary', true ) ) . '</pre>';
			},
			Assess_Content::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Register the stylesheet. Enqueued on demand via get_style_depends().
	 */
	public function register_styles() {
		wp_register_style( self::STYLE_HANDLE, ERUDA_URL . 'modules/assess/assets/css/assessment-form.css', array(), ERUDA_VERSION );
	}

	/**
	 * Register the script. Enqueued on demand via get_script_depends().
	 */
	public function register_scripts() {
		wp_register_script( self::SCRIPT_HANDLE, ERUDA_URL . 'modules/assess/assets/js/assessment-form.js', array(), ERUDA_VERSION, true );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widget manager.
	 */
	public function register_widgets( $widgets_manager ) {
		try {
			if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
				return;
			}

			require_once ERUDA_PATH . 'modules/assess/widgets/class-assessment-form-widget.php';

			if ( class_exists( '\ErudaToolkit\Modules\Assess\Widgets\Assessment_Form_Widget' ) ) {
				$widgets_manager->register( new Widgets\Assessment_Form_Widget() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Eruda Toolkit: failed to register the Assessment Form - ' . $e->getMessage() );
			}
		}
	}
}
