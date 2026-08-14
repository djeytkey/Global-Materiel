<?php
/**
 * Review order table - Personnalisé Global Materiel
 * Remplace le template du thème parent TheGem
 */
defined( 'ABSPATH' ) || exit;
?>
<table class="custom-tarik shop_table woocommerce-checkout-review-order-table">
    <thead>
        <tr>
            <th class="product-name">Nom du produit</th>
            <th class="product-quantity">Quantité</th>
            <th class="product-remove">Supprimer</th>
        </tr>
    </thead>
    <tbody>
        <?php
        do_action( 'woocommerce_review_order_before_cart_contents' );

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                
                $product_id = $_product->get_id();
                $product_name = $_product->get_name();
                $product_sku = $_product->get_sku();
                $thumbnail = $_product->get_image( array( 96, 96 ), array( 'class' => 'attachment-thumbnail size-thumbnail' ) );
                $remove_url = wc_get_cart_remove_url( $cart_item_key );
                ?>
                <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
                    <td class="product-name">
                        <div class="product-info" style="display:flex; gap:10px;">
                            <div class="product-thumb">
                                <?php echo $thumbnail; ?>
                            </div>
                            <div class="product-details">
                                <strong><?php echo esc_html( $product_name ); ?></strong>
                                <?php if ( $product_sku ) : ?>
                                    <div class="product-sku">UGS&nbsp;: <?php echo esc_html( $product_sku ); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="product-quantity">
                        <div class="quantity-controls" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>" style="display:flex; align-items:center; gap:5px;">
                            <button type="button" class="qty-minus">−</button>
                            <input type="number" name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="1" class="qty-input" data-initial-qty="<?php echo esc_attr( $cart_item['quantity'] ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                            <button type="button" class="qty-plus">+</button>
                        </div>
                    </td>
                    <td class="product-remove">
                        <a href="<?php echo esc_url( $remove_url ); ?>" class="remove" aria-label="Remove this item" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-product_sku="<?php echo esc_attr( $product_sku ); ?>">×</a>
                    </td>
                </tr>
                <?php
            }
        }

        do_action( 'woocommerce_review_order_after_cart_contents' );
        ?>
    </tbody>
    <!--<tfoot>-->
    <!--    <tr class="order-total">-->
    <!--        <th>Total</th>-->
    <!--        <td colspan="2"><?php // wc_cart_totals_order_total_html(); ?></td>-->
    <!--    </tr>-->
    <!--</tfoot>-->
</table>