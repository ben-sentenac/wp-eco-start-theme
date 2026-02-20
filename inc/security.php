<?php
/**
 * Sécurité — Eco Starter
 *
 * - Headers HTTP de sécurité
 * - Désactivation XML-RPC
 * - REST API restreinte
 * - Protection brute-force login (sans plugin)
 * - Masquage des informations sensibles
 * - Nonces helpers
 * - Désactivation de l'édition de fichiers en admin
 * - Protection du wp-config et .htaccess
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// HEADERS HTTP DE SÉCURITÉ
// Envoyés sur toutes les pages front-end
// =============================================================================

add_action('send_headers', function (): void {

    // Pas de headers en admin
    if (is_admin()) return;

    /**
     * X-Content-Type-Options
     * Empêche le MIME-type sniffing (attaques via fichiers uploadés)
     */
    header('X-Content-Type-Options: nosniff');

    /**
     * X-Frame-Options
     * Empêche le clickjacking via iframes
     * SAMEORIGIN = autorise uniquement le même domaine
     */
    header('X-Frame-Options: SAMEORIGIN');

    /**
     * X-XSS-Protection
     * Protection XSS pour les vieux navigateurs (Chrome < 78, IE, Edge legacy)
     */
    header('X-XSS-Protection: 1; mode=block');

    /**
     * Referrer-Policy
     * Contrôle les infos envoyées dans l'en-tête Referer
     * strict-origin-when-cross-origin = bonne pratique actuelle
     */
    header('Referrer-Policy: strict-origin-when-cross-origin');

    /**
     * Permissions-Policy (anciennement Feature-Policy)
     * Désactive les APIs navigateur non utilisées
     * Adapter selon les besoins du projet
     */
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');

    /**
     * Content-Security-Policy
     * Politique permissive par défaut pour un thème WordPress — à durcir par projet
     *
     * Actuellement en mode "report-only" pour ne pas casser les plugins.
     * Remplacer Content-Security-Policy-Report-Only par Content-Security-Policy
     * une fois la politique validée sur le projet client.
     */
    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // unsafe-* requis pour WP admin + plugins
        "style-src 'self' 'unsafe-inline'",                // unsafe-inline requis pour WP
        "img-src 'self' data: https:",                     // data: pour avatars, https: pour CDN
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "upgrade-insecure-requests",
    ]);

    // En report-only d'abord — décommenter la ligne suivante pour activer
    // header('Content-Security-Policy: ' . $csp);
    header('Content-Security-Policy-Report-Only: ' . $csp);

    /**
     * Strict-Transport-Security (HSTS)
     * Uniquement en HTTPS — force le navigateur à toujours utiliser HTTPS
     * max-age=31536000 = 1 an | includeSubDomains | preload
     *
     * ⚠️  N'activer qu'une fois le SSL confirmé et définitif
     */
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    /**
     * Cache-Control pour les pages HTML
     * Les assets statiques (CSS/JS/images) sont gérés par le serveur web
     */
    if (!is_user_logged_in()) {
        // Pages publiques : cache navigateur court (5 min) + cache proxy (1h)
        header('Cache-Control: public, max-age=300, s-maxage=3600, stale-while-revalidate=86400');
    } else {
        // Pages pour utilisateurs connectés : jamais en cache
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
    }

}, 1);


// =============================================================================
// MASQUAGE DES INFORMATIONS WORDPRESS
// Réduire la surface d'attaque en cachant les détails techniques
// =============================================================================

// Supprime la version WordPress de toutes les URLs d'assets
add_filter('style_loader_src',  'eco_remove_wp_version_from_asset', 9999);
add_filter('script_loader_src', 'eco_remove_wp_version_from_asset', 9999);

function eco_remove_wp_version_from_asset(string $src): string
{
    if (str_contains($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// Supprime la version WordPress des flux RSS
add_filter('the_generator', '__return_empty_string');

// Supprime X-Pingback du header HTTP
add_filter('wp_headers', function (array $headers): array {
    unset($headers['X-Pingback']);
    return $headers;
});

// Supprime le lien pingback des meta
remove_action('wp_head', 'wp_pingback_header');

// Supprime l'exposition du login dans les messages d'erreur
add_filter('login_errors', fn(): string => __('Identifiant ou mot de passe incorrect.', 'eco-starter'));


// =============================================================================
// DÉSACTIVATION XML-RPC
// Vecteur d'attaque brute-force et DDoS — inutile sur la plupart des sites
// ⚠️  Désactiver uniquement si Jetpack ou apps mobiles WP ne sont pas utilisés
// =============================================================================

// Désactive XML-RPC complètement
add_filter('xmlrpc_enabled', '__return_false');

// Supprime le lien XML-RPC du head
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// Bloque les requêtes vers xmlrpc.php au niveau PHP
add_action('init', function (): void {
    if (
        isset($_SERVER['REQUEST_URI']) &&
        str_contains(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), 'xmlrpc.php')
    ) {
        wp_die(
            esc_html__('XML-RPC est désactivé sur ce site.', 'eco-starter'),
            esc_html__('Accès refusé', 'eco-starter'),
            ['response' => 403]
        );
    }
});


// =============================================================================
// REST API — RESTRICTION
// On expose uniquement les endpoints nécessaires
// =============================================================================

/**
 * Par défaut on laisse la REST API ouverte car elle est nécessaire à :
 * - L'éditeur Gutenberg
 * - WooCommerce
 * - De nombreux plugins
 *
 * On se contente de masquer la liste des utilisateurs (endpoint /users)
 * qui expose les logins — principal vecteur d'attaque
 */

// Masque les utilisateurs via REST API pour les non-connectés
add_filter('rest_endpoints', function (array $endpoints): array {

    if (is_user_logged_in()) return $endpoints;

    // Supprime l'endpoint /users et /users/<id>
    $restricted = [
        '/wp/v2/users',
        '/wp/v2/users/(?P<id>[\d]+)',
    ];

    foreach ($restricted as $endpoint) {
        if (isset($endpoints[$endpoint])) {
            unset($endpoints[$endpoint]);
        }
    }

    return $endpoints;
});

// Supprime le lien REST API du head (évite l'exposition)
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('template_redirect', 'rest_output_link_header', 11);


// =============================================================================
// PROTECTION BRUTE-FORCE LOGIN
// Sans plugin — via transients WordPress
// Limite : 5 tentatives / 15 minutes par IP
// =============================================================================

/**
 * Nombre de tentatives autorisées avant blocage
 */
define('ECO_LOGIN_MAX_ATTEMPTS', 5);

/**
 * Durée du blocage en secondes (15 minutes)
 */
define('ECO_LOGIN_LOCKOUT_DURATION', 15 * MINUTE_IN_SECONDS);


/**
 * Comptabilise les tentatives échouées
 */
add_action('wp_login_failed', function (string $username): void {

    $ip  = eco_get_client_ip();
    $key = 'eco_login_attempts_' . md5($ip);

    $attempts = (int) get_transient($key);
    $attempts++;

    set_transient($key, $attempts, ECO_LOGIN_LOCKOUT_DURATION);

    // Log en WP_DEBUG
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Eco Starter — Tentative login échouée n°%d pour IP %s (login : %s)',
            $attempts,
            $ip,
            sanitize_text_field($username)
        ));
    }
});


/**
 * Vérifie si l'IP est bloquée avant d'afficher le formulaire de login
 */
add_action('login_init', function (): void {

    // Pas de vérification pour les déconnexions et les vraies actions WP
    $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : 'login';

    if (in_array($action, ['logout', 'lostpassword', 'rp', 'resetpass'], true)) return;

    $ip  = eco_get_client_ip();
    $key = 'eco_login_attempts_' . md5($ip);

    $attempts = (int) get_transient($key);

    if ($attempts >= ECO_LOGIN_MAX_ATTEMPTS) {
        $remaining = (int) (get_option('_transient_timeout_' . $key) - time());
        $minutes   = max(1, (int) ceil($remaining / 60));

        wp_die(
            sprintf(
                '<p>' . esc_html__('Trop de tentatives de connexion. Veuillez réessayer dans %d minute(s).', 'eco-starter') . '</p>',
                $minutes
            ),
            esc_html__('Accès temporairement bloqué', 'eco-starter'),
            [
                'response'  => 429,
                'back_link' => false,
            ]
        );
    }
});


/**
 * Réinitialise le compteur après une connexion réussie
 */
add_action('wp_login', function (string $user_login, \WP_User $user): void {
    $ip  = eco_get_client_ip();
    $key = 'eco_login_attempts_' . md5($ip);
    delete_transient($key);
}, 10, 2);


/**
 * Ajoute un délai artificiel sur les tentatives échouées
 * Ralentit les attaques automatisées
 */
add_filter('authenticate', function (mixed $user, string $username, string $password): mixed {

    if ($user instanceof \WP_Error) {
        // Délai de 1 seconde sur chaque échec
        sleep(1);
    }

    return $user;
}, 30, 3);


/**
 * Retourne l'IP cliente en tenant compte des proxys
 * Ne fait jamais confiance à X-Forwarded-For sans vérification
 *
 * @return string
 */
function eco_get_client_ip(): string
{
    // Liste des headers à vérifier dans l'ordre de fiabilité
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',        // Nginx proxy
        'REMOTE_ADDR',           // IP directe (toujours disponible)
    ];

    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));

            // Valide le format IP (v4 ou v6)
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // Fallback : REMOTE_ADDR sans validation stricte
    return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
}


// =============================================================================
// DÉSACTIVATION DE L'ÉDITION DE FICHIERS EN ADMIN
// Empêche l'édition de PHP via Apparence > Éditeur
// (devrait aussi être dans wp-config.php mais on le force ici en fallback)
// =============================================================================

if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

if (!defined('DISALLOW_FILE_MODS')) {
    // Décommenter pour bloquer aussi l'installation de plugins/thèmes
    // define('DISALLOW_FILE_MODS', true);
}


// =============================================================================
// PROTECTION DES FICHIERS SENSIBLES
// Redirection vers 404 si accès direct tentés sur les fichiers sensibles
// Note : la protection .htaccess est plus fiable — ceci est un filet de sécurité
// =============================================================================

add_action('init', function (): void {

    if (!isset($_SERVER['REQUEST_URI'])) return;

    $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));

    // Fichiers qui ne doivent jamais être accessibles directement
    $blocked_patterns = [
        '/wp-config.php',
        '/.env',
        '/readme.html',
        '/license.txt',
        '/wp-admin/install.php',
        '/wp-admin/upgrade.php',
    ];

    foreach ($blocked_patterns as $pattern) {
        if (str_ends_with(strtolower($uri), $pattern)) {
            wp_die(
                esc_html__('Accès refusé.', 'eco-starter'),
                '',
                ['response' => 403]
            );
        }
    }
});


// =============================================================================
// NONCES HELPERS
// Fonctions centralisées pour la gestion des nonces dans le thème
// =============================================================================

/**
 * Génère un champ nonce HTML
 * Usage dans les formulaires : echo eco_nonce_field('mon_action');
 *
 * @param string $action Nom de l'action
 * @param string $name   Nom du champ (optionnel)
 * @return string        HTML du champ hidden
 */
function eco_nonce_field(string $action, string $name = '_eco_nonce'): string
{
    return wp_nonce_field($action, $name, true, false);
}


/**
 * Vérifie un nonce et retourne false si invalide
 * Usage : if (!eco_verify_nonce('mon_action')) { wp_die(...); }
 *
 * @param string $action Nom de l'action
 * @param string $name   Nom du champ
 * @return bool
 */
function eco_verify_nonce(string $action, string $name = '_eco_nonce'): bool
{
    if (!isset($_POST[$name])) return false;

    return (bool) wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST[$name])),
        $action
    );
}


/**
 * Vérifie un nonce AJAX et envoie une réponse JSON d'erreur si invalide
 * Usage en début de handler AJAX : eco_verify_ajax_nonce('mon_action');
 *
 * @param string $action Nom de l'action
 */
function eco_verify_ajax_nonce(string $action): void
{
    $nonce = sanitize_text_field(wp_unslash(
        $_POST['nonce'] ?? $_GET['nonce'] ?? ''
    ));

    if (!wp_verify_nonce($nonce, $action)) {
        wp_send_json_error(
            ['message' => __('Sécurité : nonce invalide.', 'eco-starter')],
            403
        );
    }
}


// =============================================================================
// SANITISATION HELPERS
// Wrappers pratiques pour la sanitisation des données
// =============================================================================

/**
 * Sanitise un tableau $_POST / $_GET de façon récursive
 *
 * @param array<string, mixed> $data   Données brutes
 * @param array<string, string> $types Types attendus par clé ['field' => 'text|email|url|int|float|bool|html']
 * @return array<string, mixed>        Données sanitisées
 */
function eco_sanitize_input(array $data, array $types = []): array
{
    $clean = [];

    foreach ($data as $key => $value) {
        $type = $types[$key] ?? 'text';

        $clean[$key] = match ($type) {
            'email'    => sanitize_email(wp_unslash((string) $value)),
            'url'      => esc_url_raw(wp_unslash((string) $value)),
            'int'      => absint($value),
            'float'    => (float) $value,
            'bool'     => (bool) $value,
            'html'     => wp_kses_post(wp_unslash((string) $value)),
            'textarea' => sanitize_textarea_field(wp_unslash((string) $value)),
            'key'      => sanitize_key(wp_unslash((string) $value)),
            'slug'     => sanitize_title(wp_unslash((string) $value)),
            default    => sanitize_text_field(wp_unslash((string) $value)),
        };
    }

    return $clean;
}


// =============================================================================
// PROTECTION UPLOAD
// Restreint les types de fichiers uploadables
// =============================================================================

add_filter('upload_mimes', function (array $mimes): array {

    // Types autorisés — ajuster selon les besoins du projet
    $allowed = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'gif'          => 'image/gif',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'avif'         => 'image/avif',
        'svg'          => 'image/svg+xml',
        'pdf'          => 'application/pdf',
        'mp4|m4v'      => 'video/mp4',
        'mp3|m4a'      => 'audio/mpeg',
        'zip'          => 'application/zip',
        'doc'          => 'application/msword',
        'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'          => 'application/vnd.ms-excel',
        'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'          => 'application/vnd.ms-powerpoint',
        'pptx'         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    // On repart d'une liste vierge pour n'autoriser que ce qu'on veut
    return $allowed;
});


/**
 * Validation supplémentaire des SVG uploadés
 * Les SVG peuvent contenir du JS malveillant
 */
add_filter('wp_check_filetype_and_ext', function (array $data, string $file, string $filename, array $mimes): array {

    if (!empty($data['ext']) && $data['ext'] === 'svg') {
        $svg_content = file_get_contents($file);

        // Détecte les SVG avec scripts embarqués
        if (
            $svg_content !== false &&
            (
                str_contains($svg_content, '<script') ||
                str_contains($svg_content, 'javascript:') ||
                str_contains($svg_content, 'onload=') ||
                str_contains($svg_content, 'onerror=')
            )
        ) {
            $data['ext']  = false;
            $data['type'] = false;
        }
    }

    return $data;
}, 10, 4);


// =============================================================================
// ADMIN — NETTOYAGE ET SÉCURISATION
// =============================================================================

// Supprime le widget "WordPress Events and News" du dashboard (données externes)
add_action('wp_dashboard_setup', function (): void {
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
});

// Masque les erreurs PHP en front (toujours géré par WP_DEBUG)
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    @ini_set('display_errors', '0');
}

// Désactive la suggestion de login ("Vouliez-vous dire [login proche] ?")
add_filter('login_forgotpassword_form', '__return_empty_string');

// Redirige wp-login.php vers la home si accès direct non légitime
// (décommenter si vous avez une page de login personnalisée)
/*
add_action('login_init', function (): void {
    if (!isset($_GET['action']) && !isset($_POST['log'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});
*/


// =============================================================================
// MONITORING — LOG DES ÉVÉNEMENTS SENSIBLES
// En WP_DEBUG uniquement — ne jamais logger en production
// =============================================================================

if (defined('WP_DEBUG') && WP_DEBUG) {

    // Log des tentatives de connexion réussies
    add_action('wp_login', function (string $user_login): void {
        error_log(sprintf(
            'Eco Starter — Connexion réussie : %s depuis %s',
            $user_login,
            eco_get_client_ip()
        ));
    });

    // Log des erreurs 404 pour détecter les scans
    add_action('wp', function (): void {
        if (is_404()) {
            error_log(sprintf(
                'Eco Starter — 404 : %s depuis %s',
                sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '')),
                eco_get_client_ip()
            ));
        }
    });
}