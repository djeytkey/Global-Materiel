<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chargeur des modules Global Matériel.
 * Singleton : un require accidentel de customizations.php ne double pas les hooks.
 */
class GM_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_modules();
	}

	private function load_modules() {
		require_once GM_PLUGIN_DIR . 'includes/class-gm-templates.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-gateway-quote.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-checkout.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-cart.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-wishlist.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-account.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-minicart.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-shop.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-carousels.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-product-data.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-cache.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-catalog.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-security.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-assets.php';
		require_once GM_PLUGIN_DIR . 'includes/class-gm-whatsapp.php';

		new GM_Templates();
		new GM_Checkout();
		new GM_Cart();
		new GM_Wishlist();
		new GM_Account();
		new GM_Minicart();
		new GM_Shop();
		new GM_Carousels();
		new GM_Product_Data();
		new GM_Cache();
		new GM_Catalog();
		new GM_Security();
		new GM_Assets();
		new GM_WhatsApp();
	}
}
