<?php
/**
 * Plugin Name:       Global Matériel
 * Plugin URI:        https://wa.me/212689385061
 * Description:       Personnalisations Global Matériel (WooCommerce, shortcodes, checkout, styles et scripts) extraites du thème enfant TheGem.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Tarik BOUKJIJ
 * Author URI:        https://wa.me/212689385061
 * Text Domain:       global-materiel
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:   9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GM_VERSION', '1.1.0' );
define( 'GM_PLUGIN_FILE', __FILE__ );
define( 'GM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Déclare la compatibilité WooCommerce (HPOS / blocs).
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', GM_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', GM_PLUGIN_FILE, true );
	}
} );

/**
 * Charge les personnalisations uniquement après WooCommerce
 * (évite les fatals sur is_checkout / is_account_page trop tôt).
 */
add_action( 'plugins_loaded', 'gm_bootstrap', 20 );

function gm_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'gm_woocommerce_missing_notice' );
		return;
	}

	require_once GM_PLUGIN_DIR . 'includes/class-gm-plugin.php';
	GM_Plugin::instance();
}

function gm_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Global Matériel nécessite WooCommerce pour fonctionner.', 'global-materiel' );
	echo '</p></div>';
}
