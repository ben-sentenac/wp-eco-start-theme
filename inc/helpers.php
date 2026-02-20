<?php
/**
 * Fonctions utilitaires partagées dans tout le thème
 *
 * Règles :
 * - Pas de hooks ici, uniquement des fonctions pures
 * - Préfixe systématique : eco_
 * - Toutes les fonctions sont documentées
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Retourne le chemin d'un template-part avec fallback
 *
 * Usage : eco_get_part('cards/post')
 * Cherche dans template-parts/cards/post.php
 *
 * @param string $slug    Chemin relatif sans extension (ex: 'cards/post')
 * @param array  $args    Variables à passer au template
 */
function eco_get_part(string $slug, array $args = []):void {
    get_template_part('template_parts/',$slug,null,$args);
}

/**
 * Retourne un SVG inline depuis assets/svg/
 * Avantage : stylable via CSS, pas de requête HTTP
 *
 * Usage : echo eco_svg('arrow-right', ['class' => 'icon icon--sm', 'aria-hidden' => 'true'])
 *
 * @param string $name  Nom du fichier SVG sans extension
 * @param array  $attrs Attributs HTML à ajouter sur la balise <svg>
 * @return string       SVG inline ou chaîne vide si fichier absent
 */
function eco_svg(string $name, array $attrs = []): string
{
    $path = ECO_STARTER_DIR . '/assets/svg/' . $name . '.svg';

    if (!file_exists($path)) {
        return '';
    }

    $svg = file_get_contents($path);
    if ($svg === false) {
        return '';
    }

    // Injection des attributs supplémentaires sur la balise <svg>
    if (!empty($attrs)) {
        $attr_string = '';
        foreach ($attrs as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        $svg = preg_replace('/<svg/', '<svg' . $attr_string, $svg, 1);
    }

    return $svg;
}

/**
 * Retourne la valeur d'un champ personnalisé avec fallback
 * Compatible ACF (si disponible) et meta WordPress native
 *
 * @param string $field    Nom du champ
 * @param int    $post_id  ID du post (0 = post courant)
 * @param mixed  $fallback Valeur par défaut si champ vide
 * @return mixed
 */
function eco_field(string $field, int $post_id = 0, mixed $fallback = ''): mixed
{
    $post_id = $post_id ?: get_the_ID();

    // ACF en priorité si disponible
    if (function_exists('get_field')) {
        $value = get_field($field, $post_id);
        return ($value !== null && $value !== false && $value !== '') ? $value : $fallback;
    }

    // Fallback natif WordPress
    $value = get_post_meta($post_id, $field, true);
    return ($value !== '') ? $value : $fallback;
}

/**
 * Génère une classe CSS conditionnelle propre
 * Filtre les valeurs falsy automatiquement
 *
 * Usage : echo eco_classes(['card', 'card--featured' => $is_featured, 'card--dark' => $is_dark])
 *
 * @param array $classes Tableau [classe => condition] ou [classe]
 * @return string
 */
function eco_classes(array $classes): string
{
    $result = [];

    foreach ($classes as $class => $condition) {
        if (is_int($class)) {
            // Classe inconditionnelle
            if (!empty($condition)) {
                $result[] = $condition;
            }
        } elseif ($condition) {
            $result[] = $class;
        }
    }

    return implode(' ', array_map('esc_attr', $result));
}

/**
 * Tronque un texte proprement (sans couper les mots) avec ellipse
 *
 * @param string $text    Texte source
 * @param int    $length  Nombre de mots maximum
 * @param string $more    Suffixe si tronqué
 * @return string
 */
function eco_excerpt(string $text, int $length = 20, string $more = '…'): string
{
    $text  = wp_strip_all_tags($text);
    $words = explode(' ', $text);

    if (count($words) <= $length) {
        return esc_html($text);
    }

    return esc_html(implode(' ', array_slice($words, 0, $length))) . $more;
}

/**
 * Vérifie si on est sur la home page (page d'accueil statique ou blog)
 */
function eco_is_home(): bool
{
    return is_front_page() || is_home();
}


/**
 * Retourne l'URL absolue du thème pour un asset
 *
 * @param string $path Chemin relatif depuis la racine du thème (ex: 'assets/css/main.css')
 * @return string
 */
function eco_asset(string $path): string
{
    return ECO_STARTER_URI . '/' . ltrim($path, '/');
}