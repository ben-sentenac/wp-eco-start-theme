<?php
/**
 * SEO natif — Eco Starter
 *
 * - Balises meta (title, description, canonical, robots)
 * - Open Graph (Facebook, LinkedIn)
 * - Twitter Cards
 * - Schema.org JSON-LD (Organization, WebSite, Article, BreadcrumbList,
 *                        FAQPage, Person, Product si WooCommerce)
 * - Breadcrumbs accessibles
 * - Nettoyage du <head>
 *
 * Compatible Yoast / RankMath : si un plugin SEO est actif,
 * les fonctions natives se désactivent automatiquement.
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// DÉTECTION PLUGIN SEO TIERS
// Si Yoast ou RankMath est actif, on leur laisse la main sur tout
// =============================================================================

function eco_seo_plugin_active(): bool
{
    return defined('WPSEO_VERSION')        // Yoast SEO
        || defined('RANK_MATH_VERSION')    // RankMath
        || defined('SQ_VERSION')           // Squirrly SEO
        || class_exists('AIOSEOP_Class');  // All In One SEO
}


// =============================================================================
// META DESCRIPTION
// =============================================================================

add_action('wp_head', function (): void {

    if (eco_seo_plugin_active()) return;

    $description = eco_get_meta_description();

    if (empty($description)) return;

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";

}, 1);


/**
 * Calcule la meta description selon le contexte
 */
function eco_get_meta_description(): string
{
    // Page d'accueil statique
    if (is_front_page() && is_page()) {
        $page_id = get_option('page_on_front');
        $desc    = get_post_meta($page_id, 'eco_meta_description', true);
        if ($desc) return $desc;
        return get_bloginfo('description');
    }

    // Blog (page des articles)
    if (is_home()) {
        return get_bloginfo('description') ?: get_bloginfo('name');
    }

    // Page ou article singulier
    if (is_singular()) {
        $post = get_queried_object();
        if (!$post instanceof \WP_Post) return '';

        // Meta custom en priorité
        $custom = get_post_meta($post->ID, 'eco_meta_description', true);
        if ($custom) return $custom;

        // Extrait WordPress
        if ($post->post_excerpt) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        // Début du contenu tronqué
        return eco_excerpt(wp_strip_all_tags($post->post_content), 25, '');
    }

    // Archive de taxonomie
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof \WP_Term && $term->description) {
            return wp_strip_all_tags($term->description);
        }
    }

    // Archive de CPT
    if (is_post_type_archive()) {
        $post_type = get_queried_object();
        if ($post_type instanceof \WP_Post_Type) {
            return $post_type->description ?: get_bloginfo('description');
        }
    }

    // Archive auteur
    if (is_author()) {
        $author = get_queried_object();
        if ($author instanceof \WP_User) {
            return $author->description ?: sprintf(
                __('Articles de %s', 'eco-starter'),
                $author->display_name
            );
        }
    }

    return get_bloginfo('description');
}


// =============================================================================
// CANONICAL
// =============================================================================

add_action('wp_head', function (): void {

    if (eco_seo_plugin_active()) return;

    $canonical = eco_get_canonical_url();

    if ($canonical) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

}, 1);


function eco_get_canonical_url(): string
{
    if (is_singular()) {
        return (string) get_permalink();
    }

    if (is_front_page()) {
        return home_url('/');
    }

    if (is_home()) {
        $page_for_posts = get_option('page_for_posts');
        return $page_for_posts ? (string) get_permalink($page_for_posts) : home_url('/');
    }

    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof \WP_Term) {
            return (string) get_term_link($term);
        }
    }

    if (is_post_type_archive()) {
        return (string) get_post_type_archive_link(get_post_type());
    }

    if (is_author()) {
        return (string) get_author_posts_url(get_queried_object_id());
    }

    return '';
}


// =============================================================================
// ROBOTS META
// =============================================================================

add_action('wp_head', function (): void {

    if (eco_seo_plugin_active()) return;

    $robots = eco_get_robots_meta();

    if ($robots) {
        echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
    }

}, 1);


function eco_get_robots_meta(): string
{
    // Pages systèmes WordPress → noindex
    if (is_search() || is_404() || is_date()) {
        return 'noindex, follow';
    }

    // Pagination profonde → noindex optionnel
    if (is_paged() && get_query_var('paged') > 5) {
        return 'noindex, follow';
    }

    // Preview ou brouillon
    if (is_preview() || get_post_status() === 'draft') {
        return 'noindex, nofollow';
    }

    return 'index, follow';
}


// =============================================================================
// OPEN GRAPH + TWITTER CARDS
// =============================================================================

add_action('wp_head', function (): void {

    if (eco_seo_plugin_active()) return;

    $og = eco_get_og_data();

    if (empty($og)) return;

    // --- Open Graph ---
    $og_tags = [
        'og:type'        => $og['type'],
        'og:title'       => $og['title'],
        'og:description' => $og['description'],
        'og:url'         => $og['url'],
        'og:site_name'   => get_bloginfo('name'),
        'og:locale'      => str_replace('-', '_', get_locale()),
    ];

    if (!empty($og['image'])) {
        $og_tags['og:image']            = $og['image'];
        $og_tags['og:image:width']      = $og['image_width']  ?? '1200';
        $og_tags['og:image:height']     = $og['image_height'] ?? '630';
        $og_tags['og:image:alt']        = $og['image_alt']    ?? $og['title'];
    }

    if ($og['type'] === 'article') {
        $og_tags['article:published_time'] = $og['published'] ?? '';
        $og_tags['article:modified_time']  = $og['modified']  ?? '';
        $og_tags['article:author']         = $og['author']    ?? '';
    }

    foreach ($og_tags as $property => $content) {
        if (empty($content)) continue;
        echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
    }

    // --- Twitter Cards ---
    $twitter_tags = [
        'twitter:card'        => !empty($og['image']) ? 'summary_large_image' : 'summary',
        'twitter:title'       => $og['title'],
        'twitter:description' => $og['description'],
    ];

    if (!empty($og['image'])) {
        $twitter_tags['twitter:image']     = $og['image'];
        $twitter_tags['twitter:image:alt'] = $og['image_alt'] ?? $og['title'];
    }

    // Handle Twitter du site (Customizer)
    $twitter_handle = get_theme_mod('twitter_handle', '');
    if ($twitter_handle) {
        $twitter_tags['twitter:site'] = '@' . ltrim($twitter_handle, '@');
    }

    foreach ($twitter_tags as $name => $content) {
        if (empty($content)) continue;
        echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
    }

}, 2);


/**
 * Construit le tableau de données Open Graph selon le contexte
 *
 * @return array<string, string>
 */
function eco_get_og_data(): array
{
    $data = [
        'type'        => 'website',
        'title'       => wp_get_document_title(),
        'description' => eco_get_meta_description(),
        'url'         => eco_get_canonical_url() ?: home_url('/'),
        'image'       => '',
        'image_width' => '',
        'image_height'=> '',
        'image_alt'   => '',
    ];

    // Image par défaut du site (Customizer)
    $default_og_image = get_theme_mod('og_default_image', '');
    if ($default_og_image) {
        $data['image'] = $default_og_image;
    }

    if (is_singular()) {
        $post = get_queried_object();
        if (!$post instanceof \WP_Post) return $data;

        $data['type'] = 'article';
        $data['published'] = get_the_date('c', $post);
        $data['modified']  = get_the_modified_date('c', $post);
        $data['author']    = get_the_author_meta('display_name', $post->post_author);

        // Image mise en avant
        if (has_post_thumbnail($post->ID)) {
            $thumb_id  = get_post_thumbnail_id($post->ID);
            $thumb_src = wp_get_attachment_image_src($thumb_id, 'eco-featured');

            if ($thumb_src) {
                $data['image']        = $thumb_src[0];
                $data['image_width']  = (string) $thumb_src[1];
                $data['image_height'] = (string) $thumb_src[2];
                $data['image_alt']    = trim((string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true))
                    ?: get_the_title($post->ID);
            }
        }

        // Première image du contenu si pas de featured image
        if (empty($data['image'])) {
            preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $matches);
            if (!empty($matches[1])) {
                $data['image'] = $matches[1];
            }
        }
    }

    // Archive : image du terme si disponible
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof \WP_Term) {
            $term_image = get_term_meta($term->term_id, 'eco_term_image', true);
            if ($term_image) {
                $data['image'] = $term_image;
            }
        }
    }

    return $data;
}


// =============================================================================
// SCHEMA.ORG JSON-LD
// =============================================================================

add_action('wp_head', function (): void {

    if (eco_seo_plugin_active()) return;

    $schemas = [];

    // WebSite — toujours présent (SearchAction pour la recherche interne)
    $schemas[] = eco_schema_website();

    // Organization — sur la home uniquement
    if (is_front_page()) {
        $schemas[] = eco_schema_organization();
    }

    // Article / BlogPosting — articles de blog
    if (is_singular('post')) {
        $schemas[] = eco_schema_article();
    }

    // BreadcrumbList — partout sauf la home
    if (!is_front_page() && !is_404()) {
        $breadcrumb_schema = eco_schema_breadcrumb();
        if ($breadcrumb_schema) {
            $schemas[] = $breadcrumb_schema;
        }
    }

    // FAQPage — sur les pages contenant des FAQ
    if (is_singular() && eco_page_has_faq()) {
        $faq_schema = eco_schema_faq();
        if ($faq_schema) {
            $schemas[] = $faq_schema;
        }
    }

    // Person — sur les singles du CPT team
    if (is_singular('team')) {
        $schemas[] = eco_schema_person();
    }

    // Product — WooCommerce
    if (class_exists('WooCommerce') && is_singular('product')) {
        $schemas[] = eco_schema_product();
    }

    // Affichage des schemas
    foreach (array_filter($schemas) as $schema) {
        echo '<script type="application/ld+json">'
            . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . '</script>' . "\n";
    }

}, 5);


/**
 * Schema WebSite avec SearchAction
 *
 * @return array<string, mixed>
 */
function eco_schema_website(): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        '@id'      => home_url('/#website'),
        'name'     => get_bloginfo('name'),
        'url'      => home_url('/'),
    ];

    $description = get_bloginfo('description');
    if ($description) {
        $schema['description'] = $description;
    }

    // SearchAction — active le sitelinks search box dans Google
    $schema['potentialAction'] = [
        '@type'       => 'SearchAction',
        'target'      => [
            '@type'       => 'EntryPoint',
            'urlTemplate' => home_url('/?s={search_term_string}'),
        ],
        'query-input' => 'required name=search_term_string',
    ];

    return $schema;
}


/**
 * Schema Organization
 * Données configurables via le Customizer (inc/customizer.php)
 *
 * @return array<string, mixed>
 */
function eco_schema_organization(): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => get_theme_mod('schema_org_type', 'Organization'),
        '@id'      => home_url('/#organization'),
        'name'     => get_theme_mod('schema_org_name', get_bloginfo('name')),
        'url'      => home_url('/'),
    ];

    // Logo
    $logo_id = get_theme_mod('custom_logo');
    if ($logo_id) {
        $logo_src = wp_get_attachment_image_src($logo_id, 'full');
        if ($logo_src) {
            $schema['logo'] = [
                '@type'  => 'ImageObject',
                '@id'    => home_url('/#logo'),
                'url'    => $logo_src[0],
                'width'  => $logo_src[1],
                'height' => $logo_src[2],
            ];
            $schema['image'] = ['@id' => home_url('/#logo')];
        }
    }

    // Coordonnées
    $phone = get_theme_mod('schema_phone', '');
    $email = get_theme_mod('schema_email', '');
    if ($phone) $schema['telephone'] = $phone;
    if ($email) $schema['email']     = $email;

    // Adresse
    $street   = get_theme_mod('schema_address_street', '');
    $city     = get_theme_mod('schema_address_city', '');
    $zip      = get_theme_mod('schema_address_zip', '');
    $country  = get_theme_mod('schema_address_country', 'FR');

    if ($street || $city) {
        $schema['address'] = array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street,
            'addressLocality' => $city,
            'postalCode'      => $zip,
            'addressCountry'  => $country,
        ]);
    }

    // Réseaux sociaux (sameAs)
    $social_profiles = array_filter([
        get_theme_mod('social_facebook', ''),
        get_theme_mod('social_twitter', ''),
        get_theme_mod('social_linkedin', ''),
        get_theme_mod('social_instagram', ''),
        get_theme_mod('social_youtube', ''),
        get_theme_mod('social_github', ''),
    ]);

    if (!empty($social_profiles)) {
        $schema['sameAs'] = array_values($social_profiles);
    }

    return $schema;
}


/**
 * Schema Article / BlogPosting
 *
 * @return array<string, mixed>
 */
function eco_schema_article(): array
{
    $post       = get_queried_object();
    $author_id  = $post->post_author;
    $thumb_id   = get_post_thumbnail_id($post->ID);
    $thumb_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'eco-featured') : null;

    $schema = [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        '@id'              => get_permalink($post->ID) . '#article',
        'headline'         => get_the_title($post->ID),
        'url'              => get_permalink($post->ID),
        'datePublished'    => get_the_date('c', $post),
        'dateModified'     => get_the_modified_date('c', $post),
        'author'           => [
            '@type' => 'Person',
            '@id'   => get_author_posts_url($author_id) . '#person',
            'name'  => get_the_author_meta('display_name', $author_id),
            'url'   => get_author_posts_url($author_id),
        ],
        'publisher'        => [
            '@id' => home_url('/#organization'),
        ],
        'isPartOf'         => [
            '@id' => home_url('/#website'),
        ],
        'inLanguage'       => get_locale(),
        'wordCount'        => str_word_count(wp_strip_all_tags($post->post_content)),
    ];

    // Description
    $description = eco_get_meta_description();
    if ($description) {
        $schema['description'] = $description;
    }

    // Image principale
    if ($thumb_src) {
        $schema['image'] = [
            '@type'  => 'ImageObject',
            'url'    => $thumb_src[0],
            'width'  => $thumb_src[1],
            'height' => $thumb_src[2],
        ];
    }

    // Catégories
    $categories = get_the_category($post->ID);
    if (!empty($categories)) {
        $schema['articleSection'] = $categories[0]->name;
        $schema['keywords']       = implode(', ', array_column($categories, 'name'));
    }

    return $schema;
}


/**
 * Schema BreadcrumbList
 *
 * @return array<string, mixed>|null
 */
function eco_schema_breadcrumb(): ?array
{
    $items     = eco_get_breadcrumb_items();
    $list_items = [];

    foreach ($items as $position => $item) {
        $list_items[] = [
            '@type'    => 'ListItem',
            'position' => $position + 1,
            'name'     => $item['name'],
            'item'     => $item['url'],
        ];
    }

    if (empty($list_items)) return null;

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $list_items,
    ];
}


/**
 * Schema FAQPage
 * Lit les posts FAQ liés à la page via meta
 *
 * @return array<string, mixed>|null
 */
function eco_schema_faq(): ?array
{
    $post_id   = get_the_ID();
    $faq_items = eco_get_page_faq_items($post_id);

    if (empty($faq_items)) return null;

    $entities = [];
    foreach ($faq_items as $item) {
        $entities[] = [
            '@type'          => 'Question',
            'name'           => wp_strip_all_tags($item['question']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => wp_strip_all_tags($item['answer']),
            ],
        ];
    }

    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    ];
}


/**
 * Schema Person (CPT team)
 *
 * @return array<string, mixed>
 */
function eco_schema_person(): array
{
    $post      = get_queried_object();
    $post_id   = $post->ID;
    $thumb_src = has_post_thumbnail($post_id)
        ? wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'eco-portrait')
        : null;

    $schema = [
        '@context'  => 'https://schema.org',
        '@type'     => 'Person',
        '@id'       => get_permalink($post_id) . '#person',
        'name'      => get_the_title($post_id),
        'jobTitle'  => eco_field('eco_job_title', $post_id),
        'url'       => get_permalink($post_id),
        'worksFor'  => ['@id' => home_url('/#organization')],
    ];

    if ($thumb_src) {
        $schema['image'] = $thumb_src[0];
    }

    $email    = eco_field('eco_email', $post_id);
    $linkedin = eco_field('eco_linkedin', $post_id);

    if ($email)    $schema['email']  = $email;
    if ($linkedin) $schema['sameAs'] = [$linkedin];

    return array_filter($schema);
}


/**
 * Schema Product (WooCommerce)
 *
 * @return array<string, mixed>|null
 */
function eco_schema_product(): ?array
{
    if (!class_exists('WC_Product')) return null;

    $product = wc_get_product(get_the_ID());
    if (!$product) return null;

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->get_name(),
        'description' => wp_strip_all_tags($product->get_description() ?: $product->get_short_description()),
        'sku'         => $product->get_sku(),
        'url'         => get_permalink($product->get_id()),
        'brand'       => [
            '@type' => 'Brand',
            'name'  => get_bloginfo('name'),
        ],
    ];

    // Image
    $image_id  = $product->get_image_id();
    $image_src = $image_id ? wp_get_attachment_image_src($image_id, 'full') : null;
    if ($image_src) {
        $schema['image'] = $image_src[0];
    }

    // Prix et disponibilité
    $schema['offers'] = [
        '@type'         => 'Offer',
        'url'           => get_permalink($product->get_id()),
        'priceCurrency' => get_woocommerce_currency(),
        'price'         => $product->get_price(),
        'priceValidUntil' => date('Y-12-31'),
        'availability'  => $product->is_in_stock()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
        'seller'        => ['@id' => home_url('/#organization')],
    ];

    // Notes / avis
    $rating_count = $product->get_rating_count();
    if ($rating_count > 0) {
        $schema['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => round($product->get_average_rating(), 1),
            'ratingCount' => $rating_count,
            'bestRating'  => '5',
            'worstRating' => '1',
        ];
    }

    return $schema;
}


// =============================================================================
// BREADCRUMBS HTML
// Accessible, Schema.org natif, compatible WordPress
// =============================================================================

/**
 * Affiche le fil d'Ariane HTML
 * Usage dans les templates : eco_breadcrumb();
 */
function eco_breadcrumb(): void
{
    if (is_front_page()) return;

    $items = eco_get_breadcrumb_items();

    if (empty($items)) return;

    echo '<nav aria-label="' . esc_attr__('Fil d\'Ariane', 'eco-starter') . '">' . "\n";
    echo '<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">' . "\n";

    $total = count($items);

    foreach ($items as $index => $item) {
        $is_last = ($index === $total - 1);
        $position = $index + 1;

        echo '<li class="breadcrumb__item"'
            . ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

        if ($is_last) {
            echo '<span class="breadcrumb__current" itemprop="name" aria-current="page">'
                . esc_html($item['name'])
                . '</span>';
        } else {
            echo '<a class="breadcrumb__link" href="' . esc_url($item['url']) . '"'
                . ' itemprop="item">'
                . '<span itemprop="name">' . esc_html($item['name']) . '</span>'
                . '</a>';
        }

        echo '<meta itemprop="position" content="' . esc_attr($position) . '">';
        echo '</li>' . "\n";
    }

    echo '</ol>' . "\n";
    echo '</nav>' . "\n";
}


/**
 * Calcule les items du fil d'Ariane selon le contexte
 *
 * @return array<int, array{name: string, url: string}>
 */
function eco_get_breadcrumb_items(): array
{
    $items = [
        ['name' => __('Accueil', 'eco-starter'), 'url' => home_url('/')],
    ];

    // Article de blog
    if (is_singular('post')) {
        $page_for_posts = get_option('page_for_posts');
        if ($page_for_posts) {
            $items[] = [
                'name' => get_the_title($page_for_posts),
                'url'  => get_permalink($page_for_posts),
            ];
        }

        // Première catégorie
        $categories = get_the_category();
        if (!empty($categories)) {
            $cat = $categories[0];
            // Catégories parentes
            $cat_ancestors = array_reverse(get_ancestors($cat->term_id, 'category'));
            foreach ($cat_ancestors as $ancestor_id) {
                $ancestor = get_category($ancestor_id);
                $items[]  = ['name' => $ancestor->name, 'url' => get_category_link($ancestor_id)];
            }
            $items[] = ['name' => $cat->name, 'url' => get_category_link($cat->term_id)];
        }

        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
        return $items;
    }

    // Page hiérarchique
    if (is_page()) {
        $ancestors = array_reverse(get_post_ancestors(get_the_ID()));
        foreach ($ancestors as $ancestor_id) {
            $items[] = ['name' => get_the_title($ancestor_id), 'url' => get_permalink($ancestor_id)];
        }
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
        return $items;
    }

    // CPT single
    if (is_singular()) {
        $post_type        = get_post_type();
        $post_type_object = get_post_type_object($post_type);

        if ($post_type_object && $post_type_object->has_archive) {
            $archive_url = get_post_type_archive_link($post_type);
            if ($archive_url) {
                $items[] = [
                    'name' => $post_type_object->labels->name,
                    'url'  => $archive_url,
                ];
            }
        }

        // Taxonomie principale du CPT
        $taxonomies = get_object_taxonomies($post_type);
        if (!empty($taxonomies)) {
            $terms = get_the_terms(get_the_ID(), $taxonomies[0]);
            if ($terms && !is_wp_error($terms)) {
                $items[] = ['name' => $terms[0]->name, 'url' => get_term_link($terms[0])];
            }
        }

        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
        return $items;
    }

    // Archive de catégorie
    if (is_category()) {
        $cat      = get_queried_object();
        $ancestors = array_reverse(get_ancestors($cat->term_id, 'category'));
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_category($ancestor_id);
            $items[]  = ['name' => $ancestor->name, 'url' => get_category_link($ancestor_id)];
        }
        $items[] = ['name' => $cat->name, 'url' => get_category_link($cat->term_id)];
        return $items;
    }

    // Archive de taxonomie custom
    if (is_tax()) {
        $term      = get_queried_object();
        $post_type = get_taxonomy($term->taxonomy)->object_type[0] ?? '';

        if ($post_type) {
            $pto = get_post_type_object($post_type);
            if ($pto && $pto->has_archive) {
                $items[] = [
                    'name' => $pto->labels->name,
                    'url'  => (string) get_post_type_archive_link($post_type),
                ];
            }
        }

        // Termes parents
        $ancestors = array_reverse(get_ancestors($term->term_id, $term->taxonomy));
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, $term->taxonomy);
            $items[]  = ['name' => $ancestor->name, 'url' => (string) get_term_link($ancestor)];
        }

        $items[] = ['name' => $term->name, 'url' => (string) get_term_link($term)];
        return $items;
    }

    // Archive de CPT
    if (is_post_type_archive()) {
        $pto     = get_queried_object();
        $items[] = ['name' => $pto->labels->name, 'url' => (string) get_post_type_archive_link($pto->name)];
        return $items;
    }

    // Archive auteur
    if (is_author()) {
        $author  = get_queried_object();
        $items[] = [
            'name' => sprintf(__('Articles de %s', 'eco-starter'), $author->display_name),
            'url'  => get_author_posts_url($author->ID),
        ];
        return $items;
    }

    // Recherche
    if (is_search()) {
        $items[] = [
            'name' => sprintf(__('Résultats pour : %s', 'eco-starter'), get_search_query()),
            'url'  => get_search_link(),
        ];
        return $items;
    }

    // 404
    if (is_404()) {
        $items[] = ['name' => __('Page introuvable', 'eco-starter'), 'url' => ''];
        return $items;
    }

    return $items;
}


// =============================================================================
// META BOX SEO — champs meta description + og par post/page
// Interface légère pour le client dans l'éditeur WordPress
// =============================================================================

add_action('add_meta_boxes', function (): void {

    if (eco_seo_plugin_active()) return;

    $post_types = array_merge(
        ['post', 'page'],
        get_post_types(['public' => true, '_builtin' => false], 'names')
    );

    add_meta_box(
        'eco_seo_meta',
        __('SEO', 'eco-starter'),
        'eco_render_metabox_seo',
        $post_types,
        'normal',
        'low'
    );
});


function eco_render_metabox_seo(\WP_Post $post): void
{
    wp_nonce_field('eco_seo_save', 'eco_seo_nonce');

    $meta_desc = get_post_meta($post->ID, 'eco_meta_description', true);
    $og_image  = get_post_meta($post->ID, 'eco_og_image', true);

    ?>
    <div class="eco-metabox">
        <p>
            <label for="eco_meta_description">
                <strong><?php esc_html_e('Meta description', 'eco-starter'); ?></strong>
                <span style="color:#666;font-weight:normal;margin-left:8px;">
                    (<?php esc_html_e('recommandé : 150-160 caractères', 'eco-starter'); ?>)
                </span>
            </label>
            <textarea
                id="eco_meta_description"
                name="eco_meta_description"
                rows="3"
                maxlength="320"
                placeholder="<?php esc_attr_e('Description affichée dans les résultats de recherche Google…', 'eco-starter'); ?>"
                style="width:100%;margin-top:6px;"
            ><?php echo esc_textarea($meta_desc); ?></textarea>
            <span id="eco_meta_desc_count" style="font-size:12px;color:#666;">
                <?php echo strlen($meta_desc); ?>/160
            </span>
        </p>

        <p>
            <label for="eco_og_image">
                <strong><?php esc_html_e('Image Open Graph', 'eco-starter'); ?></strong>
                <span style="color:#666;font-weight:normal;margin-left:8px;">
                    (<?php esc_html_e('recommandé : 1200×630px — surcharge l\'image mise en avant', 'eco-starter'); ?>)
                </span>
            </label>
            <div style="display:flex;gap:8px;margin-top:6px;align-items:center;">
                <input
                    type="url"
                    id="eco_og_image"
                    name="eco_og_image"
                    value="<?php echo esc_url($og_image); ?>"
                    placeholder="https://"
                    style="flex:1;"
                >
                <button type="button" class="button" id="eco_og_image_btn">
                    <?php esc_html_e('Choisir', 'eco-starter'); ?>
                </button>
            </div>
            <?php if ($og_image) : ?>
                <img src="<?php echo esc_url($og_image); ?>"
                    style="margin-top:8px;max-width:200px;height:auto;border-radius:4px;"
                    alt="">
            <?php endif; ?>
        </p>
    </div>

    <script>
    // Compteur de caractères meta description
    document.getElementById('eco_meta_description')?.addEventListener('input', function() {
        const count = this.value.length;
        const counter = document.getElementById('eco_meta_desc_count');
        if (counter) {
            counter.textContent = count + '/160';
            counter.style.color = count > 160 ? '#dc2626' : count > 140 ? '#d97706' : '#666';
        }
    });

    // Media library WordPress pour l'image OG
    document.getElementById('eco_og_image_btn')?.addEventListener('click', function() {
        const frame = wp.media({
            title: '<?php esc_html_e('Choisir l\'image Open Graph', 'eco-starter'); ?>',
            button: { text: '<?php esc_html_e('Utiliser cette image', 'eco-starter'); ?>' },
            multiple: false,
            library: { type: 'image' }
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById('eco_og_image').value = attachment.url;
        });
        frame.open();
    });
    </script>
    <?php
}


add_action('save_post', function (int $post_id): void {

    if (eco_seo_plugin_active()) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (
        !isset($_POST['eco_seo_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_seo_nonce'])), 'eco_seo_save')
    ) return;

    if (isset($_POST['eco_meta_description'])) {
        update_post_meta(
            $post_id,
            'eco_meta_description',
            sanitize_textarea_field(wp_unslash($_POST['eco_meta_description']))
        );
    }

    if (isset($_POST['eco_og_image'])) {
        update_post_meta(
            $post_id,
            'eco_og_image',
            esc_url_raw(wp_unslash($_POST['eco_og_image']))
        );
    }
});


// =============================================================================
// HELPERS INTERNES
// =============================================================================

/**
 * Vérifie si la page courante contient des FAQ
 * (meta eco_has_faq ou présence de posts faq liés)
 */
function eco_page_has_faq(): bool
{
    $post_id = get_the_ID();
    return (bool) get_post_meta($post_id, 'eco_has_faq', true);
}


/**
 * Retourne les items FAQ associés à un post
 *
 * @return array<int, array{question: string, answer: string}>
 */
function eco_get_page_faq_items(int $post_id): array
{
    // FAQ stockées comme meta JSON (ajoutées via le Customizer/meta box)
    $faq_json = get_post_meta($post_id, 'eco_faq_items', true);
    if ($faq_json) {
        $items = json_decode($faq_json, true);
        if (is_array($items)) return $items;
    }

    // Fallback : posts du CPT faq liés à cette page
    $faq_ids = get_post_meta($post_id, 'eco_linked_faqs', true);
    if (!$faq_ids) return [];

    $faq_posts = get_posts([
        'post_type'      => 'faq',
        'post__in'       => array_map('absint', explode(',', $faq_ids)),
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ]);

    return array_map(fn(\WP_Post $p) => [
        'question' => $p->post_title,
        'answer'   => $p->post_content,
    ], $faq_posts);
}