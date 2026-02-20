<?php
/**
 * Template par défaut — Archive / Blog — Eco Starter
 *
 * Utilisé pour : archive d'articles, page blog, résultats de recherche,
 * archives de dates, archives d'auteurs, et tout ce qui n'a pas de
 * template plus spécifique.
 *
 * @package EcoStarter
 */
defined('ABSPATH') || exit;

get_header();

$layout     = get_theme_mod('blog_layout', 'grid');
$cols       = get_theme_mod('blog_columns', '3');
$post_type  = get_post_type() ?: 'post';
$is_search  = is_search();

// Classes de la grille selon le layout configuré
$grid_classes = eco_classes([
    'posts-grid'              => $layout === 'grid' || $layout === 'featured',
    'posts-grid--featured'    => $layout === 'featured',
    'posts-grid--' . $cols    => $layout === 'grid',
    'posts-list'              => $layout === 'list',
]);
?>

<?php eco_main_open(); ?>

    <div class="page-wrapper">

        <!-- En-tête d'archive -->
        <?php if (!is_home() || is_front_page()) : ?>
            <header class="archive-header section__header">

                <?php if ($is_search) : ?>
                    <p class="section__label">
                        <?php esc_html_e('Recherche', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php
                        printf(
                            esc_html__('Résultats pour : %s', 'eco-starter'),
                            '<span>' . get_search_query() . '</span>'
                        );
                        ?>
                    </h1>
                    <?php if (have_posts()) : ?>
                        <p class="archive-header__count section__subtitle">
                            <?php
                            printf(
                                esc_html(_n(
                                    '%s résultat trouvé',
                                    '%s résultats trouvés',
                                    (int) $wp_query->found_posts,
                                    'eco-starter'
                                )),
                                '<strong>' . number_format_i18n((int) $wp_query->found_posts) . '</strong>'
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                <?php elseif (is_category()) : ?>
                    <p class="section__label">
                        <?php esc_html_e('Catégorie', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php single_cat_title(); ?>
                    </h1>
                    <?php if (category_description()) : ?>
                        <div class="archive-header__description section__subtitle">
                            <?php echo wp_kses_post(category_description()); ?>
                        </div>
                    <?php endif; ?>

                <?php elseif (is_tag()) : ?>
                    <p class="section__label">
                        <?php esc_html_e('Tag', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php single_tag_title(); ?>
                    </h1>

                <?php elseif (is_author()) : ?>
                    <?php $author = get_queried_object(); ?>
                    <p class="section__label">
                        <?php esc_html_e('Auteur', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php echo esc_html($author->display_name); ?>
                    </h1>
                    <?php if ($author->description) : ?>
                        <p class="archive-header__description section__subtitle">
                            <?php echo esc_html($author->description); ?>
                        </p>
                    <?php endif; ?>

                <?php elseif (is_date()) : ?>
                    <p class="section__label">
                        <?php esc_html_e('Archive', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php
                        if (is_year()) {
                            echo esc_html(get_the_date('Y'));
                        } elseif (is_month()) {
                            echo esc_html(get_the_date('F Y'));
                        } else {
                            echo esc_html(get_the_date(get_option('date_format')));
                        }
                        ?>
                    </h1>

                <?php elseif (is_post_type_archive()) : ?>
                    <p class="section__label">
                        <?php esc_html_e('Archive', 'eco-starter'); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php post_type_archive_title(); ?>
                    </h1>

                <?php elseif (is_tax()) : ?>
                    <?php $term = get_queried_object(); ?>
                    <p class="section__label">
                        <?php echo esc_html(get_taxonomy($term->taxonomy)->labels->singular_name); ?>
                    </p>
                    <h1 class="archive-header__title section__title">
                        <?php echo esc_html($term->name); ?>
                    </h1>
                    <?php if ($term->description) : ?>
                        <div class="archive-header__description section__subtitle">
                            <?php echo wp_kses_post($term->description); ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </header><!-- /.archive-header -->
        <?php endif; ?>

        <!-- Breadcrumb -->
        <?php if (!is_front_page() && !is_home()) : ?>
            <?php eco_breadcrumb(); ?>
        <?php endif; ?>

        <!-- Layout sidebar ou full-width -->
        <div class="<?php echo get_theme_mod('sidebar_enabled', false) ? 'layout-sidebar' : 'layout-fullwidth'; ?>">

            <!-- Zone principale -->
            <div class="content-area">

                <?php if (have_posts()) : ?>

                    <!-- Grille / Liste d'articles -->
                    <div class="<?php echo esc_attr($grid_classes); ?>"
                        id="posts-container"
                        aria-live="polite"
                        aria-label="<?php esc_attr_e('Liste des articles', 'eco-starter'); ?>">

                        <?php
                        while (have_posts()) {
                            the_post();
                            eco_get_part('cards/post', [
                                'layout'    => $layout,
                                'show_meta' => get_theme_mod('show_post_meta', true),
                            ]);
                        }
                        ?>

                    </div><!-- /#posts-container -->

                    <!-- Load more / Pagination -->
                    <?php if (get_theme_mod('blog_load_more', false)) : ?>
                        <?php eco_get_part('components/load-more', [
                            'post_type' => $post_type,
                            'per_page'  => get_option('posts_per_page'),
                            'layout'    => $layout,
                        ]); ?>
                    <?php else : ?>
                        <?php
                        the_posts_pagination([
                            'mid_size'           => 2,
                            'prev_text'          => '<span aria-hidden="true">←</span> '
                                . '<span>' . esc_html__('Précédent', 'eco-starter') . '</span>',
                            'next_text'          => '<span>' . esc_html__('Suivant', 'eco-starter') . '</span> '
                                . '<span aria-hidden="true">→</span>',
                            'screen_reader_text' => __('Navigation entre les pages', 'eco-starter'),
                            'aria_label'         => __('Navigation entre les pages d\'articles', 'eco-starter'),
                        ]);
                        ?>
                    <?php endif; ?>

                <?php else : ?>

                    <!-- Aucun résultat -->
                    <?php eco_get_part('components/no-results', [
                        'is_search' => $is_search,
                    ]); ?>

                <?php endif; ?>

            </div><!-- /.content-area -->

            <!-- Sidebar optionnelle -->
            <?php if (get_theme_mod('sidebar_enabled', false) && is_active_sidebar('sidebar-blog')) : ?>
                <?php get_sidebar('blog'); ?>
            <?php endif; ?>

        </div><!-- /.layout-* -->

    </div><!-- /.page-wrapper -->

<?php eco_main_close(); ?>

<?php get_footer(); ?>