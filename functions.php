<?php
/**
 * Eco Starter — Bootstrap principal
 *
 * Ce fichier est volontairement minimal : il ne fait que charger les modules.
 * Toute la logique est dans inc/
 *
 * @package EcoStarter
 * @version 1.0.0
 */

declare(strict_types=1);

// Sécurité — accès direct interdit
defined('ABSPATH') || exit;

/**
 * Version du thème — utilisée pour le versioning des assets
 * En prod, WordPress utilise la version du thème comme cache-buster
 */
define('ECO_STARTER_VERSION', wp_get_theme()->get('Version'));
define('ECO_STARTER_DIR',     get_template_directory());
define('ECO_STARTER_URI',     get_template_directory_uri());

/**
 * Chargeur de modules
 * Ordre important : setup avant enqueue, helpers avant tout le reste
 */
$eco_modules = [
    'inc/helpers.php',       // Fonctions utilitaires partagées
    'inc/setup.php',         // Supports thème, menus, sidebars, image sizes
    'inc/cpt.php',           // Custom Post Types & Taxonomies
    'inc/enqueue.php',       // Assets conditionnels CSS/JS
    'inc/seo.php',           // Meta, Schema.org, Open Graph
    'inc/performance.php',   // Cache, preload, critical CSS
    'inc/security.php',      // Headers, nonces, protections
    'inc/accessibility.php', // Skip links, ARIA helpers
    'inc/forms.php',         // Formulaires natifs
    'inc/mail.php',          // Templates wp_mail
    'inc/customizer.php',    // Options thème (Customizer)
    'inc/ajax.php',          // Handlers AJAX natifs
];

foreach ($eco_modules as $module) {
    $path = ECO_STARTER_DIR . '/' . $module;

    if (file_exists($path)) {
        require_once $path;
    } else {
        // En mode WP_DEBUG, on signale un module manquant
        if (defined('WP_DEBUG') && WP_DEBUG) {
            trigger_error(
                sprintf('Eco Starter : module manquant — %s', $path),
                E_USER_WARNING
            );
        }
    }
}

// WooCommerce — chargé uniquement si le plugin est actif
if (class_exists('WooCommerce')) {
    $wc_module = ECO_STARTER_DIR . '/inc/woocommerce.php';
    if (file_exists($wc_module)) {
        require_once $wc_module;
    }
}