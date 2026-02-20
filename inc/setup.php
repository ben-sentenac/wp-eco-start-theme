<?php
/**
 * Configuration principale du thème
 *
 * - add_theme_support()
 * - Menus de navigation
 * - Sidebars (optionnelles, full-width par défaut)
 * - Tailles d'images
 * - Divers hooks de nettoyage WordPress
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// THEME SUPPORT
// =============================================================================

add_action('after_setup_theme', function (): void {

    /**
     * Traductions — doit être la première chose appelée
     * Le dossier languages/ contient les fichiers .po/.mo/.pot
     */
    load_theme_textdomain('eco-starter', ECO_STARTER_DIR . '/languages');

    /**
     * Synchronisation du titre avec WordPress (balise <title> gérée par WP)
     * Permet à SEO natif + Yoast/RankMath de contrôler le titre sans conflit
     */
    add_theme_support('title-tag');

    /**
     * Images mises en avant sur tous les post types
     */
    add_theme_support('post-thumbnails');

    /**
     * Formats d'articles (utile pour un blog riche)
     * Formats activés : uniquement ceux qu'on va vraiment utiliser
     */
    add_theme_support('post-formats', [
        'aside',
        'gallery',
        'video',
        'quote',
        'link',
    ]);

    /**
     * Support HTML5 — évite les attributs type="text/css" et type="text/javascript"
     * + balises sémantiques pour les éléments WordPress natifs
     */
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
        'navigation-widgets',
    ]);

    /**
     * Logo personnalisé via le Customizer
     * flex-width + flex-height = pas de contrainte imposée, le client uploade ce qu'il veut
     */
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => ['site-title', 'site-description'],
        'unlink-homepage-logo' => true, // Pas de double lien <a> sur la home
    ]);

    /**
     * Couleur de fond personnalisable (Customizer > Couleurs)
     */
    add_theme_support('custom-background', [
        'default-color' => 'ffffff',
    ]);

    /**
     * Largeur maximale du contenu — utilisée par WordPress pour les embeds
     * À ajuster selon le design client
     */
    $GLOBALS['content_width'] = 1200;

    /**
     * Éditeur Gutenberg — alignements larges et pleine largeur
     * Permet les blocs "wide" et "full" dans l'éditeur
     */
    add_theme_support('align-wide');

    /**
     * Gutenberg — désactive les styles de blocs par défaut
     * On gère nos propres styles, on ne veut pas les .wp-block-* de WordPress
     */
    add_theme_support('disable-custom-colors');
    add_theme_support('editor-color-palette', eco_get_editor_palette());

    /**
     * Gutenberg — styles de l'éditeur synchronisés avec le front
     * Le fichier editor-style.css sera créé dans assets/css/
     */
    add_editor_style('assets/css/editor-style.css');

    /**
     * Flux RSS auto-découverte (head)
     */
    add_theme_support('automatic-feed-links');

    /**
     * WooCommerce — déclaration explicite de la compatibilité
     * Sans ça, WooCommerce affiche un warning et force ses propres styles
     */
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

}, 10);


// =============================================================================
// PALETTE GUTENBERG
// Centralisée ici, synchronisée avec les tokens CSS
// =============================================================================

/**
 * Retourne la palette de couleurs pour l'éditeur Gutenberg
 * Doit correspondre aux variables CSS dans assets/css/base/tokens.css
 *
 * @return array
 */
function eco_get_editor_palette(): array
{
    return [
        [
            'name'  => __('Primaire', 'eco-starter'),
            'slug'  => 'primary',
            'color' => '#2563eb',
        ],
        [
            'name'  => __('Primaire sombre', 'eco-starter'),
            'slug'  => 'primary-dark',
            'color' => '#1d4ed8',
        ],
        [
            'name'  => __('Secondaire', 'eco-starter'),
            'slug'  => 'secondary',
            'color' => '#16a34a',
        ],
        [
            'name'  => __('Texte principal', 'eco-starter'),
            'slug'  => 'foreground',
            'color' => '#1a1a1a',
        ],
        [
            'name'  => __('Texte atténué', 'eco-starter'),
            'slug'  => 'muted',
            'color' => '#6b7280',
        ],
        [
            'name'  => __('Fond', 'eco-starter'),
            'slug'  => 'background',
            'color' => '#ffffff',
        ],
        [
            'name'  => __('Fond alternatif', 'eco-starter'),
            'slug'  => 'surface',
            'color' => '#f9fafb',
        ],
        [
            'name'  => __('Bordure', 'eco-starter'),
            'slug'  => 'border',
            'color' => '#e5e7eb',
        ],
        [
            'name'  => __('Erreur', 'eco-starter'),
            'slug'  => 'error',
            'color' => '#dc2626',
        ],
        [
            'name'  => __('Succès', 'eco-starter'),
            'slug'  => 'success',
            'color' => '#16a34a',
        ],
    ];
}


// =============================================================================
// MENUS DE NAVIGATION
// =============================================================================

add_action('after_setup_theme', function (): void {

    register_nav_menus([
        'primary'   => __('Navigation principale', 'eco-starter'),
        'footer'    => __('Navigation pied de page', 'eco-starter'),
        'secondary' => __('Navigation secondaire (haut de page)', 'eco-starter'),
        'mobile'    => __('Navigation mobile (si différente)', 'eco-starter'),
    ]);

}, 10);


// =============================================================================
// SIDEBARS
// Full-width par défaut — les sidebars sont déclarées mais inactives
// Activation via le Customizer (inc/customizer.php) ou hook enfant
// =============================================================================

add_action('widgets_init', function (): void {

    /**
     * Sidebar principale
     * Affichée uniquement si le template de page la demande
     * ET si l'option Customizer "sidebar_enabled" est activée
     */
    register_sidebar([
        'name'          => __('Sidebar principale', 'eco-starter'),
        'id'            => 'sidebar-main',
        'description'   => __('Sidebar optionnelle. Activez-la via Apparence > Personnaliser > Mise en page.', 'eco-starter'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ]);

    /**
     * Sidebar blog
     * Spécifique aux archives et articles
     */
    register_sidebar([
        'name'          => __('Sidebar blog', 'eco-starter'),
        'id'            => 'sidebar-blog',
        'description'   => __('Sidebar affichée sur les articles et archives du blog.', 'eco-starter'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ]);

    /**
     * Footer — 3 colonnes
     * Structure : footer-col-1 / footer-col-2 / footer-col-3
     */
    for ($i = 1; $i <= 3; $i++) {
        register_sidebar([
            'name'          => sprintf(__('Pied de page — Colonne %d', 'eco-starter'), $i),
            'id'            => 'footer-col-' . $i,
            'description'   => sprintf(__('Zone de widgets dans la colonne %d du footer.', 'eco-starter'), $i),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget__title">',
            'after_title'   => '</h3>',
        ]);
    }

});


// =============================================================================
// TAILLES D'IMAGES
// Nommées de façon sémantique, pas technique
// =============================================================================

add_action('after_setup_theme', function (): void {

    // Désactive les tailles WordPress par défaut qu'on ne veut pas générer
    // (économie de stockage + temps de traitement)
    add_filter('intermediate_image_sizes_advanced', function (array $sizes): array {
        unset(
            $sizes['medium_large'], // 768px — rarement utilisé
        );
        return $sizes;
    });

    /**
     * Tailles personnalisées
     * Format : add_image_size(slug, largeur, hauteur, crop)
     * crop = false → proportionnel | true → crop centré | [x, y] → crop positionné
     */

    // Vignette pour les listes d'articles, cards CPT
    add_image_size('eco-card', 600, 400, true);

    // Image mise en avant dans un article / page standard
    add_image_size('eco-featured', 1200, 630, true); // Ratio OG image = bonus SEO

    // Image hero pleine largeur (above the fold)
    add_image_size('eco-hero', 1920, 800, true);

    // Portrait pour CPT Équipe / Témoignages
    add_image_size('eco-portrait', 400, 500, true);

    // Miniature carrée (galeries, WooCommerce secondary)
    add_image_size('eco-square', 300, 300, true);

    // Large pour le contenu éditorial pleine colonne
    add_image_size('eco-content', 900, 0, false); // hauteur libre

}, 10);


// =============================================================================
// NETTOYAGE DU <head>
// Suppression des balises inutiles pour alléger le HTML et réduire les infos exposées
// =============================================================================

add_action('init', function (): void {

    // Supprime le lien vers le flux RSS des commentaires (rarement utile)
    remove_action('wp_head', 'feed_links_extra', 3);

    // Supprime les liens RSD et Windows Live Writer (obsolètes)
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');

    // Supprime le lien "prev" pour la page d'accueil (inutile)
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'parent_post_rel_link', 10);
    remove_action('wp_head', 'start_post_rel_link', 10);
    remove_action('wp_head', 'adjacent_posts_rel_link', 10);
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

    // Supprime la version WordPress (sécurité + éco)
    remove_action('wp_head', 'wp_generator');

    // Supprime les emoji WordPress (charge ~10kb de JS/CSS inutiles)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    // Supprime le lien shortlink (inutile si on a des URLs propres)
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);

    // Supprime oEmbed discovery links (si on n'expose pas le thème en tant que source oEmbed)
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

});


// =============================================================================
// BODY CLASS — classes utiles pour le CSS
// =============================================================================

add_filter('body_class', function (array $classes): array {

    // Sidebar active ou non — permet au CSS de basculer la mise en page
    $sidebar_enabled = get_theme_mod('sidebar_enabled', false);
    $has_sidebar     = $sidebar_enabled && is_active_sidebar('sidebar-main');

    $classes[] = $has_sidebar ? 'layout--sidebar' : 'layout--fullwidth';

    // Type de page courant (utile pour CSS ciblé sans sélecteurs JS)
    if (is_singular()) {
        $classes[] = 'is-singular';
        $classes[] = 'post-type--' . get_post_type();
    }

    if (is_archive() || is_home()) {
        $classes[] = 'is-archive';
    }

    // Mode maintenance
    if (get_theme_mod('maintenance_mode', false) && !current_user_can('manage_options')) {
        $classes[] = 'is-maintenance';
    }

    return $classes;
});


// =============================================================================
// EXCERPT — longueur et suite personnalisées
// =============================================================================

// Longueur par défaut : 20 mots (plus court = plus performant pour les cards)
add_filter('excerpt_length', fn(): int => 20, 999);

// Remplacement du "[...]" de WordPress par une ellipse propre
add_filter('excerpt_more', fn(): string => '…');