# **PROPOSITION COMMERCIALE** 

Date d'émission : 7 juillet 2026 Validité de l'offre : 30 jours 

**Objet :** Audit technique complet et proposition d'optimisation du site globalmateriel.ma 

## **1- PRÉSENTATION DE LA MISSION** 

Madame, Monsieur, 

Dans le cadre de l'amélioration continue de votre présence en ligne, nous avons réalisé un audit technique approfondi de votre site internet **https://globalmateriel.ma/** . Ce document présente les résultats complets de cet audit ainsi que notre proposition d'intervention pour corriger l'ensemble des problématiques identifiées. 

Notre mission s'articule autour de trois axes principaux : 

- Diagnostic complet de l'état actuel du site 

- Identification des vulnérabilités et points d'amélioration 

- Proposition de solutions techniques détaillées avec chiffrage 

## **2- INFORMATIONS TECHNIQUES DU SITE** 

Infrastructure technique identifiée : 

- Système de gestion de contenu (CMS) : WordPress 7.0 

- Module e-commerce : WooCommerce 10.8.1 

- Constructeur de pages : Elementor 4.1.1 

- Hébergeur : o2switch (offre PowerBoost-v3) 

- Localisation serveur : France 

- Certificat SSL : Let's Encrypt (valide jusqu'au 03/09/2026) 

- Protocole de sécurité : TLSv1.3 

- Redirection HTTPS : Configurée et fonctionnelle 

Technologies tierces détectées : 

- Site Kit by Google 1.180.0 

- reCAPTCHA 

- Google Tag Manager 

- 57 scripts externes 

- 3 domaines externes sollicités 

## **3- RÉSULTATS DE L'AUDIT - SCORE GLOBAL** 

Score global du site : **4,25 sur 10** 

Détail des scores par catégorie : 

- Performance : 4/10 – **INSUFFISANT** 

   - Temps de chargement trop élevé 

   - Ressources non optimisées 

   - Absence de système de cache 

- Sécurité : 3/10 - **CRITIQUE** 

   - Aucun header de sécurité configuré 

   - Cookies non sécurisés 

   - API WordPress exposée publiquement 

- SEO (référencement) : 6/10 - **MOYEN** 

   - Structure de titres incomplète 

   - Meta description à optimiser 

   - Potentiel d'amélioration important 

- Accessibilité : 4/10 - **INSUFFISANT** 

   - Nombreuses images sans description 

   - Non-conformité aux standards WCAG 

## **4- PROBLÉMATIQUES IDENTIFIÉES - DIAGNOSTIC DÉTAILLÉ** 

## **4-1 PROBLÉMATIQUES DE SÉCURITÉ (PRIORITÉ CRITIQUE)** 

## **4-1-1 Absence totale d'en-têtes de sécurité HTTP** 

Constat : Votre site ne dispose d'aucun header de sécurité HTTP, ce qui constitue la faille la plus critique identifiée. 

Headers manquants : 

- Strict-Transport-Security (HSTS) : Protège contre les attaques par interception 

- Content-Security-Policy (CSP) : Protège contre les injections de code malveillant 

- X-Content-Type-Options : Empêche l'interprétation malveillante de fichiers 

- X-Frame-Options : Protège contre le clickjacking (détournement de clics) 

- Referrer-Policy : Contrôle les informations de référence envoyées 

- Permissions-Policy : Restreint l'accès aux fonctionnalités du navigateur 

## Risques encourus : 

- Attaques XSS (Cross-Site Scripting) 

- Clickjacking 

- Interception de sessions (MITM - Man In The Middle) 

- MIME sniffing 

- Vol de données sensibles 

## **4.1.2 Cookies non sécurisés** 

Constat : Les cookies utilisés par votre site ne possèdent aucun flag de sécurité. 

## Flags manquants : 

- Flag "Secure" : Les cookies peuvent être transmis en HTTP non chiffré 

- Flag "HttpOnly" : Les cookies sont accessibles par JavaScript (risque de vol) 

- Attribut "SameSite" : Vulnérabilité aux attaques CSRF (Cross-Site Request Forgery) 

## Risques encourus : 

- Vol de session utilisateur 

- Détournement de compte 

- Accès non autorisé aux données clients 

## **4.1.3 API WordPress exposée publiquement** 

Constat : L'endpoint /wp-json/ est accessible sans aucune authentification. 

## Conséquences : 

- Énumération possible de tous les utilisateurs du site 

- Récupération d'informations sensibles sur la structure 

- Facilitation des attaques brute-force sur l'administration 

- Exposition des données internes 

## **4.1.4 Formulaires non protégés** 

Constat : Les formulaires du site ne disposent pas de protection captcha ou anti-spam efficace. 

## Risques encourus : 

- Soumissions malveillantes automatisées 

- Spam massif 

- Attaques brute-force sur le formulaire de connexion 

- Surcharge du serveur 

## **4.2 PROBLÉMATIQUES DE PERFORMANCE** 

## **4.2.1 Temps de réponse serveur excessif** 

Constat : Le serveur met trop de temps à répondre aux requêtes. 

Métriques relevées : 

- TTFB (Time To First Byte) : 1,5 à 1,7 secondes 

   - Objectif recommandé : moins de 0,8 seconde 

- Largest Contentful Paint (LCP) : 3,3 secondes sur mobile 

- First Contentful Paint (FCP) : 2,4 secondes 

Impact : 

- 25% des visiteurs quittent un site dont le chargement dépasse 3 secondes 

- Pénalisation par Google dans les résultats de recherche 

- Baisse du taux de conversion estimée à 20% 

## **4.2.2 Ressources non optimisées** 

Constat : Le site charge un nombre excessif de ressources non optimisées. 

Détail : 

- 57 scripts externes bloquant le rendu de la page 

- 49 feuilles de style CSS à charger 

- 400 images sur la page d'accueil 

- 583 KB de HTML (volume excessif) 

- Seulement 130 images avec lazy loading activé 

- Seulement 57 images en format responsive (srcset) 

- 35 scripts inline non optimisés 

## **4.2.3 Absence de système de cache** 

Constat : Aucun mécanisme de cache n'est configuré. 

Conséquences : 

- Chaque visite nécessite un chargement complet 

- Sollicitation excessive du serveur 

- Temps de chargement multiplié pour les visiteurs récurrents 

- Pas de CDN (Content Delivery Network) pour la distribution mondiale 

## **4.3 PROBLÉMATIQUES SEO ET ACCESSIBILITÉ** 

## **4.3.1 Structure SEO défaillante** 

Constat : La structure des titres ne respecte pas les bonnes pratiques SEO. 

## Problèmes identifiés : 

- Absence de balise H1 sur la page d'accueil (élément fondamental pour Google) 

- Meta description incomplète et peu attractive 

- Hiérarchie des titres non optimisée (H1, H2, H3...) 

- Potentiel de référencement sous-exploité 

## **4.3.2 Accessibilité non conforme** 

Constat : Le site ne respecte pas les standards d'accessibilité WCAG. 

## Problèmes identifiés : 

- 262 images sans attribut alt (65% des images du site) 

- Navigation non optimisée pour les lecteurs d'écran 

- Contrastes insuffisants sur certains éléments 

- Exclusion des utilisateurs en situation de handicap 

## Conséquences : 

- Exclusion de 15 à 20% de la population potentielle 

- Pénalisation SEO (Google prend en compte l'accessibilité) 

- Risque juridique (obligation d'accessibilité pour les sites e-commerce) 

## **5- SOLUTIONS PROPOSÉES** 

## **5.1 PHASE 1 : SÉCURISATION DU SITE** 

## Interventions techniques : 

- Configuration des en-têtes de sécurité HTTP : 

- Ajout de Strict-Transport-Security avec durée de 1 an 

- Mise en place de Content-Security-Policy 

- Configuration de X-Content-Type-Options 

- Activation de X-Frame-Options 

- Définition de Referrer-Policy 

- Configuration de Permissions-Policy 

## Sécurisation des cookies : 

- Activation du flag Secure sur tous les cookies 

- Activation du flag HttpOnly 

- Configuration de l'attribut SameSite 

- Forçage du SSL pour l'administration 

## Protection de l'API WordPress : 

- Restriction de l'accès à l'API REST aux utilisateurs authentifiés 

- Désactivation de l'énumération des utilisateurs 

- Masquage de la version de WordPress 

- Protection des endpoints sensibles 

## Sécurisation des formulaires : 

- Installation de reCAPTCHA sur tous les formulaires 

- Protection contre les attaques brute-force 

- Limitation des tentatives de connexion 

- Filtrage anti-spam avancé 

## Sauvegarde préalable : 

- Sauvegarde complète du site avant intervention 

- Sauvegarde de la base de données 

- Test de restauration 

## **5.2 PHASE 2 : OPTIMISATION DES PERFORMANCES** 

## Interventions techniques : 

- Mise en place du cache : 

   - Installation d'un plugin de cache avancé (WP Rocket ou équivalent) 

   - Configuration du cache des pages 

   - Configuration du cache navigateur 

   - Activation de la compression Gzip/Brotli 

- Optimisation des images : 

   - Conversion automatique au format WebP 

   - Compression optimisée sans perte visible 

   - Activation du lazy loading sur toutes les images 

   - Génération des tailles responsive (srcset) 

- Optimisation des fichiers : 

   - Minification des fichiers CSS 

   - Minification des fichiers JavaScript 

   - Combinaison des fichiers lorsque possible 

   - Chargement asynchrone des scripts non essentiels 

   - Suppression des scripts inutilisés 

- Optimisation serveur : 

   - Mise en place d'un CDN (Cloudflare) 

   - Optimisation du temps de réponse serveur 

   - Activation d'OPcache PHP 

   - Optimisation des requêtes base de données 

   - Nettoyage de la base de données (révisions, transients, spam) 

## **5.3 PHASE 3 : OPTIMISATION SEO ET ACCESSIBILITÉ** 

Interventions techniques : 

- Optimisation SEO : 

   - Ajout de la balise H1 sur la page d'accueil 

   - Correction de la hiérarchie des titres (H1, H2, H3, H4, H5, H6) 

   - Optimisation des meta descriptions (150-160 caractères) 

   - Optimisation des meta titres 

   - Configuration des balises Open Graph (Facebook, LinkedIn) 

   - Configuration des Twitter Cards 

   - Optimisation du fichier robots.txt 

   - Optimisation du sitemap XML 

   - Configuration du lien canonique 

- Optimisation de l'accessibilité : 

   - Ajout d'attributs alt descriptifs sur les 262 images 

   - Amélioration des contrastes de couleur 

   - Optimisation de la navigation au clavier 

   - Ajout des ARIA labels lorsque nécessaire 

   - Tests avec lecteurs d'écran 

## **6- RÉCAPITULATIF FINANCIER** 

**TOTAL TTC  : 4 500,00 DHS** 

## **7- PLANNING D'INTERVENTION** 

Semaine 1 : Audit et sécurisation 

- Jour 1-2 : Audit technique complet et sauvegarde du site 

- Jour 3-4 : Mise en place des corrections de sécurité 

- Jour 5 : Tests de sécurité et validation 

Semaine 2 : Optimisation des performances 

- Jour 6-7 : Installation du cache et du CDN 

- Jour 8-9 : Optimisation des images et des fichiers 

- Jour 10 : Tests de performance et ajustements 

Semaine 3 : SEO, accessibilité et livraison 

- Jour 11-12 : Corrections SEO et structure des titres 

- Jour 13-14 : Ajout des attributs alt et accessibilité 

- Jour 15 : Tests finaux, documentation et livraison 

Délai total : 3 semaines à compter de la validation du devis et du versement de l'acompte. 

## **8- MODALITÉS DE RÈGLEMENT** 

Échéancier de paiement : 

- Acompte de 45% à l’acceptation du devis : 2 000,00 DHS TTC 

- Solde de 55% à la livraison finale : 2 500,00 DHS TTC 

Modes de paiement acceptés : 

- Virement bancaire 

Coordonnées bancaires : 

- RIB : 350810000000000060701512 

- TARIK BOUKJIJ 

- AL BARID BANK 

## **9- GARANTIES ET ENGAGEMENTS** 

Garanties techniques : 

- Garantie de 3 mois sur l'ensemble des interventions 

- Support technique inclus pendant 30 jours après livraison 

- Aucun impact sur le contenu existant du site 

- Sauvegarde complète avant toute intervention 

- Intervention en environnement de test avant mise en production 

## Engagements de résultats : 

- Score de sécurité : passage de 3/10 à minimum 8/10 

- Temps de chargement : réduction de 50% minimum 

- Score de performance : passage de 4/10 à minimum 7/10 

- Accessibilité : conformité aux standards WCAG niveau A 

- Toutes les images auront un attribut alt descriptif 

## Confidentialité : 

- Confidentialité totale sur les données et accès au site 

- Non-divulgation des informations techniques et stratégiques 

- Destruction des accès après fin d'intervention 

- Signature d'un accord de confidentialité si nécessaire 

## **10- RETOUR SUR INVESTISSEMENT** 

## Sécurité renforcée : 

- Protection contre les principales attaques web 

- Sécurisation des données de vos clients 

- Conformité aux bonnes pratiques de cybersécurité 

- Réduction des risques de piratage et de perte de données 

## Performance améliorée : 

- Temps de chargement divisé par 2 ou 3 

- Meilleure expérience utilisateur 

- Augmentation estimée du taux de conversion de 15 à 25% 

- Réduction du taux de rebond 

## Meilleur référencement : 

- Positionnement amélioré sur Google 

- Visibilité accrue auprès de vos clients potentiels 

- Accessibilité conforme aux standards 

- Attractivité améliorée dans les résultats de recherche 

## **11- ACCEPTATION DU DEVIS** 

Pour accepter ce devis, merci de le retourner signé avec la mention "Bon pour accord" accompagnée de la date, de votre signature et de votre cachet. 

Fait à _________________________, le _________________________ 

Le client Le prestataire (Signature et cachet) (Signature) Précédé de la mention "Bon pour accord" 

