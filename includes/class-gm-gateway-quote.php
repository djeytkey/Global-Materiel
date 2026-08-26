<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Passerelle silencieuse : valide la commande sans encaisser de paiement.
 */
class GM_Gateway_Quote extends WC_Payment_Gateway {

	public const ID = 'gm_quote';

	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'Demande de devis', 'global-materiel' );
		$this->method_description = __( 'Valide la commande sans mode de paiement (devis).', 'global-materiel' );
		$this->title              = __( 'Demande de devis', 'global-materiel' );
		$this->description        = '';
		$this->supports           = array( 'products' );
		$this->enabled            = 'yes';
	}

	public function is_available() {
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$order->update_status(
			'on-hold',
			__( 'Demande de devis reçue. Aucun paiement n\'a été collecté.', 'global-materiel' )
		);

		wc_empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
