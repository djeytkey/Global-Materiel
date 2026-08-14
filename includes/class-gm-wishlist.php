<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Wishlist {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'inject_wishlist_badge_to_specific_icon' ));
		add_action( 'wp_ajax_get_wishlist_count', array( $this, 'handle_get_wishlist_count_ajax' ));
		add_action( 'wp_ajax_nopriv_get_wishlist_count', array( $this, 'handle_get_wishlist_count_ajax' ));
		add_shortcode( 'custom_wishlist_table', array( $this, 'render_custom_wishlist_table' ));
	}

	// 1. INJECTION CIBLÉE DU BADGE SUR LE WIDGET ELEMENTOR
	// 2. ENDPOINT AJAX POUR RÉCUPÉRER LE COMPTEUR WISHLIST
	// 3. SHORTCODE TABLEAU PERSONNALISÉ DE LA LISTE DE SOUHAITS

	public function inject_wishlist_badge_to_specific_icon() {
	    ?>
	    <script>
	    (function($) {
	        'use strict';
	        
	        function injectBadge() {
	            var $wishlistLink = $('.custom-wishlist-icon a');
	            
	            if ($wishlistLink.length && !$wishlistLink.find('.elementor-button-icon-qty').length) {
	                $wishlistLink.prepend('<span class="elementor-button-icon-qty" data-counter="0">0</span>');
	            }
	        }
	        
	        function updateWishlistCount() {
	            $.ajax({
	                url: '<?php echo admin_url('admin-ajax.php'); ?>',
	                type: 'POST',
	                data: { action: 'get_wishlist_count' },
	                success: function(response) {
	                    if (response.success && typeof response.data.count !== 'undefined') {
	                        var count = parseInt(response.data.count, 10);
	                        $('.custom-wishlist-icon a .elementor-button-icon-qty')
	                            .text(count)
	                            .attr('data-counter', count);
	                    }
	                }
	            });
	        }
	        
	        $(document).ready(function() {
	            injectBadge();
	            setTimeout(updateWishlistCount, 500);
	        });
	        
	        $(document.body).on('added_to_wishlist removed_from_wishlist', function() {
	            setTimeout(updateWishlistCount, 600);
	        });
	        
	    })(jQuery);
	    </script>
	    <?php
	}

	public function handle_get_wishlist_count_ajax() {
	    $count = 0;
	    
	    if (class_exists('YITH_WCWL')) {
	        if (method_exists(YITH_WCWL(), 'count_products')) {
	            $count = YITH_WCWL()->count_products();
	        } else {
	            global $wpdb;
	            $user_id = get_current_user_id();
	            $wishlist_id = null;
	            
	            if ($user_id > 0) {
	                $wishlist_id = $wpdb->get_var($wpdb->prepare(
	                    "SELECT ID FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id = %d LIMIT 1",
	                    $user_id
	                ));
	            } else {
	                if (isset($_COOKIE['yith_wcwl_session'])) {
	                    $token = sanitize_text_field($_COOKIE['yith_wcwl_session']);
	                    $wishlist_id = $wpdb->get_var($wpdb->prepare(
	                        "SELECT ID FROM {$wpdb->prefix}yith_wcwl_lists WHERE wishlist_token = %s LIMIT 1",
	                        $token
	                    ));
	                }
	            }
	            
	            if ($wishlist_id) {
	                $count = (int) $wpdb->get_var($wpdb->prepare(
	                    "SELECT COUNT(*) FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id = %d",
	                    $wishlist_id
	                ));
	            }
	        }
	    }
	    
	    wp_send_json_success(array('count' => (int) $count));
	}

	public function render_custom_wishlist_table() {
	    if (!class_exists('YITH_WCWL')) {
	        return '<p style="color: red; text-align: center; padding: 20px;">Veuillez installer et activer le plugin YITH WooCommerce Wishlist.</p>';
	    }
	
	    global $wpdb;
	    $wishlist_items = array();
	    $user_id = get_current_user_id();
	    $wishlist_id = null;
	    
	    if ($user_id > 0) {
	        $wishlist_id = $wpdb->get_var($wpdb->prepare(
	            "SELECT ID FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id = %d AND is_default = 1 LIMIT 1",
	            $user_id
	        ));
	        if (!$wishlist_id) {
	            $wishlist_id = $wpdb->get_var($wpdb->prepare(
	                "SELECT ID FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id = %d LIMIT 1",
	                $user_id
	            ));
	        }
	    } else {
	        if (isset($_COOKIE['yith_wcwl_session'])) {
	            $token = sanitize_text_field($_COOKIE['yith_wcwl_session']);
	            $wishlist_id = $wpdb->get_var($wpdb->prepare(
	                "SELECT ID FROM {$wpdb->prefix}yith_wcwl_lists WHERE wishlist_token = %s LIMIT 1",
	                $token
	            ));
	        }
	    }
	    
	    if ($wishlist_id) {
	        $items = $wpdb->get_results($wpdb->prepare(
	            "SELECT prod_id FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id = %d ORDER BY position ASC",
	            $wishlist_id
	        ));
	        
	        if ($items) {
	            foreach ($items as $item) {
	                $wishlist_items[] = array('prod_id' => $item->prod_id);
	            }
	        }
	    }
	
	    if (empty($wishlist_items)) {
	        $shop_url = get_permalink(wc_get_page_id('shop'));
	        return '
	        <div class="woocommerce custom-wishlist-empty">
	            <p style="text-align: center; font-size: 18px; margin-bottom: 20px; color: #666;">Votre liste de souhaits est actuellement vide.</p>
	            <div style="text-align: center;">
	                <a href="' . esc_url($shop_url) . '" class="button">Retourner à la boutique</a>
	            </div>
	        </div>';
	    }
	
	    ob_start();
	    ?>
	    <div class="woocommerce custom-wishlist-wrapper">
	        <table class="shop_table shop_table_responsive cart custom-wishlist-table">
	            <thead>
	                <tr>
	                    <th class="product-remove" style="width: 50px; text-align: center;">&nbsp;</th>
	                    <th class="product-thumbnail" style="width: 100px; text-align: center;">&nbsp;</th>
	                    <th class="product-name">Produit</th>
	                    <th class="product-price" style="width: 150px; text-align: center;">Prix</th>
	                    <th class="product-add-to-cart" style="width: 200px; text-align: center;">&nbsp;</th>
	                </tr>
	            </thead>
	            <tbody>
	                <?php foreach ($wishlist_items as $item) : 
	                    $product_id = $item['prod_id'];
	                    $product = wc_get_product($product_id);
	                    
	                    if (!$product) continue;
	
	                    $remove_url = YITH_WCWL()->get_remove_url($product_id);
	                    
	                    $thumbnail = get_the_post_thumbnail($product_id, 'woocommerce_thumbnail');
	                    $permalink = get_permalink($product_id);
	                    $title = $product->get_name();
	                    $price_html = $product->get_price_html();
	                    $add_to_cart_url = $product->add_to_cart_url();
	                    $is_in_stock = $product->is_in_stock();
	                ?>
	                <tr class="wishlist-item">
	                    <td class="product-remove" data-title="Supprimer">
	                        <a href="<?php echo esc_url($remove_url); ?>" class="remove" aria-label="Supprimer ce produit" title="Supprimer">×</a>
	                    </td>
	                    
	                    <td class="product-thumbnail" data-title="Image">
	                        <a href="<?php echo esc_url($permalink); ?>">
	                            <?php echo $thumbnail ? $thumbnail : wc_placeholder_img('woocommerce_thumbnail'); ?>
	                        </a>
	                    </td>
	                    
	                    <td class="product-name" data-title="Produit">
	                        <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
	                    </td>
	                    
	                    <td class="product-price" data-title="Prix">
	                        <?php echo $price_html; ?>
	                    </td>
	                    
	                    <td class="product-add-to-cart" data-title="Ajouter au panier">
	                        <?php if ($is_in_stock) : ?>
	                            <a href="<?php echo esc_url($add_to_cart_url); ?>" 
	                               class="button product_type_<?php echo esc_attr($product->get_type()); ?> add_to_cart_button ajax_add_to_cart" 
	                               data-product_id="<?php echo esc_attr($product_id); ?>" 
	                               data-quantity="1">
	                                Ajouter au panier
	                            </a>
	                        <?php else : ?>
	                            <span class="out-of-stock" style="color: #999; font-size: 13px;">Indisponible</span>
	                        <?php endif; ?>
	                    </td>
	                </tr>
	                <?php endforeach; ?>
	            </tbody>
	        </table>
	    </div>
	    <?php
	    return ob_get_clean();
	}
}
