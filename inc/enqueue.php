<?php
/**
 * Gestion des assets CSS et JS
 *
 * Principes :
 * - Chaque fichier CSS est chargé conditionnellement selon le contexte
 * - Zéro JS externe, zéro jQuery
 * - Les assets sont versionnés via la constante ECO_STARTER_VERSION
 * - En mode dev (WP_DEBUG), les fichiers non minifiés sont chargés
 * - En production, les fichiers .min.css/.min.js sont utilisés s'ils existent
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// HELPERS INTERNES
// =============================================================================

/**
 * Retourne le suffixe de fichier selon l'environnement
 * Dev  → '' (fichier lisible)
 * Prod → '.min' (fichier minifié)
 *
 * @return string
 */
function eco_asset_suffix(): string
{
    return (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';
}

/**
 * Enregistre et charge un fichier CSS du thème
 *
 * @param string      $handle   Identifiant unique WordPress
 * @param string      $path     Chemin relatif depuis assets/css/ (sans extension)
 * @param array       $deps     Dépendances (handles CSS)
 * @param string|null $media    Média CSS (all, print, screen…)
 */
function eco_enqueue_style(
    string $handle,
    string $path,
    array $deps = [],
    string $media = 'all'
): void {
    $suffix = eco_asset_suffix();
    $file   = "assets/css/{$path}{$suffix}.css";
    $full   = ECO_STARTER_DIR . '/' . $file;

    // Fallback : si le .min n'existe pas, on charge le fichier normal
    if (!file_exists($full)) {
        $file = "assets/css/{$path}.css";
        $full = ECO_STARTER_DIR . '/' . $file;
    }

    if (!file_exists($full)) {
        return; // Fichier absent → silencieux en prod, warning en dev géré par WP
    }

    wp_enqueue_style(
        'eco-' . $handle,
        ECO_STARTER_URI . '/' . $file,
        array_map(fn(string $d): string => 'eco-' . $d, $deps),
        ECO_STARTER_VERSION,
        $media
    );
}

/**
 * Enregistre et charge un fichier JS du thème
 *
 * @param string $handle   Identifiant unique
 * @param string $path     Chemin relatif depuis assets/js/ (sans extension)
 * @param array  $deps     Dépendances (handles JS)
 * @param bool   $defer    true → defer | false → normal
 */
function eco_enqueue_script(
    string $handle,
    string $path,
    array $deps = [],
    bool $defer = true
): void {
    $suffix = eco_asset_suffix();
    $file   = "assets/js/{$path}{$suffix}.js";
    $full   = ECO_STARTER_DIR . '/' . $file;

    if (!file_exists($full)) {
        $file = "assets/js/{$path}.js";
        $full = ECO_STARTER_DIR . '/' . $file;
    }

    if (!file_exists($full)) {
        return;
    }

    wp_enqueue_script(
        'eco-' . $handle,
        ECO_STARTER_URI . '/' . $file,
        array_map(fn(string $d): string => 'eco-' . $d, $deps),
        ECO_STARTER_VERSION,
        ['in_footer' => true, 'strategy' => $defer ? 'defer' : 'async']
    );
}


// =============================================================================
// SUPPRESSION DES ASSETS WORDPRESS PAR DÉFAUT
// =============================================================================

add_action('wp_enqueue_scripts', function (): void {

    // jQuery — on ne l'utilise pas, on le désenregistre en front
    // Note : certains plugins en ont besoin, WP le recharge si nécessaire
    wp_deregister_script('jquery');
    wp_deregister_script('jquery-core');
    wp_deregister_script('jquery-migrate');

    // Block styles — on gère nos propres styles Gutenberg
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles'); // FSE — inutile en thème classique

}, 1); // Priorité 1 = avant tout le reste


// =============================================================================
// ASSETS FRONT-END
// =============================================================================

add_action('wp_enqueue_scripts', function (): void {

    // -------------------------------------------------------------------------
    // CSS — chargé dans l'ordre de la cascade
    // -------------------------------------------------------------------------

    // Base : tokens + reset + typo (toujours chargés)
    eco_enqueue_style('tokens',     'base/tokens');
    eco_enqueue_style('reset',      'base/reset',      ['tokens']);
    eco_enqueue_style('typography', 'base/typography', ['reset']);
    eco_enqueue_style('utilities',  'base/utilities',  ['reset']);

    // Composants globaux (navigation, header, footer, boutons, formulaires)
    eco_enqueue_style('components', 'components/components', ['typography']);

    // Layout principal
    eco_enqueue_style('layout', 'layout/layout', ['components']);

    // CSS conditionnel selon le contexte de la page
    // → Économie de bande passante : on ne charge que ce qui est nécessaire

    if (is_singular('post') || is_home() || is_archive() || is_category() || is_tag()) {
        eco_enqueue_style('blog', 'templates/blog', ['layout']);
    }

    if (is_singular('page')) {
        eco_enqueue_style('page', 'templates/page', ['layout']);
    }

    if (is_singular('portfolio') || is_post_type_archive('portfolio')) {
        eco_enqueue_style('portfolio', 'templates/portfolio', ['layout']);
    }

    if (is_singular('team')) {
        eco_enqueue_style('team', 'templates/team', ['layout']);
    }

    if (is_singular('testimonial') || is_post_type_archive('testimonial')) {
        eco_enqueue_style('testimonials', 'templates/testimonials', ['layout']);
    }

    if (is_page_template('templates/template-contact.php')) {
        eco_enqueue_style('contact', 'templates/contact', ['layout']);
    }

    if (is_404()) {
        eco_enqueue_style('404', 'templates/404', ['layout']);
    }

    // WooCommerce — uniquement si plugin actif et page WC concernée
    if (
        class_exists('WooCommerce') &&
        (is_woocommerce() || is_cart() || is_checkout() || is_account_page())
    ) {
        eco_enqueue_style('woocommerce', 'templates/woocommerce', ['layout']);
    }

    // Print — uniquement pour le média print (navigateur le charge séparément)
    eco_enqueue_style('print', 'base/print', [], 'print');

    // -------------------------------------------------------------------------
    // JS — vanilla ES2020, defer par défaut
    // -------------------------------------------------------------------------

    // Navigation (menu mobile, sous-menus accessibles)
    eco_enqueue_script('navigation', 'navigation');

    // Formulaires natifs (validation + soumission AJAX)
    if (
        is_page_template('templates/template-contact.php') ||
        is_singular() // formulaire de commentaire possible partout
    ) {
        eco_enqueue_script('forms', 'forms');
    }

    // Lazy loading polyfill (pour navigateurs très anciens — optionnel)
    // eco_enqueue_script('lazy', 'lazy');

    // Variables PHP → JS (AJAX URL, nonce, etc.)
    wp_localize_script('eco-navigation', 'ecoStarter', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('eco_ajax_nonce'),
        'homeUrl' => home_url('/'),
        'i18n'    => [
            'menuOpen'    => __('Ouvrir le menu', 'eco-starter'),
            'menuClose'   => __('Fermer le menu', 'eco-starter'),
            'formSending' => __('Envoi en cours…', 'eco-starter'),
            'formSuccess' => __('Message envoyé avec succès.', 'eco-starter'),
            'formError'   => __('Une erreur est survenue. Veuillez réessayer.', 'eco-starter'),
        ],
    ]);

}, 10);


// =============================================================================
// ATTRIBUTS defer/async sur les balises <script>
// WordPress 6.3+ gère nativement la strategy → ce filtre est un fallback
// =============================================================================

add_filter('script_loader_tag', function (string $tag, string $handle): string {

    // Liste des scripts qui doivent être en defer
    $defer_scripts = ['eco-navigation', 'eco-forms'];

    if (in_array($handle, $defer_scripts, true) && !str_contains($tag, 'defer')) {
        $tag = str_replace(' src=', ' defer src=', $tag);
    }

    return $tag;
}, 10, 2);


// =============================================================================
// PRELOAD DES RESSOURCES CRITIQUES
// Indique au navigateur de télécharger les assets importants en priorité
// =============================================================================

add_action('wp_head', function (): void {

    $suffix = eco_asset_suffix();

    // Preload du CSS principal (évite le FOUC)
    echo '<link rel="preload" href="' . esc_url(eco_asset("assets/css/base/tokens{$suffix}.css")) . '" as="style">' . "\n";
    echo '<link rel="preload" href="' . esc_url(eco_asset("assets/css/components/components{$suffix}.css")) . '" as="style">' . "\n";

    // Preload du JS de navigation (interaction dès le chargement)
    $nav_js = ECO_STARTER_DIR . "/assets/js/navigation{$suffix}.js";
    if (file_exists($nav_js)) {
        echo '<link rel="preload" href="' . esc_url(eco_asset("assets/js/navigation{$suffix}.js")) . '" as="script">' . "\n";
    }

    // Preload de la font si auto-hébergée
    // À décommenter et adapter selon la font choisie par le client
    /*
    echo '<link rel="preload" href="' . esc_url(eco_asset('assets/fonts/inter-var.woff2')) . '"
        as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
    */

}, 1); // Priorité 1 = dans le <head> le plus tôt possible


// =============================================================================
// DNS PREFETCH & PRECONNECT
// Pour les ressources tierces si nécessaire (paiement, analytics…)
// =============================================================================

add_action('wp_head', function (): void {

    // À décommenter selon les services tiers du client
    // echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    // echo '<link rel="dns-prefetch" href="//stats.votreanalytics.com">' . "\n";

}, 2);


// =============================================================================
// ASSETS ADMIN
// On ne charge les assets du thème en admin que si nécessaire
// =============================================================================

add_action('admin_enqueue_scripts', function (string $hook): void {

    // CSS admin uniquement sur l'éditeur et les pages du thème
    $admin_pages = ['post.php', 'post-new.php', 'appearance_page_eco-starter-settings'];

    if (!in_array($hook, $admin_pages, true)) {
        return;
    }

    wp_enqueue_style(
        'eco-admin',
        eco_asset('assets/css/admin.css'),
        [],
        ECO_STARTER_VERSION
    );
});