<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Security {

	public function __construct() {
		add_filter('script_loader_tag', function($tag, $handle, $src) {
		    if (strpos($src, 'http') === 0 && strpos($src, home_url()) === false) {
		        $src = str_replace('://', 'https://', $src);
		        $src = str_replace('http://', 'https://', $src);
		        
		        if (strpos($tag, 'crossorigin') === false) {
		            $tag = str_replace(' src', ' crossorigin="anonymous" src', $tag);
		        }
		    }
		    return $tag;
		}, 10, 3);
		add_filter('style_loader_tag', function($tag, $handle, $src) {
		    if (strpos($src, 'http') === 0 && strpos($src, home_url()) === false) {
		        $src = str_replace('://', 'https://', $src);
		        $src = str_replace('http://', 'https://', $src);
		        
		        if (strpos($tag, 'crossorigin') === false) {
		            $tag = str_replace(' href', ' crossorigin="anonymous" href', $tag);
		        }
		    }
		    return $tag;
		}, 10, 3);
		add_filter('rest_authentication_errors', function($result) {
		    if (!empty($result)) {
		        return $result;
		    }
		
		    if (!is_user_logged_in()) {
		        return new WP_Error('rest_not_logged_in', 'Vous devez être connecté pour accéder à l\'API REST.', array('status' => 401));
		    }
		    return $result;
		});
		add_action('init', function() {
		    if (is_admin() && !is_user_logged_in()) {
		        return;
		    }
		    
		    if (isset($_GET['author']) && is_numeric($_GET['author'])) {
		        wp_redirect(home_url(), 301);
		        exit;
		    }
		
		    if (defined('REST_REQUEST') && REST_REQUEST && !is_user_logged_in()) {
		        wp_die('Non autorisé', 'Accès refusé', array('response' => 403));
		    }
		});
		remove_action('wp_head', 'wp_shortlink_wp_head', 10);
		remove_action('wp_head', 'wp_generator');
		add_filter('the_generator', '__return_empty_string');
		add_filter( 'script_loader_src', array( $this, 'remove_version_scripts_styles' ), 15, 1);
		add_filter( 'style_loader_src', array( $this, 'remove_version_scripts_styles' ), 15, 1);
		add_filter('xmlrpc_enabled', '__return_false');
		add_filter('wp_headers', function($headers) {
		    unset($headers['X-Pingback']);
		    return $headers;
		});
		add_filter('authenticate', function($user, $username, $password) {
		    if (empty($username) || empty($password)) {
		        return new WP_Error('empty_fields', 'Veuillez remplir tous les champs.');
		    }
		    
		    $ip = $_SERVER['REMOTE_ADDR'];
		    $transient_name = 'login_attempts_' . md5($ip);
		    $attempts = get_transient($transient_name);
		    
		    if ($attempts >= 5) {
		        return new WP_Error('too_many_attempts', 'Trop de tentatives. Veuillez réessayer dans 15 minutes.');
		    }
		    
		    return $user;
		}, 30, 3);
		add_action('wp_login_failed', function($username) {
		    $ip = $_SERVER['REMOTE_ADDR'];
		    $transient_name = 'login_attempts_' . md5($ip);
		    $attempts = get_transient($transient_name);
		    if (!$attempts) {
		        $attempts = 0;
		    }
		    
		    $attempts++;
		    set_transient($transient_name, $attempts, 900);
		});
		add_action('wp_login', function($username) {
		    $ip = $_SERVER['REMOTE_ADDR'];
		    $transient_name = 'login_attempts_' . md5($ip);
		    delete_transient($transient_name);
		});
		add_filter( 'site_transient_update_plugins', array( $this, 'hide_ai1wm_plugin_updates' ), 999 );
		add_filter( 'auto_update_plugin', array( $this, 'disable_ai1wm_auto_updates' ), 10, 2 );
	}

	// SÉCURITÉ : FORCER HTTPS ET CROSSORIGIN SUR SCRIPTS EXTERNES
	// SÉCURITÉ - PROTECTION API REST
	// SÉCURITÉ - DÉSACTIVER ÉNUMÉRATION UTILISATEURS
	// SÉCURITÉ - MASQUER VERSION WORDPRESS
	// SÉCURITÉ - DÉSACTIVER XML-RPC
	// SÉCURITÉ - LIMITER TENTATIVES CONNEXION
	// MASQUER LES MISES À JOUR ALL-IN-ONE WP MIGRATION (GARDER LES VERSIONS ACTUELLES)

	/**
	 * Plugins All-in-One WP Migration à figer sur la version installée.
	 *
	 * @return string[]
	 */
	private function locked_ai1wm_plugin_files() {
		return array(
			'all-in-one-wp-migration/all-in-one-wp-migration.php',
			'all-in-one-wp-migration-gdrive-extension/all-in-one-wp-migration-gdrive-extension.php',
		);
	}

	private function is_locked_ai1wm_plugin( $plugin_file ) {
		$plugin_file = str_replace( '\\', '/', (string) $plugin_file );
		if ( in_array( $plugin_file, $this->locked_ai1wm_plugin_files(), true ) ) {
			return true;
		}

		$dir = dirname( $plugin_file );
		return in_array(
			$dir,
			array(
				'all-in-one-wp-migration',
				'all-in-one-wp-migration-gdrive-extension',
			),
			true
		);
	}

	public function hide_ai1wm_plugin_updates( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		foreach ( $this->locked_ai1wm_plugin_files() as $plugin_file ) {
			if ( isset( $transient->response[ $plugin_file ] ) ) {
				unset( $transient->response[ $plugin_file ] );
			}
		}

		if ( ! empty( $transient->response ) && is_array( $transient->response ) ) {
			foreach ( array_keys( $transient->response ) as $plugin_file ) {
				if ( $this->is_locked_ai1wm_plugin( $plugin_file ) ) {
					unset( $transient->response[ $plugin_file ] );
				}
			}
		}

		return $transient;
	}

	public function disable_ai1wm_auto_updates( $update, $item ) {
		$plugin_file = '';
		if ( is_object( $item ) && ! empty( $item->plugin ) ) {
			$plugin_file = $item->plugin;
		} elseif ( is_string( $item ) ) {
			$plugin_file = $item;
		}

		if ( $plugin_file && $this->is_locked_ai1wm_plugin( $plugin_file ) ) {
			return false;
		}

		return $update;
	}

	public function remove_version_scripts_styles($src) {
	    if (strpos($src, 'ver=')) {
	        $src = remove_query_arg('ver', $src);
	    }
	    return $src;
	}
}
