<?php
/**
 * Settings screen.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One page under Settings with a checkbox per module.
 *
 * Built on the Settings API, which handles the nonce, the capability check on
 * submission and the redirect back. All this class supplies is the field
 * markup and a sanitize callback.
 */
final class Settings {

	const PAGE_SLUG = 'eruda-toolkit';

	/**
	 * Singleton instance.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook the admin screen up. Admin-side only.
	 */
	public function boot() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Add the settings page.
	 */
	public function add_page() {
		add_options_page(
			esc_html__( 'Eruda Toolkit', 'numbered-accordion' ),
			esc_html__( 'Eruda Toolkit', 'numbered-accordion' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register the option.
	 */
	public function register() {
		register_setting(
			self::PAGE_SLUG,
			Toolkit::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Cast known module ids to bool and drop everything else.
	 *
	 * Unchecked boxes are absent from the POST body, so the value is rebuilt
	 * from the registry rather than from the submitted keys.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return array<string, bool>
	 */
	public function sanitize( $value ) {
		$submitted = is_array( $value ) ? $value : array();
		$clean     = array();

		foreach ( Toolkit::instance()->ids() as $id ) {
			$clean[ $id ] = ! empty( $submitted[ $id ] );
		}

		return $clean;
	}

	/**
	 * Render the page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$toolkit = Toolkit::instance();
		$stored  = get_option( Toolkit::OPTION, array() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Eruda Toolkit', 'numbered-accordion' ); ?></h1>
			<p><?php echo esc_html__( 'Switch individual modules on or off. Modules are on by default.', 'numbered-accordion' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( self::PAGE_SLUG ); ?>

				<table class="form-table" role="presentation">
					<tbody>
					<?php
					foreach ( $toolkit->ids() as $id ) {
						$class = $toolkit->load( $id );

						if ( null === $class ) {
							continue;
						}

						$problems = $class::requirement_messages();
						$enabled  = Toolkit::is_enabled( $id, $stored );
						$field_id = 'eruda-module-' . $id;
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $class::label() ); ?></th>
							<td>
								<label for="<?php echo esc_attr( $field_id ); ?>">
									<input type="checkbox"
										id="<?php echo esc_attr( $field_id ); ?>"
										name="<?php echo esc_attr( Toolkit::OPTION . '[' . $id . ']' ); ?>"
										value="1"
										<?php checked( $enabled ); ?> />
									<?php echo esc_html( $class::description() ); ?>
								</label>

								<?php if ( ! empty( $problems ) ) : ?>
									<p class="description" style="color:#b32d2e;">
										<?php
										foreach ( $problems as $problem ) {
											echo esc_html( $problem ) . '<br />';
										}
										?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
