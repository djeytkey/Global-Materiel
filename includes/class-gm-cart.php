<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Cart {

	public function __construct() {
		add_action( 'wp_ajax_update_custom_cart_quantity', array( $this, 'handle_custom_cart_ajax_update' ));
		add_action( 'wp_ajax_nopriv_update_custom_cart_quantity', array( $this, 'handle_custom_cart_ajax_update' ));
		add_shortcode( 'custom_cart_table', array( $this, 'render_custom_cart_table' ));
	}

	// GESTIONNAIRE AJAX POUR MISE À JOUR AUTO DU PANIER
	// SHORTCODE PANIER PERSONNALISÉ (DESIGN ÉPURÉ + AJAX)

	public function handle_custom_cart_ajax_update() {
	    if ( ! isset( $_POST['cart_item_key'] ) || ! isset( $_POST['qty'] ) ) {
	        wp_send_json_error( array( 'message' => 'Données manquantes' ) );
	        return;
	    }
	
	    $cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
	    $quantity = intval( $_POST['qty'] );
	
	    if ( $quantity < 0 ) {
	        $quantity = 0;
	    }
	
	    try {
	        
	        $updated = WC()->cart->set_quantity( $cart_item_key, $quantity );
	        WC()->cart->calculate_totals();
	        
	        wp_send_json_success( array( 
	            'message' => 'OK',
	            'quantity' => $quantity
	        ) );
	    } catch (Exception $e) {
	        wp_send_json_error( array( 'message' => $e->getMessage() ) );
	    }
	}

	public function render_custom_cart_table() {
	    if ( ! class_exists( 'WooCommerce' ) ) return '';
	
	    if ( WC()->cart->is_empty() ) {
	        return '
	        <div class="woocommerce cart-is-empty">
	            <p class="cart-empty-message">Votre panier est actuellement vide.</p>
	            <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button back-to-shop">Retourner à la boutique</a>
	        </div>';
	    }
	
	    ob_start();
	    ?>
	    <form class="woocommerce-cart-form custom-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	        <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
	            <thead>
	                <tr>
	                    <th class="product-name">Nom du produit</th>
	                    <th class="product-quantity">Quantité</th>
	                    <th class="product-remove">Supprimer</th>
	                </tr>
	            </thead>
	            <tbody>
	                <?php
	                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
	                    $product = $cart_item['data'];
	                    $product_id = $cart_item['product_id'];
	                    $quantity = $cart_item['quantity'];
	                    
	                    $product_name = $product->get_name();
	                    $product_sku = $product->get_sku();
	                    $product_link = $product->is_visible() ? $product->get_permalink() : '';
	                    
	                    $thumbnail = $product->get_image( array( 300, 300 ), array( 'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail' ) );
	                    
	                    $remove_url = wc_get_cart_remove_url( $cart_item_key );
	                    ?>
	                    <tr class="woocommerce-cart-form__cart-item cart_item">
	                        
	                        <td class="product-name" data-title="Produit">
	                            <?php if ( $product_link ) : ?>
	                                <a href="<?php echo esc_url( $product_link ); ?>">
	                                    <?php echo $thumbnail; ?>
	                                </a>
	                            <?php else : ?>
	                                <?php echo $thumbnail; ?>
	                            <?php endif; ?>
	                            
	                            <div class="cart-product-info">
	                                <?php if ( $product_link ) : ?>
	                                    <a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
	                                <?php else : ?>
	                                    <?php echo esc_html( $product_name ); ?>
	                                <?php endif; ?>
	                                
	                                <?php if ( $product_sku ) : ?>
	                                    <div class="product-sku">SKU: <?php echo esc_html( $product_sku ); ?></div>
	                                <?php endif; ?>
	                            </div>
	                        </td>
	
	                        <td class="product-quantity" data-title="Quantité">
	                            <?php
	                            if ( $product->is_sold_individually() ) {
	                                $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
	                            } else {
	                                $product_quantity = woocommerce_quantity_input(
	                                    array(
	                                        'input_name'   => "cart[{$cart_item_key}][qty]",
	                                        'input_value'  => $quantity,
	                                        'max_value'    => $product->get_max_purchase_quantity(),
	                                        'min_value'    => '0',
	                                        'product_name' => $product_name,
	                                    ),
	                                    $product,
	                                    false
	                                );
	                            }
	                            echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
	                            ?>
	                        </td>
	
	                        <td class="product-remove" data-title="Supprimer">
	                            <a href="<?php echo esc_url( $remove_url ); ?>" class="remove" aria-label="Supprimer ce produit" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-product_sku="<?php echo esc_attr( $product_sku ); ?>">×</a>
	                        </td>
	                        
	                    </tr>
	                    <?php
	                }
	                ?>
	                
	                <tr>
	                    <td colspan="3" class="actions">
	                        <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
	                        
	                        <button type="submit" class="button update-cart-hidden" name="update_cart" value="Mettre à jour le panier">Mettre à jour le panier</button>
	
	                        <div class="cart-btns-actions">
	                            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="back-to-store button">Continuer mes achats</a>
	                            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-btn button alt">Suivant</a>
	                        </div>
	                    </td>
	                </tr>
	            </tbody>
	        </table>
	    </form>
	
	    <script>
	    jQuery(document).ready(function($) {
	        var updateTimeout = null;
	        var DEBOUNCE_DELAY = 1000; // 1 seconde
	
	        function updateCartQuantity(cart_item_key, quantity, $row) {
	            $row.css('opacity', '0.5');
	            
	            $.ajax({
	                url: '<?php echo admin_url('admin-ajax.php'); ?>',
	                type: 'POST',
	                data: {
	                    action: 'update_custom_cart_quantity',
	                    cart_item_key: cart_item_key,
	                    qty: quantity
	                },
	                success: function(response) {
	                    setTimeout(function() {
	                        window.location.reload();
	                    }, 300);
	                },
	                error: function(xhr, status, error) {
	                    setTimeout(function() {
	                        window.location.reload();
	                    }, 300);
	                }
	            });
	        }
	
	        $('.custom-cart-form').on('change input', '.quantity input[type="number"]', function() {
	            var $input = $(this);
	            var quantity = parseInt($input.val());
	            var nameAttr = $input.attr('name');
	            
	            var match = nameAttr.match(/cart\[(.*?)\]\[qty\]/);
	            if (!match || isNaN(quantity)) return;
	
	            var cart_item_key = match[1];
	            var $row = $input.closest('tr');
	
	            if (updateTimeout) {
	                clearTimeout(updateTimeout);
	            }
	            
	            updateTimeout = setTimeout(function() {
	                updateCartQuantity(cart_item_key, quantity, $row);
	                updateTimeout = null;
	            }, DEBOUNCE_DELAY);
	        });
	
	        $('.custom-cart-form').on('click', '.quantity .plus, .quantity .minus', function() {
	            var $input = $(this).siblings('input[type="number"]');
	            
	            setTimeout(function() {
	                $input.trigger('change');
	            }, 50);
	        });
	    });
	    </script>
	    <?php
	
	    return ob_get_clean();
	}
}
