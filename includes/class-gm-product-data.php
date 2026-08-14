<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Product_Data {

	public function __construct() {
		add_shortcode( 'custom_product_data', array( $this, 'render_custom_product_data_shortcode' ));
	}

	// SHORTCODE POUR AFFICHER LES CHAMPS PERSONNALISÉS DANS ELEMENTOR

	public function render_custom_product_data_shortcode() {
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
}
