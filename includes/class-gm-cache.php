<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Cache {

	public function __construct() {
		add_action('elementor/editor/after_save', function() {
		    if (class_exists('LiteSpeed_Cache_API')) {
		        LiteSpeed_Cache_API::purge_all();
		    }
		});
		add_action('elementor/css-file/post-regenerate', function() {
		    if (class_exists('LiteSpeed_Cache_API')) {
		        LiteSpeed_Cache_API::purge_all();
		    }
		});
	}

	// PURGE AUTOMATIQUE LITESPEED CACHE

}
