<?php
/**
 * Email template: Document validated notification
 *
 * Transactional email sent to a student to notify them that a specific
 * supporting document has been reviewed and accepted by the International
 * Relations team. The email encourages the student to submit any remaining
 * documents and provides a call-to-action button linking to their folder.
 *
 * Rendered with a green header and a green highlighted document-name block
 * to visually distinguish a positive validation outcome from other email types.
 * All user-supplied values are escaped with ENT_QUOTES / UTF-8.
 *
 * @var string $logoUrl       Absolute URL of the AMU logo image to embed in the email header
 * @var string $studentName   Full name of the student, displayed in the greeting
 * @var string $documentLabel Human-readable label of the validated document (e.g. 'CV', 'Motivation Letter')
 * @var string $folderLink    Absolute URL to the student's folder on the platform
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Document validé</title></head>
<body>
<div class="email-wrapper">
    <div class="email-header email-header--green">
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="AMU">
        <h2>✓ Document validé</h2>
    </div>
    <div class="email-body">
        <p>Bonjour <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>,</p>
        <p>Bonne nouvelle ! Votre document a été <strong class="text-green-bold">validé</strong> :</p>
        <div class="email-highlight email-highlight--green">
            <strong>✓ <?= htmlspecialchars($documentLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <p>Votre dossier progresse bien. N'oubliez pas de déposer les autres documents si nécessaire.</p>
        <div class="email-cta">
            <a href="<?= htmlspecialchars($folderLink, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Voir mon dossier</a>
        </div>
        <p class="email-note">Si vous avez des questions, contactez le service RI.</p>
        <p class="email-note--small">Notification automatique • Service RI - IUT Aix</p>
    </div>
</div>
</body>
</html>