<?php
// =====================================================
// TEMPLATES WOOCOMMERCE DU PLUGIN GLOBAL MATÉRIEL
// =====================================================
add_filter( 'woocommerce_locate_template', 'gm_locate_woocommerce_template', 9999, 3 );
add_filter( 'wc_get_template', 'gm_force_review_order_template', 9999, 5 );

function gm_locate_woocommerce_template( $template, $template_name, $template_path ) {
	$plugin_template = GM_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;
	if ( file_exists( $plugin_template ) ) {
		return $plugin_template;
	}
	return $template;
}

function gm_force_review_order_template( $located, $template_name, $args, $template_path, $default_path ) {
	$plugin_template = GM_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;
	if ( file_exists( $plugin_template ) ) {
		return $plugin_template;
	}
	return $located;
}

// =====================================================
// SCRIPT JS POUR LES BOUTONS +/- DANS LE CHECKOUT
// =====================================================
add_action( 'wp_footer', 'add_checkout_qty_controls_script' );

function add_checkout_qty_controls_script() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Gestion des toggles Login et Coupon
        $(document).on('click', '.showlogin', function(e) {
            e.preventDefault();
            $('.woocommerce-form-login').slideToggle();
        });
        $(document).on('click', '.showcoupon', function(e) {
            e.preventDefault();
            $('.checkout_coupon').slideToggle();
        });

        // Gestion des boutons + et - avec mise à jour AJAX
        $(document).on('click', '.woocommerce-checkout-review-order-table .qty-minus, .woocommerce-checkout-review-order-table .qty-plus', function(e) {
            e.preventDefault();
            let $button = $(this);
            let $input = $button.siblings('.qty-input');
            let val = parseInt($input.val()) || 1;
            
            if ($button.hasClass('qty-minus') && val > 1) {
                $input.val(val - 1);
            } else if ($button.hasClass('qty-plus')) {
                $input.val(val + 1);
            }
            
            $input.trigger('change');
            $(document.body).trigger('update_checkout');
        });
    });
    </script>
    <?php
}

// =====================================================
// SHORTCODE CHECKOUT EXACT (VERSION SIMPLIFIÉE)
// =====================================================
add_shortcode('custom_checkout_page', 'render_custom_checkout_page');

function render_custom_checkout_page() {
    if ( ! class_exists( 'WooCommerce' ) ) return '';
    
    if ( WC()->cart->is_empty() ) {
        return '<div class="woocommerce cart-is-empty"><p>Votre panier est vide.</p><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button">Retourner à la boutique</a></div>';
    }

    $checkout = WC()->checkout();
    ob_start();
    ?>
    <!-- SECTION LOGIN & COUPON -->
    <div class="form-top-checkout">
        <div class="wrap-content">
            <h2 class="checkout-title" style="margin-bottom: 20px;">Mes coordonnées</h2>
            
            <div class="form-item form-login">
                <div class="woocommerce-form-login-toggle">
                    <div class="woocommerce-info" role="status">
                        Déjà client ? <a href="#" class="showlogin">Cliquez ici pour vous connecter</a>
                    </div>
                </div>
                <form class="woocommerce-form woocommerce-form-login login" method="post" style="display:none;">
                    <p>Si vous avez déjà commandé chez nous, saisissez vos informations ci-dessous. Nouveau client ? Passez à la section Facturation.</p>
                    <p class="form-row form-row-first">
                        <label for="username">Nom d'utilisateur ou e-mail&nbsp;<span class="required" aria-hidden="true">*</span></label>
                        <input type="text" class="input-text" name="username" id="username" autocomplete="username" required>
                    </p>
                    <p class="form-row form-row-last">
                        <label for="password">Mot de passe&nbsp;<span class="required" aria-hidden="true">*</span></label>
                        <input class="input-text woocommerce-Input" type="password" name="password" id="password" autocomplete="current-password" required>
                    </p>
                    <div class="clear"></div>
                    <p class="form-row">
                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
                            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever"> <span>Se souvenir de moi</span>
                        </label>
                        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                        <input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_checkout_url() ); ?>">
                        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Connexion">Connexion</button>
                    </p>
                    <p class="lost_password"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Mot de passe perdu ?</a></p>
                    <div class="clear"></div>
                </form>
            </div>

            <div class="form-item form-coupon">
                <div class="woocommerce-form-coupon-toggle">
                    <div class="woocommerce-info" role="status">
                        Avez-vous un code promo&nbsp;? <a href="#" class="showcoupon">Cliquez ici pour saisir votre code</a>
                    </div>
                </div>
                <form class="checkout_coupon woocommerce-form-coupon" method="post" style="display:none">
                    <p class="form-row form-row-first">
                        <input type="text" name="coupon_code" class="input-text" placeholder="Code promo" id="coupon_code" value="">
                    </p>
                    <p class="form-row form-row-last">
                        <button type="submit" class="button" name="apply_coupon" value="Appliquer le code promo">Appliquer le code promo</button>
                    </p>
                    <div class="clear"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- FORMULAIRE DE COMMANDE -->
    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" novalidate="novalidate">
        <div class="cart-wrapper">
            <div class="col2-set" id="customer_details">
                <div class="col-1">
                    <?php if ( function_exists( 'wc_order_attribution_inputs' ) ) wc_order_attribution_inputs(); ?>
                    <div class="woocommerce-billing-fields">
                        <h3>Détails de facturation</h3>
                        <div class="woocommerce-billing-fields__field-wrapper">
                            <?php
                            foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) {
                                woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                            }
                            ?>
                        </div>
                    </div>
                    <div class="woocommerce-account-fields">
                        <?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
                            <p class="form-row form-row-wide create-account woocommerce-validated">
                                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                    <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" type="checkbox" name="createaccount" value="1"> <span>Créer un compte ?</span>
                                </label>
                            </p>
                            <?php do_action( 'woocommerce_checkout_before_create_account' ); ?>
                            <?php do_action( 'woocommerce_checkout_after_create_account' ); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-2">
                    <div class="woocommerce-shipping-fields"></div>
                    <div class="woocommerce-additional-fields">
                        <h3>Informations complémentaires</h3>
                        <div class="woocommerce-additional-fields__field-wrapper">
                            <?php
                            foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) {
                                woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RÉCAPITULATIF DE COMMANDE -->
            <div class="order-right tarik" id="order-right">
                <div class="wrap-content">	
                    <h4 id="order_review_heading">Votre commande</h4>
                    
                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php wc_get_template( 'checkout/review-order.php' ); ?>

                        <div id="payment" class="woocommerce-checkout-payment">
                            <div class="form-row place-order">
                                <noscript>
                                    Votre navigateur ne supporte pas JavaScript ou bien il est désactivé, assurez vous de cliquer sur le bouton <em>Mise à Jour Totaux</em> avant de passer votre commande. Vous pouvez être facturé plus que le montant indiqué ci-dessus si vous omettez de le faire.<br/>
                                    <button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="Mise à jour des totaux">Mise à jour des totaux</button>
                                </noscript>

                                <div class="woocommerce-terms-and-conditions-wrapper">
                                    <div class="woocommerce-privacy-policy-text">
                                        <p>Vos données personnelles seront utilisées pour le traitement de votre commande, vous accompagner au cours de votre visite du site web, et pour d'autres raisons décrites dans notre <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" class="woocommerce-privacy-policy-link" target="_blank">politique de confidentialité</a>.</p>
                                    </div>
                                </div>
                            
                                <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Commander" data-value="Commander">Demander Un Devis</button>
                                
                                <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
                                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( wc_get_checkout_url() ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// =====================================================
// COUCHE 1 : FORCER L'ORDRE ET LES PROPRIÉTÉS DES CHAMPS
// =====================================================
add_filter( 'woocommerce_billing_fields', 'custom_reorder_billing_fields', 99999 );

function custom_reorder_billing_fields( $fields ) {
    
    // SUPPRIMER les champs indésirables
    unset( $fields['billing_address_2'] );
    unset( $fields['billing_state'] );
    
    // MODIFIER les propriétés
    if ( isset( $fields['billing_postcode'] ) ) {
        $fields['billing_postcode']['required'] = false;
        if ( isset( $fields['billing_postcode']['label'] ) ) {
            $fields['billing_postcode']['label'] = str_replace( '<span class="required"', '<span class="optional"', $fields['billing_postcode']['label'] );
        }
    }
    
    // DÉFINIR l'ordre exact avec priorité
    $priorities = array(
        'billing_company'    => 10,
        'billing_first_name' => 20,
        'billing_last_name'  => 30,
        'billing_address_1'  => 40,
        'billing_city'       => 50,
        'billing_postcode'   => 60,
        'billing_country'    => 70,
        'billing_email'      => 80,
        'billing_phone'      => 90,
    );
    
    // DÉFINIR les classes
    $classes = array(
        'billing_company'    => array( 'form-row-wide' ),
        'billing_first_name' => array( 'form-row-first' ),
        'billing_last_name'  => array( 'form-row-last' ),
        'billing_address_1'  => array( 'form-row-wide' ),
        'billing_city'       => array( 'form-row-first' ),
        'billing_postcode'   => array( 'form-row-last' ),
        'billing_country'    => array( 'form-row-wide' ),
        'billing_email'      => array( 'form-row-wide' ),
        'billing_phone'      => array( 'form-row-wide' ),
    );
    
    // APPLIQUER les modifications
    foreach ( $fields as $key => &$field ) {
        if ( isset( $priorities[ $key ] ) ) {
            $field['priority'] = $priorities[ $key ];
        }
        if ( isset( $classes[ $key ] ) ) {
            // Supprimer toutes les classes existantes et mettre les nouvelles
            $field['class'] = $classes[ $key ];
        }
    }
    
    // TRIER par priorité
    uasort( $fields, function( $a, $b ) {
        return ( isset( $a['priority'] ) ? $a['priority'] : 999 ) <=> ( isset( $b['priority'] ) ? $b['priority'] : 999 );
    } );
    
    return $fields;
}

// =====================================================
// COUCHE 2 : NETTOYER LES CLASSES THWCFD QUI ÉCRASENT NOS MODIFICATIONS
// =====================================================
add_filter( 'woocommerce_form_field', 'clean_thwcfd_classes', 99999, 4 );

function clean_thwcfd_classes( $field, $key, $args, $value ) {
    
    // Supprimer les classes thwcfd qui forcent l'ordre
    $field = preg_replace( '/thwcfd-required/', '', $field );
    $field = preg_replace( '/thwcfd-optional/', '', $field );
    $field = preg_replace( '/thwcfd-field-wrapper/', '', $field );
    $field = preg_replace( '/thwcfd-field-text/', '', $field );
    $field = preg_replace( '/thwcfd-field-email/', '', $field );
    $field = preg_replace( '/thwcfd-field-tel/', '', $field );
    $field = preg_replace( '/thwcfd-field-country/', '', $field );
    $field = preg_replace( '/thwcfd-field-textarea/', '', $field );
    $field = preg_replace( '/validate-postcode/', '', $field );
    
    // Nettoyer les espaces multiples
    $field = preg_replace( '/\s+/', ' ', $field );
    
    return $field;
}

// =====================================================
// COUCHE 3 : FORCER LA SUPPRESSION DU CHAMP STATE
// =====================================================
add_filter( 'woocommerce_states', 'remove_states_completely', 99999 );

function remove_states_completely( $states ) {
    return array();
}

// =====================================================
// COUCHE 4 : JAVASCRIPT DE SECOURS (SI LE PHP NE SUFFIT PAS)
// =====================================================
add_action( 'wp_footer', 'force_billing_fields_order_js' );

function force_billing_fields_order_js() {
    if ( ! is_checkout() ) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        
        // Fonction pour réorganiser les champs
        function reorderBillingFields() {
            var $wrapper = $('.woocommerce-billing-fields__field-wrapper');
            
            if ($wrapper.length === 0) return;
            
            // Ordre souhaité
            var order = [
                'billing_company',
                'billing_first_name',
                'billing_last_name',
                'billing_address_1',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'billing_email',
                'billing_phone'
            ];
            
            // Supprimer les champs indésirables
            $wrapper.find('#billing_address_2_field, #billing_state_field').remove();
            
            // Réorganiser les champs
            order.forEach(function(fieldId) {
                var $field = $wrapper.find('#' + fieldId + '_field');
                if ($field.length) {
                    $wrapper.append($field);
                }
            });
            
            // Appliquer les classes correctes
            $wrapper.find('#billing_company_field').removeClass('form-row-first form-row-last').addClass('form-row-wide');
            $wrapper.find('#billing_first_name_field').removeClass('form-row-wide form-row-last').addClass('form-row-first');
            $wrapper.find('#billing_last_name_field').removeClass('form-row-wide form-row-first').addClass('form-row-last');
            $wrapper.find('#billing_address_1_field').removeClass('form-row-first form-row-last').addClass('form-row-wide');
            $wrapper.find('#billing_city_field').removeClass('form-row-wide form-row-last').addClass('form-row-first');
            $wrapper.find('#billing_postcode_field').removeClass('form-row-wide form-row-first').addClass('form-row-last');
            $wrapper.find('#billing_country_field').removeClass('form-row-first form-row-last').addClass('form-row-wide');
            $wrapper.find('#billing_email_field').removeClass('form-row-first form-row-last').addClass('form-row-wide');
            $wrapper.find('#billing_phone_field').removeClass('form-row-first form-row-last').addClass('form-row-wide');
            
            // Rendre le code postal facultatif (supprimer l'étoile rouge)
            var $postcodeLabel = $wrapper.find('#billing_postcode_field label .required');
            if ($postcodeLabel.length) {
                $postcodeLabel.removeClass('required').addClass('optional').text('(facultatif)');
            }
            
            // Supprimer l'attribut required du champ code postal
            $wrapper.find('#billing_postcode').removeAttr('required').removeAttr('aria-required');
        }
        
        // Exécuter au chargement
        reorderBillingFields();
        
        // Réexécuter après mise à jour du checkout (AJAX)
        $(document.body).on('updated_checkout', function() {
            setTimeout(reorderBillingFields, 100);
        });
        
    });
    </script>
    <?php
}

// ======================================================================================================================== //

// GESTIONNAIRE AJAX POUR MISE À JOUR AUTO DU PANIER
add_action('wp_ajax_update_custom_cart_quantity', 'handle_custom_cart_ajax_update');
add_action('wp_ajax_nopriv_update_custom_cart_quantity', 'handle_custom_cart_ajax_update');

function handle_custom_cart_ajax_update() {
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

// SHORTCODE PANIER PERSONNALISÉ (DESIGN ÉPURÉ + AJAX)
add_shortcode('custom_cart_table', 'render_custom_cart_table');

function render_custom_cart_table() {
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

// ======================================================================================================================== //

// FORCER LE TÉLÉCHARGEMENT DES FICHIERS CSS/JS AVEC TIMESTAMP "Le numéro change à chaque visite"
add_action('init', 'force_asset_version_bust');

function force_asset_version_bust() {
    if (is_admin()) return;
    
    $timestamp = time();
    
    add_filter('style_loader_src', function($src) use ($timestamp) {
        if (strpos($src, home_url()) !== false) {
            $src = remove_query_arg('ver', $src);
            $src = add_query_arg('v', $timestamp, $src);
        }
        return $src;
    }, 9999);
    
    add_filter('script_loader_src', function($src) use ($timestamp) {
        if (strpos($src, home_url()) !== false) {
            $src = remove_query_arg('ver', $src);
            $src = add_query_arg('v', $timestamp, $src);
        }
        return $src;
    }, 9999);
}

// FORCER LE TÉLÉCHARGEMENT DES FICHIERS CSS/JS AVEC TIMESTAMP "Le numéro change toutes les heures"
/*add_action('init', 'force_asset_version_bust');

function force_asset_version_bust() {
    if (is_admin()) return;
    
    $timestamp = time();
    
    add_filter('style_loader_src', function($src) use ($timestamp) {
        if (strpos($src, home_url()) !== false) {
            $src = remove_query_arg('ver', $src);
            $src = add_query_arg('v', $timestamp, $src);
        }
        return $src;
    }, 9999);
    
    add_filter('script_loader_src', function($src) use ($timestamp) {
        if (strpos($src, home_url()) !== false) {
            $src = remove_query_arg('ver', $src);
            $src = add_query_arg('v', $timestamp, $src);
        }
        return $src;
    }, 9999);
}*/

// ======================================================================================================================== //

// 1. INJECTION CIBLÉE DU BADGE SUR LE WIDGET ELEMENTOR
add_action('wp_footer', 'inject_wishlist_badge_to_specific_icon');

function inject_wishlist_badge_to_specific_icon() {
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

// 2. ENDPOINT AJAX POUR RÉCUPÉRER LE COMPTEUR WISHLIST
add_action('wp_ajax_get_wishlist_count', 'handle_get_wishlist_count_ajax');
add_action('wp_ajax_nopriv_get_wishlist_count', 'handle_get_wishlist_count_ajax');

function handle_get_wishlist_count_ajax() {
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

// 3. SHORTCODE TABLEAU PERSONNALISÉ DE LA LISTE DE SOUHAITS
add_shortcode('custom_wishlist_table', 'render_custom_wishlist_table');

function render_custom_wishlist_table() {
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

// ======================================================================================================================== //

// FORCER L'UTILISATION DU TEMPLATE FORM-LOGIN DU PLUGIN
add_action('woocommerce_before_customer_login_form', 'custom_capture_login_form', 0);
add_action('woocommerce_after_customer_login_form', 'custom_replace_login_form', 999);

function custom_capture_login_form() {
    if (current_user_can('manage_options')) return;
    ob_start();
}

function custom_replace_login_form() {
    if (current_user_can('manage_options')) return;
    
    ob_end_clean();
    
    $custom_template = GM_PLUGIN_DIR . 'templates/woocommerce/myaccount/form-login.php';
    
    if (file_exists($custom_template)) {
        include($custom_template);
    }
}

// MODIFICATION DES TEXTES DU FORMULAIRE (CLIENTS SEULEMENT)
add_filter('gettext', 'custom_login_texts_override', 999, 3);
add_filter('ngettext', 'custom_login_texts_override', 999, 3);

function custom_login_texts_override($translated_text, $text, $domain) {
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

// ======================================================================================================================== //

// GESTIONNAIRE AJAX POUR MISE A JOUR DE TOUTES LES QUANTITES
add_action('wp_ajax_update_mini_cart_quantities', 'handle_mini_cart_quantities_update');
add_action('wp_ajax_nopriv_update_mini_cart_quantities', 'handle_mini_cart_quantities_update');

function handle_mini_cart_quantities_update() {
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

// SCRIPT JAVASCRIPT POUR SELECTEUR QUANTITE + BOUTON METTRE A JOUR
add_action('wp_footer', 'add_elementor_menu_cart_quantity_script');

function add_elementor_menu_cart_quantity_script() {
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
        
        function checkQuantitiesAndToggleButton() {
            var hasChanges = false;
            
            $('.quantity-controls .qty-input').each(function() {
                var $input = $(this);
                var currentValue = parseInt($input.val()) || 0;
                var initialValue = parseInt($input.data('initial-qty')) || 0;
                
                if (currentValue !== initialValue) {
                    hasChanges = true;
                    return false;
                }
            });
            
            var $updateBtn = $('.qty-update-all-btn');
            if ($updateBtn.length) {
                if (hasChanges) {
                    $updateBtn.slideDown(200);
                } else {
                    $updateBtn.slideUp(200);
                }
            }
        }
        
        $(document).on('click', '.quantity-controls .qty-minus', function(e) {
            e.preventDefault();
            var $input = $(this).siblings('.qty-input');
            var currentValue = parseInt($input.val()) || 1;
            var cartItemKey = $(this).data('cart-item-key');
            
            if (currentValue > 0) {
                $input.val(currentValue - 1);
                checkQuantitiesAndToggleButton();
            }
        });
        
        $(document).on('click', '.quantity-controls .qty-plus', function(e) {
            e.preventDefault();
            var $input = $(this).siblings('.qty-input');
            var currentValue = parseInt($input.val()) || 0;
            var cartItemKey = $(this).data('cart-item-key');
            
            $input.val(currentValue + 1);
            checkQuantitiesAndToggleButton();
        });
        
        $(document).on('change keyup', '.quantity-controls .qty-input', function() {
            var $input = $(this);
            var newValue = parseInt($input.val()) || 0;
            var cartItemKey = $input.data('cart-item-key');
            
            if (newValue < 0) newValue = 0;
            if (newValue > 999) newValue = 999;
            
            $input.val(newValue);
            checkQuantitiesAndToggleButton();
        });
        
        $(document).on('click', '.qty-update-all-btn', function(e) {
            e.preventDefault();
            
            if (isUpdating) return;
            isUpdating = true;
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('Mise a jour...');
            
            var quantitiesToUpdate = {};
            
            $('.quantity-controls .qty-input').each(function() {
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

// ======================================================================================================================== //

// FILTRE PERSONNALISÉ : TRI PAR PRIX AVEC LEFT JOIN
function custom_price_sort_with_nulls_last($clauses, $query) {
    if (!isset($query->query_vars['custom_shop_price_sort'])) {
        return $clauses;
    }
    
    global $wpdb;
    
    $clauses['join'] = preg_replace('/INNER JOIN ' . $wpdb->postmeta . '.*?ON.*?=/s', '', $clauses['join']);
    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS price_meta ON ({$wpdb->posts}.ID = price_meta.post_id AND price_meta.meta_key = '_price')";
    
    $order = $query->query_vars['custom_shop_price_sort'];
    $clauses['orderby'] = "CASE WHEN price_meta.meta_value IS NULL THEN 1 ELSE 0 END ASC, CAST(price_meta.meta_value AS DECIMAL(10,2)) {$order}";
    
    return $clauses;
}

// SHORTCODE GRILLE PRODUITS AVEC INFINITE SCROLL ET RECHERCHE
add_shortcode('custom_shop_products', 'render_custom_shop_products');

function render_custom_shop_products() {
    // Autoriser l'affichage sur la boutique, les catégories, la recherche, ou si le paramètre 's' est présent
    if (!is_shop() && !is_product_category() && !is_search() && empty($_GET['s'])) {
        return '';
    }

    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    $per_page = isset($_GET['product_count']) ? intval($_GET['product_count']) : 24;
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    
    // ✅ GESTION DE LA RECHERCHE
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $is_search = !empty($search_term) || is_search();

    $is_category = is_product_category() ? 1 : 0;
    $term_id = $is_category ? get_queried_object_id() : 0;
    
    // ✅ TITRE DYNAMIQUE SELON LE CONTEXTE
    if ($is_search) {
        $title = 'Résultats de recherche pour : "' . esc_html($search_term) . '"';
    } elseif ($is_category) {
        $title = single_term_title('', false);
    } else {
        $title = 'Boutique';
    }

    $count_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    );
    
    if ($is_category) {
        $count_args['tax_query'] = array(array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => $term_id,
            'include_children' => true,
        ));
    }
    
    // ✅ AJOUT DU TERME DE RECHERCHE DANS LE COMPTEUR
    if ($is_search) {
        $count_args['s'] = $search_term;
    }
    
    $count_query = new WP_Query($count_args);
    $total_count = $count_query->found_posts;

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'no_found_rows'  => false,
    );

    if ($is_category) {
        $args['tax_query'] = array(array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => $term_id,
            'include_children' => true,
        ));
    }

    // ✅ AJOUT DU TERME DE RECHERCHE DANS LA REQUÊTE PRINCIPALE
    if ($is_search) {
        $args['s'] = $search_term;
    }

    switch ($orderby) {
        case 'popularity':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'price':
            $args['custom_shop_price_sort'] = 'ASC';
            break;
        case 'price-desc':
            $args['custom_shop_price_sort'] = 'DESC';
            break;
        case 'best-sale':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'most-viewed':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case '_discount_amount':
            $args['meta_key'] = '_discount_amount';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    add_filter('posts_clauses', 'custom_price_sort_with_nulls_last', 10, 2);
    $products_query = new WP_Query($args);
    remove_filter('posts_clauses', 'custom_price_sort_with_nulls_last', 10);

    $start = $total_count > 0 ? ($paged - 1) * $per_page + 1 : 0;
    $end = min($paged * $per_page, $total_count);
    $counter_text = sprintf('Affichage de %d–%d sur %d résultats', $start, $end, $total_count);

    ob_start();
    ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <div class="wbs-add-to-cart-notices-ajax"></div>
            
            <h1 class="page-title"><?php echo esc_html($title); ?></h1>
            
            <div class="products-wrapper">
                <div class="product-nav">
                    <div class="products-nav">
                        <p class="woocommerce-result-count" role="status" aria-relevant="all">
                            <?php echo esc_html($counter_text); ?>
                        </p>
                        
                        <div class="catalog-ordering-wrap">
                            <div class="view-mode-wrap">
                                <div class="view-mode">
                                    <a href="#" class="list-view" title="Vue Liste"><i class="fas fa-list"></i></a>
                                    <a href="#" class="grid-view active" title="Vue Grille"><i class="fas fa-th"></i></a>
                                </div>
                            </div>

                            <div class="product-number">
                                <select class="product-count-select">
                                    <option value="24" <?php selected($per_page, 24); ?>>24 Produits</option>
                                    <option value="48" <?php selected($per_page, 48); ?>>48 Produits</option>
                                    <option value="72" <?php selected($per_page, 72); ?>>72 Produits</option>
                                    <option value="96" <?php selected($per_page, 96); ?>>96 Produits</option>
                                </select>
                            </div>
                            
                            <div class="product-sortby">
                                <span class="sort-by">Trier par </span>
                                <select class="orderby custom-orderby-select" aria-label="Commande">
                                    <option value="popularity" <?php selected($orderby, 'popularity'); ?>>Tri par popularité</option>
                                    <option value="date" <?php selected($orderby, 'date'); ?>>Tri du plus récent au plus ancien</option>
                                    <option value="price" <?php selected($orderby, 'price'); ?>>Tri par tarif croissant</option>
                                    <option value="price-desc" <?php selected($orderby, 'price-desc'); ?>>Tri par tarif décroissant</option>
                                    <option value="best-sale" <?php selected($orderby, 'best-sale'); ?>>Meilleur Vente</option>
                                    <option value="most-viewed" <?php selected($orderby, 'most-viewed'); ?>>Les Plus Vues</option>
                                    <option value="_discount_amount" <?php selected($orderby, '_discount_amount'); ?>>Réduction: de faible à élevé</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="products products-loop row grid clearfix" id="product_listing">
                    <?php if ($products_query->have_posts()) : while ($products_query->have_posts()) : $products_query->the_post(); 
                        $product = wc_get_product(get_the_ID());
                        echo render_single_product_card($product);
                    endwhile; wp_reset_postdata(); endif; ?>
                </ul>
                
                <div class="clear"></div>
                
                <div class="infinite-scroll-trigger" data-paged="<?php echo esc_attr($paged); ?>" data-total="<?php echo esc_attr($total_count); ?>" style="text-align:center; padding:30px; display:<?php echo ($start >= $total_count) ? 'none' : 'block'; ?>;">
                    <span class="loading-spinner" style="display:none; border:3px solid #f3f3f3; border-top:3px solid #f07d00; border-radius:50%; width:30px; height:30px; animation:spin 1s linear infinite; margin:0 auto;"></span>
                    <span class="load-more-text">Chargement des produits suivants...</span>
                </div>
            </div>
        </main>
    </div>

    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <script>
    jQuery(document).ready(function($) {
        var viewMode = localStorage.getItem('shop_view_mode') || 'grid';
        
        function applyViewMode(mode) {
            var $wrapper = $('#product_listing');
            $wrapper.removeClass('grid-view list-view').addClass(mode + '-view');
            
            $('.view-mode a').removeClass('active');
            $('.view-mode .' + mode + '-view').addClass('active');
            
            localStorage.setItem('shop_view_mode', mode);
        }
        
        applyViewMode(viewMode);
        
        $('.view-mode .grid-view').on('click', function(e) {
            e.preventDefault();
            applyViewMode('grid');
        });
        
        $('.view-mode .list-view').on('click', function(e) {
            e.preventDefault();
            applyViewMode('list');
        });
        
        function updateShopUrl(param, value) {
            var url = new URL(window.location.href);
            url.searchParams.set(param, value);
            
            var countSelect = document.querySelector('.product-count-select');
            var orderbySelect = document.querySelector('.custom-orderby-select');
            
            if (param === 'product_count' && orderbySelect) {
                url.searchParams.set('orderby', orderbySelect.value);
            } else if (param === 'orderby' && countSelect) {
                url.searchParams.set('product_count', countSelect.value);
            }
            
            url.searchParams.set('paged', 1);
            window.location.href = url.toString();
        }

        var countSelect = document.querySelector('.product-count-select');
        if (countSelect) {
            countSelect.addEventListener('change', function() {
                updateShopUrl('product_count', this.value);
            });
        }

        var orderbySelect = document.querySelector('.custom-orderby-select');
        if (orderbySelect) {
            orderbySelect.addEventListener('change', function() {
                updateShopUrl('orderby', this.value);
            });
        }

        var paged = parseInt($('.infinite-scroll-trigger').data('paged'));
        var total = parseInt($('.infinite-scroll-trigger').data('total'));
        var loading = false;
        var per_page = parseInt($('.product-count-select').val()) || 24;
        var orderby = '<?php echo esc_js($orderby); ?>';
        var is_category = <?php echo $is_category ? 1 : 0; ?>;
        var term_id = <?php echo $term_id; ?>;
        
        // ✅ RÉCUPÉRATION DU TERME DE RECHERCHE POUR L'AJAX
        var search_term = new URLSearchParams(window.location.search).get('s') || '';

        $(window).on('scroll', function() {
            if (loading || paged * per_page >= total) return;
            
            var scrollBottom = $(window).scrollTop() + $(window).height();
            var documentHeight = $(document).height();
            
            if (scrollBottom > documentHeight - 800) {
                loading = true;
                $('.loading-spinner').show();
                $('.load-more-text').hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'load_more_custom_products',
                        paged: paged + 1,
                        product_count: per_page,
                        orderby: orderby,
                        is_category: is_category,
                        term_id: term_id,
                        search_term: search_term // ✅ ENVOI DU TERME DE RECHERCHE
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $('#product_listing').append(response.data.html);
                            paged++;
                            $('.infinite-scroll-trigger').data('paged', paged);
                            $(document.body).trigger('wc_fragment_refresh');
                        } else {
                            $('.infinite-scroll-trigger').hide();
                        }
                        loading = false;
                        $('.loading-spinner').hide();
                        $('.load-more-text').show();
                    },
                    error: function() {
                        loading = false;
                        $('.loading-spinner').hide();
                        $('.load-more-text').text('Erreur de chargement.');
                    }
                });
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// GESTIONNAIRE AJAX POUR LE CHARGEMENT INFINI (AVEC SUPPORT RECHERCHE)
add_action('wp_ajax_load_more_custom_products', 'handle_load_more_custom_products');
add_action('wp_ajax_nopriv_load_more_custom_products', 'handle_load_more_custom_products');

function handle_load_more_custom_products() {
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 2;
    $per_page = isset($_POST['product_count']) ? intval($_POST['product_count']) : 24;
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
    $is_category = isset($_POST['is_category']) ? intval($_POST['is_category']) : 0;
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    
    // ✅ RÉCUPÉRATION DU TERME DE RECHERCHE
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'no_found_rows'  => true,
    );

    if ($is_category && $term_id) {
        $args['tax_query'] = array(array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => $term_id,
            'include_children' => true,
        ));
    }
    
    // ✅ AJOUT DU TERME DE RECHERCHE DANS LA REQUÊTE AJAX
    if (!empty($search_term)) {
        $args['s'] = $search_term;
    }

    switch ($orderby) {
        case 'popularity':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'price':
            $args['custom_shop_price_sort'] = 'ASC';
            break;
        case 'price-desc':
            $args['custom_shop_price_sort'] = 'DESC';
            break;
        case 'best-sale':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'most-viewed':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case '_discount_amount':
            $args['meta_key'] = '_discount_amount';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    add_filter('posts_clauses', 'custom_price_sort_with_nulls_last', 10, 2);
    $products_query = new WP_Query($args);
    remove_filter('posts_clauses', 'custom_price_sort_with_nulls_last', 10);

    if (!$products_query->have_posts()) {
        wp_send_json_success(array('html' => '', 'end' => true));
    }

    ob_start();
    while ($products_query->have_posts()) {
        $products_query->the_post();
        $product = wc_get_product(get_the_ID());
        echo render_single_product_card($product);
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html, 'end' => false));
}

// FONCTION D'AFFICHAGE D'UNE CARTE PRODUIT
function render_single_product_card($product) {
    if (!$product) return '';
    
    $product_id = $product->get_id();
    $product_url = get_permalink($product_id);
    $product_name = $product->get_name();
    $product_sku = $product->get_sku();
    
    $main_image_id = $product->get_image_id();
    $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
    
    $gallery_ids = $product->get_gallery_image_ids();
    $hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : $main_image_url;

    ob_start();
    ?>
    <li class="item col-lg-3 col-md-3 col-sm-6 col-xs-6 mb-1column post-<?php echo esc_attr($product_id); ?> product type-product status-publish">
        <div class="products-entry item-wrap clearfix">
            <div class="item-detail">
                <div class="item-img products-thumb">
                    <a href="<?php echo esc_url($product_url); ?>">
                        <div class="product-thumb-hover">
                            <img src="<?php echo esc_url($main_image_url); ?>" class="wp-post-image main-img" alt="<?php echo esc_attr($product_name); ?>" loading="lazy">
                            <img src="<?php echo esc_url($hover_image_url); ?>" class="hover-image1 back" alt="<?php echo esc_attr($product_name); ?>" loading="lazy">
                        </div>
                    </a>
                    <div class="product-hover-overlay">
                        <a href="javascript:void(0)" data-product_id="<?php echo esc_attr($product_id); ?>" class="overlay-btn sw-quickview group fancybox" data-type="quickview" data-ajax_url="/?wc-ajax=%%endpoint%%" title="Aperçu rapide">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </a>
                        <a href="?add-to-cart=<?php echo esc_attr($product_id); ?>" class="overlay-btn add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr($product_id); ?>" data-product_sku="<?php echo esc_attr($product_sku); ?>" data-quantity="1" rel="nofollow" title="Ajouter au panier">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </a>
                    </div>
                </div>
                <div class="item-content products-content">
                    <?php if ($product_sku) : ?>
                        <p class="sku">Ref: <?php echo esc_html($product_sku); ?></p>
                    <?php endif; ?>
                    <h4><a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>"><?php echo esc_html($product_name); ?></a></h4>
                    <a class="infosplus" href="<?php echo esc_url($product_url); ?>">+ En savoir plus</a>
                </div>
            </div>
        </div>
    </li>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE CARROUSEL DES CATÉGORIES (SLICK)
add_shortcode('custom_category_carousel', 'render_custom_category_carousel');

function render_custom_category_carousel($atts) {
    wp_enqueue_style('slick-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css', array(), '1.8.1');
    wp_enqueue_style('slick-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css', array(), '1.8.1');
    wp_enqueue_script('slick-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), '1.8.1', true);

    if (!(is_shop() || is_product_category())) {
        return '';
    }

    if (is_product_category()) {
        $current = get_queried_object();
        $parent_id = $current->term_id;
    } else {
        $parent_id = 0;
    }

    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => $parent_id,
    ));

    if ((is_wp_error($terms) || empty($terms)) && is_product_category()) {
        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => $current->parent,
        ));
    }

    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    $exclues = array('all', 'uncategorized', 'non-classe');

    ob_start();
    ?>
    <div class="product-categories-carousel-wrapper">
        <div class="product-categories-carousel">
            <?php foreach ($terms as $term) : 
                if (in_array($term->slug, $exclues, true)) {
                    continue;
                }
                
                $link = get_term_link($term);
                $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                $img_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                
                $is_current = (is_product_category() && get_queried_object_id() === $term->term_id) ? ' is-current' : '';
            ?>
                <a href="<?php echo esc_url($link); ?>" class="category-item<?php echo esc_attr($is_current); ?>">
                    <div class="category-item-image">
                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($term->name); ?>" loading="lazy">
                    </div>
                    <span><?php echo esc_html($term->name); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function ($) {
        var $carousel = $('.product-categories-carousel');
        
        if ($carousel.length && !$carousel.hasClass('slick-initialized')) {
            $carousel.slick({
                slidesToShow: 5,
                slidesToScroll: 1,
                rows: 1,
                slidesPerRow: 1,
                arrows: true,
                dots: false,
                infinite: $carousel.children().length > 5,
                responsive: [
                    { breakpoint: 1200, settings: { slidesToShow: 4 } },
                    { breakpoint: 992,  settings: { slidesToShow: 3 } },
                    { breakpoint: 600,  settings: { slidesToShow: 2 } },
                    { breakpoint: 480,  settings: { slidesToShow: 1 } }
                ]
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE SIDEBAR CATÉGORIES
add_shortcode('category_sidebar', 'render_category_sidebar_shortcode');

function render_category_sidebar_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'CATÉGORIES DE PRODUITS',
    ), $atts);
    
    $parent_categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'menu_order',
        'order'      => 'ASC'
    ));
    
    if (is_wp_error($parent_categories) || empty($parent_categories)) {
        return '';
    }
    
    $active_term_id = 0;
    $active_ancestors = array();
    
    if (is_product_category()) {
        $current_term = get_queried_object();
        if ($current_term && is_a($current_term, 'WP_Term')) {
            $active_term_id = $current_term->term_id;
            $active_ancestors = get_ancestors($current_term->term_id, 'product_cat', 'taxonomy');
        }
    }
    
    $unique_id = 'sidebar-' . uniqid();
    
    ob_start();
    ?>
    <div class="widget_text widget-inner">
        <div class="block-title-widget">
            <h2><span><?php echo esc_html($atts['title']); ?></span></h2>
        </div>
        <div class="textwidget custom-html-widget">
            <div class="wpb_category_n_menu_accordion wpb_wmca_accordion_wrapper_theme_dark" id="<?php echo esc_attr($unique_id); ?>" data-accordion="true">
                <ul class="wpb_category_n_menu_accordion_list">
                    <?php render_category_tree_thegem_style($parent_categories, $active_term_id, $active_ancestors, 0); ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var accordions = document.querySelectorAll('.wpb_category_n_menu_accordion');
        
        accordions.forEach(function(accordion) {
            var indicators = accordion.querySelectorAll('.wpb-submenu-indicator');
            
            indicators.forEach(function(indicator) {
                indicator.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var parentLi = this.closest('li.cat-item');
                    var childUl = parentLi.querySelector(':scope > ul.children');
                    
                    if (!childUl) return;
                    
                    var isOpen = childUl.style.display === 'block';
                    
                    if (isOpen) {
                        childUl.style.display = 'none';
                        parentLi.classList.remove('active');
                    } else {
                        childUl.style.display = 'block';
                        parentLi.classList.add('active');
                    }
                });
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function render_category_tree_thegem_style($categories, $active_term_id, $active_ancestors, $depth) {
    if (empty($categories)) {
        return;
    }
    
    foreach ($categories as $cat) {
        $children = get_terms(array(
            'taxonomy'   => 'product_cat',
            'parent'     => $cat->term_id,
            'hide_empty' => false,
            'orderby'    => 'menu_order',
            'order'      => 'ASC'
        ));
        
        $has_children = !is_wp_error($children) && !empty($children);
        $is_current = ($cat->term_id == $active_term_id);
        $is_in_active_path = in_array($cat->term_id, $active_ancestors);
        
        $li_classes = array('cat-item', 'cat-item-' . $cat->term_id);
        if ($has_children) $li_classes[] = 'cat-item-have-child';
        if ($is_current) $li_classes[] = 'current-cat';
        if ($is_in_active_path) $li_classes[] = 'current-cat-parent';
        
        $is_active = ($is_current || $is_in_active_path);
        if ($is_active) $li_classes[] = 'active';
        
        $li_classes_str = implode(' ', $li_classes);
        $term_link = get_term_link($cat);
        $display_style = $is_active ? 'block' : 'none';
        ?>
        <li class="<?php echo esc_attr($li_classes_str); ?>">
            <a href="<?php echo esc_url($term_link); ?>">
                <span class="cat-name"><?php echo esc_html($cat->name); ?></span>
                
                <?php if ($has_children) : ?>
                    <span class="wpb-submenu-indicator">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                <?php endif; ?>
            </a>
            
            <?php if ($has_children) : ?>
                <ul class="children" data-index="<?php echo esc_attr($depth); ?>" style="display: <?php echo esc_attr($display_style); ?>;">
                    <?php render_category_tree_thegem_style($children, $active_term_id, $active_ancestors, $depth + 1); ?>
                </ul>
            <?php endif; ?>
        </li>
        <?php
    }
}

// ======================================================================================================================== //

// SHORTCODE CARROUSEL PRODUITS ASSOCIÉS
add_action('wp_enqueue_scripts', 'enqueue_swiper_for_related_products');

function enqueue_swiper_for_related_products() {
    global $post;
    $has_shortcode = is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'related_products_carousel');
    
    if ($has_shortcode || is_product()) {
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    }
}

add_shortcode('related_products_carousel', 'render_related_products_carousel_shortcode');

function render_related_products_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 12,
        'title' => 'Produits associés',
    ), $atts);
    
    global $product;
    if (!$product || !is_a($product, 'WC_Product')) {
        return '';
    }
    
    $product_id = $product->get_id();
    $related_ids = wc_get_related_products($product_id, intval($atts['limit']));
    
    if (empty($related_ids)) {
        return '';
    }
    
    $products = wc_get_products(array(
        'status'  => 'publish',
        'include' => $related_ids,
        'limit'   => intval($atts['limit']),
        'orderby' => 'rand',
        'return'  => 'objects',
    ));
    
    if (empty($products)) {
        return '';
    }
    
    $unique_id = 'related-products-' . uniqid();
    
    ob_start();
    ?>
    <div class="resp-slider-container related-products-carousel" id="<?php echo esc_attr($unique_id); ?>">
        <?php if (!empty($atts['title'])) : ?>
            <h2 class="carousel-section-title" style="text-align:center; margin-bottom:30px; font-size:1.5rem; font-weight:700; color:#2c3e50; text-transform:uppercase;">
                <?php echo esc_html($atts['title']); ?>
            </h2>
        <?php endif; ?>
        
        <div class="swiper-overflow-wrapper">
            <div class="slider responsive swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($products as $rel_product) : 
                        $rel_product_id = $rel_product->get_id();
                        $rel_product_url = get_permalink($rel_product_id);
                        $rel_product_name = $rel_product->get_name();
                        $rel_product_sku = $rel_product->get_sku();
                        
                        $main_image_id = $rel_product->get_image_id();
                        $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                        $main_image_alt = get_post_meta($main_image_id, '_wp_attachment_image_alt', true) ?: $rel_product_name;
                        
                        $gallery_ids = $rel_product->get_gallery_image_ids();
                        $hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : $main_image_url;
                    ?>
                        <div class="item item-nonprice product clearfix swiper-slide">
                            <div class="item-wrap">
                                <div class="item-detail">                                        
                                    <div class="item-img products-thumb">		
                                        <a href="<?php echo esc_url($rel_product_url); ?>" title="<?php echo esc_attr($rel_product_name); ?>">
                                            <div class="product-thumb-hover">
                                                <img src="<?php echo esc_url($main_image_url); ?>" loading="lazy" decoding="async" class="wp-post-image main-img" alt="<?php echo esc_attr($main_image_alt); ?>">
                                                <img src="<?php echo esc_url($hover_image_url); ?>" loading="lazy" decoding="async" class="hover-image1 back" alt="<?php echo esc_attr($rel_product_name); ?>">
                                            </div>
                                        </a>																			
                                    </div>										
                                    
                                    <div class="item-content">
                                        <div class="content-inner">
                                            <?php if ($rel_product_sku) : ?>
                                                <p class="sku">
                                                    <a href="<?php echo esc_url($rel_product_url); ?>" title="<?php echo esc_attr($rel_product_name); ?>">
                                                        Ref: <?php echo esc_html($rel_product_sku); ?>
                                                    </a>
                                                </p>  
                                            <?php endif; ?>
                                            
                                            <h4>
                                                <a href="<?php echo esc_url($rel_product_url); ?>" title="<?php echo esc_attr($rel_product_name); ?>">
                                                    <?php echo esc_html($rel_product_name); ?>
                                                </a>
                                            </h4>
                                        </div>
                                        <a class="infosplus" href="<?php echo esc_url($rel_product_url); ?>">+ En savoir plus</a> 
                                    </div>								
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <button class="rel-prod-prev" aria-label="Précédent">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="rel-prod-next" aria-label="Suivant">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($unique_id); ?> .slider', {
                slidesPerView: 1,
                spaceBetween: 15,
                loop: true,
                grabCursor: true,
                autoplay: { delay: 3000, disableOnInteraction: false, pauseOnMouseEnter: true },
                speed: 600,
                navigation: { nextEl: '.rel-prod-next', prevEl: '.rel-prod-prev' },
                breakpoints: {
                    768: { slidesPerView: 2, spaceBetween: 20 },
                    992: { slidesPerView: 5, spaceBetween: 25 }
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE CARROUSEL PRODUITS PAR CATÉGORIE
add_action('wp_enqueue_scripts', 'enqueue_swiper_for_category_products');

function enqueue_swiper_for_category_products() {
    global $post;
    $has_shortcode = is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'category_products_carousel');
    
    if ($has_shortcode) {
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    }
}

add_shortcode('category_products_carousel', 'render_category_products_carousel_shortcode');

function render_category_products_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category_id' => '',
        'limit'       => 12,
        'title'       => '',
    ), $atts);
    
    if (empty($atts['category_id'])) {
        return '<p style="color:red; text-align:center; padding:20px;">Veuillez spécifier un ID de catégorie. Ex: [category_products_carousel category_id="358"]</p>';
    }
    
    $category_id = intval($atts['category_id']);
    
    $args = array(
        'status'   => 'publish',
        'limit'    => intval($atts['limit']),
        'orderby'  => 'date',
        'order'    => 'DESC',
        'return'   => 'objects',
        'tax_query' => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $category_id,
                'include_children' => true,
            ),
        ),
    );
    
    $products = wc_get_products($args);
    
    if (empty($products)) {
        return '<p style="text-align:center; color:#666; padding: 20px; background:#f9f9f9; border:1px dashed #ccc; border-radius:4px;">Aucun produit publié trouvé pour la catégorie ID: <strong>' . $category_id . '</strong>.</p>';
    }
    
    $unique_id = 'cat-products-' . uniqid();
    
    ob_start();
    ?>
    <div class="resp-slider-container category-specific-carousel" id="<?php echo esc_attr($unique_id); ?>">
        <?php if (!empty($atts['title'])) : ?>
            <h2 class="carousel-section-title" style="text-align:center; margin-bottom:30px; font-size:1.5rem; font-weight:700; color:#2c3e50; text-transform:uppercase;">
                <?php echo esc_html($atts['title']); ?>
            </h2>
        <?php endif; ?>
        
        <div class="swiper-overflow-wrapper">
            <div class="slider responsive swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($products as $product) : 
                        $product_id = $product->get_id();
                        $product_url = get_permalink($product_id);
                        $product_name = $product->get_name();
                        $product_sku = $product->get_sku();
                        
                        $main_image_id = $product->get_image_id();
                        $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                        $main_image_alt = get_post_meta($main_image_id, '_wp_attachment_image_alt', true) ?: $product_name;
                        
                        $gallery_ids = $product->get_gallery_image_ids();
                        $hover_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : $main_image_url;
                    ?>
                        <div class="item item-nonprice product clearfix swiper-slide">
                            <div class="item-wrap">
                                <div class="item-detail">                                        
                                    <div class="item-img products-thumb">		
                                        <a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>">
                                            <div class="product-thumb-hover">
                                                <img src="<?php echo esc_url($main_image_url); ?>" loading="lazy" decoding="async" class="wp-post-image main-img" alt="<?php echo esc_attr($main_image_alt); ?>">
                                                <img src="<?php echo esc_url($hover_image_url); ?>" loading="lazy" decoding="async" class="hover-image1 back" alt="<?php echo esc_attr($product_name); ?>">
                                            </div>
                                        </a>																			
                                    </div>										
                                    
                                    <div class="item-content">
                                        <div class="content-inner">
                                            <h4>
                                                <a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>">
                                                    <?php echo esc_html($product_name); ?>
                                                </a>
                                            </h4>
                                            <?php if ($product_sku) : ?>
                                                <p class="sku">
                                                    <a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>">
                                                        Ref: <?php echo esc_html($product_sku); ?>
                                                    </a>
                                                </p>  
                                            <?php endif; ?>
                                        </div>
                                        <a class="infosplus" href="<?php echo esc_url($product_url); ?>">+ En savoir plus</a> 
                                    </div>								
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <button class="cat-prod-prev" aria-label="Précédent">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="cat-prod-next" aria-label="Suivant">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($unique_id); ?> .slider', {
                slidesPerView: 1,
                spaceBetween: 15,
                loop: true,
                grabCursor: true,
                autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true },
                speed: 600,
                navigation: { nextEl: '.cat-prod-next', prevEl: '.cat-prod-prev' },
                breakpoints: {
                    768: { slidesPerView: 2, spaceBetween: 20 },
                    992: { slidesPerView: 5, spaceBetween: 25 }
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE CARROUSEL PRODUITS EN PROMOTION (AVEC AJAX ADD TO CART)
add_action('wp_enqueue_scripts', 'enqueue_swiper_for_promo_products');

function enqueue_swiper_for_promo_products() {
    global $post;
    $has_shortcode = is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'promo_products_carousel');
    
    if ($has_shortcode || is_front_page()) {
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
        
        if (class_exists('WooCommerce')) {
            wp_enqueue_script('wc-add-to-cart');
        }
    }
}

add_shortcode('promo_products_carousel', 'render_promo_products_carousel_shortcode');

function render_promo_products_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'title' => 'PROMOTIONS',
    ), $atts);
    
    $on_sale_ids = wc_get_product_ids_on_sale();
    if (empty($on_sale_ids)) return '';
    
    $args = array(
        'status'   => 'publish',
        'limit'    => intval($atts['limit']),
        'orderby'  => 'date',
        'order'    => 'DESC',
        'return'   => 'objects',
        'include'  => $on_sale_ids,
    );
    
    $products = wc_get_products($args);
    if (empty($products)) return '';
    
    $unique_id = 'promo-products-' . uniqid();
    
    ob_start();
    ?>
    <div class="promo-products-section">
        <div class="promo-products-container">
            <?php if (!empty($atts['title'])) : ?>
                <div class="promo-products-header">
                    <h2 class="promo-products-title"><?php echo esc_html($atts['title']); ?></h2>
                </div>
            <?php endif; ?>
            
            <div class="promo-products-swiper-overflow">
                <div class="swiper promo-products-swiper" id="<?php echo esc_attr($unique_id); ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($products as $product) : 
                            $product_id = $product->get_id();
                            $product_url = get_permalink($product_id);
                            $product_name = $product->get_name();
                            $product_sku = $product->get_sku();
                            $product_price_html = $product->get_price_html();
                            $product_type = $product->get_type();
                            
                            $categories = wp_get_post_terms($product_id, 'product_cat', array('number' => 1));
                            $category_name = !empty($categories) ? $categories[0]->name : '';
                            $category_link = !empty($categories) ? get_term_link($categories[0]) : '#';
                            
                            $main_image_id = $product->get_image_id();
                            $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                            $main_image_alt = get_post_meta($main_image_id, '_wp_attachment_image_alt', true) ?: $product_name;
                            
                            $gallery_ids = $product->get_gallery_image_ids();
                            $secondary_image_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail') : $main_image_url;

                            $is_simple_or_external = $product->is_type('simple') || $product->is_type('external');
                            $add_to_cart_class = 'button product_type_' . esc_attr($product_type) . ' add_to_cart_button';
                            
                            if ($is_simple_or_external) {
                                $add_to_cart_class .= ' ajax_add_to_cart';
                                $button_url = esc_url($product->add_to_cart_url());
                                $button_attrs = 'data-quantity="1" data-product_id="' . esc_attr($product_id) . '" data-product_sku="' . esc_attr($product_sku) . '" rel="nofollow"';
                            } else {
                                $button_url = esc_url($product_url);
                                $button_attrs = '';
                            }
                        ?>
                            <div class="swiper-slide">
                                <div class="item product clearfix promo-product-card">
                                    <div class="item-wrap23">
                                        <div class="item-detail">
                                            <div class="item-img products-thumb">
                                                <a href="<?php echo esc_url($product_url); ?>" class="product-image-link" title="<?php echo esc_attr($product_name); ?>">
                                                    <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr($main_image_alt); ?>" class="main-image wp-post-image" loading="lazy">
                                                    <img src="<?php echo esc_url($secondary_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" class="secondary-image wp-post-image" loading="lazy">
                                                </a>
                                                
                                                <div class="hover-add-to-cart">
                                                    <a href="<?php echo $button_url; ?>" 
                                                       <?php echo $button_attrs; ?>
                                                       class="<?php echo $add_to_cart_class; ?>"
                                                       aria-label="<?php echo esc_attr(sprintf('Ajouter "%s" au panier', $product_name)); ?>">
                                                        Ajouter au panier
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <div class="item-content">
                                                <?php if ($category_name) : ?>
                                                    <div class="item-categories">
                                                        <a href="<?php echo esc_url($category_link); ?>"><?php echo esc_html($category_name); ?></a>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <h4>
                                                    <a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>">
                                                        <?php echo esc_html($product_name); ?>
                                                    </a>
                                                </h4>
                                                
                                                <?php if ($product_sku) : ?>
                                                    <div class="item-ref">Ref: <?php echo esc_html($product_sku); ?></div>
                                                <?php endif; ?>
                                                
                                                <div class="item-price">
                                                    <?php echo $product_price_html; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <button class="promo-products-prev" aria-label="Précédent">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="promo-products-next" aria-label="Suivant">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($unique_id); ?>', {
                slidesPerView: 1,
                spaceBetween: 15,
                loop: true,
                grabCursor: true,
                autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true },
                speed: 600,
                navigation: { nextEl: '.promo-products-next', prevEl: '.promo-products-prev' },
                breakpoints: {
                    768: { slidesPerView: 2, spaceBetween: 20 },
                    992: { slidesPerView: 5, spaceBetween: 25 }
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE SLIDER PAGE D'ACCUEIL
add_action('wp_enqueue_scripts', 'enqueue_swiper_for_homepage_slider');

function enqueue_swiper_for_homepage_slider() {
    global $post;
    $has_shortcode = false;
    
    if (is_singular() && isset($post->post_content)) {
        $has_shortcode = has_shortcode($post->post_content, 'homepage_slider');
    }
    
    if (is_front_page()) {
        $has_shortcode = true;
    }
    
    if ($has_shortcode) {
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    }
}

add_shortcode('homepage_slider', 'render_homepage_slider_shortcode');

function render_homepage_slider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'images' => '',
    ), $atts);
    
    if (empty($atts['images'])) {
        return '<p style="text-align:center; color:#999;">Veuillez spécifier les IDs des images. Exemple : [homepage_slider images="123,456,789"]</p>';
    }
    
    $image_ids = array_map('intval', explode(',', $atts['images']));
    $image_ids = array_filter($image_ids);
    
    if (empty($image_ids)) {
        return '';
    }
    
    $unique_id = 'home-slider-' . uniqid();
    
    ob_start();
    ?>
    <div class="homepage-slider-section">
        <div class="homepage-slider-container">
            <div class="swiper homepage-swiper" id="<?php echo esc_attr($unique_id); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($image_ids as $image_id) : 
                        $image_url = wp_get_attachment_image_url($image_id, 'full');
                        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                        
                        if (!$image_url) continue;
                    ?>
                        <div class="swiper-slide">
                            <div class="homepage-slider-image-wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="lazy">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($unique_id); ?>', {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: true,
                grabCursor: true,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                speed: 600,
                pagination: {
                    el: '#<?php echo esc_js($unique_id); ?> .swiper-pagination',
                    clickable: true,
                },
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE CARROUSEL DES CATÉGORIES ASSOCIÉES
add_action('wp_enqueue_scripts', 'enqueue_swiper_for_category_carousel');

function enqueue_swiper_for_category_carousel() {
    global $post;
    $has_shortcode = false;
    
    if (is_singular() && isset($post->post_content)) {
        $has_shortcode = has_shortcode($post->post_content, 'category_carousel');
    }
    
    if (is_product_category() || is_shop()) {
        $has_shortcode = true;
    }
    
    if ($has_shortcode) {
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    }
}

add_shortcode('category_carousel', 'render_category_carousel_shortcode');

function render_category_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => '',
    ), $atts);
    
    $categories_to_show = array();
    $current_term_id = 0;
    
    if (is_shop()) {
        $parent_categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'menu_order',
            'order'      => 'ASC'
        ));
        
        if (!is_wp_error($parent_categories) && !empty($parent_categories)) {
            $categories_to_show = $parent_categories;
        }
    } elseif (is_product_category()) {
        $current_term = get_queried_object();
        if ($current_term && is_a($current_term, 'WP_Term')) {
            $current_term_id = $current_term->term_id;
            
            $children = get_terms(array(
                'taxonomy'   => 'product_cat',
                'parent'     => $current_term->term_id,
                'hide_empty' => false,
                'number'     => 1
            ));
            
            $has_children = !is_wp_error($children) && !empty($children);
            
            if ($has_children) {
                $children = get_terms(array(
                    'taxonomy'   => 'product_cat',
                    'parent'     => $current_term->term_id,
                    'hide_empty' => false,
                    'orderby'    => 'menu_order',
                    'order'      => 'ASC'
                ));
                
                if (!is_wp_error($children) && !empty($children)) {
                    $categories_to_show = $children;
                }
            } else {
                if ($current_term->parent > 0) {
                    $siblings = get_terms(array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => $current_term->parent,
                        'hide_empty' => false,
                        'orderby'    => 'menu_order',
                        'order'      => 'ASC'
                    ));
                    
                    if (!is_wp_error($siblings) && !empty($siblings)) {
                        $categories_to_show = $siblings;
                    }
                }
                
                if (empty($categories_to_show)) {
                    $parent_categories = get_terms(array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => 0,
                        'hide_empty' => false,
                        'orderby'    => 'menu_order',
                        'order'      => 'ASC'
                    ));
                    
                    if (!is_wp_error($parent_categories) && !empty($parent_categories)) {
                        $categories_to_show = $parent_categories;
                    }
                }
            }
        }
    } else {
        return '';
    }
    
    $categories_count = 0;
    foreach ($categories_to_show as $cat) {
        if ($cat->term_id != $current_term_id) {
            $categories_count++;
        }
    }
    
    if ($categories_count < 2) {
        return '';
    }
    
    $unique_id = 'swiper-' . uniqid();
    
    ob_start();
    ?>
    <div class="category-carousel-section">
        <div class="category-carousel-container">
            <div class="category-swiper-overflow">
                <div class="swiper category-swiper" id="<?php echo esc_attr($unique_id); ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($categories_to_show as $cat) : 
                            if ($cat->term_id == $current_term_id) continue;
                            
                            $thumbnail_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
                            $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src('woocommerce_thumbnail');
                        ?>
                            <div class="swiper-slide">
                                <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="category-carousel-item">
                                    <div class="category-carousel-image">
                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($cat->name); ?>" loading="lazy">
                                    </div>
                                    <div class="category-carousel-info">
                                        <h4 class="category-carousel-name"><?php echo esc_html($cat->name); ?></h4>
                                        <span class="category-carousel-count">
                                            <?php echo esc_html($cat->count); ?> produit<?php echo $cat->count > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <button class="custom-swiper-prev" aria-label="Précédent">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="custom-swiper-next" aria-label="Suivant">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swiper !== 'undefined') {
            new Swiper('#<?php echo esc_js($unique_id); ?>', {
                slidesPerView: 2,
                spaceBetween: 15,
                loop: true,
                grabCursor: true,
                autoplay: {
                    delay: 2000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                    reverseDirection: false
                },
                speed: 600,
                navigation: {
                    nextEl: '.custom-swiper-next',
                    prevEl: '.custom-swiper-prev',
                },
                breakpoints: {
                    768: { slidesPerView: 3, spaceBetween: 20 },
                    992: { slidesPerView: 5, spaceBetween: 25 }
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// SHORTCODE POUR AFFICHER LES CHAMPS PERSONNALISÉS DANS ELEMENTOR
add_shortcode('custom_product_data', 'render_custom_product_data_shortcode');

function render_custom_product_data_shortcode() {
    global $product;
    if (!$product) {
        return '';
    }

    $product_id = $product->get_id();

    $table_html = get_post_meta($product_id, 'referencesbeta', true);
    $pdf_1      = get_post_meta($product_id, 'doc1', true);
    $pdf_2      = get_post_meta($product_id, 'doc2', true);
    $pdf_3      = get_post_meta($product_id, 'doc3', true);

    if (empty($table_html) && empty($pdf_1) && empty($pdf_2) && empty($pdf_3)) {
        return '';
    }

    ob_start();
    ?>
    <div class="custom-product-addons-wrapper">
        <?php if (!empty($table_html)) : ?>
            <div class="custom-references-section">
                <h3 class="custom-section-title">Références et Dimensions</h3>
                <div class="custom-ref-table-wrapper">
                    <?php echo wp_kses_post($table_html); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php 
        $pdfs = array_filter([$pdf_1, $pdf_2, $pdf_3]);
        if (!empty($pdfs)) : 
        ?>
            <div class="custom-pdfs-section">
                <h3 class="custom-section-title">Documentation Technique</h3>
                <div class="custom-pdf-links-container">
                    <?php foreach ($pdfs as $pdf_url) : 
                        $pdf_url = esc_url(trim($pdf_url));
                        $file_name = basename(parse_url($pdf_url, PHP_URL_PATH));
                        $display_name = str_replace(['+', '%20', '_', '-'], ' ', $file_name);
                        $display_name = str_replace('.pdf', '', $display_name);
                        $display_name = ucwords(trim($display_name));
                    ?>
                        <a href="<?php echo $pdf_url; ?>" class="custom-pdf-link" target="_blank" rel="noopener noreferrer">
                            <svg class="custom-pdf-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2ZM16 18H8V16H16V18ZM16 14H8V12H16V14ZM13 9V3.5L18.5 9H13Z" fill="#dc3545"/>
                                <text x="12" y="16" font-family="Arial" font-size="4" font-weight="bold" fill="white" text-anchor="middle">PDF</text>
                            </svg>
                            <span class="custom-pdf-text"><?php echo esc_html($display_name); ?></span>
                            <span class="custom-pdf-arrow">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ======================================================================================================================== //

// PURGE AUTOMATIQUE LITESPEED CACHE
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

// ======================================================================================================================== //

// MODIFICATION SYMBOLE DEVISE (MAD -> DHS)
add_filter('woocommerce_currency_symbol', function($symbol, $currency) {
    if ($currency === 'MAD') {
        return 'DHS';
    }
    return $symbol;
}, 10, 2);

// ======================================================================================================================== //

// FORCER L'AFFICHAGE DU BOUTON AJOUTER AU PANIER POUR LES PRODUITS À 0,00 DHS
add_filter('woocommerce_is_purchasable', 'force_purchasable_for_zero_price', 10, 2);
function force_purchasable_for_zero_price($purchasable, $product) {
    if ($product->get_price() == 0 || $product->get_price() === '') {
        return true;
    }
    return $purchasable;
}

add_filter('woocommerce_product_is_in_stock', 'force_in_stock_for_zero_price', 10, 2);
function force_in_stock_for_zero_price($in_stock, $product) {
    if ($product->get_price() == 0 || $product->get_price() === '') {
        return true;
    }
    return $in_stock;
}

add_filter('thegem_woocommerce_is_purchasable', 'force_purchasable_for_zero_price_thegem', 10, 2);
function force_purchasable_for_zero_price_thegem($purchasable, $product) {
    if ($product->get_price() == 0 || $product->get_price() === '') {
        return true;
    }
    return $purchasable;
}

add_filter('woocommerce_quantity_input_args', 'force_quantity_input_for_zero_price', 10, 2);
function force_quantity_input_for_zero_price($args, $product) {
    if ($product->get_price() == 0 || $product->get_price() === '') {
        $args['min_qty'] = 1;
        $args['max_qty'] = '';
    }
    return $args;
}

add_filter('woocommerce_get_price_html', 'show_zero_price', 10, 2);
function show_zero_price($price, $product) {
    if ($product->get_price() == 0) {
        return '<span class="amount">0,00 DHS</span>';
    }
    return $price;
}

// ======================================================================================================================== //

// SÉCURITÉ : FORCER HTTPS ET CROSSORIGIN SUR SCRIPTS EXTERNES
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

// SÉCURITÉ - PROTECTION API REST
add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) {
        return $result;
    }

    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'Vous devez être connecté pour accéder à l\'API REST.', array('status' => 401));
    }
    return $result;
});

// SÉCURITÉ - DÉSACTIVER ÉNUMÉRATION UTILISATEURS
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

// SÉCURITÉ - MASQUER VERSION WORDPRESS
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

function remove_version_scripts_styles($src) {
    if (strpos($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'remove_version_scripts_styles', 15, 1);
add_filter('style_loader_src', 'remove_version_scripts_styles', 15, 1);

// SÉCURITÉ - DÉSACTIVER XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// SÉCURITÉ - LIMITER TENTATIVES CONNEXION
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

// ======================================================================================================================== //

// CHARGEMENT DES ASSETS DU PLUGIN GLOBAL MATÉRIEL
function gm_enqueue_custom_scripts() {
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
add_action( 'wp_enqueue_scripts', 'gm_enqueue_custom_scripts' );

function gm_enqueue_custom_styles() {
	$main_css = GM_PLUGIN_DIR . 'assets/css/global-materiel.css';
	if ( file_exists( $main_css ) ) {
		wp_enqueue_style(
			'gm-main-css',
			GM_PLUGIN_URL . 'assets/css/global-materiel.css',
			array(),
			GM_VERSION
		);
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
add_action( 'wp_enqueue_scripts', 'gm_enqueue_custom_styles' );