<?php
/**
 * Email template: Incomplete folder reminder (relance)
 *
 * Transactional reminder email sent to a student whose folder is still
 * incomplete. When $itemsToComplete is non-empty, the missing items are listed
 * as individual bullet points; otherwise a generic fallback message is shown
 * asking the student to complete their missing documents.
 *
 * The email includes a call-to-action button linking directly to the student's
 * folder so they can take action immediately. $itemsToComplete defaults to an
 * empty array when not injected by the caller.
 * All user-supplied values are escaped with ENT_QUOTES / UTF-8.
 *
 * @var string             $logoUrl          Absolute URL of the AMU logo image to embed in the email header
 * @var string             $studentName      Full name of the student, displayed in the greeting
 * @var string             $dossierId        Folder identifier (typically the student number), displayed in the reminder body
 * @var string             $folderLink       Absolute URL to the student's folder on the platform
 * @var array<int, string> $itemsToComplete  List of human-readable labels for the missing documents; defaults to []
 */
$itemsToComplete = $itemsToComplete ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Rappel dossier</title></head>
<body>
<div class="email-wrapper">
    <div class="email-header email-header--blue">
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="AMU">
        <h2>⚠️ Rappel — Dossier incomplet</h2>
    </div>
    <div class="email-body">
        <p>Bonjour <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>,</p>
        <p>Votre dossier n°<strong><?= htmlspecialchars($dossierId, ENT_QUOTES, 'UTF-8') ?></strong> est actuellement <strong>incomplet</strong>.</p>

        <?php if (!empty($itemsToComplete)): ?>
            <ul>
                <?php foreach ($itemsToComplete as $item): ?>
                    <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Veuillez compléter les documents manquants dans votre dossier.</p>
        <?php endif; ?>

        <div class="email-cta">
            <a href="<?= htmlspecialchars($folderLink, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Accéder à mon dossier</a>
        </div>
        <p class="email-note">Pour toute question, contactez le service RI.</p>
        <p class="email-note--small">Email automatique • Service RI - IUT Aix</p>
    </div>
</div>
</body>
</html>