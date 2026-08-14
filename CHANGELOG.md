# Changelog — Global Matériel

Toutes les modifications notables de ce plugin sont documentées dans ce fichier.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Versionnement Sémantique](https://semver.org/lang/fr/).

## [1.1.0] — 2026-08-14

### Modifié
- Les personnalisations PHP sont réparties en classes (`GM_Checkout`, `GM_Cart`, `GM_Shop`, etc.), chargées par `GM_Plugin`. Shortcodes, AJAX et hooks inchangés.
- Le CSS de `global-materiel.css` est découpé dans `assets/css/parts/` (un fichier par bloc commenté). `custom.css` (TheGem) n’est pas scindé.

## [1.0.2] — 2026-08-14

### Corrigé
- Checkout : la quantité s’incrémentait de 2 (handlers ± du checkout et du mini-panier qui se cumulaient).

### Ajouté
- Bouton **Mettre à jour** sous le tableau `woocommerce-checkout-review-order-table`, visible uniquement si au moins une quantité a changé (même logique que le mini-panier). Au clic, les quantités sont enregistrées puis la page se recharge.

## [1.0.1] — 2026-08-13

### Corrigé
- Fatal error `Call to undefined function is_account_page()` : garde `function_exists` sur le filtre `gettext`.
- Chargement des personnalisations reporté à `plugins_loaded` (après WooCommerce).
- Garde similaire sur `is_checkout()` dans le script checkout.

## [1.0.0] — 2026-08-13

### Ajouté
- Création du plugin **Global Matériel** (auteur : Tarik BOUKJIJ).
- Migration des personnalisations depuis le thème enfant TheGem Elementor Child :
  - Checkout personnalisé (shortcode `custom_checkout_page`, quantités ±, template `review-order.php`).
  - Panier (shortcode `custom_cart_table`, AJAX quantités).
  - Mini-panier Elementor (mise à jour des quantités en AJAX).
  - Wishlist (badge, compteur AJAX, shortcode `custom_wishlist_table`).
  - Compte client (template `form-login.php`, textes FR).
  - Boutique (shortcode `custom_shop_products`, load more AJAX, tri prix).
  - Carrousels / sliders (catégories, produits liés, promo, homepage, sidebar catégories).
  - Shortcode `custom_product_data`.
  - Produits à prix zéro achetable / en stock.
  - Symbole de devise, durcissement sécurité (XML-RPC, versions, brute-force login).
  - Assets CSS (`global-materiel.css`, `custom.css`) et JS (`custom.js`).

### Notes
- Le thème enfant TheGem doit rester actif uniquement pour le héritage TheGem (fichiers minimaux `style.css` / `functions.php`).
- Après activation du plugin, vider le cache (plugin cache / CDN / navigateur).
