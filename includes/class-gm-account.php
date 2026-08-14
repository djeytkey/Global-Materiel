<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Account {

	public function __construct() {
		add_action( 'woocommerce_before_customer_login_form', array( $this, 'custom_capture_login_form' ), 0);
		add_action( 'woocommerce_after_customer_login_form', array( $this, 'custom_replace_login_form' ), 999);
		add_filter( 'gettext', array( $this, 'custom_login_texts_override' ), 999, 3);
		add_filter( 'ngettext', array( $this, 'custom_login_texts_override' ), 999, 3);
	}

	// FORCER L'UTILISATION DU TEMPLATE FORM-LOGIN DU PLUGIN
	// MODIFICATION DES TEXTES DU FORMULAIRE (CLIENTS SEULEMENT)

	public function custom_capture_login_form() {
	    if (current_user_can('manage_options')) return;
	    ob_start();
	}

	public function custom_replace_login_form() {
	    if (current_user_can('manage_options')) return;
	    
	    ob_end_clean();
	    
	    $custom_template = GM_PLUGIN_DIR . 'templates/woocommerce/myaccount/form-login.php';
	    
	    if (file_exists($custom_template)) {
	        include($custom_template);
	    }
	}

	public function custom_login_texts_override($translated_text, $text, $domain) {
	    // gettext peut s'exécuter avant le chargement de WooCommerce
	    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
	        return $translated_text;
	    }
	    
	    if (current_user_can('manage_options')) {
	        return $translated_text;
	    }
	    
	    $custom_texts = array(
	        'Login' => 'Connexion',
	        'Username or email address' => 'Nom d’utilisateur ou adresse e-mail',
	        'Password' => 'Mot de passe',
	        'Remember me' => 'Se souvenir de moi',
	        'Lost your password?' => 'Mot de passe perdu ?',
	        'Register' => 'S’inscrire',
	        'Username' => 'Identifiant',
	        'Email address' => 'Adresse e-mail',
	        'A link to set a new password will be sent to your email address.' => 'Un mot de passe sera envoyé à votre adresse e-mail.',
	        'Required' => 'Obligatoire',
	    );
	    
	    if (isset($custom_texts[$text])) {
	        return $custom_texts[$text];
	    }
	    
	    return $translated_text;
	}
}
