<?php
/**
 * Page statique — Eco Starter
 *
 * @package EcoStarter
 */
defined('ABSPATH') || exit;

get_header();

$sidebar_on = get_theme_mod('sidebar_enabled', false) && is_active_sidebar('sidebar-main');
?>

<?php eco_main_open(); ?>

    <div class="page-wrapper">

        <!-- Breadcrumb (pas sur la home) -->
        <?php if (!is_front_page()) : ?>
            <?php eco_breadcrumb(); ?>
        <?php endif; ?>

        <div class="<?php echo $sidebar_on ? 'layout-sidebar' : 'layout-fullwidth'; ?>">

            <!-- Contenu principal -->
            <div class="content-area">

                <?php
                while (have_posts()) {
                    the_post();
                ?>

                <article id="page-<?php the_ID(); ?>" <?php post_class('entry entry--page'); ?>>

                    <!-- Titre de la page
                         Masqué sur la home si un hero prend le relais -->
                    <?php if (!is_front_page()) : ?>
                        <header class="entry-header">
                            <h1 class="entry-title"><?php the_title(); ?></h1>

                            <!-- Image mise en avant de la page -->
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="entry-thumbnail">
                                    <?php
                                    the_post_thumbnail('eco-hero', [
                                        'class'         => 'entry-thumbnail__img',
                                        'loading'       => 'eager',
                                        'fetchpriority' => 'high',
                                        'alt'           => trim(strip_tags(get_post_meta(
                                            get_post_thumbnail_id(),
                                            '_wp_attachment_image_alt',
                                            true
                                        ))) ?: get_the_title(),
                                    ]);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </header>
                    <?php endif; ?>

                    <!-- Contenu Gutenberg ou classique -->
                    <div class="entry-content prose">
                        <?php the_content(); ?>

                        <!-- Pagination interne (<!--nextpage-->) -->
                        <?php
                        wp_link_pages([
                            'before'      => '<nav class="entry-pages"
                                aria-label="' . esc_attr__('Pages', 'eco-starter') . '">',
                            'after'       => '</nav>',
                            'link_before' => '<span>',
                            'link_after'  => '</span>',
                        ]);
                        ?>
                    </div><!-- /.entry-content -->

                    <!-- Commentaires sur page (si activés) -->
                    <?php if (comments_open() || get_comments_number()) : ?>
                        <?php comments_template(); ?>
                    <?php endif; ?>

                </article><!-- /.entry--page -->

                <!-- Sous-pages (si la page a des enfants) -->
                <?php
                $children = get_pages([
                    'child_of'    => get_the_ID(),
                    'sort_column' => 'menu_order',
                    'sort_order'  => 'ASC',
                ]);

                if (!empty($children)) :
                ?>
                    <section class="subpages"
                        aria-label="<?php esc_attr_e('Sous-pages', 'eco-starter'); ?>">
                        <h2 class="subpages__title">
                            <?php esc_html_e('Pages associées', 'eco-starter'); ?>
                        </h2>
                        <ul class="subpages__list posts-grid posts-grid--3">
                            <?php foreach ($children as $child) : ?>
                                <li>
                                    <a href="<?php echo esc_url(get_permalink($child->ID)); ?>"
                                        class="card card--minimal">
                                        <?php if (has_post_thumbnail($child->ID)) : ?>
                                            <div class="card__media">
                                                <?php echo get_the_post_thumbnail($child->ID, 'eco-card', [
                                                    'loading' => 'lazy',
                                                    'alt'     => esc_attr($child->post_title),
                                                ]); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card__body">
                                            <h3 class="card__title">
                                                <?php echo esc_html($child->post_title); ?>
                                            </h3>
                                            <?php if ($child->post_excerpt) : ?>
                                                <p class="card__excerpt">
                                                    <?php echo esc_html(wp_trim_words($child->post_excerpt, 15)); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php } // end while ?>

            </div><!-- /.content-area -->

            <!-- Sidebar optionnelle -->
            <?php if ($sidebar_on) : ?>
                <?php get_sidebar(); ?>
            <?php endif; ?>

        </div><!-- /.layout-* -->

    </div><!-- /.page-wrapper -->

<?php eco_main_close(); ?>

<?php get_footer(); ?>