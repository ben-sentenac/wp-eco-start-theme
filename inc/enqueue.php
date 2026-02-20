<?php
/**
 * Enqueue CSS & JS — Eco Starter
 *
 * Stratégie :
 * 1. wp_register_style()  — enregistre TOUS les styles d'abord
 * 2. wp_enqueue_style()   — active ceux dont on a besoin
 *
 * WordPress 6.9+ exige que les dépendances soient enregistrées
 * AVANT d'être référencées dans le tableau $deps d'un autre style.
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// HELPERS
// =============================================================================

/**
 * Retourne le suffixe .min ou chaîne vide selon l'environnement
 */
function eco_asset_suffix(): string
{
    return (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
}

/**
 * Enregistre un style (sans l'activer)
 *
 * @param string   $handle   Identifiant unique
 * @param string   $file     Chemin relatif depuis assets/css/ (sans extension)
 * @param string[] $deps     Dépendances (handles déjà enregistrés)
 * @param string   $media    Médias CSS
 */
function eco_register_style(
    string $handle,
    string $file,
    array $deps  = [],
    string $media = 'all'
): void {
    $suffix = eco_asset_suffix();
    $src    = eco_asset("assets/css/{$file}{$suffix}.css");
    $path   = ECO_STARTER_DIR . "/assets/css/{$file}{$suffix}.css";

    // Fallback sur la version non-minifiée si .min absente
    if ($suffix && !file_exists($path)) {
        $src  = eco_asset("assets/css/{$file}.css");
        $path = ECO_STARTER_DIR . "/assets/css/{$file}.css";
    }

    $version = file_exists($path) ? (string) filemtime($path) : ECO_STARTER_VERSION;

    wp_register_style("eco-{$handle}", $src, $deps, $version, $media);
}

/**
 * Enregistre ET active un style
 *
 * @param string   $handle   Identifiant unique
 * @param string   $file     Chemin relatif depuis assets/css/ (sans extension)
 * @param string[] $deps     Dépendances (handles déjà enregistrés)
 */
function eco_enqueue_style(
    string $handle,
    string $file,
    array $deps = []
): void {
    eco_register_style($handle, $file, $deps);
    wp_enqueue_style("eco-{$handle}");
}

/**
 * Enregistre ET active un script
 *
 * @param string   $handle    Identifiant unique
 * @param string   $file      Chemin relatif depuis assets/js/ (sans extension)
 * @param string[] $deps      Dépendances
 * @param bool     $in_footer Charger en footer
 * @param string   $strategy  'defer' | 'async' | ''
 */
function eco_enqueue_script(
    string $handle,
    string $file,
    array $deps      = [],
    bool $in_footer  = true,
    string $strategy = 'defer'
): void {
    $suffix  = eco_asset_suffix();
    $src     = eco_asset("assets/js/{$file}{$suffix}.js");
    $path    = ECO_STARTER_DIR . "/assets/js/{$file}{$suffix}.js";

    if ($suffix && !file_exists($path)) {
        $src  = eco_asset("assets/js/{$file}.js");
        $path = ECO_STARTER_DIR . "/assets/js/{$file}.js";
    }

    $version = file_exists($path) ? (string) filemtime($path) : ECO_STARTER_VERSION;

    $args = $in_footer ? ['in_footer' => true] : [];

    if ($strategy && in_array($strategy, ['defer', 'async'], true)) {
        $args['strategy'] = $strategy; // WordPress 6.3+ gère defer/async nativement
    }

    wp_enqueue_script("eco-{$handle}", $src, $deps, $version, $args);
}


// =============================================================================
// FRONT-END STYLES
// =============================================================================

add_action('wp_enqueue_scripts', function (): void {

    // -----------------------------------------------------------------
    // PHASE 1 : Suppression des styles WordPress inutiles
    // -----------------------------------------------------------------
    wp_dequeue_style('wp-block-library');        // CSS Gutenberg en front
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');

    // Dashicons en front (seulement pour visiteurs non connectés)
    if (!is_user_logged_in()) {
        wp_dequeue_style('dashicons');
    }

    // -----------------------------------------------------------------
    // PHASE 2 : Enregistrement de TOUS les styles du thème
    // Ordre important : les dépendances AVANT les dépendants
    // -----------------------------------------------------------------

    // --- Base (aucune dépendance interne) ---
    eco_register_style('tokens',     'base/tokens');
    eco_register_style('reset',      'base/reset',      ['eco-tokens']);
    eco_register_style('typography', 'base/typography', ['eco-tokens', 'eco-reset']);
    eco_register_style('utilities',  'base/utilities',  ['eco-tokens']);
    eco_register_style('print',      'base/print',      [], 'print');

    // --- Composants (dépendent de tokens) ---
    eco_register_style('buttons',    'components/buttons',    ['eco-tokens']);
    eco_register_style('forms',      'components/forms',      ['eco-tokens']);
    eco_register_style('cards',      'components/cards',      ['eco-tokens']);
    eco_register_style('badges',     'components/badges',     ['eco-tokens']);
    eco_register_style('alerts',     'components/alerts',     ['eco-tokens']);
    eco_register_style('navigation', 'components/navigation', ['eco-tokens']);
    eco_register_style('header',     'components/header',     ['eco-tokens', 'eco-navigation']);
    eco_register_style('footer',     'components/footer',     ['eco-tokens']);
    eco_register_style('hero',       'components/hero',       ['eco-tokens']);
    eco_register_style('pagination', 'components/pagination', ['eco-tokens']);
    eco_register_style('breadcrumb', 'components/breadcrumb', ['eco-tokens']);
    eco_register_style('modal',      'components/modal',      ['eco-tokens']);
    eco_register_style('accordion',  'components/accordion',  ['eco-tokens']);

    // --- Layout ---
    eco_register_style('layout',     'layout/layout',    ['eco-tokens', 'eco-utilities']);

    // --- Templates conditionnels ---
    eco_register_style('tpl-blog',         'templates/blog',         ['eco-tokens', 'eco-cards']);
    eco_register_style('tpl-page',         'templates/page',         ['eco-tokens']);
    eco_register_style('tpl-portfolio',    'templates/portfolio',    ['eco-tokens', 'eco-cards']);
    eco_register_style('tpl-team',         'templates/team',         ['eco-tokens', 'eco-cards']);
    eco_register_style('tpl-testimonials', 'templates/testimonials', ['eco-tokens']);
    eco_register_style('tpl-contact',      'templates/contact',      ['eco-tokens', 'eco-forms']);
    eco_register_style('tpl-404',          'templates/404',          ['eco-tokens']);
    eco_register_style('tpl-woocommerce',  'templates/woocommerce',  ['eco-tokens']);

    // -----------------------------------------------------------------
    // PHASE 3 : Activation (enqueue) des styles nécessaires
    // -----------------------------------------------------------------

    // Styles globaux — toujours chargés
    $global_styles = [
        'eco-tokens',
        'eco-reset',
        'eco-typography',
        'eco-utilities',
        'eco-print',
        'eco-buttons',
        'eco-forms',
        'eco-cards',
        'eco-badges',
        'eco-alerts',
        'eco-navigation',
        'eco-header',
        'eco-footer',
        'eco-hero',
        'eco-pagination',
        'eco-breadcrumb',
        'eco-modal',
        'eco-accordion',
        'eco-layout',
    ];

    foreach ($global_styles as $handle) {
        wp_enqueue_style($handle);
    }

    // --- Templates conditionnels ---

    if (is_home() || is_archive() || is_search()) {
        wp_enqueue_style('eco-tpl-blog');
    }

    if (is_singular('page') || is_page()) {
        wp_enqueue_style('eco-tpl-page');
    }

    if (is_post_type_archive('portfolio') || is_singular('portfolio') || is_tax('portfolio_category') || is_tax('portfolio_tag')) {
        wp_enqueue_style('eco-tpl-portfolio');
    }

    if (is_post_type_archive('team') || is_singular('team')) {
        wp_enqueue_style('eco-tpl-team');
    }

    if (is_404()) {
        wp_enqueue_style('eco-tpl-404');
    }

    // Formulaire de contact — détecté via template ou shortcode
    if (
        is_page_template('templates/template-contact.php') ||
        eco_page_has_shortcode('eco_contact_form')
    ) {
        wp_enqueue_style('eco-tpl-contact');
    }

    // WooCommerce
    if (class_exists('WooCommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page())) {
        wp_enqueue_style('eco-tpl-woocommerce');
    }

    // -----------------------------------------------------------------
    // PHASE 4 : Preload des ressources critiques
    // -----------------------------------------------------------------
    add_filter('style_loader_tag', function (string $html, string $handle): string {

        $preload_handles = ['eco-tokens', 'eco-reset', 'eco-typography'];

        if (in_array($handle, $preload_handles, true)) {
            // Extrait le href du tag <link> pour le preload
            if (preg_match("/href='([^']+)'/", $html, $m)) {
                $preload = sprintf(
                    '<link rel="preload" href="%s" as="style">' . "\n",
                    esc_url($m[1])
                );
                return $preload . $html;
            }
        }

        return $html;

    }, 10, 2);

}, 10);


// =============================================================================
// FRONT-END SCRIPTS
// =============================================================================

add_action('wp_enqueue_scripts', function (): void {

    // Supprime jQuery en front si non utilisé par un plugin
    // ⚠️  Décommenter uniquement si aucun plugin n'en dépend
    // wp_deregister_script('jquery');

    // Script de navigation — toujours chargé
    eco_enqueue_script('navigation', 'navigation', [], true, 'defer');

    // Script de formulaires — uniquement si un formulaire est présent
    if (
        is_page_template('templates/template-contact.php') ||
        eco_page_has_shortcode('eco_contact_form') ||
        comments_open()
    ) {
        eco_enqueue_script('forms', 'forms', ['eco-navigation'], true, 'defer');
    }

    // Données globales localisées (AJAX nonce, i18n, etc.)
    wp_localize_script('eco-navigation', 'ecoStarter', eco_get_ajax_localize_data());

    // Config spécifique formulaire de contact
    if (wp_script_is('eco-forms', 'enqueued')) {
        wp_localize_script('eco-forms', 'ecoContactForm', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('eco_contact_form'),
            'action'   => 'eco_contact_form',
            'redirect' => esc_url(get_theme_mod('contact_redirect_url', '')),
        ]);
    }

}, 10);


// =============================================================================
// ADMIN STYLES & SCRIPTS
// =============================================================================

add_action('admin_enqueue_scripts', function (string $hook): void {

    // Tokens CSS en admin pour les meta boxes
    eco_register_style('tokens', 'base/tokens');
    wp_enqueue_style('eco-tokens');

    // Style admin général
    $admin_css = ECO_STARTER_DIR . '/assets/css/admin.css';
    if (file_exists($admin_css)) {
        wp_enqueue_style(
            'eco-admin',
            eco_asset('assets/css/admin.css'),
            ['eco-tokens'],
            (string) filemtime($admin_css)
        );
    }

    // Éditeur Gutenberg — synchronisation front/back
    if (in_array($hook, ['post.php', 'post-new.php'], true)) {
        $editor_css = ECO_STARTER_DIR . '/assets/css/editor-style.css';
        if (file_exists($editor_css)) {
            wp_enqueue_style(
                'eco-editor',
                eco_asset('assets/css/editor-style.css'),
                ['eco-tokens'],
                (string) filemtime($editor_css)
            );
        }
    }

    // Customizer preview — géré dans customizer.php
});


// =============================================================================
// EDITOR STYLE (Gutenberg block editor)
// =============================================================================

add_action('after_setup_theme', function (): void {
    // Enregistre le style de l'éditeur
    add_editor_style('assets/css/editor-style.css');
});


// =============================================================================
// HELPER — Détecte si la page contient un shortcode donné
// =============================================================================

/**
 * Vérifie si le contenu de la page courante contient un shortcode
 *
 * @param string $shortcode Nom du shortcode (sans crochets)
 * @return bool
 */
function eco_page_has_shortcode(string $shortcode): bool
{
    global $post;

    if (!$post instanceof \WP_Post) return false;

    return has_shortcode($post->post_content, $shortcode);
}