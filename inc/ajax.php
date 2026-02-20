<?php
/**
 * Handlers AJAX natifs — Eco Starter
 *
 * - Recherche live (autocomplete)
 * - Chargement de posts (load more)
 * - Filtre de portfolio
 * - Handlers utilitaires
 *
 * Convention de nommage :
 * - Action WordPress : eco_<action>
 * - Fonction handler : eco_ajax_<action>
 * - Nonce : eco_ajax_<action>
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// RECHERCHE LIVE — autocomplete
// =============================================================================

add_action('wp_ajax_eco_search',        'eco_ajax_search');
add_action('wp_ajax_nopriv_eco_search', 'eco_ajax_search');

function eco_ajax_search(): void
{
    eco_verify_ajax_nonce('eco_ajax_nonce');

    $query = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));

    if (mb_strlen($query) < 2) {
        wp_send_json_success(['results' => [], 'total' => 0]);
    }

    // Types de posts inclus dans la recherche
    $post_types = apply_filters('eco_search_post_types', [
        'post',
        'page',
        'portfolio',
        'faq',
    ]);

    $search_query = new \WP_Query([
        's'              => $query,
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => apply_filters('eco_search_limit', 6),
        'no_found_rows'  => true,
        'fields'         => 'ids', // Récupère seulement les IDs pour la perf
    ]);

    $results = [];

    if ($search_query->have_posts()) {
        foreach ($search_query->posts as $post_id) {
            $post_id = (int) $post_id;

            $results[] = [
                'id'        => $post_id,
                'title'     => html_entity_decode(get_the_title($post_id), ENT_QUOTES, 'UTF-8'),
                'url'       => get_permalink($post_id),
                'type'      => get_post_type($post_id),
                'type_label'=> eco_get_post_type_label($post_id),
                'thumbnail' => has_post_thumbnail($post_id)
                    ? get_the_post_thumbnail_url($post_id, 'thumbnail')
                    : null,
                'excerpt'   => eco_excerpt(
                    get_the_excerpt($post_id) ?: get_the_content(null, false, $post_id),
                    12
                ),
            ];
        }
    }

    wp_send_json_success([
        'results' => $results,
        'total'   => count($results),
        'query'   => $query,
    ]);
}


/**
 * Retourne le label lisible du type de post
 */
function eco_get_post_type_label(int $post_id): string
{
    $post_type = get_post_type($post_id);
    $pto       = get_post_type_object($post_type);

    return $pto ? $pto->labels->singular_name : $post_type;
}


// =============================================================================
// LOAD MORE — chargement de posts supplémentaires
// Utilisé dans les archives et la grille portfolio
// =============================================================================

add_action('wp_ajax_eco_load_more',        'eco_ajax_load_more');
add_action('wp_ajax_nopriv_eco_load_more', 'eco_ajax_load_more');

function eco_ajax_load_more(): void
{
    eco_verify_ajax_nonce('eco_ajax_nonce');

    $post_type  = sanitize_key(wp_unslash($_POST['post_type']  ?? 'post'));
    $page       = absint($_POST['page']       ?? 1);
    $per_page   = absint($_POST['per_page']   ?? get_option('posts_per_page'));
    $taxonomy   = sanitize_key(wp_unslash($_POST['taxonomy']   ?? ''));
    $term_slug  = sanitize_title(wp_unslash($_POST['term']     ?? ''));
    $template   = sanitize_key(wp_unslash($_POST['template']   ?? 'card'));
    $order_by   = sanitize_key(wp_unslash($_POST['orderby']    ?? 'date'));
    $order      = in_array(strtoupper($_POST['order'] ?? 'DESC'), ['ASC', 'DESC'], true)
                  ? strtoupper($_POST['order'])
                  : 'DESC';

    // Sécurité : limite le nombre de posts par requête
    $per_page = min($per_page, 24);

    // Construction des args WP_Query
    $args = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => $order_by,
        'order'          => $order,
        'no_found_rows'  => false,
    ];

    // Filtre par taxonomie
    if ($taxonomy && $term_slug) {
        $args['tax_query'] = [[
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $term_slug,
        ]];
    }

    // Filtre par meta (ex: projets mis en avant)
    $meta_key   = sanitize_key(wp_unslash($_POST['meta_key']   ?? ''));
    $meta_value = sanitize_text_field(wp_unslash($_POST['meta_value'] ?? ''));

    if ($meta_key && $meta_value) {
        $args['meta_query'] = [[
            'key'     => $meta_key,
            'value'   => $meta_value,
            'compare' => '=',
        ]];
    }

    // Hook pour permettre la modification des args
    $args = apply_filters('eco_load_more_args', $args, $_POST);

    $query = new \WP_Query($args);

    if (!$query->have_posts()) {
        wp_send_json_success([
            'html'     => '',
            'found'    => 0,
            'has_more' => false,
        ]);
    }

    // Rendu HTML des cards
    ob_start();

    while ($query->have_posts()) {
        $query->the_post();

        // Cherche le template dans template-parts/cards/
        $template_file = ECO_STARTER_DIR . "/template-parts/cards/{$template}.php";
        $fallback_file = ECO_STARTER_DIR . "/template-parts/cards/post.php";

        if (file_exists($template_file)) {
            load_template($template_file, false, ['is_ajax' => true]);
        } elseif (file_exists($fallback_file)) {
            load_template($fallback_file, false, ['is_ajax' => true]);
        }
    }

    $html = ob_get_clean();
    wp_reset_postdata();

    $total_pages = (int) $query->max_num_pages;

    wp_send_json_success([
        'html'     => $html,
        'found'    => (int) $query->found_posts,
        'page'     => $page,
        'has_more' => $page < $total_pages,
        'total_pages' => $total_pages,
    ]);
}


// =============================================================================
// FILTRE PORTFOLIO — filtrage par taxonomie sans rechargement
// =============================================================================

add_action('wp_ajax_eco_filter_portfolio',        'eco_ajax_filter_portfolio');
add_action('wp_ajax_nopriv_eco_filter_portfolio', 'eco_ajax_filter_portfolio');

function eco_ajax_filter_portfolio(): void
{
    eco_verify_ajax_nonce('eco_ajax_nonce');

    $category = sanitize_title(wp_unslash($_POST['category'] ?? ''));
    $tag      = sanitize_title(wp_unslash($_POST['tag']      ?? ''));
    $page     = absint($_POST['page'] ?? 1);
    $per_page = min(absint($_POST['per_page'] ?? 12), 24);

    $args = [
        'post_type'      => 'portfolio',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ];

    // Filtre par catégorie + tag (combinés si les deux sont fournis)
    $tax_query = [];

    if ($category && $category !== 'all') {
        $tax_query[] = [
            'taxonomy' => 'portfolio_category',
            'field'    => 'slug',
            'terms'    => $category,
        ];
    }

    if ($tag) {
        $tax_query[] = [
            'taxonomy' => 'portfolio_tag',
            'field'    => 'slug',
            'terms'    => $tag,
        ];
    }

    if (!empty($tax_query)) {
        $args['tax_query'] = array_merge(
            ['relation' => 'AND'],
            $tax_query
        );
    }

    $args = apply_filters('eco_filter_portfolio_args', $args, $_POST);

    $query = new \WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            eco_get_part('cards/portfolio', ['is_ajax' => true]);
        }
    } else {
        echo '<div class="no-results">';
        echo '<p>' . esc_html__('Aucun projet trouvé pour ce filtre.', 'eco-starter') . '</p>';
        echo '</div>';
    }

    $html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success([
        'html'       => $html,
        'found'      => (int) $query->found_posts,
        'has_more'   => $page < (int) $query->max_num_pages,
        'total_pages'=> (int) $query->max_num_pages,
    ]);
}


// =============================================================================
// NEWSLETTER — inscription (hook vers service tiers)
// Connecter au service du client via le filtre eco_newsletter_subscribe
// =============================================================================

add_action('wp_ajax_eco_newsletter',        'eco_ajax_newsletter');
add_action('wp_ajax_nopriv_eco_newsletter', 'eco_ajax_newsletter');

function eco_ajax_newsletter(): void
{
    eco_verify_ajax_nonce('eco_ajax_nonce');

    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));

    if (!is_email($email)) {
        wp_send_json_error([
            'message' => __('Adresse email invalide.', 'eco-starter'),
            'code'    => 'invalid_email',
        ], 422);
    }

    // Rate limiting
    $ip      = eco_get_client_ip();
    $key     = 'eco_newsletter_' . md5($ip);
    $count   = (int) get_transient($key);

    if ($count >= 3) {
        wp_send_json_error([
            'message' => __('Trop de tentatives. Veuillez réessayer plus tard.', 'eco-starter'),
            'code'    => 'rate_limit',
        ], 429);
    }

    /**
     * Hook pour connecter le service de newsletter du client
     * Retourner WP_Error en cas d'échec, true en cas de succès
     *
     * @example
     * add_filter('eco_newsletter_subscribe', function($result, $email) {
     *     // Mailchimp, Brevo, Klaviyo, etc.
     *     return my_newsletter_service_subscribe($email);
     * }, 10, 2);
     */
    $result = apply_filters('eco_newsletter_subscribe', true, $email);

    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message(),
            'code'    => $result->get_error_code(),
        ], 500);
    }

    set_transient($key, $count + 1, HOUR_IN_SECONDS);

    wp_send_json_success([
        'message' => __('Merci ! Vous êtes maintenant inscrit à notre newsletter.', 'eco-starter'),
    ]);
}


// =============================================================================
// LIKE / BOOKMARK — persisté en user meta ou cookie
// =============================================================================

add_action('wp_ajax_eco_toggle_like',        'eco_ajax_toggle_like');
add_action('wp_ajax_nopriv_eco_toggle_like', 'eco_ajax_toggle_like');

function eco_ajax_toggle_like(): void
{
    eco_verify_ajax_nonce('eco_ajax_nonce');

    $post_id = absint($_POST['post_id'] ?? 0);

    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error(['message' => 'Post introuvable.'], 404);
    }

    $count_key = 'eco_like_count';

    if (is_user_logged_in()) {
        // Utilisateurs connectés : stocké en user meta
        $user_id   = get_current_user_id();
        $liked_key = 'eco_liked_posts';
        $liked     = (array) get_user_meta($user_id, $liked_key, true);

        if (in_array($post_id, $liked, true)) {
            // Retire le like
            $liked = array_diff($liked, [$post_id]);
            $liked_action = 'unliked';
        } else {
            // Ajoute le like
            $liked[]      = $post_id;
            $liked_action = 'liked';
        }

        update_user_meta($user_id, $liked_key, array_values($liked));

    } else {
        // Visiteurs anonymes : stocké en post meta globale (approx.)
        $liked_action = 'liked'; // Toujours "liked" pour les anonymes
    }

    // Met à jour le compteur global
    $current_count = (int) get_post_meta($post_id, $count_key, true);
    $new_count     = $liked_action === 'liked'
        ? $current_count + 1
        : max(0, $current_count - 1);

    update_post_meta($post_id, $count_key, $new_count);

    wp_send_json_success([
        'action' => $liked_action,
        'count'  => $new_count,
    ]);
}


// =============================================================================
// HELPER — Localize script pour les pages qui ont besoin de l'AJAX
// Appelé depuis enqueue.php via eco_enqueue_script()
// =============================================================================

/**
 * Prépare les variables JS communes pour toutes les pages
 * Appelé dans inc/enqueue.php après eco_enqueue_script('navigation', ...)
 */
function eco_get_ajax_localize_data(): array
{
    return [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('eco_ajax_nonce'),
        'homeUrl' => home_url('/'),
        'i18n'    => [
            'menuOpen'         => __('Ouvrir le menu', 'eco-starter'),
            'menuClose'        => __('Fermer le menu', 'eco-starter'),
            'formSending'      => __('Envoi en cours…', 'eco-starter'),
            'formSuccess'      => __('Message envoyé avec succès.', 'eco-starter'),
            'formError'        => __('Une erreur est survenue. Veuillez réessayer.', 'eco-starter'),
            'loadMore'         => __('Charger plus', 'eco-starter'),
            'loading'          => __('Chargement…', 'eco-starter'),
            'noMoreResults'    => __('Tous les résultats sont affichés.', 'eco-starter'),
            'searchPlaceholder'=> __('Rechercher…', 'eco-starter'),
            'searchNoResults'  => __('Aucun résultat pour cette recherche.', 'eco-starter'),
            'expanded'         => __('développé', 'eco-starter'),
            'collapsed'        => __('réduit', 'eco-starter'),
            'modalOpened'      => __('Dialogue ouvert', 'eco-starter'),
            'fieldRequired'    => __('Ce champ est requis.', 'eco-starter'),
        ],
    ];
}