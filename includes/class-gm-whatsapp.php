<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_WhatsApp {

	const OPTION = 'gm_whatsapp_phone';
	const DEFAULT_PHONE = '212663000043';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_footer', array( $this, 'render_button' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( GM_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	public static function get_phone() {
		$phone = get_option( self::OPTION, self::DEFAULT_PHONE );
		$phone = preg_replace( '/\D+/', '', (string) $phone );
		return $phone ? $phone : self::DEFAULT_PHONE;
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Global Matériel', 'global-materiel' ),
			__( 'Global Matériel', 'global-materiel' ),
			'manage_options',
			'global-materiel',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'gm_settings',
			self::OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_phone' ),
				'default'           => self::DEFAULT_PHONE,
			)
		);
	}

	public function sanitize_phone( $value ) {
		$phone = preg_replace( '/\D+/', '', (string) $value );
		return $phone ? $phone : self::DEFAULT_PHONE;
	}

	public function add_settings_link( $links ) {
		$url = admin_url( 'options-general.php?page=global-materiel' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Réglages', 'global-materiel' ) . '</a>'
		);
		return $links;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Global Matériel', 'global-materiel' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'gm_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="gm_whatsapp_phone"><?php esc_html_e( 'Numéro WhatsApp', 'global-materiel' ); ?></label>
						</th>
						<td>
							<input type="text" id="gm_whatsapp_phone" name="<?php echo esc_attr( self::OPTION ); ?>" value="<?php echo esc_attr( self::get_phone() ); ?>" class="regular-text" inputmode="numeric" pattern="[0-9]+">
							<p class="description">
								<?php esc_html_e( 'Indicatif pays sans + ni espaces. Exemple : 212663000043', 'global-materiel' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function render_button() {
		if ( is_admin() ) {
			return;
		}

		$phone = self::get_phone();
		$url   = 'https://api.whatsapp.com/send?phone=' . rawurlencode( $phone );
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="dd-m-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'WhatsApp', 'global-materiel' ); ?>">
			<span class="icon"></span>
		</a>
		<?php
	}
}
