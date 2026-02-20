<?php
/**
 * Accessibilité — Eco Starter
 *
 * WCAG 2.1 niveau AA minimum
 *
 * - Skip links (navigation clavier)
 * - ARIA landmarks
 * - Live regions (annonces dynamiques)
 * - Focus management
 * - Helpers templates
 * - Corrections WordPress natives (alt, labels, etc.)
 * - Vérifications en WP_DEBUG
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// SKIP LINKS
// Permettent aux utilisateurs clavier de sauter la navigation
// et d'atteindre directement le contenu principal
// =============================================================================

add_action('wp_body_open', function (): void {
    echo eco_get_skip_links();
});

/**
 * Génère le HTML des skip links
 * Appelé via wp_body_open() — hook WordPress 5.2+
 *
 * @return string HTML des skip links
 */
function eco_get_skip_links(): string
{
    $links = [
        'main-content' => __('Aller au contenu principal', 'eco-starter'),
        'site-nav'     => __('Aller à la navigation', 'eco-starter'),
        'site-footer'  => __('Aller au pied de page', 'eco-starter'),
    ];

    // Filtre pour permettre l'ajout/suppression de skip links
    $links = apply_filters('eco_skip_links', $links);

    if (empty($links)) return '';

    $html  = '<nav class="skip-links" aria-label="' . esc_attr__('Navigation rapide', 'eco-starter') . '">' . "\n";

    foreach ($links as $target => $label) {
        $html .= sprintf(
            '<a class="skip-link" href="#%s">%s</a>' . "\n",
            esc_attr($target),
            esc_html($label)
        );
    }

    $html .= '</nav>' . "\n";

    return $html;
}


// =============================================================================
// ARIA LANDMARKS — IDs obligatoires sur les zones principales
// Les IDs correspondent aux cibles des skip links
// =============================================================================

/**
 * Ajoute l'attribut id="main-content" sur la balise <main>
 * si le thème utilise wp_body_open() correctement.
 *
 * En pratique, les IDs sont directement dans les templates :
 * - header.php  → id="site-header"
 * - nav         → id="site-nav"
 * - main        → id="main-content"
 * - footer.php  → id="site-footer"
 *
 * Cette fonction génère les wrappers si appelée dans les templates.
 */

/**
 * Ouvre la balise <main> avec les attributs ARIA corrects
 *
 * Usage dans les templates :
 * <?php eco_main_open(); ?>
 *   ... contenu ...
 * <?php eco_main_close(); ?>
 */
function eco_main_open(): void
{
    echo '<main id="main-content" class="site-content" tabindex="-1"'
        . ' role="main"'
        . ' aria-label="' . esc_attr__('Contenu principal', 'eco-starter') . '">'
        . "\n";
}

function eco_main_close(): void
{
    echo '</main>' . "\n";
}


// =============================================================================
// LIVE REGIONS — Annonces dynamiques pour les lecteurs d'écran
// Injectées une seule fois dans le DOM, mises à jour via JS
// =============================================================================

add_action('wp_footer', function (): void {
    echo eco_get_live_regions();
}, 1);

/**
 * Génère les live regions ARIA
 * Le JS du thème écrit dans ces zones pour annoncer les changements dynamiques
 * (formulaire envoyé, erreur, chargement, etc.)
 *
 * @return string HTML des live regions
 */
function eco_get_live_regions(): string
{
    return
        '<!-- Live regions ARIA — ne pas supprimer, utilisées par navigation.js et forms.js -->' . "\n"
        // assertive : interruption immédiate (erreurs critiques)
        . '<div id="eco-live-assertive"'
        . ' role="alert"'
        . ' aria-live="assertive"'
        . ' aria-atomic="true"'
        . ' class="sr-only"'
        . ' aria-relevant="all">'
        . '</div>' . "\n"
        // polite : annonce à la fin de la lecture en cours (succès, infos)
        . '<div id="eco-live-polite"'
        . ' role="status"'
        . ' aria-live="polite"'
        . ' aria-atomic="true"'
        . ' class="sr-only"'
        . ' aria-relevant="additions text">'
        . '</div>' . "\n";
}


// =============================================================================
// HELPERS TEMPLATES
// Fonctions utilitaires pour générer du HTML accessible
// =============================================================================

/**
 * Génère un bouton accessible avec ARIA
 *
 * @param string $label    Texte visible du bouton
 * @param array  $attrs    Attributs HTML supplémentaires
 * @param string $icon     Nom du SVG (optionnel)
 * @return string          HTML du bouton
 */
function eco_button(
    string $label,
    array $attrs = [],
    string $icon = ''
): string {
    // Classes CSS par défaut
    $attrs['class'] = $attrs['class'] ?? 'btn btn--primary';
    $attrs['type']  = $attrs['type']  ?? 'button';

    // Construit la chaîne d'attributs
    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }

    $content = '';

    // Icône avant le texte
    if ($icon) {
        $content .= '<span class="btn__icon" aria-hidden="true">'
            . eco_svg($icon)
            . '</span>';
    }

    $content .= '<span class="btn__label">' . esc_html($label) . '</span>';

    return '<button' . $attr_string . '>' . $content . '</button>';
}


/**
 * Génère une image accessible
 * Refuse les images sans alt quand elles ne sont pas décoratives
 *
 * @param string $src         URL de l'image
 * @param string $alt         Texte alternatif (vide string = décoratif)
 * @param array  $attrs       Attributs supplémentaires
 * @param bool   $decorative  true = image décorative (alt="" + aria-hidden="true")
 * @return string             HTML de l'image
 */
function eco_img(
    string $src,
    string $alt = '',
    array $attrs = [],
    bool $decorative = false
): string {
    if (empty($src)) return '';

    // Image décorative → alt vide + aria-hidden
    if ($decorative) {
        $attrs['alt']        = '';
        $attrs['aria-hidden'] = 'true';
        $attrs['role']       = 'presentation';
    } else {
        $attrs['alt'] = $alt;
        // En dev : alerte si alt manquant sur une image non décorative
        if (empty($alt) && defined('WP_DEBUG') && WP_DEBUG) {
            trigger_error(
                sprintf('Eco Starter Accessibilité : attribut alt manquant sur %s', $src),
                E_USER_NOTICE
            );
        }
    }

    // Lazy loading par défaut
    $attrs['loading']  ??= 'lazy';
    $attrs['decoding'] ??= 'async';
    $attrs['src']       = $src;

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }

    return '<img' . $attr_string . '>';
}


/**
 * Génère un lien avec indication visuelle et accessible pour les liens externes
 *
 * @param string $url     URL du lien
 * @param string $label   Texte du lien
 * @param array  $attrs   Attributs supplémentaires
 * @param bool   $external Force le traitement comme lien externe
 * @return string         HTML du lien
 */
function eco_link(
    string $url,
    string $label,
    array $attrs = [],
    bool $external = false
): string {
    if (empty($url) || empty($label)) return '';

    $is_external = $external || eco_is_external_url($url);

    $attrs['href'] = $url;

    if ($is_external) {
        $attrs['target']              = '_blank';
        $attrs['rel']                 = 'noopener noreferrer';
        // Annonce aux lecteurs d'écran que le lien s'ouvre dans un nouvel onglet
        $attrs['aria-label'] = $label . ' ' . __('(s\'ouvre dans un nouvel onglet)', 'eco-starter');
    }

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }

    $content = esc_html($label);

    // Icône visuelle pour les liens externes
    if ($is_external) {
        $content .= ' <span class="link__external-icon" aria-hidden="true">'
            . eco_svg('external-link', ['class' => 'icon icon--xs'])
            . '</span>';
    }

    return '<a' . $attr_string . '>' . $content . '</a>';
}


/**
 * Vérifie si une URL est externe au site
 *
 * @param string $url URL à vérifier
 * @return bool
 */
function eco_is_external_url(string $url): bool
{
    if (empty($url) || str_starts_with($url, '#') || str_starts_with($url, '/')) {
        return false;
    }

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $url_host  = wp_parse_url($url, PHP_URL_HOST);

    if (!$url_host) return false;

    return $url_host !== $site_host;
}


/**
 * Génère un groupe de boutons radio accessible
 *
 * @param string $legend  Légende du groupe (affiché ou sr-only)
 * @param string $name    Attribut name des inputs
 * @param array  $options ['value' => 'label'] tableau des options
 * @param string $current Valeur sélectionnée
 * @param bool   $sr_legend  Légende visible ou sr-only
 * @return string           HTML du groupe
 */
function eco_radio_group(
    string $legend,
    string $name,
    array $options,
    string $current = '',
    bool $sr_legend = false
): string {
    if (empty($options)) return '';

    $legend_class = $sr_legend ? ' class="sr-only"' : '';
    $html = '<fieldset><legend' . $legend_class . '>' . esc_html($legend) . '</legend>';

    foreach ($options as $value => $label) {
        $id      = esc_attr($name . '_' . sanitize_key($value));
        $checked = checked($current, $value, false);

        $html .= sprintf(
            '<div class="field field--check">'
            . '<input type="radio" id="%s" name="%s" value="%s"%s>'
            . '<label for="%s">%s</label>'
            . '</div>',
            $id,
            esc_attr($name),
            esc_attr($value),
            $checked,
            $id,
            esc_html($label)
        );
    }

    $html .= '</fieldset>';

    return $html;
}


/**
 * Génère un select accessible avec label
 *
 * @param string $id      ID et name du select
 * @param string $label   Label visible
 * @param array  $options ['value' => 'label']
 * @param string $current Valeur sélectionnée
 * @param array  $attrs   Attributs supplémentaires sur le select
 * @return string         HTML
 */
function eco_select(
    string $id,
    string $label,
    array $options,
    string $current = '',
    array $attrs = []
): string {
    if (empty($options)) return '';

    $attrs['id']   = $id;
    $attrs['name'] = $attrs['name'] ?? $id;

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }

    $html  = '<div class="field">';
    $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
    $html .= '<select' . $attr_string . '>';

    foreach ($options as $value => $option_label) {
        $html .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr($value),
            selected($current, $value, false),
            esc_html($option_label)
        );
    }

    $html .= '</select>';
    $html .= '</div>';

    return $html;
}


/**
 * Génère un message d'état accessible (erreur, succès, info)
 *
 * @param string $message  Texte du message
 * @param string $type     'success' | 'error' | 'warning' | 'info'
 * @param bool   $live     true = injecté dans la live region via JS
 * @return string          HTML du message
 */
function eco_status_message(
    string $message,
    string $type = 'info',
    bool $live = false
): string {
    if (empty($message)) return '';

    $roles = [
        'success' => 'status',
        'error'   => 'alert',
        'warning' => 'alert',
        'info'    => 'status',
    ];

    $role = $roles[$type] ?? 'status';

    $attrs = sprintf(
        'class="alert alert--%s" role="%s" aria-live="%s" aria-atomic="true"',
        esc_attr($type),
        esc_attr($role),
        $role === 'alert' ? 'assertive' : 'polite'
    );

    if ($live) {
        $attrs .= ' data-live-region';
    }

    return '<div ' . $attrs . '>' . wp_kses_post($message) . '</div>';
}


// =============================================================================
// CORRECTIONS WORDPRESS NATIVES
// WordPress génère parfois du HTML peu accessible
// Ces filtres corrigent les cas les plus fréquents
// =============================================================================

/**
 * Ajoute aria-label sur les liens "Lire la suite" de WordPress
 * Le texte "Lire la suite" seul n'est pas assez descriptif pour les SR
 */
add_filter('the_content_more_link', function (string $link): string {

    $title = get_the_title();

    // Ajoute aria-label avec le titre de l'article
    $link = str_replace(
        '<a href=',
        '<a aria-label="' . esc_attr(sprintf(__('Lire la suite de %s', 'eco-starter'), $title)) . '" href=',
        $link
    );

    return $link;
});


/**
 * Ajoute aria-label sur les liens de pagination WordPress
 */
add_filter('next_posts_link_attributes', fn(): string => 'aria-label="' . esc_attr__('Page suivante', 'eco-starter') . '"');
add_filter('previous_posts_link_attributes', fn(): string => 'aria-label="' . esc_attr__('Page précédente', 'eco-starter') . '"');
add_filter('next_post_link', 'eco_add_aria_to_post_nav');
add_filter('previous_post_link', 'eco_add_aria_to_post_nav');

function eco_add_aria_to_post_nav(string $link): string
{
    // Extrait le titre depuis le lien pour construire l'aria-label
    if (preg_match('/rel="([^"]+)"/', $link, $matches)) {
        $rel = $matches[1];
        $label = $rel === 'next'
            ? __('Article suivant', 'eco-starter')
            : __('Article précédent', 'eco-starter');

        $link = str_replace('<a ', '<a aria-label="' . esc_attr($label) . '" ', $link);
    }

    return $link;
}


/**
 * Ajoute role="navigation" et aria-label sur les navigations WordPress
 */
add_filter('wp_nav_menu_args', function (array $args): array {

    // Mapping automatique des aria-label selon le theme_location
    $labels = [
        'primary'   => __('Navigation principale', 'eco-starter'),
        'footer'    => __('Navigation pied de page', 'eco-starter'),
        'secondary' => __('Navigation secondaire', 'eco-starter'),
        'mobile'    => __('Navigation mobile', 'eco-starter'),
    ];

    $location = $args['theme_location'] ?? '';
    if (isset($labels[$location]) && empty($args['aria_label'])) {
        $args['aria_label'] = $labels[$location];
        $args['container']  = $args['container'] ?? 'nav';
        $args['container_aria_label'] = $labels[$location];
    }

    return $args;
});


/**
 * Ajoute scope="col" sur les th de tableaux WordPress
 * Améliore la navigation dans les tableaux pour les SR
 */
add_filter('the_content', function (string $content): string {

    if (empty($content) || !str_contains($content, '<table')) return $content;

    // Ajoute scope="col" sur les th sans scope
    $content = preg_replace(
        '/<th(?![^>]*scope=)([^>]*)>/',
        '<th scope="col"$1>',
        $content
    ) ?? $content;

    // Ajoute role="table" si absent
    $content = preg_replace(
        '/<table(?![^>]*role=)([^>]*)>/',
        '<table role="table"$1>',
        $content
    ) ?? $content;

    return $content;
});


/**
 * Améliore les formulaires de recherche WordPress
 * Ajoute les attributs ARIA manquants
 */
add_filter('get_search_form', function (string $form): string {

    // Ajoute role="search" sur le formulaire
    $form = str_replace(
        '<form',
        '<form role="search"',
        $form
    );

    // Ajoute aria-label sur le champ de recherche
    $form = str_replace(
        'name="s"',
        'name="s" aria-label="' . esc_attr__('Rechercher dans le site', 'eco-starter') . '"',
        $form
    );

    return $form;
});


/**
 * Ajoute alt="" sur les images de Gravatar sans alt
 */
add_filter('get_avatar', function (string $avatar): string {

    if (!str_contains($avatar, ' alt=') && !str_contains($avatar, " alt='")) {
        $avatar = str_replace('<img ', '<img alt="" ', $avatar);
    }

    return $avatar;
});


// =============================================================================
// FOCUS MANAGEMENT
// Gestion du focus pour les interactions dynamiques
// Complété par navigation.js côté JS
// =============================================================================

/**
 * Génère un lien d'ancrage invisible pour le focus management
 * Utilisé après des actions AJAX pour repositionner le focus
 *
 * @param string $id     ID de l'ancre
 * @param string $label  Label pour les SR (sr-only)
 * @return string        HTML
 */
function eco_focus_anchor(string $id, string $label = ''): string
{
    return sprintf(
        '<span id="%s" tabindex="-1" class="sr-only">%s</span>',
        esc_attr($id),
        esc_html($label)
    );
}


// =============================================================================
// AUDIT ACCESSIBILITÉ — WP_DEBUG uniquement
// Détecte les problèmes courants et les log dans la console
// =============================================================================

if (defined('WP_DEBUG') && WP_DEBUG) {

    add_action('wp_footer', function (): void {

        if (is_admin()) return;

        ?>
        <script>
        (function() {
            'use strict';

            const issues = [];

            // --- Images sans alt ---
            document.querySelectorAll('img:not([alt])').forEach(img => {
                issues.push({
                    type: 'error',
                    rule: 'WCAG 1.1.1',
                    msg: 'Image sans attribut alt',
                    el: img
                });
            });

            // --- Inputs sans label ---
            document.querySelectorAll('input, select, textarea').forEach(input => {
                const type = input.getAttribute('type');
                if (['hidden', 'submit', 'reset', 'button', 'image'].includes(type)) return;

                const id     = input.getAttribute('id');
                const label  = id ? document.querySelector(`label[for="${id}"]`) : null;
                const ariaLabel   = input.getAttribute('aria-label');
                const ariaLabeled = input.getAttribute('aria-labelledby');

                if (!label && !ariaLabel && !ariaLabeled) {
                    issues.push({
                        type: 'error',
                        rule: 'WCAG 1.3.1',
                        msg: 'Champ de formulaire sans label',
                        el: input
                    });
                }
            });

            // --- Liens sans texte discernible ---
            document.querySelectorAll('a').forEach(link => {
                const text      = link.textContent.trim();
                const ariaLabel = link.getAttribute('aria-label');
                const ariaLabeled = link.getAttribute('aria-labelledby');
                const title     = link.getAttribute('title');
                const hasImg    = link.querySelector('img[alt]');

                if (!text && !ariaLabel && !ariaLabeled && !title && !hasImg) {
                    issues.push({
                        type: 'error',
                        rule: 'WCAG 2.4.4',
                        msg: 'Lien sans texte discernible',
                        el: link
                    });
                }

                // Liens "Lire la suite" génériques
                if (['lire la suite', 'read more', 'en savoir plus', 'cliquez ici', 'ici', 'here'].includes(text.toLowerCase())) {
                    issues.push({
                        type: 'warning',
                        rule: 'WCAG 2.4.6',
                        msg: `Texte de lien générique : "${text}"`,
                        el: link
                    });
                }
            });

            // --- Headings — ordre de hiérarchie ---
            const headings = [...document.querySelectorAll('h1, h2, h3, h4, h5, h6')];
            let prevLevel  = 0;

            headings.forEach(h => {
                const level = parseInt(h.tagName[1]);
                if (prevLevel > 0 && level > prevLevel + 1) {
                    issues.push({
                        type: 'warning',
                        rule: 'WCAG 1.3.1',
                        msg: `Saut de niveau de titre : h${prevLevel} → h${level}`,
                        el: h
                    });
                }
                prevLevel = level;
            });

            // --- Vérification h1 unique ---
            const h1s = document.querySelectorAll('h1');
            if (h1s.length === 0) {
                issues.push({ type: 'error', rule: 'WCAG 1.3.1', msg: 'Aucun h1 sur la page', el: document.body });
            } else if (h1s.length > 1) {
                issues.push({ type: 'warning', rule: 'WCAG 1.3.1', msg: `${h1s.length} balises h1 trouvées (1 recommandé)`, el: h1s[1] });
            }

            // --- Landmarks ARIA obligatoires ---
            const landmarksRequired = [
                { selector: 'main, [role="main"]',        label: 'main / [role="main"]' },
                { selector: 'nav, [role="navigation"]',   label: 'nav / [role="navigation"]' },
            ];

            landmarksRequired.forEach(({ selector, label }) => {
                if (!document.querySelector(selector)) {
                    issues.push({
                        type: 'error',
                        rule: 'WCAG 1.3.6',
                        msg: `Landmark manquant : ${label}`,
                        el: document.body
                    });
                }
            });

            // --- Focus visible ---
            // Vérifie que :focus-visible est défini (test indirect)
            const style = getComputedStyle(document.documentElement);
            const focusColor = style.getPropertyValue('--color-focus').trim();
            if (!focusColor) {
                issues.push({
                    type: 'warning',
                    rule: 'WCAG 2.4.7',
                    msg: 'Variable CSS --color-focus non définie (vérifier tokens.css)',
                    el: document.documentElement
                });
            }

            // --- Rapport console ---
            if (issues.length === 0) {
                console.log('%c✅ Eco Starter — Accessibilité : aucun problème détecté', 'color: #16a34a; font-weight: bold;');
                return;
            }

            const errors   = issues.filter(i => i.type === 'error');
            const warnings = issues.filter(i => i.type === 'warning');

            console.groupCollapsed(
                `%c♿ Eco Starter — Accessibilité : ${errors.length} erreur(s), ${warnings.length} avertissement(s)`,
                'color: #dc2626; font-weight: bold;'
            );

            issues.forEach(issue => {
                const style = issue.type === 'error' ? 'color:#dc2626' : 'color:#d97706';
                const icon  = issue.type === 'error' ? '❌' : '⚠️';
                console.groupCollapsed(`%c${icon} [${issue.rule}] ${issue.msg}`, style);
                console.log('Élément :', issue.el);
                console.groupEnd();
            });

            console.groupEnd();

        })();
        </script>
        <?php

    }, 999);
}


// =============================================================================
// CONTRASTE — HELPERS
// Fonctions utilitaires pour vérifier le contraste en PHP
// =============================================================================

/**
 * Calcule la luminance relative d'une couleur hex
 * Formule WCAG 2.1
 *
 * @param string $hex Couleur hex (#rrggbb ou #rgb)
 * @return float      Luminance entre 0 et 1
 */
function eco_get_luminance(string $hex): float
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6) return 0.0;

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    // Linéarisation
    $linearize = fn(float $c): float => $c <= 0.04045
        ? $c / 12.92
        : (($c + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $linearize($r)
         + 0.7152 * $linearize($g)
         + 0.0722 * $linearize($b);
}


/**
 * Calcule le ratio de contraste entre deux couleurs
 * WCAG AA : 4.5:1 (texte normal), 3:1 (grand texte)
 * WCAG AAA : 7:1 (texte normal), 4.5:1 (grand texte)
 *
 * @param string $color1 Couleur hex foreground
 * @param string $color2 Couleur hex background
 * @return float         Ratio de contraste
 */
function eco_get_contrast_ratio(string $color1, string $color2): float
{
    $l1 = eco_get_luminance($color1);
    $l2 = eco_get_luminance($color2);

    $lighter = max($l1, $l2);
    $darker  = min($l1, $l2);

    return round(($lighter + 0.05) / ($darker + 0.05), 2);
}


/**
 * Vérifie si deux couleurs passent le critère de contraste WCAG
 *
 * @param string $fg      Couleur foreground
 * @param string $bg      Couleur background
 * @param string $level   'AA' ou 'AAA'
 * @param bool   $large   true = grand texte (>= 18pt ou 14pt bold)
 * @return bool
 */
function eco_passes_contrast(
    string $fg,
    string $bg,
    string $level = 'AA',
    bool $large = false
): bool {
    $ratio = eco_get_contrast_ratio($fg, $bg);

    $required = match(true) {
        $level === 'AAA' && !$large => 7.0,
        $level === 'AAA' && $large  => 4.5,
        $level === 'AA'  && !$large => 4.5,
        $level === 'AA'  && $large  => 3.0,
        default                     => 4.5,
    };

    return $ratio >= $required;
}


// =============================================================================
// AUDIT CONTRASTE — WP_DEBUG
// Vérifie les paires de couleurs du design system au chargement
// =============================================================================

if (defined('WP_DEBUG') && WP_DEBUG) {

    add_action('wp_footer', function (): void {

        if (is_admin()) return;

        // Paires à vérifier : [foreground, background, label, grand_texte]
        $pairs = [
            ['#1a1a1a', '#ffffff', 'Texte principal sur fond blanc', false],
            ['#2563eb', '#ffffff', 'Couleur primaire sur fond blanc', false],
            ['#ffffff', '#2563eb', 'Blanc sur couleur primaire', false],
            ['#6b7280', '#ffffff', 'Texte atténué sur fond blanc', false],
            ['#6b7280', '#f9fafb', 'Texte atténué sur surface', false],
            ['#ffffff', '#1f2937', 'Blanc sur fond sombre', false],
        ];

        $issues = [];

        foreach ($pairs as [$fg, $bg, $label, $large]) {
            $ratio = eco_get_contrast_ratio($fg, $bg);
            $passes_aa = eco_passes_contrast($fg, $bg, 'AA', $large);

            if (!$passes_aa) {
                $issues[] = [
                    'label' => $label,
                    'ratio' => $ratio,
                    'fg'    => $fg,
                    'bg'    => $bg,
                ];
            }
        }

        if (empty($issues)) return;

        echo '<script>';
        echo 'console.groupCollapsed("%c🎨 Eco Starter — Contraste : ' . count($issues) . ' paire(s) insuffisante(s)", "color:#d97706;font-weight:bold;");';

        foreach ($issues as $issue) {
            echo 'console.warn("' . esc_js($issue['label']) . ' : ratio ' . esc_js((string)$issue['ratio']) . ':1 (min 4.5:1 WCAG AA)");';
        }

        echo 'console.groupEnd();';
        echo '</script>' . "\n";

    }, 999);
}