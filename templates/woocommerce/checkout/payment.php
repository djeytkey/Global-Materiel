<?php
/**
 * Checkout Payment Section - Global Matériel
 * Remplace le template TheGem / WooCommerce pour garder le bloc devis actuel.
 *
 * @package GlobalMateriel
 * @version 1.1.0
 */
defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
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

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Commander" data-value="Commander">Demander Un Devis</button>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
		<ul class="wc_payment_methods payment_methods methods" style="display:none;">
			<li class="wc_payment_method payment_method_gm_quote">
				<input id="payment_method_gm_quote" type="radio" class="input-radio" name="payment_method" value="gm_quote" checked="checked">
			</li>
		</ul>
		<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( wc_get_checkout_url() ); ?>">
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
