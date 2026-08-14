<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ancien point d'entrée des personnalisations.
 * Conservé pour ne pas casser un require existant : tout passe par GM_Plugin.
 */
if ( ! class_exists( 'GM_Plugin' ) ) {
	require_once GM_PLUGIN_DIR . 'includes/class-gm-plugin.php';
}

GM_Plugin::instance();
