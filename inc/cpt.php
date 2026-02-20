<?php
/**
 * Custom Post Types & Taxonomies — Eco Starter
 *
 * CPT déclarés :
 * - portfolio    → Réalisations / Projets
 * - team         → Équipe
 * - testimonial  → Témoignages
 * - faq          → Questions fréquentes
 *
 * Chaque CPT embarque :
 * - Déclaration register_post_type()
 * - Taxonomies associées
 * - Meta boxes natives (zéro ACF requis)
 * - Colonnes admin personnalisées
 * - Messages de notification admin traduits
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// ENREGISTREMENT DES CPT
// =============================================================================

add_action('init', function (): void {
    eco_register_cpt_portfolio();
    eco_register_cpt_team();
    eco_register_cpt_testimonial();
    eco_register_cpt_faq();
}, 0); // Priorité 0 — avant les autres hooks init


// =============================================================================
// PORTFOLIO
// =============================================================================

function eco_register_cpt_portfolio(): void
{
    $labels = [
        'name'                  => _x('Portfolio', 'post type general name', 'eco-starter'),
        'singular_name'         => _x('Projet', 'post type singular name', 'eco-starter'),
        'menu_name'             => _x('Portfolio', 'admin menu', 'eco-starter'),
        'add_new'               => __('Ajouter', 'eco-starter'),
        'add_new_item'          => __('Ajouter un projet', 'eco-starter'),
        'new_item'              => __('Nouveau projet', 'eco-starter'),
        'edit_item'             => __('Modifier le projet', 'eco-starter'),
        'view_item'             => __('Voir le projet', 'eco-starter'),
        'all_items'             => __('Tous les projets', 'eco-starter'),
        'search_items'          => __('Rechercher des projets', 'eco-starter'),
        'not_found'             => __('Aucun projet trouvé.', 'eco-starter'),
        'not_found_in_trash'    => __('Aucun projet dans la corbeille.', 'eco-starter'),
        'featured_image'        => __('Image du projet', 'eco-starter'),
        'set_featured_image'    => __('Définir l\'image du projet', 'eco-starter'),
        'remove_featured_image' => __('Retirer l\'image', 'eco-starter'),
    ];

    register_post_type('portfolio', [
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true, // Gutenberg + API
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-portfolio',
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
        'has_archive'        => 'portfolio', // → /portfolio/
        'rewrite'            => ['slug' => 'portfolio', 'with_front' => false],
        'query_var'          => true,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    ]);

    // Taxonomie : Catégorie de projet
    register_taxonomy('portfolio_category', 'portfolio', [
        'labels'            => [
            'name'              => _x('Catégories', 'taxonomy general name', 'eco-starter'),
            'singular_name'     => _x('Catégorie', 'taxonomy singular name', 'eco-starter'),
            'menu_name'         => __('Catégories', 'eco-starter'),
            'all_items'         => __('Toutes les catégories', 'eco-starter'),
            'edit_item'         => __('Modifier la catégorie', 'eco-starter'),
            'add_new_item'      => __('Ajouter une catégorie', 'eco-starter'),
            'new_item_name'     => __('Nouvelle catégorie', 'eco-starter'),
            'search_items'      => __('Rechercher', 'eco-starter'),
            'not_found'         => __('Aucune catégorie.', 'eco-starter'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'portfolio/categorie', 'with_front' => false],
    ]);

    // Taxonomie : Technologies / Tags projet
    register_taxonomy('portfolio_tag', 'portfolio', [
        'labels'            => [
            'name'          => _x('Technologies', 'taxonomy general name', 'eco-starter'),
            'singular_name' => _x('Technologie', 'taxonomy singular name', 'eco-starter'),
            'menu_name'     => __('Technologies', 'eco-starter'),
            'all_items'     => __('Toutes les technologies', 'eco-starter'),
            'add_new_item'  => __('Ajouter une technologie', 'eco-starter'),
            'not_found'     => __('Aucune technologie.', 'eco-starter'),
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'portfolio/techno', 'with_front' => false],
    ]);
}


// =============================================================================
// ÉQUIPE
// =============================================================================

function eco_register_cpt_team(): void
{
    $labels = [
        'name'                  => _x('Équipe', 'post type general name', 'eco-starter'),
        'singular_name'         => _x('Membre', 'post type singular name', 'eco-starter'),
        'menu_name'             => _x('Équipe', 'admin menu', 'eco-starter'),
        'add_new'               => __('Ajouter', 'eco-starter'),
        'add_new_item'          => __('Ajouter un membre', 'eco-starter'),
        'edit_item'             => __('Modifier le membre', 'eco-starter'),
        'all_items'             => __('Tous les membres', 'eco-starter'),
        'not_found'             => __('Aucun membre trouvé.', 'eco-starter'),
        'featured_image'        => __('Photo du membre', 'eco-starter'),
        'set_featured_image'    => __('Définir la photo', 'eco-starter'),
        'remove_featured_image' => __('Retirer la photo', 'eco-starter'),
    ];

    register_post_type('team', [
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => true,
        'menu_position'   => 6,
        'menu_icon'       => 'dashicons-groups',
        'supports'        => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'has_archive'     => 'equipe',
        'rewrite'         => ['slug' => 'equipe', 'with_front' => false],
        'query_var'       => true,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
    ]);

    // Taxonomie : Département / Pôle
    register_taxonomy('team_department', 'team', [
        'labels'            => [
            'name'          => _x('Départements', 'taxonomy general name', 'eco-starter'),
            'singular_name' => _x('Département', 'taxonomy singular name', 'eco-starter'),
            'menu_name'     => __('Départements', 'eco-starter'),
            'all_items'     => __('Tous les départements', 'eco-starter'),
            'add_new_item'  => __('Ajouter un département', 'eco-starter'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => ['slug' => 'equipe/departement', 'with_front' => false],
    ]);
}


// =============================================================================
// TÉMOIGNAGES
// =============================================================================

function eco_register_cpt_testimonial(): void
{
    $labels = [
        'name'               => _x('Témoignages', 'post type general name', 'eco-starter'),
        'singular_name'      => _x('Témoignage', 'post type singular name', 'eco-starter'),
        'menu_name'          => _x('Témoignages', 'admin menu', 'eco-starter'),
        'add_new'            => __('Ajouter', 'eco-starter'),
        'add_new_item'       => __('Ajouter un témoignage', 'eco-starter'),
        'edit_item'          => __('Modifier le témoignage', 'eco-starter'),
        'all_items'          => __('Tous les témoignages', 'eco-starter'),
        'not_found'          => __('Aucun témoignage trouvé.', 'eco-starter'),
        'featured_image'     => __('Photo du client', 'eco-starter'),
        'set_featured_image' => __('Définir la photo du client', 'eco-starter'),
    ];

    register_post_type('testimonial', [
        'labels'          => $labels,
        'public'          => false,  // Pas de page single publique
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => true,
        'menu_position'   => 7,
        'menu_icon'       => 'dashicons-format-quote',
        'supports'        => ['title', 'editor', 'thumbnail'],
        'has_archive'     => false,
        'rewrite'         => false,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
    ]);
}


// =============================================================================
// FAQ
// =============================================================================

function eco_register_cpt_faq(): void
{
    $labels = [
        'name'          => _x('FAQ', 'post type general name', 'eco-starter'),
        'singular_name' => _x('Question', 'post type singular name', 'eco-starter'),
        'menu_name'     => _x('FAQ', 'admin menu', 'eco-starter'),
        'add_new'       => __('Ajouter', 'eco-starter'),
        'add_new_item'  => __('Ajouter une question', 'eco-starter'),
        'edit_item'     => __('Modifier la question', 'eco-starter'),
        'all_items'     => __('Toutes les questions', 'eco-starter'),
        'not_found'     => __('Aucune question trouvée.', 'eco-starter'),
    ];

    register_post_type('faq', [
        'labels'          => $labels,
        'public'          => false, // Contenu utilisé via shortcode/bloc, pas en single
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => true,
        'menu_position'   => 8,
        'menu_icon'       => 'dashicons-editor-help',
        'supports'        => ['title', 'editor', 'page-attributes'],
        'has_archive'     => false,
        'rewrite'         => false,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
    ]);

    // Taxonomie : Catégorie de FAQ
    register_taxonomy('faq_category', 'faq', [
        'labels'            => [
            'name'          => _x('Catégories FAQ', 'taxonomy general name', 'eco-starter'),
            'singular_name' => _x('Catégorie FAQ', 'taxonomy singular name', 'eco-starter'),
            'menu_name'     => __('Catégories', 'eco-starter'),
            'all_items'     => __('Toutes les catégories', 'eco-starter'),
            'add_new_item'  => __('Ajouter une catégorie', 'eco-starter'),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ]);
}


// =============================================================================
// META BOXES NATIVES
// Zéro ACF — tout fonctionne avec get_post_meta()
// Accessibles via eco_field() dans les templates
// =============================================================================

add_action('add_meta_boxes', function (): void {
    eco_add_metabox_portfolio();
    eco_add_metabox_team();
    eco_add_metabox_testimonial();
});


// --- Portfolio ---

function eco_add_metabox_portfolio(): void
{
    add_meta_box(
        'eco_portfolio_details',
        __('Détails du projet', 'eco-starter'),
        'eco_render_metabox_portfolio',
        'portfolio',
        'normal',
        'high'
    );
}

function eco_render_metabox_portfolio(\WP_Post $post): void
{
    wp_nonce_field('eco_portfolio_save', 'eco_portfolio_nonce');

    $fields = [
        'eco_client'      => ['label' => __('Client', 'eco-starter'),           'type' => 'text',  'placeholder' => __('Nom du client', 'eco-starter')],
        'eco_year'        => ['label' => __('Année', 'eco-starter'),             'type' => 'number','placeholder' => date('Y')],
        'eco_url'         => ['label' => __('URL du projet', 'eco-starter'),     'type' => 'url',   'placeholder' => 'https://'],
        'eco_github'      => ['label' => __('URL GitHub/GitLab', 'eco-starter'), 'type' => 'url',   'placeholder' => 'https://'],
        'eco_duration'    => ['label' => __('Durée du projet', 'eco-starter'),   'type' => 'text',  'placeholder' => __('Ex : 3 mois', 'eco-starter')],
        'eco_role'        => ['label' => __('Mon rôle', 'eco-starter'),          'type' => 'text',  'placeholder' => __('Ex : Développeur full-stack', 'eco-starter')],
    ];

    echo '<div class="eco-metabox">';

    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        printf(
            '<p>
                <label for="%1$s"><strong>%2$s</strong></label><br>
                <input
                    type="%3$s"
                    id="%1$s"
                    name="%1$s"
                    value="%4$s"
                    placeholder="%5$s"
                    style="width:100%%;margin-top:4px;"
                >
            </p>',
            esc_attr($key),
            esc_html($field['label']),
            esc_attr($field['type']),
            esc_attr($value),
            esc_attr($field['placeholder'])
        );
    }

    // Champ textarea : description courte
    $short_desc = get_post_meta($post->ID, 'eco_short_desc', true);
    printf(
        '<p>
            <label for="eco_short_desc"><strong>%s</strong></label><br>
            <textarea id="eco_short_desc" name="eco_short_desc" rows="3"
                placeholder="%s" style="width:100%%;margin-top:4px;">%s</textarea>
        </p>',
        esc_html__('Description courte (cards)', 'eco-starter'),
        esc_attr__('Résumé affiché dans les listes de projets', 'eco-starter'),
        esc_textarea($short_desc)
    );

    // Checkbox : projet mis en avant
    $featured = (bool) get_post_meta($post->ID, 'eco_featured', true);
    printf(
        '<p>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="eco_featured" value="1" %s>
                <strong>%s</strong>
            </label>
            <span style="display:block;color:#666;font-size:12px;margin-top:4px;">%s</span>
        </p>',
        checked($featured, true, false),
        esc_html__('Projet mis en avant', 'eco-starter'),
        esc_html__('Affichage prioritaire sur la page portfolio et la home.', 'eco-starter')
    );

    echo '</div>';
}


// --- Équipe ---

function eco_add_metabox_team(): void
{
    add_meta_box(
        'eco_team_details',
        __('Informations du membre', 'eco-starter'),
        'eco_render_metabox_team',
        'team',
        'normal',
        'high'
    );
}

function eco_render_metabox_team(\WP_Post $post): void
{
    wp_nonce_field('eco_team_save', 'eco_team_nonce');

    $fields = [
        'eco_job_title' => ['label' => __('Poste / Titre', 'eco-starter'),   'type' => 'text', 'placeholder' => __('Ex : Directeur technique', 'eco-starter')],
        'eco_email'     => ['label' => __('Email professionnel', 'eco-starter'), 'type' => 'email','placeholder' => 'prenom@societe.com'],
        'eco_phone'     => ['label' => __('Téléphone', 'eco-starter'),       'type' => 'tel',  'placeholder' => '+33 6 00 00 00 00'],
        'eco_linkedin'  => ['label' => __('LinkedIn', 'eco-starter'),        'type' => 'url',  'placeholder' => 'https://linkedin.com/in/'],
        'eco_twitter'   => ['label' => __('Twitter / X', 'eco-starter'),     'type' => 'url',  'placeholder' => 'https://twitter.com/'],
        'eco_github'    => ['label' => __('GitHub', 'eco-starter'),          'type' => 'url',  'placeholder' => 'https://github.com/'],
    ];

    echo '<div class="eco-metabox">';
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;">';

    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        printf(
            '<p>
                <label for="%1$s"><strong>%2$s</strong></label><br>
                <input
                    type="%3$s"
                    id="%1$s"
                    name="%1$s"
                    value="%4$s"
                    placeholder="%5$s"
                    style="width:100%%;margin-top:4px;"
                >
            </p>',
            esc_attr($key),
            esc_html($field['label']),
            esc_attr($field['type']),
            esc_attr($value),
            esc_attr($field['placeholder'])
        );
    }

    echo '</div>';

    // Ordre d'affichage
    $order = get_post_meta($post->ID, 'eco_display_order', true);
    printf(
        '<p>
            <label for="eco_display_order"><strong>%s</strong></label><br>
            <input type="number" id="eco_display_order" name="eco_display_order"
                value="%s" min="0" max="999" style="width:100px;margin-top:4px;">
            <span style="color:#666;font-size:12px;margin-left:8px;">%s</span>
        </p>',
        esc_html__('Ordre d\'affichage', 'eco-starter'),
        esc_attr($order ?: '0'),
        esc_html__('0 = premier, valeur croissante = plus bas', 'eco-starter')
    );

    echo '</div>';
}


// --- Témoignages ---

function eco_add_metabox_testimonial(): void
{
    add_meta_box(
        'eco_testimonial_details',
        __('Détails du témoignage', 'eco-starter'),
        'eco_render_metabox_testimonial',
        'testimonial',
        'normal',
        'high'
    );
}

function eco_render_metabox_testimonial(\WP_Post $post): void
{
    wp_nonce_field('eco_testimonial_save', 'eco_testimonial_nonce');

    $fields = [
        'eco_author_name'    => ['label' => __('Nom complet', 'eco-starter'),        'type' => 'text', 'placeholder' => __('Prénom Nom', 'eco-starter')],
        'eco_author_company' => ['label' => __('Entreprise', 'eco-starter'),         'type' => 'text', 'placeholder' => __('Nom de l\'entreprise', 'eco-starter')],
        'eco_author_role'    => ['label' => __('Poste', 'eco-starter'),              'type' => 'text', 'placeholder' => __('Ex : CEO', 'eco-starter')],
        'eco_author_url'     => ['label' => __('Site web de l\'entreprise', 'eco-starter'), 'type' => 'url',  'placeholder' => 'https://'],
    ];

    echo '<div class="eco-metabox">';
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;">';

    foreach ($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        printf(
            '<p>
                <label for="%1$s"><strong>%2$s</strong></label><br>
                <input type="%3$s" id="%1$s" name="%1$s"
                    value="%4$s" placeholder="%5$s"
                    style="width:100%%;margin-top:4px;">
            </p>',
            esc_attr($key),
            esc_html($field['label']),
            esc_attr($field['type']),
            esc_attr($value),
            esc_attr($field['placeholder'])
        );
    }

    echo '</div>';

    // Note (étoiles)
    $rating = (int) get_post_meta($post->ID, 'eco_rating', true) ?: 5;
    echo '<p>';
    echo '<label><strong>' . esc_html__('Note', 'eco-starter') . '</strong></label><br>';
    echo '<select name="eco_rating" style="margin-top:4px;">';
    for ($i = 5; $i >= 1; $i--) {
        printf(
            '<option value="%d" %s>%s</option>',
            $i,
            selected($rating, $i, false),
            esc_html(str_repeat('★', $i) . str_repeat('☆', 5 - $i) . " ({$i}/5)")
        );
    }
    echo '</select></p>';

    // Projet lié (select parmi les portfolios)
    $linked_project = get_post_meta($post->ID, 'eco_linked_project', true);
    $projects = get_posts([
        'post_type'      => 'portfolio',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]);

    if (!empty($projects)) {
        echo '<p><label><strong>' . esc_html__('Projet associé (optionnel)', 'eco-starter') . '</strong></label><br>';
        echo '<select name="eco_linked_project" style="width:100%;margin-top:4px;">';
        echo '<option value="">' . esc_html__('— Aucun —', 'eco-starter') . '</option>';
        foreach ($projects as $project) {
            printf(
                '<option value="%d" %s>%s</option>',
                $project->ID,
                selected($linked_project, $project->ID, false),
                esc_html($project->post_title)
            );
        }
        echo '</select></p>';
    }

    echo '</div>';
}


// =============================================================================
// SAUVEGARDE DES META BOXES
// Un handler par CPT — nonce vérifié, données sanitisées
// =============================================================================

add_action('save_post', function (int $post_id): void {

    // Bail conditions communes
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $post_type = get_post_type($post_id);

    match ($post_type) {
        'portfolio'   => eco_save_meta_portfolio($post_id),
        'team'        => eco_save_meta_team($post_id),
        'testimonial' => eco_save_meta_testimonial($post_id),
        default       => null,
    };
});


function eco_save_meta_portfolio(int $post_id): void
{
    if (
        !isset($_POST['eco_portfolio_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_portfolio_nonce'])), 'eco_portfolio_save')
    ) return;

    $text_fields = ['eco_client', 'eco_year', 'eco_duration', 'eco_role'];
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    $url_fields = ['eco_url', 'eco_github'];
    foreach ($url_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, esc_url_raw(wp_unslash($_POST[$field])));
        }
    }

    if (isset($_POST['eco_short_desc'])) {
        update_post_meta($post_id, 'eco_short_desc', sanitize_textarea_field(wp_unslash($_POST['eco_short_desc'])));
    }

    // Checkbox : si absente du POST → non cochée
    update_post_meta($post_id, 'eco_featured', isset($_POST['eco_featured']) ? '1' : '');
}


function eco_save_meta_team(int $post_id): void
{
    if (
        !isset($_POST['eco_team_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_team_nonce'])), 'eco_team_save')
    ) return;

    $text_fields = ['eco_job_title', 'eco_phone'];
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    if (isset($_POST['eco_email'])) {
        update_post_meta($post_id, 'eco_email', sanitize_email(wp_unslash($_POST['eco_email'])));
    }

    $url_fields = ['eco_linkedin', 'eco_twitter', 'eco_github'];
    foreach ($url_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, esc_url_raw(wp_unslash($_POST[$field])));
        }
    }

    if (isset($_POST['eco_display_order'])) {
        update_post_meta($post_id, 'eco_display_order', absint($_POST['eco_display_order']));
    }
}


function eco_save_meta_testimonial(int $post_id): void
{
    if (
        !isset($_POST['eco_testimonial_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_testimonial_nonce'])), 'eco_testimonial_save')
    ) return;

    $text_fields = ['eco_author_name', 'eco_author_company', 'eco_author_role'];
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    if (isset($_POST['eco_author_url'])) {
        update_post_meta($post_id, 'eco_author_url', esc_url_raw(wp_unslash($_POST['eco_author_url'])));
    }

    if (isset($_POST['eco_rating'])) {
        $rating = min(5, max(1, absint($_POST['eco_rating'])));
        update_post_meta($post_id, 'eco_rating', $rating);
    }

    if (isset($_POST['eco_linked_project'])) {
        update_post_meta($post_id, 'eco_linked_project', absint($_POST['eco_linked_project']));
    }
}


// =============================================================================
// COLONNES ADMIN PERSONNALISÉES
// =============================================================================

// --- Portfolio ---

add_filter('manage_portfolio_posts_columns', function (array $columns): array {
    unset($columns['date']);
    return array_merge($columns, [
        'thumbnail' => __('Image', 'eco-starter'),
        'client'    => __('Client', 'eco-starter'),
        'year'      => __('Année', 'eco-starter'),
        'featured'  => __('Mis en avant', 'eco-starter'),
        'date'      => __('Date', 'eco-starter'),
    ]);
});

add_action('manage_portfolio_posts_custom_column', function (string $column, int $post_id): void {
    match ($column) {
        'thumbnail' => print(
            has_post_thumbnail($post_id)
                ? '<img src="' . esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')) . '" style="width:60px;height:45px;object-fit:cover;border-radius:4px;">'
                : '—'
        ),
        'client'    => print(esc_html(get_post_meta($post_id, 'eco_client', true) ?: '—')),
        'year'      => print(esc_html(get_post_meta($post_id, 'eco_year', true) ?: '—')),
        'featured'  => print(get_post_meta($post_id, 'eco_featured', true) ? '⭐' : '—'),
        default     => null,
    };
}, 10, 2);


// --- Équipe ---

add_filter('manage_team_posts_columns', function (array $columns): array {
    unset($columns['date']);
    return array_merge($columns, [
        'thumbnail' => __('Photo', 'eco-starter'),
        'job_title' => __('Poste', 'eco-starter'),
        'order'     => __('Ordre', 'eco-starter'),
        'date'      => __('Date', 'eco-starter'),
    ]);
});

add_action('manage_team_posts_custom_column', function (string $column, int $post_id): void {
    match ($column) {
        'thumbnail' => print(
            has_post_thumbnail($post_id)
                ? '<img src="' . esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')) . '" style="width:45px;height:45px;object-fit:cover;border-radius:50%;">'
                : '—'
        ),
        'job_title' => print(esc_html(get_post_meta($post_id, 'eco_job_title', true) ?: '—')),
        'order'     => print(esc_html(get_post_meta($post_id, 'eco_display_order', true) ?: '0')),
        default     => null,
    };
}, 10, 2);

// Tri par ordre d'affichage en admin
add_filter('manage_edit-team_sortable_columns', function (array $columns): array {
    $columns['order'] = 'eco_display_order';
    return $columns;
});


// --- Témoignages ---

add_filter('manage_testimonial_posts_columns', function (array $columns): array {
    unset($columns['date']);
    return array_merge($columns, [
        'thumbnail' => __('Photo', 'eco-starter'),
        'author'    => __('Auteur', 'eco-starter'),
        'company'   => __('Entreprise', 'eco-starter'),
        'rating'    => __('Note', 'eco-starter'),
        'date'      => __('Date', 'eco-starter'),
    ]);
});

add_action('manage_testimonial_posts_custom_column', function (string $column, int $post_id): void {
    match ($column) {
        'thumbnail' => print(
            has_post_thumbnail($post_id)
                ? '<img src="' . esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')) . '" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">'
                : '—'
        ),
        'author'  => print(esc_html(get_post_meta($post_id, 'eco_author_name', true) ?: get_the_title($post_id))),
        'company' => print(esc_html(get_post_meta($post_id, 'eco_author_company', true) ?: '—')),
        'rating'  => (function () use ($post_id): void {
            $rating = (int) get_post_meta($post_id, 'eco_rating', true) ?: 5;
            echo esc_html(str_repeat('★', $rating) . str_repeat('☆', 5 - $rating));
        })(),
        default => null,
    };
}, 10, 2);


// =============================================================================
// MESSAGES ADMIN TRADUITS
// Les messages de confirmation après sauvegarde/publication
// =============================================================================

add_filter('post_updated_messages', function (array $messages): array {

    $cpt_messages = [
        'portfolio' => [
            1  => __('Projet mis à jour.', 'eco-starter'),
            4  => __('Projet mis à jour.', 'eco-starter'),
            6  => __('Projet publié.', 'eco-starter'),
            7  => __('Projet sauvegardé.', 'eco-starter'),
            8  => __('Projet soumis.', 'eco-starter'),
            10 => __('Brouillon mis à jour.', 'eco-starter'),
        ],
        'team' => [
            1  => __('Membre mis à jour.', 'eco-starter'),
            4  => __('Membre mis à jour.', 'eco-starter'),
            6  => __('Membre publié.', 'eco-starter'),
            7  => __('Membre sauvegardé.', 'eco-starter'),
            8  => __('Membre soumis.', 'eco-starter'),
            10 => __('Brouillon mis à jour.', 'eco-starter'),
        ],
        'testimonial' => [
            1  => __('Témoignage mis à jour.', 'eco-starter'),
            4  => __('Témoignage mis à jour.', 'eco-starter'),
            6  => __('Témoignage publié.', 'eco-starter'),
            7  => __('Témoignage sauvegardé.', 'eco-starter'),
            8  => __('Témoignage soumis.', 'eco-starter'),
            10 => __('Brouillon mis à jour.', 'eco-starter'),
        ],
        'faq' => [
            1  => __('Question mise à jour.', 'eco-starter'),
            4  => __('Question mise à jour.', 'eco-starter'),
            6  => __('Question publiée.', 'eco-starter'),
            7  => __('Question sauvegardée.', 'eco-starter'),
            8  => __('Question soumise.', 'eco-starter'),
            10 => __('Brouillon mis à jour.', 'eco-starter'),
        ],
    ];

    return array_merge($messages, $cpt_messages);
});


// =============================================================================
// FLUSH REWRITE RULES
// Uniquement lors de l'activation/désactivation du thème
// Ne jamais appeler flush_rewrite_rules() sur init !
// =============================================================================

add_action('after_switch_theme', function (): void {
    eco_register_cpt_portfolio();
    eco_register_cpt_team();
    eco_register_cpt_testimonial();
    eco_register_cpt_faq();
    flush_rewrite_rules();
});

add_action('switch_theme', function (): void {
    flush_rewrite_rules();
});