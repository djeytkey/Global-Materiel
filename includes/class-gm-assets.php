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
		add_action( 'init', array( $this, 'handle_admin_bar_action' ), 1 );
		add_action( 'init', array( $this, 'force_asset_version_bust' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'gm_enqueue_custom_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_item' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
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
		$status  = $enabled ? __( 'Activé', 'global-materiel' ) : __( 'Désactivé', 'global-materiel' );

		$wp_admin_bar->add_node(
			array(
				'id'    => 'gm-cache-bust',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">(' . esc_html( $status ) . ')</span>',
				'href'  => false,
				'meta'  => array(
					'class' => $enabled ? 'gm-cache-bust-on' : 'gm-cache-bust-off',
					'title' => __( 'Bypass cache CSS/JS', 'global-materiel' ),
				),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'gm-cache-bust-toggle',
				'parent' => 'gm-cache-bust',
				'title'  => $enabled ? __( 'Désactiver', 'global-materiel' ) : __( 'Activer', 'global-materiel' ),
				'href'   => $this->get_action_url( $enabled ? 'disable' : 'enable' ),
			)
		);

		if ( ! $enabled ) {
			return;
		}

		$wp_admin_bar->add_group(
			array(
				'id'     => 'gm-cache-bust-modes',
				'parent' => 'gm-cache-bust',
				'meta'   => array(
					'class' => 'ab-sub-secondary',
				),
			)
		);

		$modes = array(
			self::MODE_LOAD => __( 'Chaque chargement', 'global-materiel' ),
			self::MODE_HOUR => __( 'Chaque heure', 'global-materiel' ),
		);

		foreach ( $modes as $value => $label ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'gm-cache-bust-mode-' . $value,
					'parent' => 'gm-cache-bust-modes',
					'title'  => $label,
					'href'   => $this->get_action_url( 'mode', $value ),
					'meta'   => array(
						'class' => ( $value === $mode ) ? 'gm-cache-bust-current' : '',
					),
				)
			);
		}
	}

	public function handle_admin_bar_action() {
		if ( empty( $_GET['gm_cache_bust'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'gm_cache_bust' );

		$action = sanitize_key( wp_unslash( $_GET['gm_cache_bust'] ) );

		if ( 'enable' === $action ) {
			update_option( self::OPTION_ENABLED, '1', true );
		} elseif ( 'disable' === $action ) {
			update_option( self::OPTION_ENABLED, '0', true );
		} elseif ( 'mode' === $action ) {
			$mode = isset( $_GET['gm_mode'] ) ? sanitize_key( wp_unslash( $_GET['gm_mode'] ) ) : self::MODE_LOAD;
			if ( in_array( $mode, array( self::MODE_LOAD, self::MODE_HOUR ), true ) ) {
				update_option( self::OPTION_MODE, $mode, true );
				update_option( self::OPTION_ENABLED, '1', true );
			}
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = remove_query_arg( array( 'gm_cache_bust', 'gm_mode', '_wpnonce' ) );
		} else {
			$redirect = remove_query_arg( array( 'gm_cache_bust', 'gm_mode', '_wpnonce' ), $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	public function enqueue_admin_bar_assets() {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$css = GM_PLUGIN_DIR . 'assets/css/admin-bar-cache-bust.css';
		if ( ! file_exists( $css ) ) {
			return;
		}

		wp_enqueue_style(
			'gm-admin-bar-cache-bust',
			GM_PLUGIN_URL . 'assets/css/admin-bar-cache-bust.css',
			array( 'admin-bar' ),
			(string) filemtime( $css )
		);
	}

	private function get_action_url( $action, $mode = '' ) {
		$args = array(
			'gm_cache_bust' => $action,
		);
		if ( '' !== $mode ) {
			$args['gm_mode'] = $mode;
		}

		return wp_nonce_url( add_query_arg( $args ), 'gm_cache_bust' );
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
