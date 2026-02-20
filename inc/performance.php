<?php
/**
 * Performance & Éco-conception — Eco Starter
 *
 * - Critical CSS inline (above-the-fold)
 * - Preload / Prefetch / Preconnect
 * - Lazy loading natif images + iframes
 * - Optimisation des requêtes WordPress
 * - Cache navigateur (Cache-Control headers)
 * - Nettoyage des requêtes inutiles
 * - Gestion du DNS prefetch
 * - Optimisation WP_Query
 * - Minification inline HTML
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// CRITICAL CSS INLINE
// Le CSS above-the-fold est injecté directement dans le <head>
// pour éviter le render-blocking et le FOUC.
//
// Stratégie :
// - Un fichier critical.css par type de page
// - Chargé en <style> inline dans le head
// - Le reste du CSS est chargé normalement via enqueue.php
// - En dev : fichiers lisibles | En prod : fichiers minifiés
//
// Comment générer le critical CSS :
// 1. Charger la page dans un navigateur
// 2. Copier le CSS visible dans le viewport (0-800px)
// 3. Coller dans le fichier assets/css/critical/[type].css
// Outil recommandé (hors build) : https://www.corewebvitals.io/tools/critical-css-generator
// =============================================================================

add_action('wp_head', function (): void {

    $critical_file = eco_get_critical_css_file();

    if (!$critical_file || !file_exists($critical_file)) return;

    $css = file_get_contents($critical_file);
    if (empty($css)) return;

    // Minification basique en prod (supprime commentaires et espaces superflus)
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        $css = eco_minify_css($css);
    }

    echo '<style id="eco-critical-css">' . $css . '</style>' . "\n";

}, 1); // Priorité 1 = le plus tôt possible dans le <head>


/**
 * Détermine quel fichier critical CSS charger selon le contexte
 *
 * @return string|null Chemin absolu du fichier ou null
 */
function eco_get_critical_css_file(): ?string
{
    $base = ECO_STARTER_DIR . '/assets/css/critical/';
    $suffix = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';

    // Ordre de priorité : du plus spécifique au plus générique
    $candidates = [];

    if (is_front_page()) {
        $candidates[] = 'home';
    } elseif (is_singular('post')) {
        $candidates[] = 'single';
        $candidates[] = 'blog';
    } elseif (is_singular('page')) {
        // Template de page spécifique
        $template = get_page_template_slug();
        if ($template) {
            $slug = basename($template, '.php');
            $candidates[] = 'page-' . $slug;
        }
        $candidates[] = 'page';
    } elseif (is_singular()) {
        $candidates[] = 'single-' . get_post_type();
        $candidates[] = 'single';
    } elseif (is_archive() || is_home()) {
        $candidates[] = 'archive-' . get_post_type();
        $candidates[] = 'archive';
    } elseif (is_404()) {
        $candidates[] = '404';
    } elseif (is_search()) {
        $candidates[] = 'search';
    }

    // Fallback global
    $candidates[] = 'global';

    foreach ($candidates as $candidate) {
        $path = $base . $candidate . $suffix . '.css';
        if (file_exists($path)) {
            return $path;
        }
        // Fallback sans .min
        $path_raw = $base . $candidate . '.css';
        if (file_exists($path_raw)) {
            return $path_raw;
        }
    }

    return null;
}


/**
 * Minification CSS basique — supprime commentaires, espaces et sauts de ligne inutiles
 * Sans build tool, sans dépendance.
 * Pour une minification complète, utiliser un outil externe (cssnano, etc.)
 *
 * @param string $css CSS source
 * @return string     CSS minifié
 */
function eco_minify_css(string $css): string
{
    // Supprime les commentaires /* ... */ (pas les /*! ... */ = commentaires licences)
    $css = preg_replace('/\/\*(?!!)[^*]*\*+([^\/][^*]*\*+)*\//', '', $css) ?? $css;

    // Supprime les espaces multiples
    $css = preg_replace('/\s{2,}/', ' ', $css) ?? $css;

    // Supprime les espaces autour des caractères spéciaux CSS
    $css = preg_replace('/\s*([:;,{}])\s*/', '$1', $css) ?? $css;

    // Supprime les ; avant }
    $css = str_replace(';}', '}', $css);

    // Supprime les sauts de ligne et tabulations
    $css = str_replace(["\n", "\r", "\t"], '', $css);

    return trim($css);
}


// =============================================================================
// PRELOAD — ressources critiques
// Injecté dans le <head> avant les enqueues WordPress
// =============================================================================

add_action('wp_head', function (): void {

    $suffix = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';

    // --- Font locale (décommenter si une font est auto-hébergée) ---
    /*
    $font_path = ECO_STARTER_DIR . '/assets/fonts/inter-var.woff2';
    if (file_exists($font_path)) {
        echo '<link rel="preload"'
            . ' href="' . esc_url(eco_asset('assets/fonts/inter-var.woff2')) . '"'
            . ' as="font"'
            . ' type="font/woff2"'
            . ' crossorigin="anonymous">'
            . "\n";
    }
    */

    // --- Image hero de la home (LCP critique) ---
    if (is_front_page()) {
        $hero_image = get_theme_mod('hero_image', '');
        if ($hero_image) {
            echo '<link rel="preload"'
                . ' href="' . esc_url($hero_image) . '"'
                . ' as="image"'
                . ' fetchpriority="high">'
                . "\n";
        }
    }

    // --- Image mise en avant sur les singles (LCP) ---
    if (is_singular() && has_post_thumbnail()) {
        $thumb_src = wp_get_attachment_image_src(
            get_post_thumbnail_id(),
            is_singular('post') ? 'eco-featured' : 'eco-hero'
        );
        if ($thumb_src) {
            echo '<link rel="preload"'
                . ' href="' . esc_url($thumb_src[0]) . '"'
                . ' as="image"'
                . ' fetchpriority="high">'
                . "\n";
        }
    }

}, 1);


// =============================================================================
// FETCHPRIORITY SUR L'IMAGE LCP
// Ajoute fetchpriority="high" + loading="eager" sur la featured image
// WordPress 6.3+ gère nativement fetchpriority — ce filtre est un renfort
// =============================================================================

add_filter('wp_get_attachment_image_attributes', function (array $attrs, \WP_Post $attachment, mixed $size): array {

    // On cible uniquement la première image visible (LCP probable)
    static $lcp_done = false;

    if (!$lcp_done && is_singular() && has_post_thumbnail() && get_post_thumbnail_id() === $attachment->ID) {
        $attrs['fetchpriority'] = 'high';
        $attrs['loading']       = 'eager';
        $attrs['decoding']      = 'sync';
        $lcp_done = true;
    }

    return $attrs;

}, 10, 3);


// =============================================================================
// LAZY LOADING NATIF
// WordPress 5.5+ ajoute loading="lazy" automatiquement sur les images.
// On renforce pour les iframes et les images hors contenu WordPress.
// =============================================================================

/**
 * Ajoute loading="lazy" sur les iframes dans le contenu
 */
add_filter('the_content', function (string $content): string {

    if (empty($content)) return $content;

    // Ajoute loading="lazy" sur les iframes qui n'en ont pas
    $content = preg_replace(
        '/<iframe(?![^>]*loading=)([^>]*)>/i',
        '<iframe loading="lazy"$1>',
        $content
    ) ?? $content;

    return $content;
});


/**
 * Ajoute loading="lazy" sur toutes les images WordPress
 * (renfort — WordPress le fait déjà mais pas toujours sur les thumbnails custom)
 */
add_filter('wp_get_attachment_image_attributes', function (array $attrs, \WP_Post $attachment, mixed $size): array {

    // Ne pas écraser fetchpriority="high" (image LCP)
    if (!isset($attrs['fetchpriority']) || $attrs['fetchpriority'] !== 'high') {
        $attrs['loading'] ??= 'lazy';
        $attrs['decoding'] ??= 'async';
    }

    return $attrs;

}, 10, 3);


// =============================================================================
// OPTIMISATION DES REQUÊTES WORDPRESS
// =============================================================================

/**
 * Désactive heartbeat API en front (économise des requêtes AJAX récurrentes)
 * Gardé actif en admin pour la sauvegarde automatique des articles
 */
add_action('init', function (): void {
    if (!is_admin()) {
        wp_deregister_script('heartbeat');
    }
});

/**
 * Réduit la fréquence du heartbeat en admin
 * Par défaut : 15s → on passe à 60s
 */
add_filter('heartbeat_settings', function (array $settings): array {
    $settings['interval'] = 60;
    return $settings;
});

/**
 * Supprime l'API oEmbed en front si pas utilisée
 * (économise une requête HTTP pour chaque page)
 */
add_action('init', function (): void {
    // Décommenter si on n'utilise pas d'embeds oEmbed
    // remove_action('rest_api_init', 'wp_oembed_register_route');
    // remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
});


// =============================================================================
// CACHE NAVIGATEUR — headers fins
// Complète les headers généraux de security.php
// avec des durées adaptées par type de ressource
// =============================================================================

add_action('send_headers', function (): void {

    if (is_admin()) return;

    // Déjà géré dans security.php pour les pages HTML
    // Ici on gère les cas spéciaux

    // Page de résultats de recherche → jamais en cache
    if (is_search()) {
        header('Cache-Control: no-store, no-cache');
        return;
    }

    // Pages WooCommerce dynamiques → cache court
    if (
        class_exists('WooCommerce') &&
        (is_cart() || is_checkout() || is_account_page())
    ) {
        header('Cache-Control: private, no-cache, no-store');
        return;
    }

    // Feeds RSS → cache 1h
    if (is_feed()) {
        header('Cache-Control: public, max-age=3600');
        return;
    }

}, 10);


// =============================================================================
// DNS PREFETCH & PRECONNECT
// Accélère les connexions aux domaines tiers nécessaires
// =============================================================================

add_action('wp_head', function (): void {

    $hints = eco_get_resource_hints();

    foreach ($hints as $hint) {
        $rel  = esc_attr($hint['rel']);
        $href = esc_url($hint['href']);
        $crossorigin = isset($hint['crossorigin']) ? ' crossorigin' : '';

        echo "<link rel=\"{$rel}\" href=\"{$href}\"{$crossorigin}>\n";
    }

}, 2);


/**
 * Retourne les resource hints configurés
 * À adapter selon les services tiers du projet
 *
 * @return array<int, array{rel: string, href: string, crossorigin?: bool}>
 */
function eco_get_resource_hints(): array
{
    $hints = [];

    // Exemple — décommenter selon les besoins :

    // Google Analytics 4
    // $hints[] = ['rel' => 'preconnect', 'href' => 'https://www.googletagmanager.com'];
    // $hints[] = ['rel' => 'dns-prefetch', 'href' => 'https://www.googletagmanager.com'];

    // Stripe
    // $hints[] = ['rel' => 'preconnect', 'href' => 'https://js.stripe.com'];

    // Gravatar (si commentaires activés)
    if (is_singular() && comments_open()) {
        $hints[] = ['rel' => 'dns-prefetch', 'href' => 'https://secure.gravatar.com'];
    }

    // Filtre pour permettre l'ajout via le thème enfant ou un plugin
    return apply_filters('eco_resource_hints', $hints);
}


// =============================================================================
// MINIFICATION HTML (optionnelle)
// Supprime les espaces/sauts de ligne superflus dans le HTML généré
// Gain modeste (~2-5%) mais cohérent avec l'approche éco-conception
//
// ⚠️  Désactiver si des problèmes d'affichage apparaissent
//     (certains plugins génèrent du HTML sensible aux espaces)
// =============================================================================

// Activé uniquement en production
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    add_action('template_redirect', function (): void {

        // Pas de minification sur les flux, les pages admin, les previews
        if (is_feed() || is_admin() || is_preview()) return;

        // Pas de minification si le contenu est du JSON ou XML
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (
            str_contains($content_type, 'application/json') ||
            str_contains($content_type, 'text/xml')
        ) return;

        ob_start('eco_minify_html');
    });
}


/**
 * Minification HTML — utilisée comme callback de ob_start()
 * Approche conservative : ne touche pas au contenu des <pre>, <textarea>, <script>, <style>
 *
 * @param string $html HTML brut
 * @return string      HTML minifié
 */
function eco_minify_html(string $html): string
{
    if (empty(trim($html))) return $html;

    // Protège les blocs sensibles (pre, textarea, script inline, style inline)
    $protected = [];
    $placeholder = 'ECO_PROTECTED_%d_ECO';

    // Extrait et protège les blocs <pre>, <textarea>, <script>, <style>
    $patterns = [
        '/<pre[\s\S]*?<\/pre>/i',
        '/<textarea[\s\S]*?<\/textarea>/i',
        '/<script[\s\S]*?<\/script>/i',
        '/<style[\s\S]*?<\/style>/i',
    ];

    foreach ($patterns as $pattern) {
        $html = preg_replace_callback($pattern, function (array $matches) use (&$protected, $placeholder): string {
            $index                = count($protected);
            $protected[$index]    = $matches[0];
            return sprintf($placeholder, $index);
        }, $html) ?? $html;
    }

    // Supprime les commentaires HTML (sauf les conditionnels IE et les blocs WP)
    $html = preg_replace('/<!--(?!\[if|!\s*\[if|\s*wp:|\s*\/wp:)[\s\S]*?-->/', '', $html) ?? $html;

    // Compresse les espaces entre les balises
    $html = preg_replace('/>\s{2,}</', '> <', $html) ?? $html;

    // Supprime les espaces en début et fin de ligne
    $html = preg_replace('/^\s+|\s+$/m', '', $html) ?? $html;

    // Réintègre les blocs protégés
    foreach ($protected as $index => $block) {
        $html = str_replace(sprintf($placeholder, $index), $block, $html);
    }

    return $html;
}


// =============================================================================
// OPTIMISATION DES IMAGES
// =============================================================================

/**
 * Force les attributs width/height sur les images WordPress
 * Évite le layout shift (CLS) — améliore le score Core Web Vitals
 */
add_filter('wp_get_attachment_image_attributes', function (array $attrs, \WP_Post $attachment): array {

    // width et height déjà présents
    if (isset($attrs['width'], $attrs['height'])) return $attrs;

    // Tente de récupérer les dimensions depuis les métadonnées
    $meta = wp_get_attachment_metadata($attachment->ID);

    if (isset($meta['width'], $meta['height']) && !isset($attrs['width'])) {
        $attrs['width']  = (string) $meta['width'];
        $attrs['height'] = (string) $meta['height'];
    }

    return $attrs;

}, 10, 2);


/**
 * Ajoute le support WebP/AVIF comme formats prioritaires
 * WordPress 6.1+ génère les WebP automatiquement si l'extension est disponible
 */
add_filter('wp_editor_set_quality', fn(): int => 82); // Qualité légèrement réduite = ~30% de gain

/**
 * Active la génération de WebP en plus du JPEG/PNG original
 * (WordPress 5.8+, nécessite extension GD ou Imagick avec support WebP)
 */
add_filter('wp_image_editors', function (array $editors): array {
    // Priorité à Imagick (meilleure qualité WebP)
    $imagick_key = array_search('WP_Image_Editor_Imagick', $editors, true);
    if ($imagick_key !== false) {
        unset($editors[$imagick_key]);
        array_unshift($editors, 'WP_Image_Editor_Imagick');
    }
    return $editors;
});

/**
 * Génère les sous-tailles WebP pour toutes les images uploadées
 * Uniquement si Imagick est disponible
 */
add_filter('image_editor_output_format', function (array $formats): array {
    if (extension_loaded('imagick')) {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png']  = 'image/webp';
    }
    return $formats;
});


// =============================================================================
// NETTOYAGE DES RESSOURCES INUTILES
// Chaque requête économisée = moins de CO2
// =============================================================================

add_action('init', function (): void {

    // Désactive le chargement du CSS Dashicons en front
    // (icônes admin — inutiles pour les visiteurs non connectés)
    if (!is_admin() && !is_user_logged_in()) {
        add_action('wp_enqueue_scripts', function (): void {
            wp_deregister_style('dashicons');
        }, 99);
    }

    // Désactive les styles de blocs globaux si on n'utilise pas Gutenberg en front
    // (seulement si on ne veut PAS du rendu Gutenberg en front-end)
    // add_filter('should_load_separate_core_block_assets', '__return_false');

});


/**
 * Supprime les meta inutiles de wp_head
 * (complète le nettoyage fait dans setup.php)
 */
add_action('wp_head', function (): void {
    // Supprime le DNS prefetch WordPress natif (on gère le nôtre)
    remove_action('wp_head', 'wp_resource_hints', 2);
}, 1);

/**
 * Remplace le DNS prefetch WordPress par le nôtre plus précis
 * (évite les prefetch inutiles sur wp-includes)
 */
add_filter('wp_resource_hints', function (array $hints, string $relation_type): array {

    // Supprime les hints automatiques WordPress pour wp-includes
    // On ne veut pas précharger les ressources WP génériques
    if ($relation_type === 'dns-prefetch') {
        $hints = array_filter($hints, function ($hint) {
            $url = is_array($hint) ? ($hint['href'] ?? '') : $hint;
            return !str_contains($url, 'wp-includes') &&
                   !str_contains($url, 'wp-content/plugins');
        });
    }

    return $hints;

}, 10, 2);


// =============================================================================
// POIDS DE PAGE — MONITORING
// En WP_DEBUG : affiche le poids total des ressources dans la console
// Aide à détecter les régressions de performance dès le dev
// =============================================================================

if (defined('WP_DEBUG') && WP_DEBUG) {

    add_action('wp_footer', function (): void {

        if (is_admin()) return;

        global $wpdb;

        $memory  = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $queries = $wpdb->num_queries;
        $time    = timer_stop();

        echo '<script>console.groupCollapsed("🌿 Eco Starter — Performance");</script>' . "\n";
        echo '<script>console.log("🧠 Mémoire pic : ' . esc_js($memory) . ' MB");</script>' . "\n";
        echo '<script>console.log("🗃 Requêtes SQL : ' . esc_js((string)$queries) . '");</script>' . "\n";
        echo '<script>console.log("⏱ Temps génération : ' . esc_js($time) . 's");</script>' . "\n";
        echo '<script>console.groupEnd();</script>' . "\n";

    }, 999);
}


// =============================================================================
// STRUCTURE DES FICHIERS CRITICAL CSS
// Crée les fichiers placeholder au premier lancement si absents
// Le développeur n'a qu'à les remplir
// =============================================================================

add_action('after_switch_theme', function (): void {

    $critical_dir = ECO_STARTER_DIR . '/assets/css/critical/';

    if (!file_exists($critical_dir)) {
        wp_mkdir_p($critical_dir);
    }

    $files = ['global', 'home', 'single', 'page', 'archive', '404'];

    foreach ($files as $file) {
        $path = $critical_dir . $file . '.css';
        if (!file_exists($path)) {
            file_put_contents(
                $path,
                "/*\n"
                . " * Critical CSS — {$file}\n"
                . " * Coller ici le CSS above-the-fold pour le template '{$file}'.\n"
                . " * Outil recommandé : https://www.corewebvitals.io/tools/critical-css-generator\n"
                . " */\n"
            );
        }
    }
});
