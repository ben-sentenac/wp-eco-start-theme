<?php
/**
 * En-tête du site — Eco Starter
 *
 * @package EcoStarter
 */
defined('ABSPATH') || exit;

$is_transparent = get_theme_mod('header_transparent_home', false) && is_front_page();
$is_sticky      = get_theme_mod('header_sticky', true);
$header_classes = eco_classes([
    'site-header',
    'site-header--transparent' => $is_transparent,
    'site-header--sticky'      => $is_sticky,
]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="site-header" class="<?php echo esc_attr($header_classes); ?>"
    role="banner"
    aria-label="<?php esc_attr_e('En-tête du site', 'eco-starter'); ?>">

    <div class="site-header__inner container">

        <!-- Logo / Nom du site -->
        <div class="site-header__brand">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/')); ?>"
                    class="site-header__site-name"
                    <?php echo is_front_page() ? 'aria-current="page"' : ''; ?>>
                    <?php bloginfo('name'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Navigation principale -->
        <?php if (has_nav_menu('primary')) : ?>
            <nav id="site-nav"
                class="site-nav"
                aria-label="<?php esc_attr_e('Navigation principale', 'eco-starter'); ?>">

                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-nav__list',
                    'item_class'     => 'site-nav__item',
                    'link_class'     => 'site-nav__link',
                    'walker'         => class_exists('Eco_Nav_Walker') ? new Eco_Nav_Walker() : null,
                    'fallback_cb'    => false,
                ]);
                ?>

            </nav>
        <?php endif; ?>

        <!-- Actions header (CTA + toggle mobile) -->
        <div class="site-header__actions">

            <!-- CTA optionnel (Customizer) -->
            <?php
            $cta = eco_get_header_cta();
            if ($cta) :
            ?>
                <div class="site-header__cta">
                    <?php echo $cta; // Échappé dans eco_get_header_cta() ?>
                </div>
            <?php endif; ?>

            <!-- Bouton menu mobile -->
            <button
                class="nav-toggle"
                type="button"
                aria-controls="site-nav"
                aria-expanded="false"
                aria-label="<?php esc_attr_e('Ouvrir le menu', 'eco-starter'); ?>">
                <span class="nav-toggle__bar" aria-hidden="true"></span>
                <span class="nav-toggle__bar" aria-hidden="true"></span>
                <span class="nav-toggle__bar" aria-hidden="true"></span>
            </button>

        </div>

    </div><!-- /.site-header__inner -->

</header>

<!-- Overlay menu mobile -->
<div class="nav-overlay" aria-hidden="true"></div>