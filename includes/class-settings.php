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
	 * Where per-module options live.
	 *
	 * Deliberately a second option rather than more keys in the first. That
	 * one is a flat map of module id to bool and its sanitiser rebuilds it
	 * from the registry every save; nesting arrays inside it would mean that
	 * sanitiser had to tell a module's settings from a module's switch.
	 */
	const SETTINGS_OPTION = 'eruda_toolkit_settings';

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

		register_setting(
			self::PAGE_SLUG,
			self::SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * One module's stored settings.
	 *
	 * Front-end callers use this rather than reading the option themselves,
	 * so the storage shape stays this class's business.
	 *
	 * @param string $id Module id.
	 * @return array<string, mixed> Raw stored values. Empty when unset.
	 */
	public static function module_values( $id ) {
		$stored = get_option( self::SETTINGS_OPTION, array() );

		if ( ! is_array( $stored ) || ! isset( $stored[ $id ] ) || ! is_array( $stored[ $id ] ) ) {
			return array();
		}

		return $stored[ $id ];
	}

	/**
	 * Sanitise every configurable module's submitted settings.
	 *
	 * Driven by what each module declares, so this method never learns what
	 * any particular module's options mean. A module that is switched off
	 * still keeps its settings: turning a module off and on again should not
	 * cost somebody the colour they chose.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return array<string, array<string, mixed>>
	 */
	public function sanitize_settings( $value ) {
		$submitted = is_array( $value ) ? $value : array();
		$clean     = array();
		$toolkit   = Toolkit::instance();

		foreach ( $toolkit->ids() as $id ) {
			$class = $toolkit->load( $id );

			if ( null === $class || ! is_subclass_of( $class, Configurable::class, true ) ) {
				continue;
			}

			$clean[ $id ] = Fields::sanitize(
				$class::settings_fields(),
				isset( $submitted[ $id ] ) ? $submitted[ $id ] : array()
			);
		}

		return $clean;
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

		// Not a module, so it has to be carried across by hand or saving the
		// page would silently switch it back on.
		$clean[ Toolkit::AUTO_UPDATE ] = ! empty( $submitted[ Toolkit::AUTO_UPDATE ] );

		return $clean;
	}

	/**
	 * Render whatever a module declares, under its switch.
	 *
	 * The screen knows nothing about any module's options: it renders the
	 * declarations it is handed. A module that declares nothing, or that does
	 * not implement the contract at all, prints nothing here and is otherwise
	 * untouched.
	 *
	 * @param string $id    Module id.
	 * @param string $class Module class name.
	 * @return void
	 */
	private function module_fields( $id, $class ) {
		if ( ! is_subclass_of( $class, Configurable::class, true ) ) {
			return;
		}

		$declarations = Fields::valid( $class::settings_fields() );

		if ( empty( $declarations ) ) {
			return;
		}

		$values = Fields::values( $declarations, self::module_values( $id ) );
		?>
		<div class="eruda-module-settings" style="margin-top:12px;padding-left:24px;border-left:3px solid #dcdcde;">
			<?php foreach ( $declarations as $field ) : ?>
				<?php
				$value = $values[ $field['id'] ];
				$name  = self::SETTINGS_OPTION . '[' . $id . '][' . $field['id'] . ']';
				$input = 'eruda-field-' . $id . '-' . $field['id'];
				?>
				<p style="margin:0 0 10px;">
					<?php if ( 'checkbox' === $field['type'] ) : ?>
						<label for="<?php echo esc_attr( $input ); ?>">
							<input type="checkbox"
								id="<?php echo esc_attr( $input ); ?>"
								name="<?php echo esc_attr( $name ); ?>"
								value="1"
								<?php checked( (bool) $value ); ?> />
							<?php echo esc_html( $field['label'] ); ?>
							<?php if ( ! empty( $field['help'] ) ) : ?>
								<span class="description">— <?php echo esc_html( $field['help'] ); ?></span>
							<?php endif; ?>
						</label>
					<?php else : ?>
						<label for="<?php echo esc_attr( $input ); ?>" style="display:inline-block;min-width:200px;">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
						<input type="<?php echo 'color' === $field['type'] ? 'color' : 'number'; ?>"
							id="<?php echo esc_attr( $input ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( (string) $value ); ?>"
							<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( (string) $field['min'] ); ?>"<?php endif; ?>
							<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( (string) $field['max'] ); ?>"<?php endif; ?>
							<?php if ( isset( $field['step'] ) ) : ?>step="<?php echo esc_attr( (string) $field['step'] ); ?>"<?php endif; ?> />
						<?php if ( ! empty( $field['help'] ) ) : ?>
							<span class="description"><?php echo esc_html( $field['help'] ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
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

								<?php $this->module_fields( $id, $class ); ?>
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Updates', 'numbered-accordion' ); ?></h2>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Automatic updates', 'numbered-accordion' ); ?></th>
							<td>
								<label for="eruda-auto-update">
									<input type="checkbox"
										id="eruda-auto-update"
										name="<?php echo esc_attr( Toolkit::OPTION . '[' . Toolkit::AUTO_UPDATE . ']' ); ?>"
										value="1"
										<?php checked( Toolkit::auto_update_enabled( $stored ) ); ?> />
									<?php esc_html_e( 'Install new versions of Eruda Toolkit automatically.', 'numbered-accordion' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Off means updates still appear in Dashboard \u2192 Updates, but installing one stays a deliberate click.', 'numbered-accordion' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
