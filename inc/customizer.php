<?php
/**
 * Customizer — Eco Starter
 *
 * Toutes les options configurables par le client sans toucher au code.
 *
 * Sections :
 * 1. Identité du site (logo, couleurs, tagline)
 * 2. En-tête (style, CTA, comportement)
 * 3. Mise en page (sidebar, largeur contenu)
 * 4. Pied de page (copyright, réseaux sociaux)
 * 5. Couleurs (surcharge des tokens CSS)
 * 6. Typographie (font, tailles)
 * 7. Contact (email, redirect, confirmation)
 * 8. SEO & Schema.org (données structurées)
 * 9. Réseaux sociaux
 * 10. Maintenance
 * 11. Scripts (analytics, pixels)
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// ENREGISTREMENT
// =============================================================================

add_action('customize_register', function (\WP_Customize_Manager $wp_customize): void {

    // Supprime les sections natives inutiles
    $wp_customize->remove_section('colors');      // On a notre propre section couleurs
    $wp_customize->remove_section('background_image'); // Rarement utile

    // Déplace les sections natives WordPress
    $wp_customize->get_section('title_tagline')->priority  = 10;
    $wp_customize->get_section('title_tagline')->title     = __('Identité du site', 'eco-starter');
    $wp_customize->get_section('static_front_page')->priority = 200;

    // =========================================================================
    // PANELS (regroupements de sections)
    // =========================================================================

    $wp_customize->add_panel('eco_header_panel', [
        'title'       => __('En-tête', 'eco-starter'),
        'priority'    => 30,
        'description' => __('Configuration de l\'en-tête et de la navigation.', 'eco-starter'),
    ]);

    $wp_customize->add_panel('eco_layout_panel', [
        'title'       => __('Mise en page', 'eco-starter'),
        'priority'    => 40,
        'description' => __('Structure et disposition des pages.', 'eco-starter'),
    ]);

    $wp_customize->add_panel('eco_design_panel', [
        'title'       => __('Design', 'eco-starter'),
        'priority'    => 50,
        'description' => __('Couleurs, typographie et apparence.', 'eco-starter'),
    ]);

    $wp_customize->add_panel('eco_footer_panel', [
        'title'       => __('Pied de page', 'eco-starter'),
        'priority'    => 60,
        'description' => __('Configuration du footer.', 'eco-starter'),
    ]);

    $wp_customize->add_panel('eco_seo_panel', [
        'title'       => __('SEO & Données structurées', 'eco-starter'),
        'priority'    => 70,
    ]);

    $wp_customize->add_panel('eco_tools_panel', [
        'title'       => __('Outils & Intégrations', 'eco-starter'),
        'priority'    => 80,
    ]);


    // =========================================================================
    // 1. IDENTITÉ — section native enrichie
    // =========================================================================

    // OG Image par défaut (section title_tagline existante)
    $wp_customize->add_setting('og_default_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control(new \WP_Customize_Image_Control($wp_customize, 'og_default_image', [
        'label'       => __('Image Open Graph par défaut', 'eco-starter'),
        'description' => __('Image utilisée pour le partage sur les réseaux sociaux (recommandé : 1200×630px). Utilisée quand il n\'y a pas d\'image mise en avant.', 'eco-starter'),
        'section'     => 'title_tagline',
        'priority'    => 30,
    ]));

    // Favicon / Site Icon — WordPress gère ça nativement via title_tagline
    // On ajoute simplement un lien vers la section pour le client


    // =========================================================================
    // 2. EN-TÊTE
    // =========================================================================

    // --- Section : comportement header ---
    $wp_customize->add_section('eco_header_behavior', [
        'title'    => __('Comportement', 'eco-starter'),
        'panel'    => 'eco_header_panel',
        'priority' => 10,
    ]);

    // Header sticky
    $wp_customize->add_setting('header_sticky', [
        'default'           => true,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('header_sticky', [
        'label'       => __('Header fixe (sticky)', 'eco-starter'),
        'description' => __('Le header reste visible lors du défilement.', 'eco-starter'),
        'section'     => 'eco_header_behavior',
        'type'        => 'checkbox',
    ]);

    // Header transparent sur la home
    $wp_customize->add_setting('header_transparent_home', [
        'default'           => false,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('header_transparent_home', [
        'label'       => __('Header transparent sur la page d\'accueil', 'eco-starter'),
        'description' => __('Le header est transparent au dessus d\'un hero plein écran. Devient opaque au scroll.', 'eco-starter'),
        'section'     => 'eco_header_behavior',
        'type'        => 'checkbox',
    ]);

    // --- Section : CTA header ---
    $wp_customize->add_section('eco_header_cta', [
        'title'    => __('Bouton d\'action (CTA)', 'eco-starter'),
        'panel'    => 'eco_header_panel',
        'priority' => 20,
    ]);

    $wp_customize->add_setting('header_cta_text', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('header_cta_text', [
        'label'       => __('Texte du bouton', 'eco-starter'),
        'description' => __('Laisser vide pour masquer le bouton.', 'eco-starter'),
        'section'     => 'eco_header_cta',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('header_cta_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('header_cta_url', [
        'label'   => __('URL du bouton', 'eco-starter'),
        'section' => 'eco_header_cta',
        'type'    => 'url',
    ]);

    $wp_customize->add_setting('header_cta_style', [
        'default'           => 'primary',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('header_cta_style', [
        'label'   => __('Style du bouton', 'eco-starter'),
        'section' => 'eco_header_cta',
        'type'    => 'select',
        'choices' => [
            'primary'   => __('Primaire (plein)', 'eco-starter'),
            'outline'   => __('Outline (bordure)', 'eco-starter'),
            'secondary' => __('Secondaire', 'eco-starter'),
        ],
    ]);


    // =========================================================================
    // 3. MISE EN PAGE
    // =========================================================================

    // --- Section : structure ---
    $wp_customize->add_section('eco_layout_structure', [
        'title'    => __('Structure', 'eco-starter'),
        'panel'    => 'eco_layout_panel',
        'priority' => 10,
    ]);

    // Sidebar activée
    $wp_customize->add_setting('sidebar_enabled', [
        'default'           => false,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('sidebar_enabled', [
        'label'       => __('Activer la sidebar', 'eco-starter'),
        'description' => __('Affiche la sidebar sur les pages et articles (full-width par défaut).', 'eco-starter'),
        'section'     => 'eco_layout_structure',
        'type'        => 'checkbox',
    ]);

    // Sidebar position
    $wp_customize->add_setting('sidebar_position', [
        'default'           => 'right',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('sidebar_position', [
        'label'   => __('Position de la sidebar', 'eco-starter'),
        'section' => 'eco_layout_structure',
        'type'    => 'select',
        'choices' => [
            'right' => __('Droite', 'eco-starter'),
            'left'  => __('Gauche', 'eco-starter'),
        ],
    ]);

    // Largeur max du contenu
    $wp_customize->add_setting('content_max_width', [
        'default'           => '1200',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('content_max_width', [
        'label'       => __('Largeur maximale du contenu (px)', 'eco-starter'),
        'description' => __('Largeur du container principal. Défaut : 1200px.', 'eco-starter'),
        'section'     => 'eco_layout_structure',
        'type'        => 'number',
        'input_attrs' => ['min' => 800, 'max' => 1920, 'step' => 10],
    ]);

    // --- Section : blog ---
    $wp_customize->add_section('eco_layout_blog', [
        'title'    => __('Blog & Archives', 'eco-starter'),
        'panel'    => 'eco_layout_panel',
        'priority' => 20,
    ]);

    $wp_customize->add_setting('blog_layout', [
        'default'           => 'grid',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('blog_layout', [
        'label'   => __('Disposition des articles', 'eco-starter'),
        'section' => 'eco_layout_blog',
        'type'    => 'select',
        'choices' => [
            'grid'     => __('Grille (cards)', 'eco-starter'),
            'list'     => __('Liste (horizontal)', 'eco-starter'),
            'featured' => __('Grille avec article mis en avant', 'eco-starter'),
        ],
    ]);

    $wp_customize->add_setting('blog_columns', [
        'default'           => '3',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('blog_columns', [
        'label'   => __('Colonnes (grille)', 'eco-starter'),
        'section' => 'eco_layout_blog',
        'type'    => 'select',
        'choices' => [
            '2' => __('2 colonnes', 'eco-starter'),
            '3' => __('3 colonnes', 'eco-starter'),
            '4' => __('4 colonnes', 'eco-starter'),
        ],
    ]);

    $wp_customize->add_setting('show_post_meta', [
        'default'           => true,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('show_post_meta', [
        'label'   => __('Afficher les méta articles (date, auteur, catégorie)', 'eco-starter'),
        'section' => 'eco_layout_blog',
        'type'    => 'checkbox',
    ]);

    $wp_customize->add_setting('show_reading_time', [
        'default'           => true,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('show_reading_time', [
        'label'   => __('Afficher le temps de lecture', 'eco-starter'),
        'section' => 'eco_layout_blog',
        'type'    => 'checkbox',
    ]);


    // =========================================================================
    // 4. PIED DE PAGE
    // =========================================================================

    // --- Section : contenu footer ---
    $wp_customize->add_section('eco_footer_content', [
        'title'    => __('Contenu', 'eco-starter'),
        'panel'    => 'eco_footer_panel',
        'priority' => 10,
    ]);

    $wp_customize->add_setting('footer_copyright', [
        'default'           => sprintf('© %d %s. %s', date('Y'), get_bloginfo('name'), __('Tous droits réservés.', 'eco-starter')),
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('footer_copyright', [
        'label'       => __('Texte de copyright', 'eco-starter'),
        'description' => __('HTML basique autorisé. {year} sera remplacé par l\'année en cours.', 'eco-starter'),
        'section'     => 'eco_footer_content',
        'type'        => 'textarea',
    ]);

    $wp_customize->add_setting('footer_columns', [
        'default'           => '3',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('footer_columns', [
        'label'   => __('Nombre de colonnes', 'eco-starter'),
        'section' => 'eco_footer_content',
        'type'    => 'select',
        'choices' => [
            '1' => __('1 colonne', 'eco-starter'),
            '2' => __('2 colonnes', 'eco-starter'),
            '3' => __('3 colonnes', 'eco-starter'),
            '4' => __('4 colonnes', 'eco-starter'),
        ],
    ]);

    $wp_customize->add_setting('footer_show_logo', [
        'default'           => true,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('footer_show_logo', [
        'label'   => __('Afficher le logo dans le footer', 'eco-starter'),
        'section' => 'eco_footer_content',
        'type'    => 'checkbox',
    ]);


    // =========================================================================
    // 5. COULEURS
    // =========================================================================

    $wp_customize->add_section('eco_colors', [
        'title'       => __('Couleurs', 'eco-starter'),
        'panel'       => 'eco_design_panel',
        'priority'    => 10,
        'description' => __('Surcharge les couleurs du design system. Pour une personnalisation avancée, modifier assets/css/base/tokens.css directement.', 'eco-starter'),
    ]);

    $color_settings = [
        'color_primary'    => ['default' => '#2563eb', 'label' => __('Couleur primaire', 'eco-starter')],
        'color_secondary'  => ['default' => '#16a34a', 'label' => __('Couleur secondaire', 'eco-starter')],
        'color_foreground' => ['default' => '#111827', 'label' => __('Texte principal', 'eco-starter')],
        'color_background' => ['default' => '#ffffff', 'label' => __('Fond principal', 'eco-starter')],
        'color_surface'    => ['default' => '#f9fafb', 'label' => __('Fond alternatif', 'eco-starter')],
        'color_border'     => ['default' => '#e5e7eb', 'label' => __('Bordures', 'eco-starter')],
    ];

    foreach ($color_settings as $setting_id => $config) {
        $wp_customize->add_setting($setting_id, [
            'default'           => $config['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ]);

        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $setting_id, [
            'label'   => $config['label'],
            'section' => 'eco_colors',
        ]));
    }


    // =========================================================================
    // 6. TYPOGRAPHIE
    // =========================================================================

    $wp_customize->add_section('eco_typography', [
        'title'    => __('Typographie', 'eco-starter'),
        'panel'    => 'eco_design_panel',
        'priority' => 20,
    ]);

    $wp_customize->add_setting('font_base_size', [
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('font_base_size', [
        'label'       => __('Taille de base (px)', 'eco-starter'),
        'description' => __('Taille du texte courant. Défaut : 16px.', 'eco-starter'),
        'section'     => 'eco_typography',
        'type'        => 'number',
        'input_attrs' => ['min' => 14, 'max' => 20, 'step' => 1],
    ]);

    $wp_customize->add_setting('font_family_custom', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('font_family_custom', [
        'label'       => __('Font family (CSS)', 'eco-starter'),
        'description' => __('Ex : \'Inter\', sans-serif — La font doit être auto-hébergée dans assets/fonts/ et déclarée via @font-face dans typography.css.', 'eco-starter'),
        'section'     => 'eco_typography',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('heading_weight', [
        'default'           => '700',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('heading_weight', [
        'label'   => __('Graisse des titres', 'eco-starter'),
        'section' => 'eco_typography',
        'type'    => 'select',
        'choices' => [
            '600' => __('Semi-bold (600)', 'eco-starter'),
            '700' => __('Bold (700)', 'eco-starter'),
            '800' => __('Extra-bold (800)', 'eco-starter'),
            '900' => __('Black (900)', 'eco-starter'),
        ],
    ]);


    // =========================================================================
    // 7. CONTACT
    // =========================================================================

    $wp_customize->add_section('eco_contact', [
        'title'    => __('Formulaire de contact', 'eco-starter'),
        'panel'    => 'eco_tools_panel',
        'priority' => 10,
    ]);

    $wp_customize->add_setting('contact_email', [
        'default'           => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('contact_email', [
        'label'       => __('Email destinataire', 'eco-starter'),
        'description' => __('Adresse qui reçoit les messages du formulaire de contact.', 'eco-starter'),
        'section'     => 'eco_contact',
        'type'        => 'email',
    ]);

    $wp_customize->add_setting('contact_send_confirmation', [
        'default'           => true,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('contact_send_confirmation', [
        'label'       => __('Envoyer un email de confirmation au visiteur', 'eco-starter'),
        'section'     => 'eco_contact',
        'type'        => 'checkbox',
    ]);

    $wp_customize->add_setting('contact_redirect_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('contact_redirect_url', [
        'label'       => __('Page de remerciement (redirect)', 'eco-starter'),
        'description' => __('URL vers laquelle rediriger après envoi. Laisser vide pour afficher un message en place.', 'eco-starter'),
        'section'     => 'eco_contact',
        'type'        => 'url',
    ]);


    // =========================================================================
    // 8. SEO & SCHEMA.ORG
    // =========================================================================

    // --- Section : organisation ---
    $wp_customize->add_section('eco_schema_org', [
        'title'       => __('Organisation / Entreprise', 'eco-starter'),
        'panel'       => 'eco_seo_panel',
        'priority'    => 10,
        'description' => __('Données utilisées pour le Schema.org JSON-LD. Apparaissent dans les résultats enrichis Google.', 'eco-starter'),
    ]);

    $wp_customize->add_setting('schema_org_type', [
        'default'           => 'Organization',
        'sanitize_callback' => 'eco_sanitize_select',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('schema_org_type', [
        'label'   => __('Type d\'organisation', 'eco-starter'),
        'section' => 'eco_schema_org',
        'type'    => 'select',
        'choices' => [
            'Organization'      => __('Organisation générique', 'eco-starter'),
            'LocalBusiness'     => __('Commerce local', 'eco-starter'),
            'Corporation'       => __('Société', 'eco-starter'),
            'EducationalOrganization' => __('Établissement d\'enseignement', 'eco-starter'),
            'NGO'               => __('Association / ONG', 'eco-starter'),
            'ProfessionalService' => __('Service professionnel', 'eco-starter'),
        ],
    ]);

    $wp_customize->add_setting('schema_org_name', [
        'default'           => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('schema_org_name', [
        'label'       => __('Nom officiel', 'eco-starter'),
        'description' => __('Nom légal de l\'organisation (peut différer du nom du site).', 'eco-starter'),
        'section'     => 'eco_schema_org',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('schema_phone', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('schema_phone', [
        'label'   => __('Téléphone', 'eco-starter'),
        'section' => 'eco_schema_org',
        'type'    => 'tel',
    ]);

    $wp_customize->add_setting('schema_email', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_email',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('schema_email', [
        'label'   => __('Email public', 'eco-starter'),
        'section' => 'eco_schema_org',
        'type'    => 'email',
    ]);

    // Adresse
    $address_fields = [
        'schema_address_street'  => ['label' => __('Rue', 'eco-starter'), 'autocomplete' => 'street-address'],
        'schema_address_city'    => ['label' => __('Ville', 'eco-starter'), 'autocomplete' => 'address-level2'],
        'schema_address_zip'     => ['label' => __('Code postal', 'eco-starter'), 'autocomplete' => 'postal-code'],
        'schema_address_country' => ['label' => __('Pays (code ISO)', 'eco-starter'), 'autocomplete' => 'country'],
    ];

    foreach ($address_fields as $setting_id => $field_config) {
        $wp_customize->add_setting($setting_id, [
            'default'           => $setting_id === 'schema_address_country' ? 'FR' : '',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ]);

        $wp_customize->add_control($setting_id, [
            'label'   => $field_config['label'],
            'section' => 'eco_schema_org',
            'type'    => 'text',
        ]);
    }


    // =========================================================================
    // 9. RÉSEAUX SOCIAUX
    // =========================================================================

    $wp_customize->add_section('eco_social', [
        'title'    => __('Réseaux sociaux', 'eco-starter'),
        'panel'    => 'eco_seo_panel',
        'priority' => 20,
    ]);

    $social_networks = [
        'social_facebook'  => ['label' => 'Facebook',  'placeholder' => 'https://facebook.com/votrepage'],
        'social_twitter'   => ['label' => 'Twitter / X', 'placeholder' => 'https://twitter.com/votrecompte'],
        'social_instagram' => ['label' => 'Instagram', 'placeholder' => 'https://instagram.com/votrecompte'],
        'social_linkedin'  => ['label' => 'LinkedIn',  'placeholder' => 'https://linkedin.com/company/votresociete'],
        'social_youtube'   => ['label' => 'YouTube',   'placeholder' => 'https://youtube.com/@votrechaine'],
        'social_tiktok'    => ['label' => 'TikTok',    'placeholder' => 'https://tiktok.com/@votrecompte'],
        'social_pinterest' => ['label' => 'Pinterest', 'placeholder' => 'https://pinterest.com/votrecompte'],
        'social_github'    => ['label' => 'GitHub',    'placeholder' => 'https://github.com/votreorganisation'],
        'twitter_handle'   => ['label' => __('Handle Twitter (sans @)', 'eco-starter'), 'placeholder' => 'votrecompte'],
    ];

    foreach ($social_networks as $setting_id => $network) {
        $wp_customize->add_setting($setting_id, [
            'default'           => '',
            'sanitize_callback' => $setting_id === 'twitter_handle' ? 'sanitize_text_field' : 'esc_url_raw',
            'transport'         => 'refresh',
        ]);

        $wp_customize->add_control($setting_id, [
            'label'       => $network['label'],
            'section'     => 'eco_social',
            'type'        => $setting_id === 'twitter_handle' ? 'text' : 'url',
            'input_attrs' => ['placeholder' => $network['placeholder']],
        ]);
    }


    // =========================================================================
    // 10. MAINTENANCE
    // =========================================================================

    $wp_customize->add_section('eco_maintenance', [
        'title'    => __('Mode maintenance', 'eco-starter'),
        'panel'    => 'eco_tools_panel',
        'priority' => 20,
    ]);

    $wp_customize->add_setting('maintenance_mode', [
        'default'           => false,
        'sanitize_callback' => 'eco_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('maintenance_mode', [
        'label'       => __('Activer le mode maintenance', 'eco-starter'),
        'description' => __('⚠️ Les visiteurs verront la page de maintenance. Les administrateurs continuent d\'accéder au site normalement.', 'eco-starter'),
        'section'     => 'eco_maintenance',
        'type'        => 'checkbox',
    ]);

    $wp_customize->add_setting('maintenance_title', [
        'default'           => __('Site en maintenance', 'eco-starter'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('maintenance_title', [
        'label'   => __('Titre de la page maintenance', 'eco-starter'),
        'section' => 'eco_maintenance',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('maintenance_message', [
        'default'           => __('Nous effectuons une mise à jour. Nous serons de retour très bientôt.', 'eco-starter'),
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ]);

    $wp_customize->add_control('maintenance_message', [
        'label'   => __('Message de maintenance', 'eco-starter'),
        'section' => 'eco_maintenance',
        'type'    => 'textarea',
    ]);

    $wp_customize->add_setting('maintenance_email', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_email',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('maintenance_email', [
        'label'       => __('Email de contact affiché', 'eco-starter'),
        'description' => __('Laissez vide pour ne pas afficher d\'email.', 'eco-starter'),
        'section'     => 'eco_maintenance',
        'type'        => 'email',
    ]);


    // =========================================================================
    // 11. SCRIPTS & ANALYTICS
    // =========================================================================

    $wp_customize->add_section('eco_scripts', [
        'title'       => __('Analytics & Scripts', 'eco-starter'),
        'panel'       => 'eco_tools_panel',
        'priority'    => 30,
        'description' => __('Scripts injectés dans le site. Respectez le RGPD : n\'activez ces scripts qu\'avec le consentement de l\'utilisateur.', 'eco-starter'),
    ]);

    $wp_customize->add_setting('ga4_measurement_id', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('ga4_measurement_id', [
        'label'       => __('Google Analytics 4 — Measurement ID', 'eco-starter'),
        'description' => __('Format : G-XXXXXXXXXX. Laisser vide pour désactiver. Ne pas activer sans bandeau de consentement cookies.', 'eco-starter'),
        'section'     => 'eco_scripts',
        'type'        => 'text',
        'input_attrs' => ['placeholder' => 'G-XXXXXXXXXX'],
    ]);

    $wp_customize->add_setting('gtm_container_id', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('gtm_container_id', [
        'label'       => __('Google Tag Manager — Container ID', 'eco-starter'),
        'description' => __('Format : GTM-XXXXXXX. Remplace GA4 si les deux sont renseignés.', 'eco-starter'),
        'section'     => 'eco_scripts',
        'type'        => 'text',
        'input_attrs' => ['placeholder' => 'GTM-XXXXXXX'],
    ]);

    $wp_customize->add_setting('head_scripts', [
        'default'           => '',
        'sanitize_callback' => 'eco_sanitize_scripts',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('head_scripts', [
        'label'       => __('Scripts <head> personnalisés', 'eco-starter'),
        'description' => __('Injectés juste avant </head>. Balises <script> autorisées.', 'eco-starter'),
        'section'     => 'eco_scripts',
        'type'        => 'textarea',
    ]);

    $wp_customize->add_setting('footer_scripts', [
        'default'           => '',
        'sanitize_callback' => 'eco_sanitize_scripts',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('footer_scripts', [
        'label'       => __('Scripts <footer> personnalisés', 'eco-starter'),
        'description' => __('Injectés juste avant </body>. Balises <script> autorisées.', 'eco-starter'),
        'section'     => 'eco_scripts',
        'type'        => 'textarea',
    ]);

});


// =============================================================================
// SANITISATION HELPERS
// =============================================================================

/**
 * Sanitise une case à cocher
 */
function eco_sanitize_checkbox(mixed $value): bool
{
    return (bool) $value;
}

/**
 * Sanitise une valeur de select selon les choix autorisés
 */
function eco_sanitize_select(mixed $value, \WP_Customize_Setting $setting): string
{
    $control = $setting->manager->get_control($setting->id);

    if ($control && isset($control->choices) && array_key_exists($value, $control->choices)) {
        return (string) $value;
    }

    return (string) $setting->default;
}

/**
 * Sanitise les scripts personnalisés
 * Autorise uniquement les balises <script> et leurs attributs
 */
function eco_sanitize_scripts(string $value): string
{
    // Seuls les administrateurs peuvent injecter des scripts
    if (!current_user_can('manage_options')) {
        return '';
    }

    return wp_kses($value, [
        'script' => [
            'type'     => [],
            'src'      => [],
            'async'    => [],
            'defer'    => [],
            'id'       => [],
            'data-*'   => [],
        ],
        'noscript' => [],
    ]);
}


// =============================================================================
// INJECTION DES CUSTOMISATIONS EN FRONT
// =============================================================================

/**
 * Injecte les couleurs personnalisées en CSS custom properties
 * Surcharge les tokens.css sans les écraser
 */
add_action('wp_head', function (): void {

    $color_map = [
        'color_primary'    => '--color-primary',
        'color_secondary'  => '--color-secondary',
        'color_foreground' => '--color-foreground',
        'color_background' => '--color-background',
        'color_surface'    => '--color-surface',
        'color_border'     => '--color-border',
    ];

    $custom_props = [];

    foreach ($color_map as $mod_name => $css_var) {
        $value   = get_theme_mod($mod_name, '');
        $default = eco_get_color_default($mod_name);

        // N'injecte que si la valeur diffère du défaut
        if ($value && $value !== $default) {
            $custom_props[] = $css_var . ': ' . sanitize_hex_color($value) . ';';
        }
    }

    // Typographie
    $font_size = get_theme_mod('font_base_size', '16');
    if ($font_size && $font_size !== '16') {
        $custom_props[] = '--font-base-size: ' . absint($font_size) . 'px;';
        // Pas de custom property pour font-size sur :root normalement,
        // on surcharge directement body
    }

    $font_family = get_theme_mod('font_family_custom', '');
    if ($font_family) {
        $custom_props[] = '--font-sans: ' . esc_attr($font_family) . ';';
    }

    $heading_weight = get_theme_mod('heading_weight', '700');
    if ($heading_weight && $heading_weight !== '700') {
        $custom_props[] = '--font-heading: ' . absint($heading_weight) . ';';
    }

    // Largeur de contenu
    $content_width = get_theme_mod('content_max_width', '1200');
    if ($content_width && $content_width !== '1200') {
        $custom_props[] = '--content-width: ' . absint($content_width) . 'px;';
    }

    if (empty($custom_props)) return;

    echo '<style id="eco-customizer-vars">:root{' . implode('', $custom_props) . '}</style>' . "\n";

    // Taille de base sur body si modifiée
    if ($font_size && $font_size !== '16') {
        echo '<style>body{font-size:' . absint($font_size) . 'px}</style>' . "\n";
    }

}, 5); // Après critical CSS (priorité 1), avant les enqueues (priorité 10)


/**
 * Retourne la valeur par défaut d'une couleur
 */
function eco_get_color_default(string $setting_id): string
{
    $defaults = [
        'color_primary'    => '#2563eb',
        'color_secondary'  => '#16a34a',
        'color_foreground' => '#111827',
        'color_background' => '#ffffff',
        'color_surface'    => '#f9fafb',
        'color_border'     => '#e5e7eb',
    ];

    return $defaults[$setting_id] ?? '';
}


// =============================================================================
// HEADER CTA — injection HTML
// =============================================================================

/**
 * Retourne le HTML du CTA header si configuré
 * Appelé dans header.php
 */
function eco_get_header_cta(): string
{
    $text  = get_theme_mod('header_cta_text', '');
    $url   = get_theme_mod('header_cta_url', '');
    $style = get_theme_mod('header_cta_style', 'primary');

    if (empty($text) || empty($url)) return '';

    return sprintf(
        '<a href="%s" class="btn btn--%s btn--sm">%s</a>',
        esc_url($url),
        esc_attr($style),
        esc_html($text)
    );
}


// =============================================================================
// FOOTER COPYRIGHT — remplacement {year}
// =============================================================================

/**
 * Retourne le texte de copyright avec l'année courante
 */
function eco_get_footer_copyright(): string
{
    $text = get_theme_mod(
        'footer_copyright',
        sprintf('© %d %s. %s', date('Y'), get_bloginfo('name'), __('Tous droits réservés.', 'eco-starter'))
    );

    // Remplace {year} par l'année en cours
    $text = str_replace('{year}', date('Y'), $text);

    return wp_kses_post($text);
}


// =============================================================================
// MODE MAINTENANCE
// =============================================================================

add_action('template_redirect', function (): void {

    if (!get_theme_mod('maintenance_mode', false)) return;

    // Les administrateurs voient le site normalement
    if (current_user_can('manage_options')) return;

    // Pas de maintenance sur les pages d'auth WordPress
    if (is_user_logged_in()) return;

    // Affiche la page de maintenance et arrête l'exécution
    eco_render_maintenance_page();
    exit;

}, 1);


/**
 * Affiche la page de maintenance
 */
function eco_render_maintenance_page(): void
{
    // Header HTTP 503 — indique aux moteurs de recherche que c'est temporaire
    http_response_code(503);
    header('Retry-After: 3600'); // Réessayer dans 1h

    $title   = get_theme_mod('maintenance_title', __('Site en maintenance', 'eco-starter'));
    $message = get_theme_mod('maintenance_message', __('Nous effectuons une mise à jour. Nous serons de retour très bientôt.', 'eco-starter'));
    $email   = get_theme_mod('maintenance_email', '');
    $logo_id = get_theme_mod('custom_logo');
    $logo    = $logo_id ? wp_get_attachment_image_src($logo_id, 'medium') : null;

    ?>
    <!DOCTYPE html>
    <html lang="<?php echo esc_attr(get_locale()); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title><?php echo esc_html($title . ' — ' . get_bloginfo('name')); ?></title>
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: system-ui, -apple-system, sans-serif;
                background-color: #f9fafb;
                color: #1f2937;
                padding: 2rem;
            }
            .maintenance {
                max-width: 520px;
                width: 100%;
                text-align: center;
            }
            .maintenance__logo {
                margin-bottom: 2rem;
            }
            .maintenance__logo img {
                max-height: 60px;
                width: auto;
                margin: 0 auto;
            }
            .maintenance__icon {
                font-size: 4rem;
                margin-bottom: 1.5rem;
                display: block;
            }
            .maintenance__title {
                font-size: 1.75rem;
                font-weight: 700;
                margin: 0 0 1rem;
                color: #111827;
            }
            .maintenance__message {
                font-size: 1.0625rem;
                color: #4b5563;
                line-height: 1.7;
                margin: 0 0 2rem;
            }
            .maintenance__contact {
                font-size: 0.9375rem;
                color: #6b7280;
            }
            .maintenance__contact a {
                color: #2563eb;
                text-decoration: none;
            }
            .maintenance__contact a:hover {
                text-decoration: underline;
            }
            .maintenance__bar {
                margin-top: 3rem;
                height: 4px;
                background: linear-gradient(90deg, #2563eb 0%, #16a34a 100%);
                border-radius: 9999px;
                opacity: 0.3;
            }
        </style>
    </head>
    <body>
        <div class="maintenance" role="main">

            <?php if ($logo) : ?>
                <div class="maintenance__logo">
                    <img src="<?php echo esc_url($logo[0]); ?>"
                        alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
            <?php else : ?>
                <p style="font-size:1.25rem;font-weight:700;margin:0 0 2rem;">
                    <?php echo esc_html(get_bloginfo('name')); ?>
                </p>
            <?php endif; ?>

            <span class="maintenance__icon" aria-hidden="true">🔧</span>

            <h1 class="maintenance__title"><?php echo esc_html($title); ?></h1>

            <p class="maintenance__message"><?php echo wp_kses_post($message); ?></p>

            <?php if ($email) : ?>
                <p class="maintenance__contact">
                    <?php esc_html_e('Besoin d\'aide ?', 'eco-starter'); ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>">
                        <?php echo esc_html($email); ?>
                    </a>
                </p>
            <?php endif; ?>

            <div class="maintenance__bar" aria-hidden="true"></div>

        </div>
    </body>
    </html>
    <?php
}


// =============================================================================
// ANALYTICS — injection des scripts
// =============================================================================

add_action('wp_head', function (): void {

    // Scripts personnalisés <head>
    $head_scripts = get_theme_mod('head_scripts', '');
    if ($head_scripts && current_user_can('manage_options') || !is_admin()) {
        echo $head_scripts . "\n"; // Déjà sanitisé via eco_sanitize_scripts
    }

    // Google Analytics 4
    $ga4_id = get_theme_mod('ga4_measurement_id', '');
    $gtm_id = get_theme_mod('gtm_container_id', '');

    // GTM a priorité sur GA4 direct
    if ($gtm_id && !$ga4_id) {
        $gtm_id_clean = sanitize_text_field($gtm_id);
        echo <<<HTML
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{$gtm_id_clean}');</script>
        <!-- End Google Tag Manager -->
        HTML;
    }

    if ($ga4_id && !$gtm_id) {
        $ga4_id_clean = sanitize_text_field($ga4_id);
        echo <<<HTML
        <!-- Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={$ga4_id_clean}"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{$ga4_id_clean}');
        </script>
        <!-- End Google Analytics 4 -->
        HTML;
    }

}, 99);


add_action('wp_body_open', function (): void {

    // GTM noscript
    $gtm_id = get_theme_mod('gtm_container_id', '');
    if ($gtm_id) {
        $gtm_id_clean = sanitize_text_field($gtm_id);
        echo <<<HTML
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$gtm_id_clean}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        HTML;
    }
});


add_action('wp_footer', function (): void {

    // Scripts personnalisés footer
    $footer_scripts = get_theme_mod('footer_scripts', '');
    if ($footer_scripts) {
        echo $footer_scripts . "\n";
    }

}, 99);


// =============================================================================
// POSTMESSAGE — Aperçu temps réel dans le Customizer
// Mises à jour sans rechargement pour les settings en transport: 'postMessage'
// =============================================================================

add_action('customize_preview_init', function (): void {

    wp_enqueue_script(
        'eco-customizer-preview',
        eco_asset('assets/js/customizer-preview.js'),
        ['customize-preview'],
        ECO_STARTER_VERSION,
        true
    );
});