<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Shop {

	public function __construct() {
		add_shortcode( 'custom_shop_products', array( $this, 'render_custom_shop_products' ));
		add_action( 'wp_ajax_load_more_custom_products', array( $this, 'handle_load_more_custom_products' ));
		add_action( 'wp_ajax_nopriv_load_more_custom_products', array( $this, 'handle_load_more_custom_products' ));
	}

	// FILTRE PERSONNALISÉ : TRI PAR PRIX AVEC LEFT JOIN
	// SHORTCODE GRILLE PRODUITS AVEC INFINITE SCROLL ET RECHERCHE
	// GESTIONNAIRE AJAX POUR LE CHARGEMENT INFINI (AVEC SUPPORT RECHERCHE)
	// FONCTION D'AFFICHAGE D'UNE CARTE PRODUIT

	public function custom_price_sort_with_nulls_last($clauses, $query) {
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

	public function render_custom_shop_products() {
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
	
	    add_filter( 'posts_clauses', array( $this, 'custom_price_sort_with_nulls_last' ), 10, 2);
	    $products_query = new WP_Query($args);
	    remove_filter( 'posts_clauses', array( $this, 'custom_price_sort_with_nulls_last' ), 10);
	
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
	                        echo $this->render_single_product_card($product);
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

	public function handle_load_more_custom_products() {
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
	
	    add_filter( 'posts_clauses', array( $this, 'custom_price_sort_with_nulls_last' ), 10, 2);
	    $products_query = new WP_Query($args);
	    remove_filter( 'posts_clauses', array( $this, 'custom_price_sort_with_nulls_last' ), 10);
	
	    if (!$products_query->have_posts()) {
	        wp_send_json_success(array('html' => '', 'end' => true));
	    }
	
	    ob_start();
	    while ($products_query->have_posts()) {
	        $products_query->the_post();
	        $product = wc_get_product(get_the_ID());
	        echo $this->render_single_product_card($product);
	    }
	    wp_reset_postdata();
	    $html = ob_get_clean();
	
	    wp_send_json_success(array('html' => $html, 'end' => false));
	}

	public function render_single_product_card($product) {
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
}
