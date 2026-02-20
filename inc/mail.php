<?php
/**
 * Templates et configuration wp_mail — Eco Starter
 *
 * - Configuration SMTP (hook natif WordPress)
 * - Template HTML générique réutilisable
 * - Helper d'envoi centralisé
 * - Notifications admin configurables
 * - Journalisation des emails en WP_DEBUG
 *
 * Note : les templates des emails du formulaire de contact
 * sont dans inc/forms.php pour la cohésion.
 * Ce fichier gère les emails système et les notifications admin.
 *
 * @package EcoStarter
 */

declare(strict_types=1);
defined('ABSPATH') || exit;


// =============================================================================
// CONFIGURATION GLOBALE wp_mail
// =============================================================================

/**
 * Force l'encodage UTF-8 et le Content-Type HTML pour tous les emails WordPress
 */
add_filter('wp_mail_content_type', fn(): string => 'text/html');
add_filter('wp_mail_charset',      fn(): string => 'UTF-8');

/**
 * Personnalise l'expéditeur des emails WordPress
 * Par défaut WordPress utilise wordpress@domain.com — peu professionnel
 */
add_filter('wp_mail_from', function (string $email): string {
    $custom = get_theme_mod('schema_email', '');
    if ($custom && is_email($custom)) {
        return $custom;
    }
    // Utilise admin@domain.com plutôt que wordpress@domain.com
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    return 'noreply@' . $domain;
});

add_filter('wp_mail_from_name', function (string $name): string {
    return get_theme_mod('schema_org_name', get_bloginfo('name'));
});


// =============================================================================
// SMTP — Configuration optionnelle
// Décommenter et remplir si le serveur n'a pas de sendmail configuré
// Ou utiliser un plugin dédié (WP Mail SMTP, FluentSMTP)
// =============================================================================

/*
add_action('phpmailer_init', function (\PHPMailer\PHPMailer\PHPMailer $phpmailer): void {

    $phpmailer->isSMTP();
    $phpmailer->Host       = defined('SMTP_HOST')     ? SMTP_HOST     : 'smtp.example.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = defined('SMTP_PORT')     ? SMTP_PORT     : 587;
    $phpmailer->Username   = defined('SMTP_USER')     ? SMTP_USER     : '';
    $phpmailer->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
    $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    // Désactive la vérification SSL en dev local (ne jamais faire en prod)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $phpmailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
    }
});
*/


// =============================================================================
// HELPER D'ENVOI CENTRALISÉ
// Wrapper autour de wp_mail avec logging et gestion d'erreurs
// =============================================================================

/**
 * Envoie un email via le template HTML du thème
 *
 * @param string                $to       Destinataire (email ou "Nom <email>")
 * @param string                $subject  Objet
 * @param string                $content  Contenu HTML du body (sans wrapper)
 * @param array<string, mixed>  $options  Options supplémentaires
 * @return bool                           true si envoi réussi
 *
 * Options disponibles :
 * - 'cta_text'   (string) Texte du bouton CTA
 * - 'cta_url'    (string) URL du bouton CTA
 * - 'preheader'  (string) Texte de prévisualisation (après l'objet)
 * - 'header_color' (string) Couleur hex de l'en-tête email
 * - 'reply_to'   (string) Email de réponse
 * - 'attachments' (array) Fichiers joints
 * - 'headers'    (array) Headers supplémentaires
 */
function eco_send_email(
    string $to,
    string $subject,
    string $content,
    array $options = []
): bool {

    $options = wp_parse_args($options, [
        'cta_text'     => '',
        'cta_url'      => '',
        'preheader'    => '',
        'header_color' => '#2563eb',
        'reply_to'     => '',
        'attachments'  => [],
        'headers'      => [],
    ]);

    // Construction des headers
    $headers = array_merge(
        ['Content-Type: text/html; charset=UTF-8'],
        $options['headers']
    );

    if ($options['reply_to'] && is_email($options['reply_to'])) {
        $headers[] = 'Reply-To: ' . $options['reply_to'];
    }

    // Rendu du template complet
    $body = eco_render_email_template($content, $subject, $options);

    // Envoi
    $sent = wp_mail(
        $to,
        $subject,
        $body,
        $headers,
        $options['attachments']
    );

    // Log en WP_DEBUG
    eco_log_email($to, $subject, $sent);

    return $sent;
}


// =============================================================================
// TEMPLATE EMAIL GÉNÉRIQUE
// Layout HTML compatible tous clients mail (Outlook inclus)
// =============================================================================

/**
 * Rend le template email complet
 *
 * @param string               $content Corps du message (HTML)
 * @param string               $subject Objet (pour le titre)
 * @param array<string, mixed> $options Options de rendu
 * @return string              HTML complet de l'email
 */
function eco_render_email_template(
    string $content,
    string $subject = '',
    array $options  = []
): string {
    $site_name    = get_theme_mod('schema_org_name', get_bloginfo('name'));
    $site_url     = home_url('/');
    $header_color = $options['header_color'] ?? '#2563eb';
    $preheader    = $options['preheader']    ?? '';
    $cta_text     = $options['cta_text']     ?? '';
    $cta_url      = $options['cta_url']      ?? '';
    $logo_id      = get_theme_mod('custom_logo');
    $logo_src     = $logo_id ? wp_get_attachment_image_src($logo_id, 'medium') : null;
    $year         = date('Y');

    // Couleur de texte adaptée selon la luminosité du header
    $header_text_color = eco_get_luminance($header_color) > 0.4 ? '#1a1a1a' : '#ffffff';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo esc_attr(get_locale()); ?>" xmlns:v="urn:schemas-microsoft-com:vml">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
        <!--[if mso]>
        <noscript>
            <xml><o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings></xml>
        </noscript>
        <![endif]-->
        <title><?php echo esc_html($subject ?: $site_name); ?></title>
        <style>
            /* Reset clients mail */
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }

            /* Reset Outlook */
            body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

            /* Liens */
            a { color: <?php echo esc_attr($header_color); ?>; }

            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container { width: 100% !important; }
                .fluid { width: 100% !important; max-width: 100% !important; }
                .stack-column,
                .stack-column-center { display: block !important; width: 100% !important; }
                .center-on-narrow { text-align: center !important; }
            }
        </style>
    </head>
    <body style="margin:0;padding:0;background-color:#f3f4f6;word-break:break-word;">

        <?php if ($preheader) : ?>
        <!-- Texte de prévisualisation (invisible dans l'email) -->
        <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;
            opacity:0;overflow:hidden;mso-hide:all;">
            <?php echo esc_html($preheader); ?>
            &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
        </div>
        <?php endif; ?>

        <!-- Wrapper principal -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
            width="100%" style="background-color:#f3f4f6;">
            <tr>
                <td style="padding:32px 16px;">

                    <!-- Container email -->
                    <table class="email-container" role="presentation"
                        cellspacing="0" cellpadding="0" border="0"
                        width="600" align="center"
                        style="max-width:600px;width:100%;margin:0 auto;
                        background-color:#ffffff;border-radius:12px;
                        overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                        <!-- ======= EN-TÊTE ======= -->
                        <tr>
                            <td style="background-color:<?php echo esc_attr($header_color); ?>;
                                padding:28px 40px;">
                                <table role="presentation" cellspacing="0"
                                    cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td>
                                            <?php if ($logo_src) : ?>
                                                <a href="<?php echo esc_url($site_url); ?>"
                                                    style="display:inline-block;">
                                                    <img
                                                        src="<?php echo esc_url($logo_src[0]); ?>"
                                                        alt="<?php echo esc_attr($site_name); ?>"
                                                        height="40"
                                                        style="height:40px;width:auto;
                                                        filter:brightness(0) invert(1);">
                                                </a>
                                            <?php else : ?>
                                                <a href="<?php echo esc_url($site_url); ?>"
                                                    style="font-size:20px;font-weight:700;
                                                    color:<?php echo esc_attr($header_text_color); ?>;
                                                    text-decoration:none;">
                                                    <?php echo esc_html($site_name); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- ======= CORPS ======= -->
                        <tr>
                            <td style="padding:40px;">
                                <?php
                                // Contenu — déjà échappé par l'appelant
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo $content;
                                ?>

                                <?php if ($cta_text && $cta_url) : ?>
                                <!-- CTA -->
                                <table role="presentation" cellspacing="0"
                                    cellpadding="0" border="0" align="center"
                                    style="margin-top:32px;">
                                    <tr>
                                        <td style="border-radius:8px;
                                            background-color:<?php echo esc_attr($header_color); ?>;">
                                            <a href="<?php echo esc_url($cta_url); ?>"
                                                target="_blank"
                                                style="display:inline-block;padding:14px 32px;
                                                font-size:15px;font-weight:600;
                                                color:#ffffff;text-decoration:none;
                                                border-radius:8px;
                                                mso-padding-alt:0;
                                                text-underline-color:<?php echo esc_attr($header_color); ?>;">
                                                <!--[if mso]><i style="letter-spacing:25px;mso-font-width:-100%;mso-text-raise:30pt">&nbsp;</i><![endif]-->
                                                <?php echo esc_html($cta_text); ?>
                                                <!--[if mso]><i style="letter-spacing:25px;mso-font-width:-100%">&nbsp;</i><![endif]-->
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                <?php endif; ?>

                            </td>
                        </tr>

                        <!-- ======= PIED DE PAGE ======= -->
                        <tr>
                            <td style="padding:24px 40px;background-color:#f9fafb;
                                border-top:1px solid #e5e7eb;">
                                <table role="presentation" cellspacing="0"
                                    cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="font-size:12px;color:#9ca3af;
                                            line-height:1.6;text-align:center;">

                                            <!-- Réseaux sociaux -->
                                            <?php
                                            $social_links = eco_get_email_social_links();
                                            if ($social_links) :
                                            ?>
                                            <p style="margin:0 0 12px;">
                                                <?php
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $social_links;
                                                ?>
                                            </p>
                                            <?php endif; ?>

                                            <p style="margin:0;">
                                                <a href="<?php echo esc_url($site_url); ?>"
                                                    style="color:#9ca3af;text-decoration:none;">
                                                    <?php echo esc_html($site_name); ?>
                                                </a>
                                                &nbsp;·&nbsp;
                                                <?php
                                                printf(
                                                    esc_html__('© %d Tous droits réservés', 'eco-starter'),
                                                    absint($year)
                                                );
                                                ?>
                                            </p>

                                            <?php
                                            $privacy_url = get_privacy_policy_url();
                                            if ($privacy_url) :
                                            ?>
                                            <p style="margin:8px 0 0;">
                                                <a href="<?php echo esc_url($privacy_url); ?>"
                                                    style="color:#9ca3af;font-size:11px;">
                                                    <?php esc_html_e('Politique de confidentialité', 'eco-starter'); ?>
                                                </a>
                                            </p>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                    <!-- /Container email -->

                </td>
            </tr>
        </table>
        <!-- /Wrapper principal -->

    </body>
    </html>
    <?php
    return ob_get_clean() ?: '';
}


/**
 * Génère les liens réseaux sociaux pour le footer email
 *
 * @return string HTML des liens ou chaîne vide
 */
function eco_get_email_social_links(): string
{
    $networks = [
        'social_twitter'   => 'Twitter',
        'social_linkedin'  => 'LinkedIn',
        'social_facebook'  => 'Facebook',
        'social_instagram' => 'Instagram',
        'social_youtube'   => 'YouTube',
    ];

    $links = [];

    foreach ($networks as $mod => $label) {
        $url = get_theme_mod($mod, '');
        if ($url) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" rel="noopener"'
                . ' style="color:#9ca3af;text-decoration:none;margin:0 6px;">%s</a>',
                esc_url($url),
                esc_html($label)
            );
        }
    }

    return implode('·', $links);
}


// =============================================================================
// NOTIFICATIONS ADMIN
// Emails système envoyés à l'admin selon les événements du site
// =============================================================================

/**
 * Notification d'inscription d'un nouvel utilisateur
 * Remplace l'email natif WordPress (trop basique)
 */
add_filter('wp_new_user_notification_email_admin', function (
    array $email,
    \WP_User $user,
    string $blogname
): array {

    $content = sprintf(
        '<h2 style="margin:0 0 16px;font-size:22px;color:#1f2937;">%s</h2>
        <p style="margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.7;">%s</p>
        <table style="width:100%;border:1px solid #e5e7eb;border-radius:8px;
            border-collapse:collapse;">
            <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;
                    font-weight:600;color:#374151;width:140px;">%s</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;">%s</td>
            </tr>
            <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;
                    font-weight:600;color:#374151;">%s</td>
                <td style="padding:12px 16px;border-bottom:1px solid #e5e7eb;">%s</td>
            </tr>
            <tr>
                <td style="padding:12px 16px;font-weight:600;color:#374151;">%s</td>
                <td style="padding:12px 16px;">%s</td>
            </tr>
        </table>',
        esc_html__('Nouvel utilisateur inscrit', 'eco-starter'),
        sprintf(
            esc_html__('Un nouvel utilisateur s\'est inscrit sur %s.', 'eco-starter'),
            '<strong>' . esc_html($blogname) . '</strong>'
        ),
        esc_html__('Identifiant', 'eco-starter'),
        esc_html($user->user_login),
        esc_html__('Email', 'eco-starter'),
        esc_html($user->user_email),
        esc_html__('Date', 'eco-starter'),
        esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format')))
    );

    $email['message'] = eco_render_email_template($content, $email['subject'], [
        'cta_text' => __('Gérer les utilisateurs', 'eco-starter'),
        'cta_url'  => admin_url('users.php'),
        'preheader' => sprintf(__('Nouvel utilisateur : %s', 'eco-starter'), $user->user_login),
    ]);

    return $email;
}, 10, 3);


/**
 * Notification d'un nouveau commentaire
 * Remplace le format texte natif par un email HTML
 */
add_filter('comment_notification_text', function (
    string $notify_message,
    int $comment_id
): string {

    $comment = get_comment($comment_id);
    if (!$comment) return $notify_message;

    $post    = get_post($comment->comment_post_ID);
    if (!$post) return $notify_message;

    $content = sprintf(
        '<h2 style="margin:0 0 8px;font-size:22px;color:#1f2937;">%s</h2>
        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;">%s</p>
        <div style="background:#f9fafb;border-left:4px solid #2563eb;
            padding:16px 20px;border-radius:0 8px 8px 0;margin-bottom:24px;">
            <p style="margin:0;font-style:italic;color:#374151;line-height:1.7;">
                &ldquo;%s&rdquo;
            </p>
        </div>
        <table style="width:100%;border:1px solid #e5e7eb;border-radius:8px;
            border-collapse:collapse;">
            <tr>
                <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;
                    font-weight:600;color:#374151;width:120px;">%s</td>
                <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;">%s</td>
            </tr>
            <tr>
                <td style="padding:10px 16px;font-weight:600;color:#374151;">%s</td>
                <td style="padding:10px 16px;">%s</td>
            </tr>
        </table>',
        esc_html__('Nouveau commentaire', 'eco-starter'),
        esc_html($post->post_title),
        nl2br(esc_html(wp_trim_words($comment->comment_content, 50))),
        esc_html__('Auteur', 'eco-starter'),
        esc_html($comment->comment_author)
            . ($comment->comment_author_email
                ? ' &lt;' . esc_html($comment->comment_author_email) . '&gt;'
                : ''),
        esc_html__('Date', 'eco-starter'),
        esc_html(wp_date(get_option('date_format') . ' H:i', strtotime($comment->comment_date)))
    );

    return eco_render_email_template($content, __('Nouveau commentaire', 'eco-starter'), [
        'cta_text' => __('Modérer ce commentaire', 'eco-starter'),
        'cta_url'  => admin_url('comment.php?action=editcomment&c=' . $comment_id),
        'preheader' => $comment->comment_author . ' — ' . wp_trim_words($comment->comment_content, 10),
    ]);

}, 10, 2);


// =============================================================================
// JOURNALISATION
// En WP_DEBUG uniquement — ne jamais loguer les emails en production
// =============================================================================

/**
 * Log un email envoyé
 *
 * @param string $to      Destinataire
 * @param string $subject Objet
 * @param bool   $sent    Succès ou échec
 */
function eco_log_email(string $to, string $subject, bool $sent): void
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) return;

    $status = $sent ? 'OK' : 'ECHEC';
    $date   = wp_date('Y-m-d H:i:s');

    error_log(sprintf(
        '[Eco Starter Email] [%s] %s → %s | Objet: %s',
        $date,
        $status,
        $to,
        $subject
    ));
}


/**
 * Intercepte wp_mail en WP_DEBUG pour logger toutes les tentatives
 * Utile pour déboguer les emails qui ne partent pas
 */
if (defined('WP_DEBUG') && WP_DEBUG) {

    add_action('wp_mail_failed', function (\WP_Error $error): void {
        error_log(sprintf(
            '[Eco Starter Email] ERREUR wp_mail : %s',
            $error->get_error_message()
        ));
    });

    // Log de tous les emails sortants
    add_filter('wp_mail', function (array $args): array {
        error_log(sprintf(
            '[Eco Starter Email] Envoi → %s | %s',
            is_array($args['to']) ? implode(', ', $args['to']) : $args['to'],
            $args['subject']
        ));
        return $args;
    });
}


// =============================================================================
// HELPER — Shortcodes pour les templates email
// Permet des variables dynamiques dans les contenus stockés en base
// =============================================================================

/**
 * Remplace les shortcodes dans un contenu email
 *
 * Variables disponibles :
 * {site_name}, {site_url}, {year}, {admin_email}
 *
 * @param string               $content  Contenu avec shortcodes
 * @param array<string, string> $extra   Variables supplémentaires
 * @return string                         Contenu traité
 */
function eco_parse_email_vars(string $content, array $extra = []): string
{
    $vars = array_merge([
        '{site_name}'   => get_bloginfo('name'),
        '{site_url}'    => home_url('/'),
        '{year}'        => date('Y'),
        '{admin_email}' => get_option('admin_email'),
        '{date}'        => wp_date(get_option('date_format')),
    ], $extra);

    return str_replace(
        array_keys($vars),
        array_map('wp_kses_post', array_values($vars)),
        $content
    );
}