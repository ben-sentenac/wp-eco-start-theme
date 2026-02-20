<?php
/**
 * Pied de page — Eco Starter
 *
 * @package EcoStarter
 */
defined('ABSPATH') || exit;

$footer_cols   = get_theme_mod('footer_columns', '3');
$show_logo     = get_theme_mod('footer_show_logo', true);
$has_widgets   = false;

// Vérifie si au moins une sidebar footer a des widgets
for ($i = 1; $i <= (int) $footer_cols; $i++) {
    if (is_active_sidebar('footer-col-' . $i)) {
        $has_widgets = true;
        break;
    }
}

// Réseaux sociaux
$social_networks = [
    'social_facebook'  => ['label' => 'Facebook',   'icon' => 'facebook'],
    'social_twitter'   => ['label' => 'Twitter / X', 'icon' => 'twitter'],
    'social_instagram' => ['label' => 'Instagram',   'icon' => 'instagram'],
    'social_linkedin'  => ['label' => 'LinkedIn',    'icon' => 'linkedin'],
    'social_youtube'   => ['label' => 'YouTube',     'icon' => 'youtube'],
    'social_github'    => ['label' => 'GitHub',      'icon' => 'github'],
    'social_tiktok'    => ['label' => 'TikTok',      'icon' => 'tiktok'],
];
?>

<footer id="site-footer" class="site-footer" role="contentinfo"
    aria-label="<?php esc_attr_e('Pied de page', 'eco-starter'); ?>">

    <?php if ($has_widgets) : ?>
    <!-- Zone widgets footer -->
    <div class="site-footer__main">
        <div class="container">
            <div class="site-footer__widgets"
                style="--footer-cols: <?php echo esc_attr($footer_cols); ?>;">

                <?php for ($i = 1; $i <= (int) $footer_cols; $i++) : ?>
                    <?php if (is_active_sidebar('footer-col-' . $i)) : ?>
                        <div class="site-footer__col<?php echo $i === 1 ? ' site-footer__col--wide' : ''; ?>">

                            <?php if ($i === 1 && $show_logo) : ?>
                                <!-- Logo dans la première colonne -->
                                <div class="site-footer__logo">
                                    <?php if (has_custom_logo()) : ?>
                                        <?php the_custom_logo(); ?>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(home_url('/')); ?>"
                                            class="site-footer__site-name">
                                            <?php bloginfo('name'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    $tagline = get_bloginfo('description');
                                    if ($tagline) :
                                    ?>
                                        <p class="site-footer__tagline">
                                            <?php echo esc_html($tagline); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php dynamic_sidebar('footer-col-' . $i); ?>

                        </div>
                    <?php endif; ?>
                <?php endfor; ?>

            </div><!-- /.site-footer__widgets -->
        </div>
    </div><!-- /.site-footer__main -->
    <?php endif; ?>

    <!-- Barre inférieure -->
    <div class="site-footer__bottom">
        <div class="container">
            <div class="site-footer__bottom-inner">

                <!-- Copyright -->
                <p class="site-footer__copyright">
                    <?php echo eco_get_footer_copyright(); // Échappé dans la fonction ?>
                </p>

                <!-- Réseaux sociaux -->
                <?php
                $active_networks = array_filter(
                    $social_networks,
                    fn($key) => (bool) get_theme_mod($key, ''),
                    ARRAY_FILTER_USE_KEY
                );

                if (!empty($active_networks)) :
                ?>
                <nav class="social-links"
                    aria-label="<?php esc_attr_e('Réseaux sociaux', 'eco-starter'); ?>">
                    <?php foreach ($active_networks as $mod => $network) : ?>
                        <?php $url = get_theme_mod($mod, ''); ?>
                        <a href="<?php echo esc_url($url); ?>"
                            class="social-links__item"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="<?php echo esc_attr($network['label']); ?>">
                            <?php
                            if (file_exists(ECO_STARTER_DIR . '/assets/svg/' . $network['icon'] . '.svg')) {
                                echo eco_svg($network['icon'], [
                                    'class'       => 'icon icon--sm',
                                    'aria-hidden' => 'true',
                                    'focusable'   => 'false',
                                ]);
                            } else {
                                echo esc_html($network['label']);
                            }
                            ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <!-- Menu légal (optionnel) -->
                <?php if (has_nav_menu('footer')) : ?>
                    <nav class="site-footer__legal"
                        aria-label="<?php esc_attr_e('Mentions légales', 'eco-starter'); ?>">
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'footer',
                            'container'      => false,
                            'menu_class'     => 'site-footer__legal-list',
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        ]);
                        ?>
                    </nav>
                <?php endif; ?>

            </div><!-- /.site-footer__bottom-inner -->
        </div>
    </div><!-- /.site-footer__bottom -->

</footer>

<?php wp_footer(); ?>
</body>
</html>