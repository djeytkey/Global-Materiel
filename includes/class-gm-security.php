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
	}

	// SÉCURITÉ : FORCER HTTPS ET CROSSORIGIN SUR SCRIPTS EXTERNES
	// SÉCURITÉ - PROTECTION API REST
	// SÉCURITÉ - DÉSACTIVER ÉNUMÉRATION UTILISATEURS
	// SÉCURITÉ - MASQUER VERSION WORDPRESS
	// SÉCURITÉ - DÉSACTIVER XML-RPC
	// SÉCURITÉ - LIMITER TENTATIVES CONNEXION

	public function remove_version_scripts_styles($src) {
	    if (strpos($src, 'ver=')) {
	        $src = remove_query_arg('ver', $src);
	    }
	    return $src;
	}
}
