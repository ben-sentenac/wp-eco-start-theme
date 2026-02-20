<?php
/**
 * Formulaires natifs — Eco Starter
 *
 * - Formulaire de contact (traitement PHP natif)
 * - Validation serveur complète
 * - Protection : nonce + honeypot + rate limiting
 * - Réponse JSON pour soumission AJAX
 * - Shortcode [eco_contact_form]
 * - Helpers de rendu de champs
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// CONFIGURATION
// Valeurs modifiables via filtres WordPress
// =============================================================================

/**
 * Retourne la configuration du formulaire de contact
 *
 * @return array<string, mixed>
 */
function eco_form_config(): array
{
    return apply_filters('eco_contact_form_config', [

        // Destinataire par défaut
        'recipient'       => get_theme_mod('contact_email', get_option('admin_email')),

        // Champs actifs
        'fields'          => ['name', 'email', 'phone', 'subject', 'message'],

        // Champs obligatoires
        'required'        => ['name', 'email', 'message'],

        // Longueur max du message
        'message_max_len' => 5000,

        // Rate limiting : X soumissions par IP par heure
        'rate_limit'      => 5,
        'rate_window'     => HOUR_IN_SECONDS,

        // Sujets prédéfinis (vide = champ texte libre)
        'subjects'        => [
            ''                  => __('— Choisir un sujet —', 'eco-starter'),
            'devis'             => __('Demande de devis', 'eco-starter'),
            'information'       => __('Demande d\'information', 'eco-starter'),
            'support'           => __('Support technique', 'eco-starter'),
            'partenariat'       => __('Partenariat', 'eco-starter'),
            'autre'             => __('Autre', 'eco-starter'),
        ],

        // Message de confirmation affiché après envoi
        'success_message' => __('Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.', 'eco-starter'),

        // Redirect après envoi (vide = pas de redirect, affichage du message)
        'redirect_url'    => get_theme_mod('contact_redirect_url', ''),
    ]);
}


// =============================================================================
// HANDLER AJAX
// Traite la soumission du formulaire en AJAX (connecté + non connecté)
// =============================================================================

add_action('wp_ajax_eco_contact_form',        'eco_handle_contact_form');
add_action('wp_ajax_nopriv_eco_contact_form', 'eco_handle_contact_form');

/**
 * Handler principal du formulaire de contact
 * Valide → sanitise → rate-limit → honeypot → envoie → répond JSON
 */
function eco_handle_contact_form(): void
{
    // 1. Vérification nonce
    eco_verify_ajax_nonce('eco_contact_form');

    $config = eco_form_config();

    // 2. Honeypot anti-spam
    // Le champ 'website' doit être vide — les bots le remplissent
    $honeypot = sanitize_text_field(wp_unslash($_POST['website'] ?? ''));
    if (!empty($honeypot)) {
        // Fausse réponse de succès pour ne pas alerter le bot
        wp_send_json_success([
            'message' => $config['success_message'],
            'spam'    => true,
        ]);
    }

    // 3. Rate limiting par IP
    $ip          = eco_get_client_ip();
    $rate_key    = 'eco_form_rate_' . md5($ip);
    $submissions = (int) get_transient($rate_key);

    if ($submissions >= $config['rate_limit']) {
        wp_send_json_error([
            'message' => __('Trop de soumissions. Veuillez patienter avant de réessayer.', 'eco-starter'),
            'code'    => 'rate_limit',
        ], 429);
    }

    // 4. Récupération et sanitisation des données
    $raw = [
        'name'    => sanitize_text_field(wp_unslash($_POST['name']    ?? '')),
        'email'   => sanitize_email(wp_unslash($_POST['email']        ?? '')),
        'phone'   => sanitize_text_field(wp_unslash($_POST['phone']   ?? '')),
        'subject' => sanitize_text_field(wp_unslash($_POST['subject'] ?? '')),
        'message' => sanitize_textarea_field(wp_unslash($_POST['message'] ?? '')),
    ];

    // 5. Validation serveur
    $errors = eco_validate_contact_form($raw, $config);

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => __('Veuillez corriger les erreurs ci-dessous.', 'eco-starter'),
            'code'    => 'validation',
            'fields'  => $errors,
        ], 422);
    }

    // 6. Envoi de l'email
    $sent = eco_send_contact_email($raw, $config);

    if (!$sent) {
        wp_send_json_error([
            'message' => __('Une erreur technique est survenue. Veuillez réessayer ou nous contacter directement.', 'eco-starter'),
            'code'    => 'mail_error',
        ], 500);
    }

    // 7. Incrément du compteur de soumissions
    set_transient($rate_key, $submissions + 1, $config['rate_window']);

    // 8. Réponse succès
    $response = ['message' => $config['success_message']];

    if (!empty($config['redirect_url'])) {
        $response['redirect'] = esc_url($config['redirect_url']);
    }

    // Hook post-envoi (analytics, CRM, etc.)
    do_action('eco_contact_form_sent', $raw, $config);

    wp_send_json_success($response);
}


// =============================================================================
// VALIDATION
// =============================================================================

/**
 * Valide les données du formulaire de contact
 *
 * @param array<string, string> $data   Données sanitisées
 * @param array<string, mixed>  $config Configuration du formulaire
 * @return array<string, string>        Tableau d'erreurs [champ => message]
 */
function eco_validate_contact_form(array $data, array $config): array
{
    $errors   = [];
    $required = $config['required'];

    // --- Champs obligatoires ---
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = match ($field) {
                'name'    => __('Votre nom est requis.', 'eco-starter'),
                'email'   => __('Votre adresse email est requise.', 'eco-starter'),
                'message' => __('Votre message est requis.', 'eco-starter'),
                default   => __('Ce champ est requis.', 'eco-starter'),
            };
        }
    }

    // --- Email valide ---
    if (!empty($data['email']) && !is_email($data['email'])) {
        $errors['email'] = __('L\'adresse email n\'est pas valide.', 'eco-starter');
    }

    // --- Nom : longueur ---
    if (!empty($data['name']) && mb_strlen($data['name']) < 2) {
        $errors['name'] = __('Le nom doit contenir au moins 2 caractères.', 'eco-starter');
    }

    if (!empty($data['name']) && mb_strlen($data['name']) > 100) {
        $errors['name'] = __('Le nom ne peut pas dépasser 100 caractères.', 'eco-starter');
    }

    // --- Téléphone : format souple ---
    if (!empty($data['phone'])) {
        $phone_clean = preg_replace('/[\s\-\.\(\)]/', '', $data['phone']);
        if (!preg_match('/^\+?[0-9]{7,15}$/', $phone_clean)) {
            $errors['phone'] = __('Le numéro de téléphone n\'est pas valide.', 'eco-starter');
        }
    }

    // --- Message : longueur min/max ---
    if (!empty($data['message'])) {
        if (mb_strlen($data['message']) < 10) {
            $errors['message'] = __('Le message doit contenir au moins 10 caractères.', 'eco-starter');
        }

        if (mb_strlen($data['message']) > $config['message_max_len']) {
            $errors['message'] = sprintf(
                __('Le message ne peut pas dépasser %d caractères.', 'eco-starter'),
                $config['message_max_len']
            );
        }
    }

    // --- Sujet : valeur autorisée ---
    if (!empty($data['subject']) && !empty($config['subjects'])) {
        $allowed_subjects = array_keys($config['subjects']);
        if (!in_array($data['subject'], $allowed_subjects, true)) {
            $errors['subject'] = __('Sujet non valide.', 'eco-starter');
        }
    }

    // --- Détection spam basique (URLs dans le nom) ---
    if (!empty($data['name']) && preg_match('/https?:\/\//i', $data['name'])) {
        $errors['name'] = __('Le nom ne peut pas contenir d\'URL.', 'eco-starter');
    }

    return apply_filters('eco_contact_form_validate', $errors, $data, $config);
}


// =============================================================================
// ENVOI EMAIL
// =============================================================================

/**
 * Envoie l'email de contact via wp_mail
 *
 * @param array<string, string> $data   Données validées et sanitisées
 * @param array<string, mixed>  $config Configuration
 * @return bool                         true si envoi réussi
 */
function eco_send_contact_email(array $data, array $config): bool
{
    $to      = $config['recipient'];
    $subject = sprintf(
        '[%s] %s',
        get_bloginfo('name'),
        !empty($data['subject']) ? $data['subject'] : __('Nouveau message de contact', 'eco-starter')
    );

    // Construit le corps de l'email en HTML
    $body = eco_render_contact_email_template($data, $config);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        // Reply-To = email du visiteur pour répondre directement
        'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);

    // Email de confirmation au visiteur
    if ($sent && get_theme_mod('contact_send_confirmation', true)) {
        eco_send_confirmation_email($data, $config);
    }

    return $sent;
}


/**
 * Email de confirmation automatique au visiteur
 *
 * @param array<string, string> $data
 * @param array<string, mixed>  $config
 */
function eco_send_confirmation_email(array $data, array $config): void
{
    $subject = sprintf(
        __('Confirmation de réception — %s', 'eco-starter'),
        get_bloginfo('name')
    );

    $body = eco_render_confirmation_email_template($data, $config);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
    ];

    wp_mail($data['email'], $subject, $body, $headers);
}


// =============================================================================
// TEMPLATES EMAIL
// HTML minimal, compatible tous clients mail
// =============================================================================

/**
 * Template email reçu par l'admin
 *
 * @param array<string, string> $data
 * @param array<string, mixed>  $config
 * @return string HTML de l'email
 */
function eco_render_contact_email_template(array $data, array $config): string
{
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');
    $date      = wp_date(get_option('date_format') . ' ' . get_option('time_format'));
    $ip        = eco_get_client_ip();

    // Labels des champs
    $labels = [
        'name'    => __('Nom', 'eco-starter'),
        'email'   => __('Email', 'eco-starter'),
        'phone'   => __('Téléphone', 'eco-starter'),
        'subject' => __('Sujet', 'eco-starter'),
        'message' => __('Message', 'eco-starter'),
    ];

    // Construction des lignes du tableau
    $rows = '';
    foreach ($config['fields'] as $field) {
        if (empty($data[$field])) continue;

        $value = $field === 'message'
            ? nl2br(esc_html($data[$field]))
            : esc_html($data[$field]);

        if ($field === 'email') {
            $value = '<a href="mailto:' . esc_attr($data[$field]) . '" style="color:#2563eb;">'
                . esc_html($data[$field]) . '</a>';
        }

        $rows .= sprintf(
            '<tr>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;font-weight:600;
                    color:#374151;white-space:nowrap;vertical-align:top;width:140px;">%s</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;
                    color:#1f2937;word-break:break-word;">%s</td>
            </tr>',
            esc_html($labels[$field] ?? $field),
            $value
        );
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo esc_attr(get_locale()); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($site_name); ?></title>
    </head>
    <body style="margin:0;padding:0;background-color:#f3f4f6;font-family:system-ui,-apple-system,sans-serif;">

        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 16px;">
            <tr>
                <td align="center">

                    <!-- Wrapper -->
                    <table width="600" cellpadding="0" cellspacing="0"
                        style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;
                        overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);">

                        <!-- Header -->
                        <tr>
                            <td style="background-color:#2563eb;padding:28px 32px;">
                                <a href="<?php echo esc_url($site_url); ?>"
                                    style="color:#ffffff;font-size:20px;font-weight:700;
                                    text-decoration:none;">
                                    <?php echo esc_html($site_name); ?>
                                </a>
                                <p style="color:rgba(255,255,255,0.8);margin:6px 0 0;font-size:14px;">
                                    <?php esc_html_e('Nouveau message de contact', 'eco-starter'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding:32px;">

                                <p style="margin:0 0 24px;color:#374151;font-size:16px;">
                                    <?php
                                    printf(
                                        esc_html__('Vous avez reçu un nouveau message via le formulaire de contact de %s.', 'eco-starter'),
                                        '<strong>' . esc_html($site_name) . '</strong>'
                                    );
                                    ?>
                                </p>

                                <!-- Tableau des données -->
                                <table width="100%" cellpadding="0" cellspacing="0"
                                    style="border:1px solid #e5e7eb;border-radius:8px;
                                    overflow:hidden;border-collapse:collapse;">
                                    <?php echo $rows; // Contenu déjà échappé ci-dessus ?>
                                </table>

                                <!-- Bouton répondre -->
                                <div style="margin-top:28px;text-align:center;">
                                    <a href="mailto:<?php echo esc_attr($data['email']); ?>
                                        ?subject=Re: <?php echo rawurlencode($data['subject'] ?? ''); ?>"
                                        style="display:inline-block;padding:12px 28px;
                                        background-color:#2563eb;color:#ffffff;
                                        text-decoration:none;border-radius:8px;
                                        font-weight:600;font-size:15px;">
                                        <?php esc_html_e('Répondre à ce message', 'eco-starter'); ?>
                                    </a>
                                </div>

                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:20px 32px;background:#f9fafb;
                                border-top:1px solid #e5e7eb;">
                                <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                                    <?php
                                    printf(
                                        esc_html__('Reçu le %s — IP : %s', 'eco-starter'),
                                        esc_html($date),
                                        esc_html($ip)
                                    );
                                    ?>
                                    <br>
                                    <a href="<?php echo esc_url($site_url); ?>"
                                        style="color:#9ca3af;">
                                        <?php echo esc_html($site_url); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>

                    </table>
                    <!-- /Wrapper -->

                </td>
            </tr>
        </table>

    </body>
    </html>
    <?php
    return ob_get_clean() ?: '';
}


/**
 * Template email de confirmation au visiteur
 *
 * @param array<string, string> $data
 * @param array<string, mixed>  $config
 * @return string HTML de l'email
 */
function eco_render_confirmation_email_template(array $data, array $config): string
{
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo esc_attr(get_locale()); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($site_name); ?></title>
    </head>
    <body style="margin:0;padding:0;background-color:#f3f4f6;
        font-family:system-ui,-apple-system,sans-serif;">

        <table width="100%" cellpadding="0" cellspacing="0"
            style="background-color:#f3f4f6;padding:40px 16px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0"
                        style="max-width:600px;width:100%;background:#ffffff;
                        border-radius:12px;overflow:hidden;
                        box-shadow:0 1px 3px rgba(0,0,0,0.1);">

                        <!-- Header -->
                        <tr>
                            <td style="background-color:#16a34a;padding:28px 32px;
                                text-align:center;">
                                <!-- Icône check -->
                                <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);
                                    border-radius:50%;margin:0 auto 16px;
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:28px;">✓</div>
                                <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0;">
                                    <?php esc_html_e('Message reçu !', 'eco-starter'); ?>
                                </h1>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding:36px 32px;text-align:center;">

                                <p style="margin:0 0 16px;font-size:18px;font-weight:600;
                                    color:#1f2937;">
                                    <?php
                                    printf(
                                        esc_html__('Bonjour %s,', 'eco-starter'),
                                        esc_html($data['name'])
                                    );
                                    ?>
                                </p>

                                <p style="margin:0 0 24px;font-size:15px;color:#4b5563;
                                    line-height:1.7;max-width:440px;margin-inline:auto;">
                                    <?php
                                    printf(
                                        esc_html__('Nous avons bien reçu votre message et nous vous répondrons dans les plus brefs délais à l\'adresse %s.', 'eco-starter'),
                                        '<strong>' . esc_html($data['email']) . '</strong>'
                                    );
                                    ?>
                                </p>

                                <!-- Récapitulatif du message -->
                                <div style="background:#f9fafb;border-radius:8px;padding:20px 24px;
                                    text-align:left;margin-bottom:28px;">
                                    <p style="margin:0 0 8px;font-size:12px;font-weight:600;
                                        letter-spacing:0.05em;text-transform:uppercase;
                                        color:#9ca3af;">
                                        <?php esc_html_e('Votre message', 'eco-starter'); ?>
                                    </p>
                                    <p style="margin:0;font-size:14px;color:#374151;
                                        line-height:1.7;">
                                        <?php echo nl2br(esc_html(
                                            mb_strlen($data['message']) > 200
                                                ? mb_substr($data['message'], 0, 200) . '…'
                                                : $data['message']
                                        )); ?>
                                    </p>
                                </div>

                                <a href="<?php echo esc_url($site_url); ?>"
                                    style="display:inline-block;padding:12px 28px;
                                    background-color:#2563eb;color:#ffffff;
                                    text-decoration:none;border-radius:8px;
                                    font-weight:600;font-size:15px;">
                                    <?php
                                    printf(
                                        esc_html__('Retour sur %s', 'eco-starter'),
                                        esc_html($site_name)
                                    );
                                    ?>
                                </a>

                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:20px 32px;background:#f9fafb;
                                border-top:1px solid #e5e7eb;text-align:center;">
                                <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                                    <?php esc_html_e('Ce message est envoyé automatiquement, merci de ne pas y répondre.', 'eco-starter'); ?>
                                    <br>
                                    <a href="<?php echo esc_url($site_url); ?>"
                                        style="color:#9ca3af;">
                                        <?php echo esc_html($site_name); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>

    </body>
    </html>
    <?php
    return ob_get_clean() ?: '';
}


// =============================================================================
// SHORTCODE [eco_contact_form]
// =============================================================================

add_shortcode('eco_contact_form', function (array $atts): string {

    $atts = shortcode_atts([
        'id'        => 'contact-form',
        'class'     => '',
        'title'     => '',
        'redirect'  => '',
    ], $atts, 'eco_contact_form');

    // Charge le JS du formulaire si pas déjà fait
    if (!wp_script_is('eco-forms', 'enqueued')) {
        eco_enqueue_script('forms', 'forms');

        // Passe la config au JS
        wp_localize_script('eco-forms', 'ecoContactForm', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('eco_contact_form'),
            'action'  => 'eco_contact_form',
            'redirect'=> esc_url($atts['redirect']),
        ]);
    }

    ob_start();
    eco_render_contact_form($atts);
    return ob_get_clean() ?: '';
});


// =============================================================================
// RENDU DU FORMULAIRE HTML
// =============================================================================

/**
 * Affiche le formulaire de contact
 * Appelable directement dans les templates
 *
 * @param array<string, string> $args Options d'affichage
 */
function eco_render_contact_form(array $args = []): void
{
    $config = eco_form_config();

    $args = wp_parse_args($args, [
        'id'    => 'contact-form',
        'class' => '',
        'title' => '',
    ]);

    $form_id    = sanitize_html_class($args['id']);
    $form_class = 'eco-form contact-form' . ($args['class'] ? ' ' . sanitize_html_class($args['class']) : '');
    ?>

    <?php if ($args['title']) : ?>
        <h2 class="form__title"><?php echo esc_html($args['title']); ?></h2>
    <?php endif; ?>

    <form
        id="<?php echo esc_attr($form_id); ?>"
        class="<?php echo esc_attr($form_class); ?>"
        method="post"
        novalidate
        aria-label="<?php esc_attr_e('Formulaire de contact', 'eco-starter'); ?>"
        data-form="contact"
    >
        <?php // Nonce WordPress ?>
        <?php echo eco_nonce_field('eco_contact_form'); ?>

        <?php // Honeypot — invisible pour les humains, rempli par les bots ?>
        <div class="sr-only" aria-hidden="true" style="position:absolute;left:-9999px;">
            <label for="<?php echo esc_attr($form_id); ?>-website">
                <?php esc_html_e('Ne pas remplir ce champ', 'eco-starter'); ?>
            </label>
            <input
                type="text"
                id="<?php echo esc_attr($form_id); ?>-website"
                name="website"
                value=""
                tabindex="-1"
                autocomplete="off"
            >
        </div>

        <?php // Zone de notification globale ?>
        <div
            class="form-notice"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            id="<?php echo esc_attr($form_id); ?>-notice"
        ></div>

        <?php // Grille des champs ?>
        <div class="form__grid">

            <?php if (in_array('name', $config['fields'], true)) : ?>
            <div class="field<?php echo in_array('name', $config['required'], true) ? ' field--required' : ''; ?>">
                <label for="<?php echo esc_attr($form_id); ?>-name">
                    <?php esc_html_e('Nom complet', 'eco-starter'); ?>
                    <?php if (in_array('name', $config['required'], true)) : ?>
                        <span class="required" aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>
                <input
                    type="text"
                    id="<?php echo esc_attr($form_id); ?>-name"
                    name="name"
                    class="field__input"
                    autocomplete="name"
                    spellcheck="false"
                    placeholder="<?php esc_attr_e('Prénom Nom', 'eco-starter'); ?>"
                    <?php echo in_array('name', $config['required'], true) ? 'required aria-required="true"' : ''; ?>
                    aria-describedby="<?php echo esc_attr($form_id); ?>-name-error"
                >
                <span
                    id="<?php echo esc_attr($form_id); ?>-name-error"
                    class="field__error"
                    role="alert"
                    aria-live="polite"
                ></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('email', $config['fields'], true)) : ?>
            <div class="field<?php echo in_array('email', $config['required'], true) ? ' field--required' : ''; ?>">
                <label for="<?php echo esc_attr($form_id); ?>-email">
                    <?php esc_html_e('Adresse email', 'eco-starter'); ?>
                    <?php if (in_array('email', $config['required'], true)) : ?>
                        <span class="required" aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>
                <input
                    type="email"
                    id="<?php echo esc_attr($form_id); ?>-email"
                    name="email"
                    class="field__input"
                    autocomplete="email"
                    inputmode="email"
                    placeholder="prenom@exemple.com"
                    <?php echo in_array('email', $config['required'], true) ? 'required aria-required="true"' : ''; ?>
                    aria-describedby="<?php echo esc_attr($form_id); ?>-email-error"
                >
                <span
                    id="<?php echo esc_attr($form_id); ?>-email-error"
                    class="field__error"
                    role="alert"
                    aria-live="polite"
                ></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('phone', $config['fields'], true)) : ?>
            <div class="field">
                <label for="<?php echo esc_attr($form_id); ?>-phone">
                    <?php esc_html_e('Téléphone', 'eco-starter'); ?>
                    <span class="field__hint"><?php esc_html_e('(optionnel)', 'eco-starter'); ?></span>
                </label>
                <input
                    type="tel"
                    id="<?php echo esc_attr($form_id); ?>-phone"
                    name="phone"
                    class="field__input"
                    autocomplete="tel"
                    inputmode="tel"
                    placeholder="+33 6 00 00 00 00"
                    aria-describedby="<?php echo esc_attr($form_id); ?>-phone-error"
                >
                <span
                    id="<?php echo esc_attr($form_id); ?>-phone-error"
                    class="field__error"
                    role="alert"
                    aria-live="polite"
                ></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('subject', $config['fields'], true) && !empty($config['subjects'])) : ?>
            <div class="field form__field--full<?php echo in_array('subject', $config['required'], true) ? ' field--required' : ''; ?>">
                <label for="<?php echo esc_attr($form_id); ?>-subject">
                    <?php esc_html_e('Sujet', 'eco-starter'); ?>
                    <?php if (in_array('subject', $config['required'], true)) : ?>
                        <span class="required" aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>
                <select
                    id="<?php echo esc_attr($form_id); ?>-subject"
                    name="subject"
                    class="field__input"
                    <?php echo in_array('subject', $config['required'], true) ? 'required aria-required="true"' : ''; ?>
                    aria-describedby="<?php echo esc_attr($form_id); ?>-subject-error"
                >
                    <?php foreach ($config['subjects'] as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span
                    id="<?php echo esc_attr($form_id); ?>-subject-error"
                    class="field__error"
                    role="alert"
                    aria-live="polite"
                ></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('message', $config['fields'], true)) : ?>
            <div class="field form__field--full<?php echo in_array('message', $config['required'], true) ? ' field--required' : ''; ?>">
                <label for="<?php echo esc_attr($form_id); ?>-message">
                    <?php esc_html_e('Message', 'eco-starter'); ?>
                    <?php if (in_array('message', $config['required'], true)) : ?>
                        <span class="required" aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>
                <textarea
                    id="<?php echo esc_attr($form_id); ?>-message"
                    name="message"
                    class="field__input"
                    rows="6"
                    placeholder="<?php esc_attr_e('Décrivez votre besoin…', 'eco-starter'); ?>"
                    maxlength="<?php echo esc_attr((string) $config['message_max_len']); ?>"
                    <?php echo in_array('message', $config['required'], true) ? 'required aria-required="true"' : ''; ?>
                    aria-describedby="<?php echo esc_attr($form_id); ?>-message-error <?php echo esc_attr($form_id); ?>-message-count"
                ></textarea>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span
                        id="<?php echo esc_attr($form_id); ?>-message-error"
                        class="field__error"
                        role="alert"
                        aria-live="polite"
                    ></span>
                    <span
                        id="<?php echo esc_attr($form_id); ?>-message-count"
                        class="field__hint"
                        aria-live="polite"
                        aria-label="<?php esc_attr_e('Nombre de caractères restants', 'eco-starter'); ?>"
                    >0 / <?php echo esc_html((string) $config['message_max_len']); ?></span>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.form__grid -->

        <?php // Mention RGPD ?>
        <div class="field field--check form__gdpr">
            <input
                type="checkbox"
                id="<?php echo esc_attr($form_id); ?>-gdpr"
                name="gdpr"
                value="1"
                required
                aria-required="true"
                aria-describedby="<?php echo esc_attr($form_id); ?>-gdpr-error"
            >
            <label for="<?php echo esc_attr($form_id); ?>-gdpr">
                <?php
                printf(
                    wp_kses(
                        __('J\'accepte que mes données soient utilisées pour traiter ma demande. <a href="%s">Politique de confidentialité</a>.', 'eco-starter'),
                        ['a' => ['href' => []]]
                    ),
                    esc_url(get_privacy_policy_url())
                );
                ?>
                <span class="required" aria-hidden="true">*</span>
            </label>
            <span
                id="<?php echo esc_attr($form_id); ?>-gdpr-error"
                class="field__error"
                role="alert"
                aria-live="polite"
            ></span>
        </div>

        <?php // Mention champs obligatoires ?>
        <p class="form__required-note">
            <span aria-hidden="true">*</span>
            <?php esc_html_e('Champs obligatoires', 'eco-starter'); ?>
        </p>

        <?php // Bouton de soumission ?>
        <div class="form__submit">
            <button
                type="submit"
                class="btn btn--primary btn--lg"
                data-loading-text="<?php esc_attr_e('Envoi en cours…', 'eco-starter'); ?>"
            >
                <span class="btn__label">
                    <?php esc_html_e('Envoyer le message', 'eco-starter'); ?>
                </span>
            </button>
        </div>

    </form>

    <?php
}


// =============================================================================
// CSS DU FORMULAIRE — grille responsive
// Injecté en inline pour le formulaire de contact uniquement
// =============================================================================

add_action('wp_head', function (): void {

    // Uniquement sur les pages avec le formulaire
    if (!is_page_template('templates/template-contact.php') &&
        !has_shortcode(get_post_field('post_content', get_the_ID()), 'eco_contact_form')
    ) return;

    ?>
    <style>
    .form__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-5);
        margin-bottom: var(--space-5);
    }

    .form__field--full {
        grid-column: 1 / -1;
    }

    .form__gdpr {
        margin-bottom: var(--space-4);
    }

    .form__required-note {
        font-size: var(--text-sm);
        color: var(--color-muted);
        margin-bottom: var(--space-6);
    }

    .form__submit {
        display: flex;
        align-items: center;
        gap: var(--space-4);
    }

    @media (max-width: 640px) {
        .form__grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php

}, 10);