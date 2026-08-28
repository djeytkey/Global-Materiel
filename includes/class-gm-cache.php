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
		add_filter( 'litespeed_optimize_js_excludes', array( $this, 'exclude_thegem_gallery_js' ) );
		add_filter( 'litespeed_optm_js_defer_exc', array( $this, 'exclude_thegem_gallery_js' ) );
		add_filter( 'litespeed_optm_js_delay_exc', array( $this, 'exclude_thegem_gallery_js' ) );
	}

	/**
	 * Empêche LiteSpeed de retarder le script TheGem qui définit firstImageLoaded().
	 * Sinon l'onload de la galerie produit plante (ReferenceError).
	 */
	public function exclude_thegem_gallery_js( $excludes ) {
		if ( ! is_array( $excludes ) ) {
			$excludes = array();
		}
		$excludes[] = 'firstImageLoaded';
		$excludes[] = 'firstImageGridLoaded';
		$excludes[] = 'gmFixSwiperLoopImages';
		return $excludes;
	}

	// PURGE AUTOMATIQUE LITESPEED CACHE

}
