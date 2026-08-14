<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Templates {

	public function __construct() {
		add_filter( 'woocommerce_locate_template', array( $this, 'gm_locate_woocommerce_template' ), 99999, 3 );
		add_filter( 'wc_get_template', array( $this, 'gm_force_review_order_template' ), 99999, 5 );
	}

	// TEMPLATES WOOCOMMERCE DU PLUGIN GLOBAL MATÉRIEL

	public function gm_locate_woocommerce_template( $template, $template_name, $template_path ) {
		$plugin_template = GM_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
		return $template;
	}

	public function gm_force_review_order_template( $located, $template_name, $args, $template_path, $default_path ) {
		$plugin_template = GM_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
		return $located;
	}
}
