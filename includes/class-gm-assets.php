<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Assets {

	const OPTION_ENABLED = 'gm_cache_bust_enabled';
	const OPTION_MODE    = 'gm_cache_bust_mode';
	const MODE_LOAD      = 'load';
	const MODE_HOUR      = 'hour';

	/**
	 * Timestamp applied to local CSS/JS URLs while cache bust is active.
	 *
	 * @var string
	 */
	private $bust_timestamp = '';

	public function __construct() {
		add_action( 'init', array( $this, 'force_asset_version_bust' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_item' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
		add_action( 'wp_ajax_gm_save_cache_bust', array( $this, 'ajax_save_cache_bust' ) );
	}

	public function is_cache_bust_enabled() {
		return (bool) get_option( self::OPTION_ENABLED, '1' );
	}

	public function get_cache_bust_mode() {
		$mode = get_option( self::OPTION_MODE, self::MODE_LOAD );
		return in_array( $mode, array( self::MODE_LOAD, self::MODE_HOUR ), true ) ? $mode : self::MODE_LOAD;
	}

	public function force_asset_version_bust() {
		if ( is_admin() ) {
			return;
		}

		if ( ! $this->is_cache_bust_enabled() ) {
			return;
		}

		if ( self::MODE_HOUR === $this->get_cache_bust_mode() ) {
			$this->bust_timestamp = (string) wp_date( 'YmdH' );
		} else {
			$this->bust_timestamp = (string) time();
		}

		add_filter( 'style_loader_src', array( $this, 'bust_asset_src' ), 9999 );
		add_filter( 'script_loader_src', array( $this, 'bust_asset_src' ), 9999 );
	}

	public function bust_asset_src( $src ) {
		if ( strpos( $src, home_url() ) !== false ) {
			$src = remove_query_arg( 'ver', $src );
			$src = add_query_arg( 'v', $this->bust_timestamp, $src );
		}
		return $src;
	}

	public function add_admin_bar_item( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$enabled = $this->is_cache_bust_enabled();
		$mode    = $this->get_cache_bust_mode();
		$wrap_class = $enabled ? 'is-on' : 'is-off';

		$title  = '<div class="gm-cache-bust-wrap ' . esc_attr( $wrap_class ) . '">';
		$title .= '<span class="gm-cache-bust-label">';
		$title .= '<span class="ab-icon" aria-hidden="true"></span>';
		$title .= '<span class="gm-cache-bust-label-text">' . esc_html__( 'Désactive le cache CSS/JS', 'global-materiel' ) . '</span>';
		$title .= '</span>';
		$title .= '<label class="gm-cache-bust-switch" for="gm-cache-bust-toggle">';
		$title .= '<input type="checkbox" id="gm-cache-bust-toggle" ' . checked( $enabled, true, false ) . ' />';
		$title .= '<span class="gm-cache-bust-slider"></span>';
		$title .= '<span class="screen-reader-text">' . esc_html__( 'Activer le bypass cache CSS/JS', 'global-materiel' ) . '</span>';
		$title .= '</label>';
		$title .= '<select id="gm-cache-bust-mode" class="gm-cache-bust-select"' . disabled( $enabled, false, false ) . '>';
		$title .= '<option value="' . esc_attr( self::MODE_LOAD ) . '" ' . selected( $mode, self::MODE_LOAD, false ) . '>' . esc_html__( 'Chaque chargement', 'global-materiel' ) . '</option>';
		$title .= '<option value="' . esc_attr( self::MODE_HOUR ) . '" ' . selected( $mode, self::MODE_HOUR, false ) . '>' . esc_html__( 'Chaque heure', 'global-materiel' ) . '</option>';
		$title .= '</select>';
		$title .= '</div>';

		$wp_admin_bar->add_node(
			array(
				'id'    => 'gm-cache-bust',
				'title' => $title,
				'href'  => false,
				'meta'  => array(
					'class' => 'gm-cache-bust-menu',
				),
			)
		);
	}

	public function enqueue_admin_bar_assets() {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$css = GM_PLUGIN_DIR . 'assets/css/admin-bar-cache-bust.css';
		$js  = GM_PLUGIN_DIR . 'assets/js/admin-bar-cache-bust.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'gm-admin-bar-cache-bust',
				GM_PLUGIN_URL . 'assets/css/admin-bar-cache-bust.css',
				array(),
				(string) filemtime( $css )
			);
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'gm-admin-bar-cache-bust',
				GM_PLUGIN_URL . 'assets/js/admin-bar-cache-bust.js',
				array(),
				(string) filemtime( $js ),
				true
			);

			wp_localize_script(
				'gm-admin-bar-cache-bust',
				'gmCacheBust',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'gm_cache_bust' ),
					'enabled' => $this->is_cache_bust_enabled(),
					'mode'    => $this->get_cache_bust_mode(),
				)
			);
		}
	}

	public function ajax_save_cache_bust() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		check_ajax_referer( 'gm_cache_bust', 'nonce' );

		$enabled = isset( $_POST['enabled'] ) && '1' === (string) wp_unslash( $_POST['enabled'] );
		$mode    = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : self::MODE_LOAD;

		if ( ! in_array( $mode, array( self::MODE_LOAD, self::MODE_HOUR ), true ) ) {
			$mode = self::MODE_LOAD;
		}

		update_option( self::OPTION_ENABLED, $enabled ? '1' : '0', true );
		update_option( self::OPTION_MODE, $mode, true );

		wp_send_json_success(
			array(
				'enabled' => $enabled,
				'mode'    => $mode,
			)
		);
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
