<?php
/**
 * Article de blog — Eco Starter
 *
 * @package EcoStarter
 */
defined('ABSPATH') || exit;

get_header();

$show_meta     = get_theme_mod('show_post_meta', true);
$show_reading  = get_theme_mod('show_reading_time', true);
$sidebar_on    = get_theme_mod('sidebar_enabled', false) && is_active_sidebar('sidebar-main');
?>

<?php eco_main_open(); ?>

    <div class="page-wrapper">

        <?php eco_breadcrumb(); ?>

        <div class="<?php echo $sidebar_on ? 'layout-sidebar' : 'layout-fullwidth'; ?>">

            <!-- Contenu principal -->
            <div class="content-area">

                <?php
                while (have_posts()) {
                    the_post();

                    // --- Calcul du temps de lecture ---
                    $word_count   = str_word_count(wp_strip_all_tags(get_the_content()));
                    $reading_time = max(1, (int) round($word_count / 200));

                    // --- Catégories et tags ---
                    $categories = get_the_category();
                    $tags       = get_the_tags();
                ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class('entry'); ?>>

                    <!-- En-tête article -->
                    <header class="entry-header">

                        <!-- Catégorie principale -->
                        <?php if (!empty($categories)) : ?>
                            <div class="entry-header__cats">
                                <?php foreach (array_slice($categories, 0, 2) as $cat) : ?>
                                    <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                                        class="badge badge--primary"
                                        rel="category">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Titre -->
                        <h1 class="entry-title"><?php the_title(); ?></h1>

                        <!-- Meta -->
                        <?php if ($show_meta) : ?>
                            <div class="entry-meta" aria-label="<?php esc_attr_e('Informations sur l\'article', 'eco-starter'); ?>">

                                <!-- Auteur -->
                                <span class="entry-meta__item entry-meta__author">
                                    <?php echo get_avatar(get_the_author_meta('ID'), 32, '', '', [
                                        'class'         => 'entry-meta__avatar',
                                        'loading'       => 'lazy',
                                        'extra_attr'    => 'aria-hidden="true"',
                                    ]); ?>
                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                        <?php the_author(); ?>
                                    </a>
                                </span>

                                <!-- Date -->
                                <span class="entry-meta__item entry-meta__date">
                                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                        <?php echo esc_html(get_the_date()); ?>
                                    </time>
                                    <?php if (get_the_modified_date('c') !== get_the_date('c')) : ?>
                                        <span class="entry-meta__updated">
                                            <?php
                                            printf(
                                                '<span class="sr-only">%s</span> <time datetime="%s">%s</time>',
                                                esc_html__('Mis à jour le', 'eco-starter'),
                                                esc_attr(get_the_modified_date('c')),
                                                esc_html(get_the_modified_date())
                                            );
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </span>

                                <!-- Temps de lecture -->
                                <?php if ($show_reading) : ?>
                                    <span class="entry-meta__item entry-meta__reading-time"
                                        aria-label="<?php esc_attr_e('Temps de lecture estimé', 'eco-starter'); ?>">
                                        <?php
                                        printf(
                                            esc_html(_n(
                                                '%d minute de lecture',
                                                '%d minutes de lecture',
                                                $reading_time,
                                                'eco-starter'
                                            )),
                                            $reading_time
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Commentaires -->
                                <?php if (comments_open() || get_comments_number()) : ?>
                                    <span class="entry-meta__item entry-meta__comments">
                                        <a href="#comments">
                                            <?php
                                            printf(
                                                esc_html(_n(
                                                    '%d commentaire',
                                                    '%d commentaires',
                                                    get_comments_number(),
                                                    'eco-starter'
                                                )),
                                                get_comments_number()
                                            );
                                            ?>
                                        </a>
                                    </span>
                                <?php endif; ?>

                            </div><!-- /.entry-meta -->
                        <?php endif; ?>

                    </header><!-- /.entry-header -->

                    <!-- Image mise en avant -->
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="entry-thumbnail">
                            <?php
                            the_post_thumbnail('eco-featured', [
                                'class'         => 'entry-thumbnail__img',
                                'loading'       => 'eager',
                                'fetchpriority' => 'high',
                                'decoding'      => 'sync',
                                'alt'           => trim(strip_tags(get_post_meta(
                                    get_post_thumbnail_id(),
                                    '_wp_attachment_image_alt',
                                    true
                                ))) ?: get_the_title(),
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Contenu éditorial -->
                    <div class="entry-content prose">
                        <?php
                        the_content(sprintf(
                            wp_kses(
                                __('Lire la suite<span class="sr-only"> de %s</span>', 'eco-starter'),
                                ['span' => ['class' => []]]
                            ),
                            get_the_title()
                        ));
                        ?>

                        <!-- Liens de pagination interne (<!--nextpage-->) -->
                        <?php
                        wp_link_pages([
                            'before'      => '<nav class="entry-pages" aria-label="'
                                . esc_attr__('Pages de l\'article', 'eco-starter') . '">'
                                . '<span class="entry-pages__label">'
                                . esc_html__('Pages :', 'eco-starter')
                                . '</span>',
                            'after'       => '</nav>',
                            'link_before' => '<span>',
                            'link_after'  => '</span>',
                        ]);
                        ?>
                    </div><!-- /.entry-content -->

                    <!-- Pied d'article -->
                    <footer class="entry-footer">

                        <!-- Tags -->
                        <?php if ($tags) : ?>
                            <div class="entry-tags"
                                aria-label="<?php esc_attr_e('Tags de l\'article', 'eco-starter'); ?>">
                                <span class="entry-tags__label">
                                    <?php esc_html_e('Tags :', 'eco-starter'); ?>
                                </span>
                                <?php foreach ($tags as $tag) : ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                        class="badge"
                                        rel="tag">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Partage (URLs natives — sans JS externe) -->
                        <div class="entry-share"
                            aria-label="<?php esc_attr_e('Partager cet article', 'eco-starter'); ?>">
                            <span class="entry-share__label">
                                <?php esc_html_e('Partager :', 'eco-starter'); ?>
                            </span>

                            <?php
                            $share_url   = rawurlencode(get_permalink());
                            $share_title = rawurlencode(get_the_title());

                            $share_links = [
                                'twitter'  => [
                                    'url'   => "https://twitter.com/intent/tweet?url={$share_url}&text={$share_title}",
                                    'label' => 'Twitter / X',
                                ],
                                'linkedin' => [
                                    'url'   => "https://www.linkedin.com/sharing/share-offsite/?url={$share_url}",
                                    'label' => 'LinkedIn',
                                ],
                                'facebook' => [
                                    'url'   => "https://www.facebook.com/sharer/sharer.php?u={$share_url}",
                                    'label' => 'Facebook',
                                ],
                            ];

                            foreach ($share_links as $network => $share) :
                            ?>
                                <a href="<?php echo esc_url($share['url']); ?>"
                                    class="entry-share__link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="<?php echo esc_attr(sprintf(
                                        __('Partager sur %s', 'eco-starter'),
                                        $share['label']
                                    )); ?>">
                                    <?php echo esc_html($share['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div><!-- /.entry-share -->

                        <!-- Biographie auteur -->
                        <?php
                        $author_bio = get_the_author_meta('description');
                        if ($author_bio) :
                        ?>
                            <div class="entry-author-bio">
                                <?php echo get_avatar(get_the_author_meta('ID'), 80, '', '', [
                                    'class'   => 'entry-author-bio__avatar',
                                    'loading' => 'lazy',
                                ]); ?>
                                <div class="entry-author-bio__content">
                                    <p class="entry-author-bio__name">
                                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                            <?php the_author(); ?>
                                        </a>
                                    </p>
                                    <p class="entry-author-bio__text">
                                        <?php echo esc_html($author_bio); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                    </footer><!-- /.entry-footer -->

                </article><!-- /.entry -->

                <!-- Navigation entre articles -->
                <nav class="post-navigation"
                    aria-label="<?php esc_attr_e('Navigation entre les articles', 'eco-starter'); ?>">
                    <?php
                    the_post_navigation([
                        'prev_text' => '<span class="nav-label">'
                            . esc_html__('Article précédent', 'eco-starter')
                            . '</span><span class="nav-title">%title</span>',
                        'next_text' => '<span class="nav-label">'
                            . esc_html__('Article suivant', 'eco-starter')
                            . '</span><span class="nav-title">%title</span>',
                    ]);
                    ?>
                </nav>

                <!-- Articles liés (même catégorie) -->
                <?php
                $related = new \WP_Query([
                    'post_type'      => 'post',
                    'posts_per_page' => 3,
                    'post__not_in'   => [get_the_ID()],
                    'orderby'        => 'rand',
                    'no_found_rows'  => true,
                    'tax_query'      => !empty($categories) ? [[
                        'taxonomy' => 'category',
                        'field'    => 'term_id',
                        'terms'    => wp_list_pluck($categories, 'term_id'),
                    ]] : [],
                ]);

                if ($related->have_posts()) :
                ?>
                    <section class="related-posts"
                        aria-label="<?php esc_attr_e('Articles liés', 'eco-starter'); ?>">
                        <h2 class="related-posts__title">
                            <?php esc_html_e('Articles liés', 'eco-starter'); ?>
                        </h2>
                        <div class="posts-grid posts-grid--3">
                            <?php
                            while ($related->have_posts()) {
                                $related->the_post();
                                eco_get_part('cards/post', ['layout' => 'grid']);
                            }
                            wp_reset_postdata();
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Commentaires -->
                <?php comments_template(); ?>

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