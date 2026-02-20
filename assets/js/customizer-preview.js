/**
 * Customizer Preview — Eco Starter
 * Mises à jour temps réel sans rechargement de page
 *
 * @package EcoStarter
 */

'use strict';

(function(wp) {

    // Couleurs → CSS custom properties en temps réel
    const colorBindings = {
        'color_primary':    '--color-primary',
        'color_secondary':  '--color-secondary',
        'color_foreground': '--color-foreground',
        'color_background': '--color-background',
        'color_surface':    '--color-surface',
        'color_border':     '--color-border',
    };

    Object.entries(colorBindings).forEach(([setting, cssVar]) => {
        wp.customize(setting, value => {
            value.bind(newValue => {
                document.documentElement.style.setProperty(cssVar, newValue);
            });
        });
    });

    // Typographie
    wp.customize('font_base_size', value => {
        value.bind(newValue => {
            document.body.style.fontSize = parseInt(newValue, 10) + 'px';
        });
    });

    wp.customize('font_family_custom', value => {
        value.bind(newValue => {
            if (newValue) {
                document.documentElement.style.setProperty('--font-sans', newValue);
            }
        });
    });

    wp.customize('heading_weight', value => {
        value.bind(newValue => {
            document.documentElement.style.setProperty('--font-heading', newValue);
            document.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(h => {
                h.style.fontWeight = newValue;
            });
        });
    });

    // Largeur de contenu
    wp.customize('content_max_width', value => {
        value.bind(newValue => {
            document.documentElement.style.setProperty('--content-width', parseInt(newValue, 10) + 'px');
        });
    });

    // CTA Header
    wp.customize('header_cta_text', value => {
        value.bind(newValue => {
            const cta = document.querySelector('.site-header__cta .btn__label');
            if (cta) cta.textContent = newValue;
        });
    });

    wp.customize('header_cta_url', value => {
        value.bind(newValue => {
            const cta = document.querySelector('.site-header__cta');
            if (cta) cta.href = newValue;
        });
    });

    // Copyright footer
    wp.customize('footer_copyright', value => {
        value.bind(newValue => {
            const copyright = document.querySelector('.site-footer__copyright');
            if (copyright) copyright.innerHTML = newValue.replace('{year}', new Date().getFullYear());
        });
    });

    // Maintenance
    wp.customize('maintenance_title', value => {
        value.bind(newValue => {
            const el = document.querySelector('.maintenance__title');
            if (el) el.textContent = newValue;
        });
    });

    wp.customize('maintenance_message', value => {
        value.bind(newValue => {
            const el = document.querySelector('.maintenance__message');
            if (el) el.innerHTML = newValue;
        });
    });

})(wp);