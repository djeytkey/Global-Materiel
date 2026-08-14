<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Assets {

	public function __construct() {
		add_action( 'init', array( $this, 'force_asset_version_bust' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_styles' ) );
	}

	public function force_asset_version_bust() {
	    if (is_admin()) return;
	    
	    $timestamp = time();
	    
	    add_filter('style_loader_src', function($src) use ($timestamp) {
	        if (strpos($src, home_url()) !== false) {
	            $src = remove_query_arg('ver', $src);
	            $src = add_query_arg('v', $timestamp, $src);
	        }
	        return $src;
	    }, 9999);
	    
	    add_filter('script_loader_src', function($src) use ($timestamp) {
	        if (strpos($src, home_url()) !== false) {
	            $src = remove_query_arg('ver', $src);
	            $src = add_query_arg('v', $timestamp, $src);
	        }
	        return $src;
	    }, 9999);
	}

	public function gm_enqueue_custom_scripts() {
		$js = GM_PLUGIN_DIR . 'assets/js/custom.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'gm-custom-js',
				GM_PLUGIN_URL . 'assets/js/custom.js',
				array( 'jquery' ),
				(string) filemtime( $js ),
				true
			);
		}
	}

	public function gm_enqueue_custom_styles() {
		$parts_dir = GM_PLUGIN_DIR . 'assets/css/parts/';
		$parts_url = GM_PLUGIN_URL . 'assets/css/parts/';
		$parts     = array(
			'00-base',
			'01-sale-price',
			'02-empty-tabs',
			'03-product-data',
			'04-related-categories-carousel',
			'05-homepage-slider',
			'06-promo-carousel',
			'07-category-products-carousel',
			'08-related-products-carousel',
			'09-category-sidebar',
			'10-shop-grid',
			'11-shop-views',
			'12-quantity-selector',
			'13-wishlist',
			'14-account-login',
			'15-cart',
			'16-checkout',
			'17-billing-fields',
			'18-theme-overrides',
		);

		$prev = array();
		foreach ( $parts as $part ) {
			$path = $parts_dir . $part . '.css';
			if ( ! file_exists( $path ) ) {
				continue;
			}
			$handle = 'gm-' . $part;
			wp_enqueue_style( $handle, $parts_url . $part . '.css', $prev, GM_VERSION );
			$prev = array( $handle );
		}

		if ( ! empty( $prev ) ) {
			wp_register_style( 'gm-main-css', false, $prev, GM_VERSION );
			wp_enqueue_style( 'gm-main-css' );
		}

		$custom_css = GM_PLUGIN_DIR . 'assets/css/custom.css';
		if ( file_exists( $custom_css ) ) {
			wp_enqueue_style(
				'gm-custom-css',
				GM_PLUGIN_URL . 'assets/css/custom.css',
				array( 'gm-main-css' ),
				(string) filemtime( $custom_css )
			);
		}
	}
}
