/**
 * Formulaires — Eco Starter
 *
 * - Validation en temps réel (blur + submit)
 * - Soumission AJAX
 * - Compteur de caractères
 * - États du bouton (loading, success, error)
 * - Annonces live regions accessibles
 * - Redirect post-envoi si configuré
 *
 * Vanilla JS ES2020+ — zéro dépendance
 *
 * @package EcoStarter
 */

'use strict';

import { announce } from './navigation.js';


// =============================================================================
// CONFIGURATION VALIDATION CÔTÉ CLIENT
// Miroir de la validation PHP — double sécurité
// =============================================================================

const VALIDATORS = {

    /**
     * @param {string} value
     * @returns {string} Message d'erreur ou chaîne vide
     */
    name(value) {
        if (!value.trim()) return window.ecoStarter?.i18n?.fieldRequired ?? 'Ce champ est requis.';
        if (value.trim().length < 2) return 'Le nom doit contenir au moins 2 caractères.';
        if (value.trim().length > 100) return 'Le nom ne peut pas dépasser 100 caractères.';
        if (/https?:\/\//i.test(value)) return 'Le nom ne peut pas contenir d\'URL.';
        return '';
    },

    email(value) {
        if (!value.trim()) return 'L\'adresse email est requise.';
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        if (!emailRegex.test(value.trim())) return 'L\'adresse email n\'est pas valide.';
        return '';
    },

    phone(value) {
        if (!value.trim()) return ''; // Champ optionnel
        const cleaned = value.replace(/[\s\-\.\(\)]/g, '');
        if (!/^\+?[0-9]{7,15}$/.test(cleaned)) return 'Le numéro de téléphone n\'est pas valide.';
        return '';
    },

    message(value, { maxLen = 5000 } = {}) {
        if (!value.trim()) return 'Le message est requis.';
        if (value.trim().length < 10) return 'Le message doit contenir au moins 10 caractères.';
        if (value.trim().length > maxLen) return `Le message ne peut pas dépasser ${maxLen} caractères.`;
        return '';
    },

    gdpr(value, { checked = false } = {}) {
        if (!checked) return 'Vous devez accepter la politique de confidentialité.';
        return '';
    },

    subject(value, { required = false } = {}) {
        if (required && !value) return 'Veuillez choisir un sujet.';
        return '';
    },
};


// =============================================================================
// CLASSE FORM MANAGER
// Une instance par formulaire sur la page
// =============================================================================

class EcoForm {

    /**
     * @param {HTMLFormElement} form
     */
    constructor(form) {
        this.form       = form;
        this.formId     = form.id;
        this.submitBtn  = form.querySelector('[type="submit"]');
        this.notice     = form.querySelector('.form-notice');
        this.isSubmitting = false;

        this.init();
    }

    init() {
        // Compteur de caractères sur le textarea
        this.initCharCounter();

        // Validation en temps réel (au blur sur chaque champ)
        this.initRealtimeValidation();

        // Handler de soumission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }


    // -------------------------------------------------------------------------
    // COMPTEUR DE CARACTÈRES
    // -------------------------------------------------------------------------

    initCharCounter() {
        const textarea = this.form.querySelector('textarea[name="message"]');
        const counter  = this.form.querySelector(`#${this.formId}-message-count`);

        if (!textarea || !counter) return;

        const maxLen = parseInt(textarea.getAttribute('maxlength') ?? '5000', 10);

        const update = () => {
            const len       = textarea.value.length;
            const remaining = maxLen - len;
            counter.textContent = `${len} / ${maxLen}`;

            // Alerte visuelle quand on approche de la limite
            if (remaining < 100) {
                counter.style.color = 'var(--color-error)';
                counter.setAttribute('aria-label',
                    `${remaining} caractère${remaining > 1 ? 's' : ''} restant${remaining > 1 ? 's' : ''}`
                );
            } else if (remaining < 200) {
                counter.style.color = 'var(--color-warning)';
            } else {
                counter.style.color = '';
                counter.setAttribute('aria-label', 'Nombre de caractères');
            }
        };

        textarea.addEventListener('input', update);
        update(); // Init
    }


    // -------------------------------------------------------------------------
    // VALIDATION EN TEMPS RÉEL (au blur)
    // -------------------------------------------------------------------------

    initRealtimeValidation() {
        const fields = this.form.querySelectorAll('input, select, textarea');

        fields.forEach(field => {
            // Validation au blur (quand l'utilisateur quitte le champ)
            field.addEventListener('blur', () => {
                if (field.name && field.name !== 'website') {
                    this.validateField(field);
                }
            });

            // Efface l'erreur dès que l'utilisateur recommence à taper
            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) {
                    this.clearFieldError(field);
                }
            });

            // Checkbox : validation immédiate au change
            if (field.type === 'checkbox') {
                field.addEventListener('change', () => this.validateField(field));
            }
        });
    }


    // -------------------------------------------------------------------------
    // VALIDATION D'UN CHAMP
    // -------------------------------------------------------------------------

    /**
     * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
     * @returns {string} Message d'erreur ou chaîne vide
     */
    validateField(field) {
        const name      = field.name;
        const value     = field.type === 'checkbox' ? '' : field.value;
        const isChecked = field.type === 'checkbox' ? field.checked : false;
        const maxLen    = parseInt(field.getAttribute('maxlength') ?? '5000', 10);
        const required  = field.hasAttribute('required');

        const validator = VALIDATORS[name];
        if (!validator) return '';

        const error = validator(value, {
            checked: isChecked,
            maxLen,
            required,
        });

        if (error) {
            this.setFieldError(field, error);
        } else {
            this.clearFieldError(field);
        }

        return error;
    }


    // -------------------------------------------------------------------------
    // GESTION DES ERREURS PAR CHAMP
    // -------------------------------------------------------------------------

    /**
     * @param {Element} field
     * @param {string}  message
     */
    setFieldError(field, message) {
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        const errorEl = this.form.querySelector(`#${this.formId}-${field.name}-error`);
        if (errorEl) {
            errorEl.textContent = message;
        }
    }

    /**
     * @param {Element} field
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');

        const errorEl = this.form.querySelector(`#${this.formId}-${field.name}-error`);
        if (errorEl) {
            errorEl.textContent = '';
        }
    }

    /**
     * Efface toutes les erreurs du formulaire
     */
    clearAllErrors() {
        this.form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
        });

        this.form.querySelectorAll('.field__error').forEach(el => {
            el.textContent = '';
        });

        this.hideNotice();
    }


    // -------------------------------------------------------------------------
    // NOTICE GLOBALE
    // -------------------------------------------------------------------------

    /**
     * @param {string} message
     * @param {'success'|'error'} type
     */
    showNotice(message, type = 'success') {
        if (!this.notice) return;

        this.notice.textContent  = message;
        this.notice.className    = `form-notice form-notice--${type} is-visible`;
        this.notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Annonce aux lecteurs d'écran
        announce(message, type === 'error' ? 'assertive' : 'polite');
    }

    hideNotice() {
        if (!this.notice) return;
        this.notice.className = 'form-notice';
        this.notice.textContent = '';
    }


    // -------------------------------------------------------------------------
    // ÉTAT DU BOUTON
    // -------------------------------------------------------------------------

    setButtonLoading(loading) {
        if (!this.submitBtn) return;

        if (loading) {
            this.submitBtn.disabled = true;
            this.submitBtn.classList.add('is-loading');
            this.submitBtn.setAttribute('aria-busy', 'true');

            // Sauvegarde le texte original
            const label = this.submitBtn.querySelector('.btn__label');
            if (label) {
                this.submitBtn.dataset.originalText = label.textContent;
                const loadingText = this.submitBtn.dataset.loadingText
                    ?? window.ecoStarter?.i18n?.formSending
                    ?? 'Envoi en cours…';
                label.textContent = loadingText;
            }
        } else {
            this.submitBtn.disabled = false;
            this.submitBtn.classList.remove('is-loading');
            this.submitBtn.removeAttribute('aria-busy');

            const label = this.submitBtn.querySelector('.btn__label');
            if (label && this.submitBtn.dataset.originalText) {
                label.textContent = this.submitBtn.dataset.originalText;
            }
        }
    }

    setButtonSuccess() {
        if (!this.submitBtn) return;

        this.submitBtn.disabled = true;
        this.submitBtn.classList.remove('is-loading');
        this.submitBtn.classList.add('btn--secondary');
        this.submitBtn.classList.remove('btn--primary');

        const label = this.submitBtn.querySelector('.btn__label');
        if (label) {
            label.textContent = '✓ ' + (window.ecoStarter?.i18n?.formSuccess ?? 'Message envoyé');
        }
    }


    // -------------------------------------------------------------------------
    // VALIDATION COMPLÈTE DU FORMULAIRE
    // -------------------------------------------------------------------------

    /**
     * Valide tous les champs et retourne true si valide
     * @returns {boolean}
     */
    validateAll() {
        const fields  = this.form.querySelectorAll('input, select, textarea');
        let hasErrors = false;
        let firstError = null;

        fields.forEach(field => {
            if (!field.name || field.name === 'website') return;

            const error = this.validateField(field);
            if (error && !hasErrors) {
                hasErrors  = true;
                firstError = field;
            }
        });

        // Focus sur le premier champ en erreur
        if (firstError) {
            firstError.focus();
        }

        return !hasErrors;
    }


    // -------------------------------------------------------------------------
    // SOUMISSION AJAX
    // -------------------------------------------------------------------------

    /**
     * @param {SubmitEvent} e
     */
    async handleSubmit(e) {
        e.preventDefault();

        if (this.isSubmitting) return;

        // Efface les erreurs précédentes
        this.clearAllErrors();

        // Validation client
        if (!this.validateAll()) {
            this.showNotice(
                window.ecoStarter?.i18n?.formValidationError ?? 'Veuillez corriger les erreurs ci-dessous.',
                'error'
            );
            return;
        }

        // Démarre le chargement
        this.isSubmitting = true;
        this.setButtonLoading(true);

        try {
            const formData = new FormData(this.form);

            // Ajoute les infos nécessaires pour l'AJAX WordPress
            formData.append('action', window.ecoContactForm?.action ?? 'eco_contact_form');
            formData.append('nonce',  window.ecoContactForm?.nonce  ?? '');

            const response = await fetch(
                window.ecoContactForm?.ajaxUrl ?? window.ecoStarter?.ajaxUrl ?? '/wp-admin/admin-ajax.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.handleSuccess(data.data);
            } else {
                this.handleError(data.data);
            }

        } catch (error) {
            console.error('Eco Form error:', error);
            this.showNotice(
                window.ecoStarter?.i18n?.formError ?? 'Une erreur est survenue. Veuillez réessayer.',
                'error'
            );
            this.setButtonLoading(false);
        } finally {
            this.isSubmitting = false;
        }
    }


    // -------------------------------------------------------------------------
    // GESTION SUCCÈS
    // -------------------------------------------------------------------------

    /**
     * @param {{ message: string, redirect?: string, spam?: boolean }} data
     */
    handleSuccess(data) {
        // Succès silencieux pour les spambots
        if (data.spam) {
            this.handleSpamSuccess(data.message);
            return;
        }

        // Redirect si configuré
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        // Affichage du message de succès
        this.setButtonSuccess();
        this.showNotice(data.message, 'success');

        // Réinitialise le formulaire
        this.form.reset();

        // Remet le compteur à zéro
        const counter = this.form.querySelector(`#${this.formId}-message-count`);
        if (counter) {
            const maxLen = this.form.querySelector('textarea[name="message"]')
                ?.getAttribute('maxlength') ?? '5000';
            counter.textContent = `0 / ${maxLen}`;
            counter.style.color = '';
        }

        // Scroll vers la notice
        this.notice?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Succès simulé pour les spambots
     * @param {string} message
     */
    handleSpamSuccess(message) {
        this.setButtonSuccess();
        this.showNotice(message, 'success');
        this.form.reset();
    }


    // -------------------------------------------------------------------------
    // GESTION ERREUR
    // -------------------------------------------------------------------------

    /**
     * @param {{ message: string, code: string, fields?: Record<string, string> }} data
     */
    handleError(data) {
        this.setButtonLoading(false);

        // Erreurs de validation par champ
        if (data.code === 'validation' && data.fields) {
            Object.entries(data.fields).forEach(([fieldName, errorMessage]) => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    this.setFieldError(field, errorMessage);
                }
            });
        }

        this.showNotice(data.message, 'error');
    }
}


// =============================================================================
// INITIALISATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {

    // Initialise tous les formulaires avec data-form="contact"
    document.querySelectorAll('form[data-form="contact"]').forEach(form => {
        new EcoForm(form);
    });

});