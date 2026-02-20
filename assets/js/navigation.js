/**
 * Navigation — Eco Starter
 *
 * - Menu mobile (toggle, drawer, overlay)
 * - Sous-menus accessibles (clavier + souris)
 * - Header scroll (classe is-scrolled)
 * - Focus trap dans le menu mobile
 * - Live regions helpers
 * - Fermeture menu sur Escape
 *
 * Vanilla JS ES2020+ — zéro dépendance
 *
 * @package EcoStarter
 */

'use strict';

// =============================================================================
// LIVE REGIONS — API publique utilisable par les autres modules
// =============================================================================

/**
 * Annonce un message via les live regions ARIA
 *
 * @param {string}  message    Texte à annoncer
 * @param {'polite'|'assertive'} priority  Urgence de l'annonce
 */
export function announce(message, priority = 'polite') {
    const regionId = priority === 'assertive' ? 'eco-live-assertive' : 'eco-live-polite';
    const region   = document.getElementById(regionId);

    if (!region) return;

    // Reset puis mise à jour — nécessaire pour que les SR re-lisent même le même texte
    region.textContent = '';

    // Délai minimal pour que le DOM soit mis à jour avant l'annonce
    requestAnimationFrame(() => {
        region.textContent = message;
    });
}


// =============================================================================
// HEADER — Classe is-scrolled au scroll
// =============================================================================

function initScrollHeader() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    const SCROLL_THRESHOLD = 40; // px avant activation

    function updateHeader() {
        const scrolled = window.scrollY > SCROLL_THRESHOLD;
        header.classList.toggle('is-scrolled', scrolled);
    }

    // Vérification initiale (page chargée en milieu de scroll)
    updateHeader();

    // Scroll optimisé via requestAnimationFrame
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                updateHeader();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}


// =============================================================================
// MENU MOBILE
// =============================================================================

function initMobileMenu() {
    const toggle  = document.querySelector('.nav-toggle');
    const nav     = document.querySelector('.site-nav');
    const overlay = document.querySelector('.nav-overlay');

    if (!toggle || !nav) return;

    let isOpen = false;

    /**
     * Ouvre le menu mobile
     */
    function openMenu() {
        isOpen = true;
        document.body.classList.add('nav-is-open');
        toggle.setAttribute('aria-expanded', 'true');
        nav.removeAttribute('inert');

        // Focus sur le premier lien du menu
        const firstLink = nav.querySelector('a, button');
        if (firstLink) {
            // Délai pour laisser la transition CSS se faire
            setTimeout(() => firstLink.focus(), 50);
        }

        announce(ecoStarter?.i18n?.menuOpen ?? 'Menu ouvert');
    }

    /**
     * Ferme le menu mobile
     * @param {boolean} restoreFocus  Remettre le focus sur le toggle
     */
    function closeMenu(restoreFocus = true) {
        isOpen = false;
        document.body.classList.remove('nav-is-open');
        toggle.setAttribute('aria-expanded', 'false');
        nav.setAttribute('inert', '');

        if (restoreFocus) toggle.focus();

        announce(ecoStarter?.i18n?.menuClose ?? 'Menu fermé');
    }

    // Init : menu fermé au chargement
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', 'site-nav');
    nav.setAttribute('inert', '');

    // Toggle au clic
    toggle.addEventListener('click', () => {
        isOpen ? closeMenu() : openMenu();
    });

    // Fermeture sur l'overlay
    overlay?.addEventListener('click', () => closeMenu());

    // Fermeture sur Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
        }
    });

    // Fermeture si resize vers desktop (évite menu bloqué après rotation)
    const mediaQuery = window.matchMedia('(min-width: 1024px)');
    mediaQuery.addEventListener('change', (e) => {
        if (e.matches && isOpen) {
            closeMenu(false);
            nav.removeAttribute('inert'); // Sur desktop, inert ne doit pas rester
        }
    });

    // Focus trap dans le menu ouvert
    nav.addEventListener('keydown', (e) => {
        if (!isOpen || e.key !== 'Tab') return;

        const focusables = nav.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusables[0];
        const last  = focusables[focusables.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
}


// =============================================================================
// SOUS-MENUS — accessibles clavier + souris
// =============================================================================

function initSubMenus() {
    const navItems = document.querySelectorAll('.site-nav__item.menu-item-has-children');

    if (!navItems.length) return;

    navItems.forEach(item => {
        const link    = item.querySelector(':scope > .site-nav__link, :scope > a');
        const submenu = item.querySelector(':scope > .sub-menu');

        if (!link || !submenu) return;

        // ARIA sur le lien parent
        const submenuId = 'submenu-' + Math.random().toString(36).slice(2, 8);
        submenu.id = submenuId;
        link.setAttribute('aria-haspopup', 'true');
        link.setAttribute('aria-expanded', 'false');
        link.setAttribute('aria-controls', submenuId);

        /**
         * Ouvre le sous-menu
         */
        function openSubmenu() {
            // Ferme les autres sous-menus du même niveau
            const siblings = item.parentElement?.querySelectorAll(':scope > .menu-item-has-children.is-open');
            siblings?.forEach(sibling => {
                if (sibling !== item) closeSubmenu(sibling);
            });

            item.classList.add('is-open');
            link.setAttribute('aria-expanded', 'true');
        }

        /**
         * Ferme le sous-menu
         * @param {Element} targetItem
         */
        function closeSubmenu(targetItem = item) {
            targetItem.classList.remove('is-open');
            const targetLink = targetItem.querySelector(':scope > .site-nav__link, :scope > a');
            targetLink?.setAttribute('aria-expanded', 'false');

            // Ferme récursivement les sous-sous-menus
            targetItem.querySelectorAll('.is-open').forEach(child => {
                child.classList.remove('is-open');
                child.querySelector('[aria-expanded]')?.setAttribute('aria-expanded', 'false');
            });
        }

        // --- Hover (desktop) ---
        let hoverTimer;

        item.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimer);
            // Vérifie qu'on est bien sur desktop
            if (window.innerWidth >= 1024) openSubmenu();
        });

        item.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 1024) {
                // Délai avant fermeture — évite la fermeture accidentelle
                hoverTimer = setTimeout(() => closeSubmenu(), 150);
            }
        });

        // --- Clic (mobile + desktop) ---
        link.addEventListener('click', (e) => {
            // Sur desktop : ne bloque le lien que si le sous-menu est fermé
            if (window.innerWidth >= 1024) {
                if (!item.classList.contains('is-open')) {
                    e.preventDefault();
                    openSubmenu();
                }
                // Si déjà ouvert → navigation normale vers le lien
                return;
            }

            // Sur mobile : toggle accordéon
            e.preventDefault();
            item.classList.contains('is-open') ? closeSubmenu() : openSubmenu();
        });

        // --- Clavier ---
        link.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'Enter':
                case ' ':
                    // Espace ou Entrée sur le lien parent → ouvre le sous-menu
                    if (!item.classList.contains('is-open')) {
                        e.preventDefault();
                        openSubmenu();
                        // Focus sur le premier item du sous-menu
                        submenu.querySelector('a')?.focus();
                    }
                    break;

                case 'Escape':
                    closeSubmenu();
                    link.focus();
                    break;

                case 'ArrowDown':
                    e.preventDefault();
                    openSubmenu();
                    submenu.querySelector('a')?.focus();
                    break;
            }
        });

        // Navigation clavier à l'intérieur du sous-menu
        submenu.addEventListener('keydown', (e) => {
            const links    = [...submenu.querySelectorAll('a')];
            const current  = document.activeElement;
            const index    = links.indexOf(current);

            switch (e.key) {
                case 'Escape':
                    closeSubmenu();
                    link.focus();
                    break;

                case 'ArrowDown':
                    e.preventDefault();
                    links[index + 1]?.focus();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if (index === 0) {
                        closeSubmenu();
                        link.focus();
                    } else {
                        links[index - 1]?.focus();
                    }
                    break;

                case 'Home':
                    e.preventDefault();
                    links[0]?.focus();
                    break;

                case 'End':
                    e.preventDefault();
                    links[links.length - 1]?.focus();
                    break;
            }
        });
    });

    // Ferme tous les sous-menus au clic en dehors
    document.addEventListener('click', (e) => {
        navItems.forEach(item => {
            if (!item.contains(e.target)) {
                item.classList.remove('is-open');
                item.querySelector('[aria-expanded]')?.setAttribute('aria-expanded', 'false');
            }
        });
    });
}


// =============================================================================
// ACCORDÉON — <details> / <summary> natif
// Améliore l'expérience clavier et annonce l'état
// =============================================================================

function initAccordions() {
    const accordions = document.querySelectorAll('.accordion__item');

    if (!accordions.length) return;

    accordions.forEach(item => {
        const summary = item.querySelector('.accordion__trigger');
        if (!summary) return;

        item.addEventListener('toggle', () => {
            const isOpen = item.hasAttribute('open');

            // Annonce l'état aux SR
            announce(
                isOpen
                    ? (summary.textContent.trim() + ' — ' + (ecoStarter?.i18n?.expanded ?? 'développé'))
                    : (summary.textContent.trim() + ' — ' + (ecoStarter?.i18n?.collapsed ?? 'réduit')),
                'polite'
            );
        });
    });
}


// =============================================================================
// MODALS — <dialog> natif
// Accessibilité : focus trap, Escape, aria-labelledby
// =============================================================================

function initModals() {
    // Boutons d'ouverture : data-modal-target="#modal-id"
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        const targetId = trigger.dataset.modalTarget;
        const modal    = document.querySelector(targetId);

        if (!modal || modal.tagName !== 'DIALOG') return;

        // Associe aria-controls
        trigger.setAttribute('aria-controls', targetId.replace('#', ''));
        trigger.setAttribute('aria-haspopup', 'dialog');

        trigger.addEventListener('click', () => {
            modal.showModal();

            // Focus sur le premier élément focusable dans la modal
            const firstFocusable = modal.querySelector(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            firstFocusable?.focus();

            announce(
                modal.querySelector('[data-modal-title]')?.textContent?.trim()
                    ?? (ecoStarter?.i18n?.modalOpened ?? 'Dialogue ouvert'),
                'assertive'
            );
        });

        // Bouton de fermeture interne
        modal.querySelectorAll('[data-modal-close], .modal__close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                modal.close();
                trigger.focus(); // Retour du focus sur le déclencheur
            });
        });

        // Fermeture sur clic en dehors (backdrop)
        modal.addEventListener('click', (e) => {
            const rect = modal.getBoundingClientRect();
            const clickedOutside =
                e.clientX < rect.left || e.clientX > rect.right ||
                e.clientY < rect.top  || e.clientY > rect.bottom;

            if (clickedOutside) {
                modal.close();
                trigger.focus();
            }
        });

        // Focus trap dans la modal
        modal.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            const focusables = modal.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );

            const first = focusables[0];
            const last  = focusables[focusables.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });
    });
}


// =============================================================================
// SMOOTH SCROLL — ancres internes accessibles
// =============================================================================

function initSmoothScroll() {
    // Respecte prefers-reduced-motion
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;

        link.addEventListener('click', (e) => {
            const target = document.querySelector(href);
            if (!target) return;

            e.preventDefault();

            // Scroll
            target.scrollIntoView({
                behavior: prefersReduced ? 'auto' : 'smooth',
                block: 'start',
            });

            // Focus sur la cible (important pour les skip links)
            const targetTabindex = target.getAttribute('tabindex');
            if (!targetTabindex) {
                target.setAttribute('tabindex', '-1');
            }
            target.focus({ preventScroll: true });

            // Met à jour l'URL sans recharger la page
            history.pushState(null, '', href);
        });
    });
}


// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initScrollHeader();
    initMobileMenu();
    initSubMenus();
    initAccordions();
    initModals();
    initSmoothScroll();
});