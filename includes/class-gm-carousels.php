<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GM_Carousels {

	public function __construct() {
		add_shortcode( 'custom_category_carousel', array( $this, 'render_custom_category_carousel' ));
		add_shortcode( 'category_sidebar', array( $this, 'render_category_sidebar_shortcode' ));
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_swiper_for_related_products' ));
		add_shortcode( 'related_products_carousel', array( $this, 'render_related_products_carousel_shortcode' ));
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_swiper_for_category_products' ));
		add_shortcode( 'category_products_carousel', array( $this, 'render_category_products_carousel_shortcode' ));
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_swiper_for_promo_products' ));
		add_shortcode( 'promo_products_carousel', array( $this, 'render_promo_products_carousel_shortcode' ));
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_swiper_for_homepage_slider' ));
		add_shortcode( 'homepage_slider', array( $this, 'render_homepage_slider_shortcode' ));
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_swiper_for_category_carousel' ));
		add_shortcode( 'category_carousel', array( $this, 'render_category_carousel_shortcode' ));
	}

	/**
	 * Attributs anti-lazy pour les images de carousels.
	 * Native lazy + ShortPixel + LiteSpeed cassent les slides hors viewport.
	 */
	private function carousel_img_attrs( $extra_class = '' ) {
		$classes = trim( 'skip-lazy ' . $extra_class );
		return 'class="' . esc_attr( $classes ) . '" loading="eager" decoding="async" data-no-lazy="1" data-skip-lazy="1" data-spai-excluded="true"';
	}

	// SHORTCODE CARROUSEL DES CATÉGORIES (SLICK)
	// SHORTCODE SIDEBAR CATÉGORIES
	// SHORTCODE CARROUSEL PRODUITS ASSOCIÉS
	// SHORTCODE CARROUSEL PRODUITS PAR CATÉGORIE
	// SHORTCODE CARROUSEL PRODUITS EN PROMOTION (AVEC AJAX ADD TO CART)
	// SHORTCODE SLIDER PAGE D'ACCUEIL
	// SHORTCODE CARROUSEL DES CATÉGORIES ASSOCIÉES

	public function render_custom_category_carousel($atts) {
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
	                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($term->name); ?>" <?php echo $this->carousel_img_attrs(); ?>>
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

	public function render_category_sidebar_shortcode($atts) {
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
	                    <?php $this->render_category_tree_thegem_style($parent_categories, $active_term_id, $active_ancestors, 0); ?>
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

	public function render_category_tree_thegem_style($categories, $active_term_id, $active_ancestors, $depth) {
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
	                    <?php $this->render_category_tree_thegem_style($children, $active_term_id, $active_ancestors, $depth + 1); ?>
	                </ul>
	            <?php endif; ?>
	        </li>
	        <?php
	    }
	}

	public function enqueue_swiper_for_related_products() {
	    global $post;
	    $has_shortcode = is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'related_products_carousel');
	    
	    if ($has_shortcode || is_product()) {
	        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
	        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
	    }
	}

	public function render_related_products_carousel_shortcode($atts) {
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
	    <div class="related-products-carousel">
	        <div class="related-products-container">
	            <?php if (!empty($atts['title'])) : ?>
	                <h2 class="carousel-section-title" style="text-align:center; margin-bottom:30px; font-size:1.5rem; font-weight:700; color:#2c3e50; text-transform:uppercase;">
	                    <?php echo esc_html($atts['title']); ?>
	                </h2>
	            <?php endif; ?>
	            
	            <div class="related-products-swiper-overflow">
	                <div class="swiper related-products-swiper" id="<?php echo esc_attr($unique_id); ?>">
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
	                            <div class="swiper-slide">
	                                <div class="item item-nonprice product clearfix">
	                                    <div class="item-wrap">
	                                        <div class="item-detail">
	                                            <div class="item-img products-thumb">
	                                                <a href="<?php echo esc_url($rel_product_url); ?>" title="<?php echo esc_attr($rel_product_name); ?>">
	                                                    <div class="product-thumb-hover">
	                                                        <img src="<?php echo esc_url($main_image_url); ?>" <?php echo $this->carousel_img_attrs( 'wp-post-image main-img' ); ?> alt="<?php echo esc_attr($main_image_alt); ?>">
	                                                        <img src="<?php echo esc_url($hover_image_url); ?>" <?php echo $this->carousel_img_attrs( 'hover-image1 back' ); ?> alt="<?php echo esc_attr($rel_product_name); ?>">
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
	    </div>
	
	    <script>
	    document.addEventListener('DOMContentLoaded', function() {
	        var swiperEl = document.getElementById('<?php echo esc_js($unique_id); ?>');
	        if (swiperEl && typeof Swiper !== 'undefined') {
	            var container = swiperEl.closest('.related-products-container');
	            new Swiper(swiperEl, {
	                slidesPerView: 1,
	                spaceBetween: 15,
	                loop: true,
	                loopAddBlankSlides: false,
	                grabCursor: true,
	                autoplay: { delay: 3000, disableOnInteraction: false, pauseOnMouseEnter: true },
	                speed: 600,
	                watchOverflow: true,
	                navigation: {
	                    nextEl: container.querySelector('.rel-prod-next'),
	                    prevEl: container.querySelector('.rel-prod-prev')
	                },
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

	public function enqueue_swiper_for_category_products() {
	    global $post;
	    $has_shortcode = is_singular() && isset($post->post_content) && has_shortcode($post->post_content, 'category_products_carousel');
	    
	    if ($has_shortcode) {
	        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
	        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
	    }
	}

	public function render_category_products_carousel_shortcode($atts) {
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
	    <div class="category-specific-carousel">
	        <div class="category-products-container">
	            <?php if (!empty($atts['title'])) : ?>
	                <h2 class="carousel-section-title" style="text-align:center; margin-bottom:30px; font-size:1.5rem; font-weight:700; color:#2c3e50; text-transform:uppercase;">
	                    <?php echo esc_html($atts['title']); ?>
	                </h2>
	            <?php endif; ?>
	            
	            <div class="category-products-swiper-overflow">
	                <div class="swiper category-products-swiper" id="<?php echo esc_attr($unique_id); ?>">
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
	                            <div class="swiper-slide">
	                                <div class="item item-nonprice product clearfix">
	                                    <div class="item-wrap">
	                                        <div class="item-detail">
	                                            <div class="item-img products-thumb">
	                                                <a href="<?php echo esc_url($product_url); ?>" title="<?php echo esc_attr($product_name); ?>">
	                                                    <div class="product-thumb-hover">
	                                                        <img src="<?php echo esc_url($main_image_url); ?>" <?php echo $this->carousel_img_attrs( 'wp-post-image main-img' ); ?> alt="<?php echo esc_attr($main_image_alt); ?>">
	                                                        <img src="<?php echo esc_url($hover_image_url); ?>" <?php echo $this->carousel_img_attrs( 'hover-image1 back' ); ?> alt="<?php echo esc_attr($product_name); ?>">
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
	    </div>
	
	    <script>
	    document.addEventListener('DOMContentLoaded', function() {
	        var swiperEl = document.getElementById('<?php echo esc_js($unique_id); ?>');
	        if (swiperEl && typeof Swiper !== 'undefined') {
	            var container = swiperEl.closest('.category-products-container');
	            new Swiper(swiperEl, {
	                slidesPerView: 1,
	                spaceBetween: 15,
	                loop: true,
	                loopAddBlankSlides: false,
	                grabCursor: true,
	                autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true },
	                speed: 600,
	                watchOverflow: true,
	                navigation: {
	                    nextEl: container.querySelector('.cat-prod-next'),
	                    prevEl: container.querySelector('.cat-prod-prev')
	                },
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

	public function enqueue_swiper_for_promo_products() {
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

	public function render_promo_products_carousel_shortcode($atts) {
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
	                                                    <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr($main_image_alt); ?>" <?php echo $this->carousel_img_attrs( 'main-image wp-post-image' ); ?>>
	                                                    <img src="<?php echo esc_url($secondary_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" <?php echo $this->carousel_img_attrs( 'secondary-image wp-post-image' ); ?>>
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

	public function enqueue_swiper_for_homepage_slider() {
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

	public function render_homepage_slider_shortcode($atts) {
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
	                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" <?php echo $this->carousel_img_attrs(); ?>>
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

	public function enqueue_swiper_for_category_carousel() {
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

	public function render_category_carousel_shortcode($atts) {
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
	                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($cat->name); ?>" <?php echo $this->carousel_img_attrs(); ?>>
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
}
