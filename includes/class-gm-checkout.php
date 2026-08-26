<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Checkout {

	public function __construct() {
		add_action( 'wp_footer', array( $this, 'add_checkout_qty_controls_script' ) );
		add_shortcode( 'custom_checkout_page', array( $this, 'render_custom_checkout_page' ));
		add_filter( 'woocommerce_billing_fields', array( $this, 'custom_reorder_billing_fields' ), 99999 );
		add_filter( 'woocommerce_form_field', array( $this, 'clean_thwcfd_classes' ), 99999, 4 );
		add_filter( 'woocommerce_states', array( $this, 'remove_states_completely' ), 99999 );
		add_action( 'wp_footer', array( $this, 'force_billing_fields_order_js' ) );
		add_filter( 'woocommerce_order_button_text', array( $this, 'order_button_text' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'force_payment_fragment' ), 99999 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_quote_gateway' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'only_quote_gateway' ), 99999 );
		add_filter( 'woocommerce_cart_needs_payment', array( $this, 'cart_always_needs_quote_gateway' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'force_quote_payment_method' ) );
		add_filter( 'woocommerce_order_needs_payment', array( $this, 'quote_order_needs_payment' ), 10, 2 );
		add_action( 'woocommerce_checkout_init', array( $this, 'set_chosen_quote_gateway' ) );
	}

	// SCRIPT JS POUR LES BOUTONS +/- DANS LE CHECKOUT
	// SHORTCODE CHECKOUT EXACT (VERSION SIMPLIFIÉE)
	// COUCHE 1 : FORCER L'ORDRE ET LES PROPRIÉTÉS DES CHAMPS
	// COUCHE 2 : NETTOYER LES CLASSES THWCFD QUI ÉCRASENT NOS MODIFICATIONS
	// COUCHE 3 : FORCER LA SUPPRESSION DU CHAMP STATE
	// COUCHE 4 : JAVASCRIPT DE SECOURS (SI LE PHP NE SUFFIT PAS)

	public function add_checkout_qty_controls_script() {
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
	
	        var isUpdatingCheckoutQty = false;
	
	        function getCheckoutQtyInputs() {
	            return $('.woocommerce-checkout-review-order-table .quantity-controls .qty-input');
	        }
	
	        function injectCheckoutUpdateButton() {
	            var $table = $('.woocommerce-checkout-review-order-table');
	            if (!$table.length) return;
	            if ($('#order_review .qty-update-checkout-btn').length) return;
	            $table.after('<button type="button" class="qty-update-checkout-btn" style="display:none;">Mettre à jour</button>');
	        }
	
	        function checkCheckoutQuantitiesAndToggleButton() {
	            var hasChanges = false;
	
	            getCheckoutQtyInputs().each(function() {
	                var $input = $(this);
	                var currentValue = parseInt($input.val(), 10) || 1;
	                var initialValue = parseInt($input.data('initial-qty'), 10) || 1;
	
	                if (currentValue !== initialValue) {
	                    hasChanges = true;
	                    return false;
	                }
	            });
	
	            var $updateBtn = $('#order_review .qty-update-checkout-btn');
	            if (!$updateBtn.length) return;
	
	            if (hasChanges) {
	                $updateBtn.slideDown(200);
	            } else {
	                $updateBtn.slideUp(200);
	            }
	        }
	
	        injectCheckoutUpdateButton();
	
	        $(document).on('click', '.woocommerce-checkout-review-order-table .qty-minus, .woocommerce-checkout-review-order-table .qty-plus', function(e) {
	            e.preventDefault();
	            e.stopImmediatePropagation();
	
	            var $button = $(this);
	            var $input = $button.siblings('.qty-input');
	            var val = parseInt($input.val(), 10) || 1;
	
	            if ($button.hasClass('qty-minus') && val > 1) {
	                $input.val(val - 1);
	            } else if ($button.hasClass('qty-plus')) {
	                $input.val(val + 1);
	            }
	
	            checkCheckoutQuantitiesAndToggleButton();
	        });
	
	        $(document).on('change keyup', '.woocommerce-checkout-review-order-table .qty-input', function() {
	            var $input = $(this);
	            var newValue = parseInt($input.val(), 10) || 1;
	
	            if (newValue < 1) newValue = 1;
	
	            $input.val(newValue);
	            checkCheckoutQuantitiesAndToggleButton();
	        });
	
	        $(document).on('click', '.qty-update-checkout-btn', function(e) {
	            e.preventDefault();
	
	            if (isUpdatingCheckoutQty) return;
	            isUpdatingCheckoutQty = true;
	
	            var $btn = $(this);
	            $btn.prop('disabled', true).text('Mise à jour...');
	
	            var quantitiesToUpdate = {};
	
	            getCheckoutQtyInputs().each(function() {
	                var $input = $(this);
	                var currentValue = parseInt($input.val(), 10) || 1;
	                var initialValue = parseInt($input.data('initial-qty'), 10) || 1;
	                var cartItemKey = $input.data('cart-item-key') || $input.closest('.quantity-controls').data('cart-key');
	
	                if (currentValue !== initialValue && cartItemKey) {
	                    quantitiesToUpdate[cartItemKey] = currentValue;
	                }
	            });
	
	            if (Object.keys(quantitiesToUpdate).length === 0) {
	                $btn.prop('disabled', false).text('Mettre à jour');
	                isUpdatingCheckoutQty = false;
	                $btn.slideUp(200);
	                return;
	            }
	
	            $.ajax({
	                url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
	                type: 'POST',
	                data: {
	                    action: 'update_mini_cart_quantities',
	                    quantities: quantitiesToUpdate
	                },
	                success: function(response) {
	                    if (response.success) {
	                        window.location.reload();
	                    } else {
	                        alert((response.data && response.data.message) || 'Erreur lors de la mise à jour');
	                        $btn.prop('disabled', false).text('Mettre à jour');
	                        isUpdatingCheckoutQty = false;
	                    }
	                },
	                error: function() {
	                    alert('Erreur de connexion. Veuillez réessayer.');
	                    $btn.prop('disabled', false).text('Mettre à jour');
	                    isUpdatingCheckoutQty = false;
	                }
	            });
	        });
	
	        $(document.body).on('updated_checkout', function() {
	            injectCheckoutUpdateButton();
	            checkCheckoutQuantitiesAndToggleButton();
	        });
	    });
	    </script>
	    <?php
	}

	public function render_custom_checkout_page() {
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
	                        <?php woocommerce_checkout_payment(); ?>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </form>
	    <?php
	    return ob_get_clean();
	}

	public function order_button_text( $text ) {
		return 'Demander Un Devis';
	}

	public function register_quote_gateway( $gateways ) {
		$gateways[] = 'GM_Gateway_Quote';
		return $gateways;
	}

	public function only_quote_gateway( $gateways ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $gateways;
		}

		if ( isset( $gateways[ GM_Gateway_Quote::ID ] ) ) {
			return array( GM_Gateway_Quote::ID => $gateways[ GM_Gateway_Quote::ID ] );
		}

		return $gateways;
	}

	public function cart_always_needs_quote_gateway() {
		return true;
	}

	public function force_quote_payment_method( $data ) {
		$data['payment_method'] = GM_Gateway_Quote::ID;
		return $data;
	}

	public function quote_order_needs_payment( $needs_payment, $order ) {
		if ( $order && GM_Gateway_Quote::ID === $order->get_payment_method() ) {
			return false;
		}
		return $needs_payment;
	}

	public function set_chosen_quote_gateway() {
		if ( WC()->session ) {
			WC()->session->set( 'chosen_payment_method', GM_Gateway_Quote::ID );
		}
	}

	public function force_payment_fragment( $fragments ) {
		if ( ! function_exists( 'woocommerce_checkout_payment' ) ) {
			return $fragments;
		}

		ob_start();
		woocommerce_checkout_payment();
		$fragments['.woocommerce-checkout-payment'] = ob_get_clean();

		return $fragments;
	}

	public function custom_reorder_billing_fields( $fields ) {
	    
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

	public function clean_thwcfd_classes( $field, $key, $args, $value ) {
	    
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

	public function remove_states_completely( $states ) {
	    return array();
	}

	public function force_billing_fields_order_js() {
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
}
