<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Minicart {

	public function __construct() {
		add_action( 'wp_ajax_update_mini_cart_quantities', array( $this, 'handle_mini_cart_quantities_update' ));
		add_action( 'wp_ajax_nopriv_update_mini_cart_quantities', array( $this, 'handle_mini_cart_quantities_update' ));
		add_action( 'wp_footer', array( $this, 'add_elementor_menu_cart_quantity_script' ));
	}

	// GESTIONNAIRE AJAX POUR MISE A JOUR DE TOUTES LES QUANTITES
	// SCRIPT JAVASCRIPT POUR SELECTEUR QUANTITE + BOUTON METTRE A JOUR

	public function handle_mini_cart_quantities_update() {
	    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : array();
	    
	    if (empty($quantities) || !is_array($quantities)) {
	        wp_send_json_error(array('message' => 'Parametres invalides'));
	        return;
	    }
	    
	    foreach ($quantities as $cart_item_key => $quantity) {
	        $cart_item_key = sanitize_text_field($cart_item_key);
	        $quantity = intval($quantity);
	        
	        if ($quantity < 0) continue;
	        
	        if ($quantity === 0) {
	            WC()->cart->remove_cart_item($cart_item_key);
	        } else {
	            WC()->cart->set_quantity($cart_item_key, $quantity, true);
	        }
	    }
	    
	    WC()->cart->calculate_totals();
	    
	    wp_send_json_success(array(
	        'cart_hash' => WC()->cart->get_cart_hash(),
	        'cart_total' => WC()->cart->get_cart_total(),
	        'cart_count' => WC()->cart->get_cart_contents_count(),
	    ));
	}

	public function add_elementor_menu_cart_quantity_script() {
	    if (!class_exists('WooCommerce')) return;
	    ?>
	    <script>
	    (function($) {
	        'use strict';
	        
	        var DEBUG = true;
	        var initialQuantities = {};
	        var isUpdating = false;
	        
	        function log(msg) {
	            if (DEBUG) console.log('[MiniCart] ' + msg);
	        }
	        
	        function getCartItemKey($product) {
	            var key = null;
	            
	            key = $product.attr('data-cart-item');
	            if (key) return key;
	            
	            var $removeBtn = $product.find('.elementor-menu-cart__product-remove');
	            if ($removeBtn.length) {
	                key = $removeBtn.data('cart_item_key') || $removeBtn.attr('data-cart_item_key');
	                if (key) return key;
	                
	                var href = $removeBtn.attr('href') || '';
	                var match = href.match(/cart_item_key=([^&]+)/);
	                if (match) return match[1];
	            }
	            
	            $product.find('[data-cart_item_key]').each(function() {
	                key = $(this).data('cart_item_key') || $(this).attr('data-cart_item_key');
	                if (key) return false;
	            });
	            
	            if (key) return key;
	            
	            $product.find('a[href*="cart_item_key"]').each(function() {
	                var href = $(this).attr('href');
	                var match = href.match(/cart_item_key=([^&]+)/);
	                if (match) {
	                    key = match[1];
	                    return false;
	                }
	            });
	            
	            return key;
	        }
	        
	        function getCurrentQuantity($product) {
	            var qty = null;
	            
	            var $qtyEl = $product.find('.elementor-menu-cart__product-quantity');
	            if ($qtyEl.length) {
	                var qtyText = $qtyEl.text();
	                var match = qtyText.match(/(\d+)/);
	                if (match) {
	                    qty = parseInt(match[1]);
	                    return qty;
	                }
	            }
	            
	            $product.find('[class*="quantity"], [class*="qty"]').each(function() {
	                var text = $(this).text();
	                var match = text.match(/(\d+)/);
	                if (match) {
	                    var val = parseInt(match[1]);
	                    if (val > 0 && val < 1000) {
	                        qty = val;
	                        return false;
	                    }
	                }
	            });
	            
	            if (qty !== null) return qty;
	            
	            var $price = $product.find('.elementor-menu-cart__product-price');
	            if ($price.length) {
	                var priceText = $price.text();
	                var match = priceText.match(/x\s*(\d+)/i) || priceText.match(/×\s*(\d+)/);
	                if (match) {
	                    qty = parseInt(match[1]);
	                    return qty;
	                }
	            }
	            
	            $product.find('span, div, p').each(function() {
	                var text = $(this).text().trim();
	                var match = text.match(/^(\d+)$/) || text.match(/x\s*(\d+)/i) || text.match(/×\s*(\d+)/);
	                if (match) {
	                    var val = parseInt(match[1]);
	                    if (val > 0 && val < 1000) {
	                        qty = val;
	                        return false;
	                    }
	                }
	            });
	            
	            if (qty !== null) return qty;
	            
	            var fullText = $product.text();
	            var numbers = fullText.match(/\d+/g);
	            if (numbers) {
	                for (var i = numbers.length - 1; i >= 0; i--) {
	                    var val = parseInt(numbers[i]);
	                    if (val > 0 && val < 100) {
	                        qty = val;
	                        break;
	                    }
	                }
	            }
	            
	            if (qty !== null) return qty;
	            
	            return 1;
	        }
	        
	        function injectUpdateButton() {
	            var $subtotal = $('.elementor-menu-cart__subtotal');
	            if ($subtotal.length === 0) {
	                return;
	            }
	            
	            if ($subtotal.find('.qty-update-all-btn').length > 0) {
	                return;
	            }
	            
	            var $updateBtn = $('<button type="button" class="qty-update-all-btn" style="display:none;">Mettre a jour</button>');
	            $subtotal.prepend($updateBtn);
	        }
	        
	        function replacePriceWithQuantitySelector() {
	            var $products = $('.elementor-menu-cart__product');
	            
	            if ($products.length === 0) {
	                $products = $('.e-cart-item, .cart_item, [class*="cart-product"]');
	            }
	            
	            $products.each(function() {
	                var $product = $(this);
	                var $priceContainer = $product.find('.elementor-menu-cart__product-price.product-price');
	                
	                if ($priceContainer.length === 0) return;
	                if ($priceContainer.find('.quantity-controls').length > 0) return;
	                
	                var cartItemKey = getCartItemKey($product);
	                if (!cartItemKey) return;
	                
	                var currentQty = getCurrentQuantity($product);
	                initialQuantities[cartItemKey] = currentQty;
	                
	                var $quantitySelector = $('<div class="quantity-controls">' +
	                    '<button type="button" class="qty-minus" data-cart-item-key="' + cartItemKey + '">-</button>' +
	                    '<input type="number" class="qty-input" value="' + currentQty + '" min="0" max="999" data-cart-item-key="' + cartItemKey + '" data-initial-qty="' + currentQty + '">' +
	                    '<button type="button" class="qty-plus" data-cart-item-key="' + cartItemKey + '">+</button>' +
	                    '</div>');
	                
	                $priceContainer.empty().append($quantitySelector);
	            });
	            
	            injectUpdateButton();
	        }
	        
	        function getMenuCartQtyInputs() {
	            return $('.elementor-menu-cart__product .quantity-controls .qty-input');
	        }
	
	        function checkQuantitiesAndToggleButton() {
	            var hasChanges = false;
	            
	            getMenuCartQtyInputs().each(function() {
	                var $input = $(this);
	                var currentValue = parseInt($input.val()) || 0;
	                var initialValue = parseInt($input.data('initial-qty')) || 0;
	                
	                if (currentValue !== initialValue) {
	                    hasChanges = true;
	                    return false;
	                }
	            });
	            
	            var $updateBtn = $('.elementor-menu-cart__subtotal .qty-update-all-btn');
	            if ($updateBtn.length) {
	                if (hasChanges) {
	                    $updateBtn.slideDown(200);
	                } else {
	                    $updateBtn.slideUp(200);
	                }
	            }
	        }
	        
	        $(document).on('click', '.elementor-menu-cart__product .quantity-controls .qty-minus', function(e) {
	            e.preventDefault();
	            var $input = $(this).siblings('.qty-input');
	            var currentValue = parseInt($input.val()) || 1;
	            var cartItemKey = $(this).data('cart-item-key');
	            
	            if (currentValue > 0) {
	                $input.val(currentValue - 1);
	                checkQuantitiesAndToggleButton();
	            }
	        });
	        
	        $(document).on('click', '.elementor-menu-cart__product .quantity-controls .qty-plus', function(e) {
	            e.preventDefault();
	            var $input = $(this).siblings('.qty-input');
	            var currentValue = parseInt($input.val()) || 0;
	            var cartItemKey = $(this).data('cart-item-key');
	            
	            $input.val(currentValue + 1);
	            checkQuantitiesAndToggleButton();
	        });
	        
	        $(document).on('change keyup', '.elementor-menu-cart__product .quantity-controls .qty-input', function() {
	            var $input = $(this);
	            var newValue = parseInt($input.val()) || 0;
	            var cartItemKey = $input.data('cart-item-key');
	            
	            if (newValue < 0) newValue = 0;
	            if (newValue > 999) newValue = 999;
	            
	            $input.val(newValue);
	            checkQuantitiesAndToggleButton();
	        });
	        
	        $(document).on('click', '.elementor-menu-cart__subtotal .qty-update-all-btn', function(e) {
	            e.preventDefault();
	            
	            if (isUpdating) return;
	            isUpdating = true;
	            
	            var $btn = $(this);
	            $btn.prop('disabled', true).text('Mise a jour...');
	            
	            var quantitiesToUpdate = {};
	            
	            getMenuCartQtyInputs().each(function() {
	                var $input = $(this);
	                var currentValue = parseInt($input.val()) || 0;
	                var initialValue = parseInt($input.data('initial-qty')) || 0;
	                var cartItemKey = $input.data('cart-item-key');
	                
	                if (currentValue !== initialValue) {
	                    quantitiesToUpdate[cartItemKey] = currentValue;
	                }
	            });
	            
	            if (Object.keys(quantitiesToUpdate).length === 0) {
	                $btn.prop('disabled', false).text('Mettre a jour');
	                isUpdating = false;
	                return;
	            }
	            
	            $.ajax({
	                url: '<?php echo admin_url('admin-ajax.php'); ?>',
	                type: 'POST',
	                data: {
	                    action: 'update_mini_cart_quantities',
	                    quantities: quantitiesToUpdate
	                },
	                success: function(response) {
	                    if (response.success) {
	                        window.location.reload();
	                    } else {
	                        alert(response.data.message || 'Erreur lors de la mise a jour');
	                        $btn.prop('disabled', false).text('Mettre a jour');
	                        isUpdating = false;
	                    }
	                },
	                error: function() {
	                    alert('Erreur de connexion. Veuillez reessayer.');
	                    $btn.prop('disabled', false).text('Mettre a jour');
	                    isUpdating = false;
	                }
	            });
	        });
	        
	        $(document).ready(function() {
	            setTimeout(replacePriceWithQuantitySelector, 500);
	            setTimeout(replacePriceWithQuantitySelector, 1500);
	            setTimeout(replacePriceWithQuantitySelector, 3000);
	        });
	        
	        $(document).on('click', '.elementor-menu-cart__toggle, .elementor-menu-cart__container', function() {
	            setTimeout(replacePriceWithQuantitySelector, 300);
	            setTimeout(replacePriceWithQuantitySelector, 800);
	        });
	        
	        $(document.body).on('added_to_cart wc_fragments_refreshed', function() {
	            setTimeout(replacePriceWithQuantitySelector, 500);
	            setTimeout(replacePriceWithQuantitySelector, 1500);
	        });
	        
	    })(jQuery);
	    </script>
	    <?php
	}
}
