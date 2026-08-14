<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Catalog {

	public function __construct() {
		add_filter('woocommerce_currency_symbol', function($symbol, $currency) {
		    if ($currency === 'MAD') {
		        return 'DHS';
		    }
		    return $symbol;
		}, 10, 2);
		add_filter( 'woocommerce_is_purchasable', array( $this, 'force_purchasable_for_zero_price' ), 10, 2);
		add_filter( 'woocommerce_product_is_in_stock', array( $this, 'force_in_stock_for_zero_price' ), 10, 2);
		add_filter( 'thegem_woocommerce_is_purchasable', array( $this, 'force_purchasable_for_zero_price_thegem' ), 10, 2);
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'force_quantity_input_for_zero_price' ), 10, 2);
		add_filter( 'woocommerce_get_price_html', array( $this, 'show_zero_price' ), 10, 2);
	}

	// MODIFICATION SYMBOLE DEVISE (MAD -> DHS)
	// FORCER L'AFFICHAGE DU BOUTON AJOUTER AU PANIER POUR LES PRODUITS À 0,00 DHS

	public function force_purchasable_for_zero_price($purchasable, $product) {
	    if ($product->get_price() == 0 || $product->get_price() === '') {
	        return true;
	    }
	    return $purchasable;
	}

	public function force_in_stock_for_zero_price($in_stock, $product) {
	    if ($product->get_price() == 0 || $product->get_price() === '') {
	        return true;
	    }
	    return $in_stock;
	}

	public function force_purchasable_for_zero_price_thegem($purchasable, $product) {
	    if ($product->get_price() == 0 || $product->get_price() === '') {
	        return true;
	    }
	    return $purchasable;
	}

	public function force_quantity_input_for_zero_price($args, $product) {
	    if ($product->get_price() == 0 || $product->get_price() === '') {
	        $args['min_qty'] = 1;
	        $args['max_qty'] = '';
	    }
	    return $args;
	}

	public function show_zero_price($price, $product) {
	    if ($product->get_price() == 0) {
	        return '<span class="amount">0,00 DHS</span>';
	    }
	    return $price;
	}
}
