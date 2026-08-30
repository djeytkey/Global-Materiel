const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, VerticalAlign, PageNumber, LevelFormat } = require("docx");
const fs = require("fs");
const path = require("path");

const NAVY = "1B365D";
const ORANGE = "F07D00";
const GREEN = "1B7A4E";
const ROW_ALT = "F4F7FA";
const HEADER_BG = "1B365D";
const WHITE = "FFFFFF";
const BODY = "333333";
const MUTED = "5A6570";
const LINE = "C5CDD6";
const CHECK_BG = "E8F5EE";

const PAGE_W = 11906;
const MARGIN = 1134;
const CONTENT_W = PAGE_W - MARGIN * 2;

const thin = { style: BorderStyle.SINGLE, size: 4, color: LINE };
const borders = { top: thin, bottom: thin, left: thin, right: thin };
const noBorder = { style: BorderStyle.NONE, size: 0, color: "FFFFFF" };
const noBorders = { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder };

function run(text, opts = {}) {
  return new TextRun({
    text: text == null ? "" : String(text),
    font: "Calibri",
    size: opts.size || 21,
    bold: !!opts.bold,
    italics: !!opts.italics,
    color: opts.color || BODY,
    underline: opts.underline ? {} : undefined,
  });
}

function p(text, opts = {}) {
  const children = Array.isArray(text)
    ? text
    : [run(text, { size: opts.size, bold: opts.bold, italics: opts.italics, color: opts.color })];
  return new Paragraph({
    spacing: { after: opts.after ?? 160, before: opts.before ?? 0, line: opts.line || 276 },
    alignment: opts.align || AlignmentType.JUSTIFIED,
    children,
  });
}

function h1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 360, after: 160 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: ORANGE, space: 4 } },
    children: [run(text, { size: 28, bold: true, color: NAVY })],
  });
}

function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 280, after: 120 },
    children: [run(text, { size: 24, bold: true, color: NAVY })],
  });
}

function h3(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_3,
    spacing: { before: 200, after: 80 },
    children: [run(text, { size: 22, bold: true, color: ORANGE })],
  });
}

function cell(text, opts = {}) {
  const width = opts.width || 2000;
  const children = Array.isArray(text) ? text : [
    new Paragraph({
      alignment: opts.align || AlignmentType.LEFT,
      spacing: { after: 0, before: 0, line: 240 },
      children: Array.isArray(opts.runs) ? opts.runs : [run(text, {
        size: opts.size || 18,
        bold: !!opts.bold,
        color: opts.color || (opts.header ? WHITE : BODY),
      })],
    }),
  ];
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: opts.shading ? { type: ShadingType.CLEAR, fill: opts.shading } : undefined,
    verticalAlign: VerticalAlign.CENTER,
    margins: { top: 60, bottom: 60, left: 80, right: 80 },
    children,
  });
}

function checkCell(width) {
  return cell("✓", {
    width,
    bold: true,
    size: 22,
    color: GREEN,
    align: AlignmentType.CENTER,
    shading: CHECK_BG,
  });
}

function headerRow(labels, widths) {
  return new TableRow({
    tableHeader: true,
    children: labels.map((label, i) => cell(label, {
      width: widths[i],
      header: true,
      bold: true,
      size: 18,
      color: WHITE,
      shading: HEADER_BG,
      align: i === labels.length - 1 && label === "Statut" ? AlignmentType.CENTER : AlignmentType.LEFT,
    })),
  });
}

function dataTable(headers, rows, widths) {
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: widths,
    rows: [
      headerRow(headers, widths),
      ...rows.map((cols, idx) => new TableRow({
        children: cols.map((col, i) => {
          const isCheck = headers[i] === "Statut";
          if (isCheck) return checkCell(widths[i]);
          return cell(col, {
            width: widths[i],
            shading: idx % 2 === 1 ? ROW_ALT : WHITE,
            size: 18,
          });
        }),
      })),
    ],
  });
}

function checkTable(items) {
  const w = [7600, 1400, 640];
  return dataTable(
    ["Intervention prévue (proposition commerciale)", "Mise en œuvre", "Statut"],
    items.map((it) => [it.item, it.how, "✓"]),
    w
  );
}

function spacer(after = 200) {
  return new Paragraph({ spacing: { after }, children: [] });
}

function bullet(text) {
  return new Paragraph({
    numbering: { reference: "bullets", level: 0 },
    spacing: { after: 80, line: 260 },
    children: [run(text, { size: 21 })],
  });
}

function coverMeta(label, value) {
  return new Paragraph({
    spacing: { after: 60 },
    children: [
      run(label + " ", { size: 21, bold: true, color: NAVY }),
      run(value, { size: 21 }),
    ],
  });
}

const phase1Headers = [
  { item: "Ajout de Strict-Transport-Security (HSTS) avec durée de 1 an", how: "Cloudflare + HTTPS forcé" },
  { item: "Mise en place de Content-Security-Policy (CSP)", how: "En-tête CDN Cloudflare" },
  { item: "Configuration de X-Content-Type-Options", how: "En-tête CDN (nosniff)" },
  { item: "Activation de X-Frame-Options", how: "En-tête CDN (SAMEORIGIN)" },
  { item: "Définition de Referrer-Policy", how: "En-tête CDN" },
  { item: "Configuration de Permissions-Policy", how: "En-tête CDN" },
];

const phase1Cookies = [
  { item: "Activation du flag Secure sur tous les cookies", how: "HTTPS + cookies sécurisés" },
  { item: "Activation du flag HttpOnly", how: "Cookies de session WordPress" },
  { item: "Configuration de l’attribut SameSite", how: "Protection CSRF" },
  { item: "Forçage du SSL pour l’administration", how: "FORCE_SSL_ADMIN / HTTPS" },
];

const phase1Api = [
  { item: "Restriction de l’accès à l’API REST aux utilisateurs authentifiés", how: "Plugin Global Matériel" },
  { item: "Désactivation de l’énumération des utilisateurs", how: "Redirection ?author=ID" },
  { item: "Masquage de la version de WordPress", how: "Générateur et ?ver= retirés" },
  { item: "Protection des endpoints sensibles", how: "XML-RPC off, pingback retiré" },
];

const phase1Forms = [
  { item: "Installation de reCAPTCHA sur tous les formulaires", how: "reCAPTCHA actif sur les formulaires" },
  { item: "Protection contre les attaques brute-force", how: "Limitation par adresse IP" },
  { item: "Limitation des tentatives de connexion", how: "5 essais / 15 minutes" },
  { item: "Filtrage anti-spam avancé", how: "reCAPTCHA + filtrage serveur" },
];

const phase1Backup = [
  { item: "Sauvegarde complète du site avant intervention", how: "Archive fichiers complète" },
  { item: "Sauvegarde de la base de données", how: "Dump SQL avant travaux" },
  { item: "Test de restauration", how: "Contrôle d’intégrité effectué" },
];

const phase2Cache = [
  { item: "Installation d’un plugin de cache avancé (WP Rocket ou équivalent)", how: "LiteSpeed Cache (serveur o2switch)" },
  { item: "Configuration du cache des pages", how: "Cache HTML LiteSpeed" },
  { item: "Configuration du cache navigateur", how: "LiteSpeed + Cloudflare" },
  { item: "Activation de la compression Gzip/Brotli", how: "Origine + CDN Brotli" },
];

const phase2Images = [
  { item: "Conversion automatique au format WebP", how: "Optimisation images LiteSpeed" },
  { item: "Compression optimisée sans perte visible", how: "Compression automatique" },
  { item: "Activation du lazy loading sur toutes les images", how: "Lazy load natif + plugin" },
  { item: "Génération des tailles responsive (srcset)", how: "srcset WordPress / WooCommerce" },
];

const phase2Files = [
  { item: "Minification des fichiers CSS", how: "Optimisation LiteSpeed" },
  { item: "Minification des fichiers JavaScript", how: "Optimisation LiteSpeed" },
  { item: "Combinaison des fichiers lorsque possible", how: "Regroupement contrôlé" },
  { item: "Chargement asynchrone des scripts non essentiels", how: "Différé / asynchrone" },
  { item: "Suppression des scripts inutilisés", how: "Exclusions et nettoyage" },
];

const phase2Server = [
  { item: "Mise en place d’un CDN (Cloudflare)", how: "Zone Cloudflare active" },
  { item: "Optimisation du temps de réponse serveur", how: "Cache page + CDN + OPcache" },
  { item: "Activation d’OPcache PHP", how: "OPcache hébergeur o2switch" },
  { item: "Optimisation des requêtes base de données", how: "Requêtes et index nettoyés" },
  { item: "Nettoyage de la base de données (révisions, transients, spam)", how: "Révisions / transients / spam" },
];

const phase3Seo = [
  { item: "Ajout de la balise H1 sur la page d’accueil", how: "H1 présent sur l’accueil" },
  { item: "Correction de la hiérarchie des titres (H1, H2, H3, H4, H5, H6)", how: "Hiérarchie revue" },
  { item: "Optimisation des meta descriptions (150-160 caractères)", how: "Meta descriptions ajustées" },
  { item: "Optimisation des meta titres", how: "Balises title optimisées" },
  { item: "Configuration des balises Open Graph (Facebook, LinkedIn)", how: "Open Graph configuré" },
  { item: "Configuration des Twitter Cards", how: "Twitter Cards configurées" },
  { item: "Optimisation du fichier robots.txt", how: "robots.txt contrôlé" },
  { item: "Optimisation du sitemap XML", how: "Sitemap XML à jour" },
  { item: "Configuration du lien canonique", how: "URL canoniques actives" },
];

const phase3A11y = [
  { item: "Ajout d’attributs alt descriptifs sur les 262 images", how: "Alt renseignés / générés" },
  { item: "Amélioration des contrastes de couleur", how: "Contrastes renforcés" },
  { item: "Optimisation de la navigation au clavier", how: "Parcours clavier vérifié" },
  { item: "Ajout des ARIA labels lorsque nécessaire", how: "ARIA sur actions clés" },
  { item: "Tests avec lecteurs d’écran", how: "Contrôles d’accessibilité" },
];

const cfDns = [
  { item: "Rattachement du domaine globalmateriel.ma à Cloudflare", how: "Zone DNS Cloudflare" },
  { item: "Proxy CDN activé (nuage orange) sur les enregistrements du site", how: "Trafic filtré et mis en cache" },
  { item: "Masquage de l’adresse IP du serveur d’origine (o2switch)", how: "IP origine non exposée" },
];

const cfSsl = [
  { item: "Chiffrement SSL/TLS de bout en bout", how: "Cloudflare + Let’s Encrypt origine" },
  { item: "Always Use HTTPS / redirection HTTP → HTTPS", how: "HTTPS forcé au CDN" },
  { item: "Protocole TLS 1.3", how: "TLS 1.3 actif" },
  { item: "HSTS — max-age 1 an", how: "Strict-Transport-Security" },
];

const cfPerf = [
  { item: "Réseau CDN mondial (points de présence Cloudflare)", how: "Distribution des assets" },
  { item: "Compression Brotli au niveau CDN", how: "Brotli activé" },
  { item: "Cache des ressources statiques (CSS, JS, images, polices)", how: "Cache navigateur + edge" },
  { item: "HTTP/2 et HTTP/3 (QUIC)", how: "Protocoles modernes" },
];

const cfSec = [
  { item: "En-têtes de sécurité HTTP appliqués en périphérie", how: "HSTS, CSP, X-CTO, XFO, Referrer, Permissions" },
  { item: "Pare-feu applicatif / filtrage du trafic malveillant", how: "Règles de sécurité Cloudflare" },
  { item: "Protection bots et atténuation des attaques automatisées", how: "Filtrage bots CDN" },
];

const shortcodes = [
  ["Page de devis (checkout)", "Tunnel complet : connexion, code promo, coordonnées, récapitulatif et bouton « Demander Un Devis ».", "[custom_checkout_page]"],
  ["Tableau du panier", "Panier personnalisé (produit, quantité AJAX, suppression) avec « Continuer mes achats » et « Suivant ».", "[custom_cart_table]"],
  ["Liste de souhaits", "Tableau wishlist (image, nom, prix, ajout au panier, suppression). Nécessite YITH Wishlist.", "[custom_wishlist_table]"],
  ["Grille boutique", "Listing produits : H1, compteur, vues grille/liste, tri, chargement infini. Boutique, catégories, recherche.", "[custom_shop_products]"],
  ["Données fiche produit", "Tableau « Références et Dimensions » et PDF « Documentation Technique » du produit en cours.", "[custom_product_data]"],
  ["Carrousel de catégories", "Catégories enfants de la page en cours (boutique = racines ; catégorie = sous-catégories).", "[custom_category_carousel]"],
  ["Menu latéral catégories", "Accordéon des catégories racines et enfants. Surlignage de la catégorie active.", "[category_sidebar]"],
  ["Menu latéral (titre)", "Identique, titre personnalisable.", '[category_sidebar title="CATÉGORIES DE PRODUITS"]'],
  ["Produits associés", "Carrousel des produits liés à la fiche produit affichée.", "[related_products_carousel]"],
  ["Produits associés (options)", "Nombre d’articles et titre personnalisables.", '[related_products_carousel limit="12" title="Produits associés"]'],
  ["Produits d’une catégorie", "Derniers produits d’une catégorie WooCommerce (ID obligatoire).", '[category_products_carousel category_id="358"]'],
  ["Produits d’une catégorie (complet)", "Limite et titre en plus de l’ID de catégorie.", '[category_products_carousel category_id="358" limit="12" title=""]'],
  ["Produits en promotion", "Carrousel des produits en solde, ajout au panier AJAX.", "[promo_products_carousel]"],
  ["Promotions (options)", "Limite et titre personnalisables.", '[promo_products_carousel limit="10" title="PROMOTIONS"]'],
  ["Slider page d’accueil", "Diaporama à partir des identifiants d’images de la médiathèque.", '[homepage_slider images="123,456,789"]'],
  ["Catégories associées", "Boutique : catégories racines. Catégorie : enfants, ou sœurs si aucun enfant.", "[category_carousel]"],
  ["Catégories associées (titre)", "Identique, titre optionnel.", '[category_carousel title=""]'],
];

const children = [];

children.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: 80, before: 200 },
  children: [run("GLOBAL MATÉRIEL", { size: 22, bold: true, color: ORANGE })],
}));
children.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: 200 },
  children: [run("https://globalmateriel.ma/", { size: 20, color: MUTED })],
}));
children.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: 280 },
  children: [run("RÉCAPITULATIF TECHNIQUE DE LIVRAISON", { size: 36, bold: true, color: NAVY })],
}));
children.push(new Paragraph({
  alignment: AlignmentType.CENTER,
  spacing: { after: 360 },
  border: { bottom: { style: BorderStyle.SINGLE, size: 16, color: ORANGE, space: 8 } },
  children: [run("Dossier de clôture — audit, sécurisation, performances, SEO et personnalisations e-commerce", { size: 20, italics: true, color: MUTED })],
}));

children.push(coverMeta("Date", "31 août 2026"));
children.push(coverMeta("Référence", "Proposition commerciale du 7 juillet 2026"));
children.push(coverMeta("Montant négocié et accepté", "4 000,00 DHS TTC"));
children.push(coverMeta("Acompte reçu (50 %)", "2 000,00 DHS TTC"));
children.push(coverMeta("Solde à réception (50 %)", "2 000,00 DHS TTC"));
children.push(spacer(280));

children.push(h1("1. Objet du document"));
children.push(p("Madame, Monsieur,"));
children.push(p("Le présent document constitue le récapitulatif technique de livraison du projet d’intervention sur le site globalmateriel.ma. Il formalise l’ensemble des travaux réalisés afin de permettre la clôture du projet et le règlement du solde, conformément à la proposition commerciale du 7 juillet 2026 et à la négociation acceptée portant le montant total à 4 000,00 DHS TTC."));
children.push(p("Pour chaque intervention prévue au devis, un tableau de conformité indique le libellé exact de la proposition commerciale, la mise en œuvre retenue, et un statut « fait » (✓)."));

children.push(h1("2. Contexte et périmètre"));
children.push(h2("2.1 Mission initiale"));
children.push(p("La proposition commerciale du 7 juillet 2026 prévoyait une intervention en trois phases :"));
children.push(bullet("Phase 1 — Sécurisation du site (priorité critique)"));
children.push(bullet("Phase 2 — Optimisation des performances"));
children.push(bullet("Phase 3 — Optimisation SEO et accessibilité"));
children.push(p("Score d’audit initial (7 juillet 2026) : 4,25 / 10 — Performance 4/10, Sécurité 3/10, SEO 6/10, Accessibilité 4/10."));

children.push(h2("2.2 Livrable principal"));
children.push(p("Les développements, corrections et personnalisations sont regroupés dans le plugin WordPress dédié « Global Matériel » (version 1.1.0), compatible WordPress 5.8+, PHP 7.4+ et WooCommerce 5.0+, y compris les tables de commandes HPOS. Ce plugin porte la logique e-commerce, les shortcodes, les templates WooCommerce, les styles, les scripts et une partie des mesures de sécurité. Le contenu éditorial (catalogue, pages, commandes, médias) n’a pas été altéré."));

children.push(h2("2.3 Conservation de l’identité visuelle (exigence client)"));
children.push(p("Sur demande expresse du client, l’identité visuelle existante a été intégralement conservée sur l’ensemble du site : page d’accueil, boutique, fiches produits, panier, tunnel de devis, compte client, liste de souhaits, en-tête, pied de page et parcours mobile. Toutes les personnalisations fonctionnelles ont été développées pour reproduire fidèlement le design d’origine, sans rupture d’expérience pour les visiteurs."));

children.push(h1("3. Architecture technique livrée"));
children.push(p("Le plugin est organisé en modules chargés uniquement si WooCommerce est actif."));
children.push(spacer(80));
children.push(dataTable(
  ["Module", "Rôle"],
  [
    ["Sécurité", "API REST, XML-RPC, énumération utilisateurs, limitation des connexions, masquage des versions"],
    ["Cache", "Purge LiteSpeed, exclusions JS critiques, bypass cache CSS/JS (barre d’admin)"],
    ["Checkout / devis", "Tunnel transformé en demande de devis, sans encaissement"],
    ["Panier", "Tableau personnalisé, quantités en AJAX"],
    ["Mini-panier", "Sélecteur ± et bouton « Mettre à jour » dans le menu"],
    ["Boutique", "Grille, vues, tri, chargement infini, recherche"],
    ["Catalogue", "Devise DHS, produits à 0,00 DHS achetable"],
    ["Compte client", "Connexion / inscription en français, accessibilité"],
    ["Liste de souhaits", "Tableau personnalisé + badge compteur"],
    ["Carrousels & sliders", "Accueil, catégories, promotions, produits associés, sidebar"],
    ["Fiche produit", "Références / dimensions, PDF, attributs en texte"],
    ["WhatsApp", "Bouton flottant configurable en administration"],
  ],
  [2800, 6840]
));
children.push(spacer(160));
children.push(p("Templates WooCommerce livrés : récapitulatif de commande (quantités ±, suppression, sans total — parcours devis), bloc paiement avec bouton « Demander Un Devis », formulaire de connexion / inscription client."));

children.push(h1("4. Phase 1 — Sécurisation du site"));
children.push(p("Interventions techniques prévues à la section 5.1 de la proposition commerciale. Toutes les lignes ci-dessous sont réalisées (✓)."));

children.push(h2("4.1 En-têtes de sécurité HTTP"));
children.push(p("Ces en-têtes, absents lors de l’audit, sont appliqués en périphérie via Cloudflare (voir section 5.5)."));
children.push(checkTable(phase1Headers));

children.push(h2("4.2 Sécurisation des cookies"));
children.push(checkTable(phase1Cookies));

children.push(h2("4.3 Protection de l’API WordPress"));
children.push(checkTable(phase1Api));
children.push(spacer(80));
children.push(p("Note : l’API REST reste accessible de façon contrôlée aux services nécessaires au fonctionnement du site (Google Site Kit, MonsterInsights, WooCommerce Store API), afin de ne pas casser le suivi analytics ni le mini-panier."));

children.push(h2("4.4 Sécurisation des formulaires"));
children.push(checkTable(phase1Forms));

children.push(h2("4.5 Sauvegarde préalable"));
children.push(checkTable(phase1Backup));

children.push(h1("5. Phase 2 — Optimisation des performances"));
children.push(p("Interventions techniques prévues à la section 5.2 de la proposition commerciale. Toutes les lignes ci-dessous sont réalisées (✓)."));

children.push(h2("5.1 Mise en place du cache"));
children.push(p("L’hébergeur o2switch (offre PowerBoost) repose sur LiteSpeed : l’équivalent de WP Rocket retenu est LiteSpeed Cache, nativement adapté à cette infrastructure."));
children.push(checkTable(phase2Cache));

children.push(h2("5.2 Optimisation des images"));
children.push(checkTable(phase2Images));

children.push(h2("5.3 Optimisation des fichiers"));
children.push(checkTable(phase2Files));
children.push(spacer(80));
children.push(p("Les scripts critiques (galeries produit, carrousels) sont exclus du chargement différé afin d’éviter les erreurs d’affichage tout en conservant l’optimisation du reste des fichiers."));

children.push(h2("5.4 Optimisation serveur"));
children.push(checkTable(phase2Server));

children.push(h2("5.5 Intégration et configuration Cloudflare"));
children.push(p("Conformément à la proposition commerciale (« Mise en place d’un CDN — Cloudflare »), le domaine globalmateriel.ma a été intégré à Cloudflare. Le CDN se situe entre le visiteur et le serveur o2switch : il accélère la livraison des pages, absorbe une partie du trafic, applique les en-têtes de sécurité et masque l’IP d’origine."));

children.push(h3("DNS et proxy CDN"));
children.push(checkTable(cfDns));
children.push(h3("SSL / TLS et HTTPS"));
children.push(checkTable(cfSsl));
children.push(h3("Performances CDN"));
children.push(checkTable(cfPerf));
children.push(h3("Sécurité en périphérie"));
children.push(checkTable(cfSec));
children.push(spacer(120));
children.push(p("Cette configuration Cloudflare couvre, au niveau réseau, les en-têtes de sécurité de la Phase 1 et le volet CDN / compression / cache navigateur de la Phase 2, en complément de LiteSpeed Cache sur le serveur d’origine."));

children.push(h1("6. Phase 3 — Optimisation SEO et accessibilité"));
children.push(p("Interventions techniques prévues à la section 5.3 de la proposition commerciale. Toutes les lignes ci-dessous sont réalisées (✓)."));

children.push(h2("6.1 Optimisation SEO"));
children.push(checkTable(phase3Seo));

children.push(h2("6.2 Optimisation de l’accessibilité"));
children.push(checkTable(phase3A11y));
children.push(spacer(80));
children.push(p("Sur les listings générés par le plugin, un titre H1 contextuel est produit (Boutique, nom de catégorie, ou résultats de recherche). Les images des grilles, carrousels et sliders reçoivent un attribut alt (nom du produit, de la catégorie, ou texte alternatif WordPress). Les actions clés (navigation carrousel, suppression panier / wishlist, compteur de résultats, champs de connexion) disposent de labels ARIA."));

children.push(h1("7. Personnalisations métier réalisées"));
children.push(p("En complément des trois phases du devis, le parcours e-commerce a été adapté au modèle commercial de Global Matériel (demande de devis, catalogue B2B) tout en conservant le design d’origine exigé par le client."));

children.push(h2("7.1 Tunnel de commande transformé en demande de devis"));
children.push(bullet("Bouton de validation libellé « Demander Un Devis » (et non « Commander » / « Payer »)."));
children.push(bullet("Passerelle interne silencieuse : la demande est enregistrée au statut « En attente », sans collecte de paiement."));
children.push(bullet("Aucun autre moyen de paiement n’est proposé au visiteur."));
children.push(bullet("Le panier est vidé après envoi de la demande."));
children.push(bullet("Note interne de commande : « Demande de devis reçue. Aucun paiement n’a été collecté. »"));
children.push(p([run("Formulaire de facturation : ", { bold: true }), run("ordre Société → Prénom → Nom → Adresse → Ville → Code postal → Pays → E-mail → Téléphone ; suppression du complément d’adresse et du champ État / Région ; code postal facultatif ; connexion client et code promo en accordéons.")], { after: 120 }));
children.push(p([run("Récapitulatif : ", { bold: true }), run("colonnes nom du produit (vignette + UGS), quantité −/+, suppression ; bouton « Mettre à jour » uniquement si une quantité a changé ; pas de total financier (cohérent avec un devis).")]));

children.push(h2("7.2 Panier et mini-panier"));
children.push(bullet("Tableau panier épuré (produit, quantité, suppression), sans colonnes prix / total."));
children.push(bullet("Mise à jour des quantités en AJAX, avec temporisation."));
children.push(bullet("Boutons « Continuer mes achats » et « Suivant »."));
children.push(bullet("Mini-panier du menu : sélecteur −/+ et bouton « Mettre à jour » visible seulement en cas de modification."));

children.push(h2("7.3 Boutique et catalogue"));
children.push(bullet("Grille 4 colonnes, adaptative mobile ; vues grille / liste mémorisées."));
children.push(bullet("Compteur de résultats ; 24 / 48 / 72 / 96 produits ; tris (popularité, date, prix, ventes, vues, réduction)."));
children.push(bullet("Chargement infini au scroll (AJAX), y compris en recherche et en catégorie."));
children.push(bullet("Image de survol, actions rapides (aperçu / panier), référence produit, lien « + En savoir plus »."));
children.push(bullet("Devise MAD affichée en « DHS »."));
children.push(bullet("Produits à 0,00 DHS : restent achetable et en stock (demande de devis possible sans prix public)."));

children.push(h2("7.4 Fiche produit"));
children.push(bullet("Bloc « Références et Dimensions » et jusqu’à 3 PDF « Documentation Technique »."));
children.push(bullet("Attributs WooCommerce en texte (sans lien vers les archives d’attributs)."));
children.push(bullet("Masquage automatique des onglets Description / Informations complémentaires s’ils sont vides."));
children.push(bullet("Prix promotionnel affiché sur deux lignes."));

children.push(h2("7.5 Compte client, liste de souhaits et WhatsApp"));
children.push(bullet("Formulaire de connexion / inscription en français, champs obligatoires signalés (visuel + lecteur d’écran)."));
children.push(bullet("Tableau wishlist au même langage visuel que le panier ; badge compteur en en-tête (AJAX)."));
children.push(bullet("Bouton WhatsApp flottant ; numéro modifiable dans Réglages → Global Matériel."));

children.push(h2("7.6 Navigation catalogue"));
children.push(bullet("Slider d’accueil, carrousel de catégories, menu latéral accordéon, promotions, produits d’une catégorie, produits associés, catégories associées."));
children.push(p("Tous ces blocs reprennent le langage visuel d’origine (couleurs, typographie, espacements, comportement mobile)."));

children.push(h1("8. Inventaire des shortcodes"));
children.push(p("Shortcodes à insérer dans Elementor (widget Shortcode) ou dans le contenu WordPress."));
children.push(spacer(80));
children.push(dataTable(
  ["Bloc", "Description", "Code du shortcode"],
  shortcodes,
  [2200, 4300, 3140]
));
children.push(h3("Paramètres utilisables"));
children.push(dataTable(
  ["Paramètre", "Shortcodes concernés", "Rôle"],
  [
    ["title", "category_sidebar, related_products_carousel, category_products_carousel, promo_products_carousel, category_carousel", "Titre affiché au-dessus du bloc"],
    ["limit", "related_products_carousel, category_products_carousel, promo_products_carousel", "Nombre maximum de produits"],
    ["category_id", "category_products_carousel", "Identifiant WooCommerce de la catégorie (obligatoire)"],
    ["images", "homepage_slider", "IDs d’images de la médiathèque, séparés par des virgules (obligatoire)"],
  ],
  [1800, 4300, 3540]
));

children.push(h1("9. Points d’administration"));
children.push(dataTable(
  ["Emplacement", "Fonction"],
  [
    ["Réglages → Global Matériel", "Numéro WhatsApp du bouton flottant"],
    ["Barre d’administration WordPress", "Bypass cache CSS/JS (chaque chargement ou chaque heure)"],
    ["WooCommerce → Commandes", "Demandes de devis au statut « En attente », sans paiement"],
    ["Médiathèque", "IDs d’images pour le slider d’accueil"],
    ["Produits → Catégories", "ID de catégorie pour [category_products_carousel]"],
    ["Tableau de bord Cloudflare", "CDN, HTTPS, en-têtes de sécurité, cache périphérie"],
    ["LiteSpeed Cache", "Cache pages, images WebP, minification, purge après Elementor"],
  ],
  [3600, 6040]
));

children.push(h1("10. Garanties et exploitation"));
children.push(p("Conformément à la proposition commerciale du 7 juillet 2026 :"));
children.push(dataTable(
  ["Engagement", "Statut"],
  [
    ["Garantie technique de 3 mois sur l’ensemble des interventions", "En vigueur à réception"],
    ["Support technique inclus 30 jours après livraison", "En vigueur à réception"],
    ["Aucun impact sur le contenu existant", "Respecté"],
    ["Identité visuelle conservée sur tout le site (exigence client)", "Respecté"],
    ["Confidentialité des accès et des données", "Respecté"],
  ],
  [7000, 2640]
));
children.push(h3("Recommandations d’exploitation"));
children.push(bullet("Conserver le plugin Global Matériel activé : il porte le checkout devis, les shortcodes et une partie de la sécurité."));
children.push(bullet("Après mise à jour ou modification Elementor, purger le cache LiteSpeed (ou activer le bypass le temps de vérifier)."));
children.push(bullet("Ne pas désactiver WooCommerce : le plugin en dépend."));
children.push(bullet("WhatsApp : Réglages → Global Matériel."));
children.push(bullet("Nouveau carrousel de catégorie : [category_products_carousel category_id=\"XXXX\"]."));
children.push(bullet("Ne pas désactiver le proxy Cloudflare sur le domaine de production (perte du CDN et des en-têtes de sécurité périphériques)."));

children.push(h1("11. Récapitulatif financier"));
children.push(p("Le montant initial de la proposition commerciale du 7 juillet 2026 s’élevait à 4 500,00 DHS TTC. Une négociation a été acceptée par les deux parties, portant le montant total de la mission à 4 000,00 DHS TTC, réglable en deux versements égaux."));
children.push(spacer(80));
children.push(dataTable(
  ["Échéance", "Montant TTC"],
  [
    ["Montant négocié et accepté — total mission", "4 000,00 DHS"],
    ["Acompte 50 % — déjà reçu", "2 000,00 DHS"],
    ["Solde 50 % — à la livraison / réception", "2 000,00 DHS"],
  ],
  [6800, 2840]
));
children.push(spacer(160));
children.push(p([run("Mode de règlement : ", { bold: true }), run("virement bancaire.")]));
children.push(p("RIB : 350810000000000060701512 — TARIK BOUKJIJ — AL BARID BANK"));

children.push(h1("12. Réception et clôture"));
children.push(p("Le projet est techniquement livré. Le présent dossier décrit l’intégralité des développements, sécurisations, optimisations, intégration Cloudflare et personnalisations réalisés, en regard de chaque intervention de la proposition commerciale."));
children.push(p("La signature ci-dessous vaut réception des travaux et autorise le versement du solde de 2 000,00 DHS TTC."));
children.push(spacer(280));
children.push(p("Fait à _________________________, le _________________________"));
children.push(spacer(400));

const signW = [4820, 4820];
children.push(new Table({
  width: { size: CONTENT_W, type: WidthType.DXA },
  columnWidths: signW,
  rows: [new TableRow({
    children: [
      new TableCell({
        borders: noBorders,
        width: { size: signW[0], type: WidthType.DXA },
        children: [
          new Paragraph({ children: [run("Le client", { bold: true, size: 21, color: NAVY })] }),
          new Paragraph({ spacing: { after: 80 }, children: [run("(Signature et cachet)", { size: 19, color: MUTED })] }),
          new Paragraph({ spacing: { after: 200 }, children: [run("Précédé de la mention « Bon pour accord — travaux réceptionnés »", { size: 18, italics: true, color: MUTED })] }),
          new Paragraph({ spacing: { before: 400 }, border: { top: { style: BorderStyle.SINGLE, size: 6, color: LINE, space: 8 } }, children: [run(" ", { size: 18 })] }),
        ],
      }),
      new TableCell({
        borders: noBorders,
        width: { size: signW[1], type: WidthType.DXA },
        children: [
          new Paragraph({ children: [run("Le prestataire", { bold: true, size: 21, color: NAVY })] }),
          new Paragraph({ spacing: { after: 80 }, children: [run("(Signature)", { size: 19, color: MUTED })] }),
          new Paragraph({ spacing: { after: 200 }, children: [run("Tarik BOUKJIJ", { size: 18, color: MUTED })] }),
          new Paragraph({ spacing: { before: 400 }, border: { top: { style: BorderStyle.SINGLE, size: 6, color: LINE, space: 8 } }, children: [run(" ", { size: 18 })] }),
        ],
      }),
    ],
  })],
}));

const doc = new Document({
  numbering: {
    config: [{
      reference: "bullets",
      levels: [{
        level: 0,
        format: LevelFormat.BULLET,
        text: "•",
        alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 360, hanging: 180 } } },
      }],
    }],
  },
  styles: {
    default: { document: { run: { font: "Calibri", size: 21 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickStyle: true, paragraph: { outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickStyle: true, paragraph: { outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickStyle: true, paragraph: { outlineLevel: 2 } },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: PAGE_W, height: 16838 },
        margin: { top: 1418, bottom: 1134, left: MARGIN, right: MARGIN, header: 567, footer: 567 },
      },
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: ORANGE, space: 6 } },
          spacing: { after: 80 },
          children: [
            run("Global Matériel  —  Récapitulatif technique de livraison", { size: 16, color: NAVY, bold: true }),
            run("          ", { size: 16 }),
            run("Confidentiel", { size: 16, color: MUTED, italics: true }),
          ],
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          border: { top: { style: BorderStyle.SINGLE, size: 6, color: LINE, space: 8 } },
          spacing: { before: 80 },
          children: [
            run("Proposition commerciale du 7 juillet 2026  ·  Solde 2 000,00 DHS TTC  ·  Page ", { size: 15, color: MUTED }),
            new TextRun({ children: [PageNumber.CURRENT], font: "Calibri", size: 15, color: MUTED }),
            run(" / ", { size: 15, color: MUTED }),
            new TextRun({ children: [PageNumber.TOTAL_PAGES], font: "Calibri", size: 15, color: MUTED }),
          ],
        })],
      }),
    },
    children,
  }],
});

const out = path.resolve(__dirname, "..", "RECAPITULATIF TECHNIQUE LIVRAISON.docx");

Packer.toBuffer(doc).then((buffer) => {
  fs.writeFileSync(out, buffer);
  console.log("OK " + out);
}).catch((err) => {
  console.error(err);
  process.exit(1);
});
