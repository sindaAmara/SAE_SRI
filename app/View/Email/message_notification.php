<?php
/**
 * Email template: New message notification
 *
 * Transactional email sent to a user (student or admin) to notify them that
 * they have received a new message on the platform. Displays the sender's name
 * and a truncated preview of the message body (capped at 150 characters; an
 * ellipsis is appended when the preview reaches that limit). A call-to-action
 * button links directly to the messaging section of the platform.
 *
 * Unlike the other email templates, this one does not include a logo, as the
 * notification is role-agnostic and used for both student and admin recipients.
 * All user-supplied values are escaped with ENT_QUOTES / UTF-8.
 *
 * @var string $recipientName  Full name of the message recipient, displayed in the greeting
 * @var string $senderName     Full name of the message sender, displayed in the body and the preview header
 * @var string $messagePreview Plain-text preview of the message content (truncated to 150 characters by the template)
 * @var string $platformLink   Absolute URL to the messaging section of the platform
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Nouveau Message</title></head>
<body>
<div class="email-wrapper">
    <div class="email-header email-header--blue">
        <h2>📬 Nouveau Message</h2>
    </div>
    <div class="email-body">
        <p>Bonjour <strong><?= htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') ?></strong>,</p>
        <p>Vous avez reçu un nouveau message de <strong><?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?></strong>.</p>

        <div class="email-highlight email-highlight--grey">
            <div class="highlight-sender">De : <?= htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="highlight-preview"><?= htmlspecialchars($messagePreview, ENT_QUOTES, 'UTF-8') ?><?= strlen($messagePreview) >= 150 ? '…' : '' ?></div>
        </div>

        <div class="email-cta">
            <a href="<?= htmlspecialchars($platformLink, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Consulter sur la plateforme</a>
        </div>
        <p class="email-note">Veuillez vous connecter à votre espace pour lire le message complet et y répondre.</p>
        <p class="email-note--small">Email automatique • Service RI - AMU</p>
    </div>
</div>
</body>
</html>