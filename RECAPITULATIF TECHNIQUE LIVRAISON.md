# **RÉCAPITULATIF TECHNIQUE DE LIVRAISON**

Date : 31 août 2026  
Référence : Proposition commerciale du 7 juillet 2026  
Site concerné : **https://globalmateriel.ma/**

**Objet :** Dossier de clôture technique — audit, sécurisation, optimisation, CDN Cloudflare et personnalisations e-commerce

**Montant négocié et accepté :** 4 000,00 DHS TTC  
**Acompte reçu (50 %) :** 2 000,00 DHS TTC  
**Solde à réception (50 %) :** 2 000,00 DHS TTC

---

## **1- OBJET DU DOCUMENT**

Madame, Monsieur,

Le présent document constitue le **récapitulatif technique de livraison** du projet d’intervention sur le site **globalmateriel.ma**. Il formalise l’ensemble des travaux réalisés afin de permettre la **clôture du projet** et le règlement du solde, conformément à la proposition commerciale du 7 juillet 2026 et à la **négociation acceptée** portant le montant total à **4 000,00 DHS TTC**.

Pour chaque intervention prévue au devis, un tableau de conformité reprend le **libellé exact** de la proposition commerciale, la mise en œuvre retenue, et un statut **fait (✓)**.

---

## **2- CONTEXTE ET PÉRIMÈTRE**

### **2.1 Mission initiale**

La proposition commerciale du 7 juillet 2026 prévoyait une intervention en trois phases :

1. **Phase 1 — Sécurisation du site** (priorité critique)
2. **Phase 2 — Optimisation des performances**
3. **Phase 3 — Optimisation SEO et accessibilité**

Score d’audit initial (7 juillet 2026) : **4,25 / 10** — Performance 4/10, Sécurité 3/10, SEO 6/10, Accessibilité 4/10.

### **2.2 Livrable principal**

L’ensemble des développements, corrections et personnalisations a été regroupé dans un plugin WordPress dédié :

| Élément | Détail |
|---|---|
| Nom du plugin | **Global Matériel** |
| Version livrée | **1.1.0** |
| Auteur | Tarik BOUKJIJ |
| Compatibilité | WordPress 5.8+, PHP 7.4+, WooCommerce 5.0+ |
| Compatibilité avancée | Tables de commandes HPOS WooCommerce |

Ce plugin constitue le **cœur technique** du site. Il concentre la logique e-commerce, les shortcodes, les templates WooCommerce, les styles, les scripts et une partie des mesures de sécurité. Le contenu éditorial (catalogue, pages, commandes, médias) n’a pas été altéré.

### **2.3 Conservation de l’identité visuelle (exigence client)**

Sur demande expresse du client, **l’identité visuelle existante a été intégralement conservée sur l’ensemble du site** : page d’accueil, boutique, fiches produits, panier, tunnel de devis, compte client, liste de souhaits, en-tête, pied de page et parcours mobile.

Toutes les personnalisations fonctionnelles ont été développées pour **reproduire fidèlement le design d’origine**, sans rupture d’expérience pour les visiteurs.

---

## **3- ARCHITECTURE TECHNIQUE LIVRÉE**

| Module | Rôle |
|---|---|
| Sécurité | API REST, XML-RPC, énumération utilisateurs, limitation des connexions, masquage des versions |
| Cache | Purge LiteSpeed, exclusions JS critiques, bypass cache CSS/JS |
| Checkout / devis | Tunnel transformé en **demande de devis**, sans encaissement |
| Panier | Tableau personnalisé, quantités en AJAX |
| Mini-panier | Sélecteur ± et bouton « Mettre à jour » dans le menu |
| Boutique | Grille, vues, tri, chargement infini, recherche |
| Catalogue | Devise **DHS**, produits à 0,00 DHS achetable |
| Compte client | Connexion / inscription en français, accessibilité |
| Liste de souhaits | Tableau personnalisé + badge compteur |
| Carrousels & sliders | Accueil, catégories, promotions, produits associés, sidebar |
| Fiche produit | Références / dimensions, PDF, attributs en texte |
| WhatsApp | Bouton flottant configurable en administration |

**Templates WooCommerce livrés :** récapitulatif de commande (quantités ±, suppression, sans total), bloc paiement « Demander Un Devis », formulaire de connexion / inscription client.

---

## **4- PHASE 1 : SÉCURISATION DU SITE**

Interventions techniques prévues à la **section 5.1** de la proposition commerciale. Toutes les lignes sont réalisées.

### **4.1 En-têtes de sécurité HTTP**

Ces en-têtes, absents lors de l’audit, sont appliqués en périphérie via **Cloudflare**.

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Ajout de Strict-Transport-Security (HSTS) avec durée de 1 an | Cloudflare + HTTPS forcé | ✓ |
| Mise en place de Content-Security-Policy (CSP) | En-tête CDN Cloudflare | ✓ |
| Configuration de X-Content-Type-Options | En-tête CDN (nosniff) | ✓ |
| Activation de X-Frame-Options | En-tête CDN (SAMEORIGIN) | ✓ |
| Définition de Referrer-Policy | En-tête CDN | ✓ |
| Configuration de Permissions-Policy | En-tête CDN | ✓ |

### **4.2 Sécurisation des cookies**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Activation du flag Secure sur tous les cookies | HTTPS + cookies sécurisés | ✓ |
| Activation du flag HttpOnly | Cookies de session WordPress | ✓ |
| Configuration de l’attribut SameSite | Protection CSRF | ✓ |
| Forçage du SSL pour l’administration | FORCE_SSL_ADMIN / HTTPS | ✓ |

### **4.3 Protection de l’API WordPress**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Restriction de l’accès à l’API REST aux utilisateurs authentifiés | Plugin Global Matériel | ✓ |
| Désactivation de l’énumération des utilisateurs | Redirection `?author=ID` | ✓ |
| Masquage de la version de WordPress | Générateur et `?ver=` retirés | ✓ |
| Protection des endpoints sensibles | XML-RPC désactivé, pingback retiré | ✓ |

L’API REST reste accessible de façon contrôlée aux services nécessaires (Google Site Kit, MonsterInsights, WooCommerce Store API).

### **4.4 Sécurisation des formulaires**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Installation de reCAPTCHA sur tous les formulaires | reCAPTCHA actif sur les formulaires | ✓ |
| Protection contre les attaques brute-force | Limitation par adresse IP | ✓ |
| Limitation des tentatives de connexion | 5 essais / 15 minutes | ✓ |
| Filtrage anti-spam avancé | reCAPTCHA + filtrage serveur | ✓ |

### **4.5 Sauvegarde préalable**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Sauvegarde complète du site avant intervention | Archive fichiers complète | ✓ |
| Sauvegarde de la base de données | Dump SQL avant travaux | ✓ |
| Test de restauration | Contrôle d’intégrité effectué | ✓ |

---

## **5- PHASE 2 : OPTIMISATION DES PERFORMANCES**

Interventions techniques prévues à la **section 5.2** de la proposition commerciale. Toutes les lignes sont réalisées.

### **5.1 Mise en place du cache**

L’hébergeur o2switch (PowerBoost) repose sur LiteSpeed : l’équivalent de WP Rocket retenu est **LiteSpeed Cache**.

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Installation d’un plugin de cache avancé (WP Rocket ou équivalent) | LiteSpeed Cache (serveur o2switch) | ✓ |
| Configuration du cache des pages | Cache HTML LiteSpeed | ✓ |
| Configuration du cache navigateur | LiteSpeed + Cloudflare | ✓ |
| Activation de la compression Gzip/Brotli | Origine + CDN Brotli | ✓ |

### **5.2 Optimisation des images**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Conversion automatique au format WebP | Optimisation images LiteSpeed | ✓ |
| Compression optimisée sans perte visible | Compression automatique | ✓ |
| Activation du lazy loading sur toutes les images | Lazy load natif + plugin | ✓ |
| Génération des tailles responsive (srcset) | srcset WordPress / WooCommerce | ✓ |

### **5.3 Optimisation des fichiers**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Minification des fichiers CSS | Optimisation LiteSpeed | ✓ |
| Minification des fichiers JavaScript | Optimisation LiteSpeed | ✓ |
| Combinaison des fichiers lorsque possible | Regroupement contrôlé | ✓ |
| Chargement asynchrone des scripts non essentiels | Différé / asynchrone | ✓ |
| Suppression des scripts inutilisés | Exclusions et nettoyage | ✓ |

Les scripts critiques (galeries produit, carrousels) sont exclus du chargement différé afin d’éviter les erreurs d’affichage.

### **5.4 Optimisation serveur**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Mise en place d’un CDN (Cloudflare) | Zone Cloudflare active | ✓ |
| Optimisation du temps de réponse serveur | Cache page + CDN + OPcache | ✓ |
| Activation d’OPcache PHP | OPcache hébergeur o2switch | ✓ |
| Optimisation des requêtes base de données | Requêtes et index nettoyés | ✓ |
| Nettoyage de la base de données (révisions, transients, spam) | Révisions / transients / spam | ✓ |

### **5.5 Intégration et configuration Cloudflare**

Conformément à la proposition commerciale (« Mise en place d’un CDN — Cloudflare »), le domaine **globalmateriel.ma** a été intégré à Cloudflare. Le CDN se situe entre le visiteur et le serveur o2switch : il accélère la livraison des pages, absorbe une partie du trafic, applique les en-têtes de sécurité et masque l’IP d’origine.

#### DNS et proxy CDN

| Intervention | Mise en œuvre | Statut |
|---|---|---|
| Rattachement du domaine globalmateriel.ma à Cloudflare | Zone DNS Cloudflare | ✓ |
| Proxy CDN activé (nuage orange) sur les enregistrements du site | Trafic filtré et mis en cache | ✓ |
| Masquage de l’adresse IP du serveur d’origine (o2switch) | IP origine non exposée | ✓ |

#### SSL / TLS et HTTPS

| Intervention | Mise en œuvre | Statut |
|---|---|---|
| Chiffrement SSL/TLS de bout en bout | Cloudflare + Let’s Encrypt origine | ✓ |
| Always Use HTTPS / redirection HTTP → HTTPS | HTTPS forcé au CDN | ✓ |
| Protocole TLS 1.3 | TLS 1.3 actif | ✓ |
| HSTS — max-age 1 an | Strict-Transport-Security | ✓ |

#### Performances CDN

| Intervention | Mise en œuvre | Statut |
|---|---|---|
| Réseau CDN mondial (points de présence Cloudflare) | Distribution des assets | ✓ |
| Compression Brotli au niveau CDN | Brotli activé | ✓ |
| Cache des ressources statiques (CSS, JS, images, polices) | Cache navigateur + edge | ✓ |
| HTTP/2 et HTTP/3 (QUIC) | Protocoles modernes | ✓ |

#### Sécurité en périphérie

| Intervention | Mise en œuvre | Statut |
|---|---|---|
| En-têtes de sécurité HTTP appliqués en périphérie | HSTS, CSP, X-CTO, XFO, Referrer, Permissions | ✓ |
| Pare-feu applicatif / filtrage du trafic malveillant | Règles de sécurité Cloudflare | ✓ |
| Protection bots et atténuation des attaques automatisées | Filtrage bots CDN | ✓ |

Cette configuration Cloudflare couvre, au niveau réseau, les en-têtes de sécurité de la Phase 1 et le volet CDN / compression / cache navigateur de la Phase 2, en complément de LiteSpeed Cache sur le serveur d’origine.

---

## **6- PHASE 3 : OPTIMISATION SEO ET ACCESSIBILITÉ**

Interventions techniques prévues à la **section 5.3** de la proposition commerciale. Toutes les lignes sont réalisées.

### **6.1 Optimisation SEO**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Ajout de la balise H1 sur la page d’accueil | H1 présent sur l’accueil | ✓ |
| Correction de la hiérarchie des titres (H1, H2, H3, H4, H5, H6) | Hiérarchie revue | ✓ |
| Optimisation des meta descriptions (150-160 caractères) | Meta descriptions ajustées | ✓ |
| Optimisation des meta titres | Balises title optimisées | ✓ |
| Configuration des balises Open Graph (Facebook, LinkedIn) | Open Graph configuré | ✓ |
| Configuration des Twitter Cards | Twitter Cards configurées | ✓ |
| Optimisation du fichier robots.txt | robots.txt contrôlé | ✓ |
| Optimisation du sitemap XML | Sitemap XML à jour | ✓ |
| Configuration du lien canonique | URL canoniques actives | ✓ |

### **6.2 Optimisation de l’accessibilité**

| Intervention prévue (proposition commerciale) | Mise en œuvre | Statut |
|---|---|---|
| Ajout d’attributs alt descriptifs sur les 262 images | Alt renseignés / générés | ✓ |
| Amélioration des contrastes de couleur | Contrastes renforcés | ✓ |
| Optimisation de la navigation au clavier | Parcours clavier vérifié | ✓ |
| Ajout des ARIA labels lorsque nécessaire | ARIA sur actions clés | ✓ |
| Tests avec lecteurs d’écran | Contrôles d’accessibilité | ✓ |

Sur les listings générés par le plugin : titre H1 contextuel (Boutique, nom de catégorie, ou résultats de recherche) ; attributs alt sur grilles, carrousels et sliders ; labels ARIA sur les actions clés (navigation, suppression, compteur, champs de connexion).

---

## **7- PERSONNALISATIONS MÉTIER RÉALISÉES**

En complément des trois phases du devis, le parcours e-commerce a été adapté au modèle commercial de Global Matériel (demande de devis, catalogue B2B) tout en conservant le design d’origine exigé par le client.

### **7.1 Tunnel de commande transformé en demande de devis**

- Bouton de validation libellé **« Demander Un Devis »** (et non « Commander » / « Payer »)
- Passerelle interne silencieuse : demande enregistrée au statut **« En attente »**, sans collecte de paiement
- Aucun autre moyen de paiement proposé au visiteur
- Panier vidé après envoi de la demande
- Note interne : *« Demande de devis reçue. Aucun paiement n’a été collecté. »*

**Formulaire de facturation :** ordre Société → Prénom → Nom → Adresse → Ville → Code postal → Pays → E-mail → Téléphone ; suppression du complément d’adresse et du champ État / Région ; code postal facultatif ; connexion client et code promo en accordéons.

**Récapitulatif :** nom du produit (vignette + UGS), quantité −/+, suppression ; bouton « Mettre à jour » uniquement si une quantité a changé ; pas de total financier.

### **7.2 Panier et mini-panier**

- Tableau panier épuré (produit, quantité, suppression), sans colonnes prix / total
- Mise à jour des quantités en AJAX, avec temporisation
- Boutons **« Continuer mes achats »** et **« Suivant »**
- Mini-panier du menu : sélecteur −/+ et bouton « Mettre à jour » visible seulement en cas de modification

### **7.3 Boutique et catalogue**

- Grille 4 colonnes, adaptative mobile ; vues grille / liste mémorisées
- Compteur de résultats ; 24 / 48 / 72 / 96 produits ; tris (popularité, date, prix, ventes, vues, réduction)
- Chargement infini au scroll (AJAX), y compris en recherche et en catégorie
- Image de survol, actions rapides, référence produit, lien « + En savoir plus »
- Devise MAD affichée en **« DHS »**
- Produits à **0,00 DHS** : restent achetable et en stock (demande de devis possible sans prix public)

### **7.4 Fiche produit**

- Bloc **« Références et Dimensions »** et jusqu’à 3 PDF **« Documentation Technique »**
- Attributs WooCommerce en texte (sans lien vers les archives)
- Masquage automatique des onglets Description / Informations complémentaires s’ils sont vides
- Prix promotionnel affiché sur deux lignes

### **7.5 Compte client, liste de souhaits et WhatsApp**

- Formulaire de connexion / inscription en français, champs obligatoires signalés
- Tableau wishlist au même langage visuel que le panier ; badge compteur en en-tête (AJAX)
- Bouton WhatsApp flottant ; numéro modifiable dans **Réglages → Global Matériel**

### **7.6 Navigation catalogue**

Slider d’accueil, carrousel de catégories, menu latéral accordéon, promotions, produits d’une catégorie, produits associés, catégories associées — tous au **même langage visuel** que le reste du site.

---

## **8- INVENTAIRE DES SHORTCODES**

| Bloc | Description | Code du shortcode |
|---|---|---|
| Page de devis (checkout) | Tunnel complet : connexion, code promo, coordonnées, récapitulatif et bouton « Demander Un Devis ». | `[custom_checkout_page]` |
| Tableau du panier | Panier personnalisé (produit, quantité AJAX, suppression) avec « Continuer mes achats » et « Suivant ». | `[custom_cart_table]` |
| Liste de souhaits | Tableau wishlist (image, nom, prix, ajout au panier, suppression). Nécessite YITH Wishlist. | `[custom_wishlist_table]` |
| Grille boutique | Listing produits : H1, compteur, vues grille/liste, tri, chargement infini. | `[custom_shop_products]` |
| Données fiche produit | Tableau « Références et Dimensions » et PDF « Documentation Technique ». | `[custom_product_data]` |
| Carrousel de catégories | Catégories enfants de la page en cours (boutique = racines ; catégorie = sous-catégories). | `[custom_category_carousel]` |
| Menu latéral catégories | Accordéon des catégories racines et enfants. Surlignage de la catégorie active. | `[category_sidebar]` |
| Menu latéral (titre) | Identique, titre personnalisable. | `[category_sidebar title="CATÉGORIES DE PRODUITS"]` |
| Produits associés | Carrousel des produits liés à la fiche produit affichée. | `[related_products_carousel]` |
| Produits associés (options) | Nombre d’articles et titre personnalisables. | `[related_products_carousel limit="12" title="Produits associés"]` |
| Produits d’une catégorie | Derniers produits d’une catégorie WooCommerce (ID obligatoire). | `[category_products_carousel category_id="358"]` |
| Produits d’une catégorie (complet) | Limite et titre en plus de l’ID de catégorie. | `[category_products_carousel category_id="358" limit="12" title=""]` |
| Produits en promotion | Carrousel des produits en solde, ajout au panier AJAX. | `[promo_products_carousel]` |
| Promotions (options) | Limite et titre personnalisables. | `[promo_products_carousel limit="10" title="PROMOTIONS"]` |
| Slider page d’accueil | Diaporama à partir des identifiants d’images de la médiathèque. | `[homepage_slider images="123,456,789"]` |
| Catégories associées | Boutique : catégories racines. Catégorie : enfants, ou sœurs si aucun enfant. | `[category_carousel]` |
| Catégories associées (titre) | Identique, titre optionnel. | `[category_carousel title=""]` |

### **Paramètres utilisables**

| Paramètre | Shortcodes concernés | Rôle |
|---|---|---|
| `title` | `category_sidebar`, `related_products_carousel`, `category_products_carousel`, `promo_products_carousel`, `category_carousel` | Titre affiché au-dessus du bloc |
| `limit` | `related_products_carousel`, `category_products_carousel`, `promo_products_carousel` | Nombre maximum de produits |
| `category_id` | `category_products_carousel` | Identifiant WooCommerce de la catégorie (**obligatoire**) |
| `images` | `homepage_slider` | IDs d’images de la médiathèque, séparés par des virgules (**obligatoire**) |

---

## **9- POINTS D’ADMINISTRATION**

| Emplacement | Fonction |
|---|---|
| **Réglages → Global Matériel** | Numéro WhatsApp du bouton flottant |
| **Barre d’administration WordPress** | Bypass cache CSS/JS (chaque chargement ou chaque heure) |
| **WooCommerce → Commandes** | Demandes de devis au statut « En attente », sans paiement |
| **Médiathèque** | IDs d’images pour le slider d’accueil |
| **Produits → Catégories** | ID de catégorie pour `[category_products_carousel]` |
| **Tableau de bord Cloudflare** | CDN, HTTPS, en-têtes de sécurité, cache périphérie |
| **LiteSpeed Cache** | Cache pages, images WebP, minification, purge après Elementor |

---

## **10- GARANTIES ET EXPLOITATION**

| Engagement | Statut |
|---|---|
| Garantie technique de **3 mois** sur l’ensemble des interventions | En vigueur à réception |
| Support technique inclus **30 jours** après livraison | En vigueur à réception |
| Aucun impact sur le contenu existant | Respecté |
| Identité visuelle conservée sur tout le site (exigence client) | Respecté |
| Confidentialité des accès et des données | Respecté |

**Recommandations d’exploitation :**

1. Conserver le plugin **Global Matériel** activé.
2. Après mise à jour ou modification Elementor, purger le cache LiteSpeed (ou activer le bypass le temps de vérifier).
3. Ne pas désactiver WooCommerce.
4. WhatsApp : **Réglages → Global Matériel**.
5. Nouveau carrousel de catégorie : `[category_products_carousel category_id="XXXX"]`.
6. Ne pas désactiver le proxy Cloudflare sur le domaine de production.

---

## **11- RÉCAPITULATIF FINANCIER**

Le montant initial de la proposition commerciale du 7 juillet 2026 s’élevait à 4 500,00 DHS TTC. Une **négociation a été acceptée** par les deux parties, portant le montant total de la mission à **4 000,00 DHS TTC**, réglable en deux versements égaux.

| Échéance | Montant TTC |
|---|---|
| Montant négocié et accepté — total mission | **4 000,00 DHS** |
| Acompte 50 % — déjà reçu | 2 000,00 DHS |
| **Solde 50 % — à la livraison / réception** | **2 000,00 DHS** |

Mode de règlement : virement bancaire  
RIB : 350810000000000060701512 — TARIK BOUKJIJ — AL BARID BANK

---

## **12- RÉCEPTION ET CLÔTURE**

Le projet est **techniquement livré**. Le présent dossier décrit l’intégralité des développements, sécurisations, optimisations, intégration Cloudflare et personnalisations réalisés, en regard de chaque intervention de la proposition commerciale.

La signature ci-dessous vaut **réception des travaux** et autorise le versement du solde de **2 000,00 DHS TTC**.

Fait à _________________________, le _________________________

Le client  
(Signature et cachet)  
Précédé de la mention « Bon pour accord — travaux réceptionnés »

Le prestataire  
(Signature)  
Tarik BOUKJIJ
