<?php
/**
 * Email template: Full folder validation confirmation
 *
 * Transactional email sent to a student to confirm that their folder has been
 * fully reviewed and that all listed documents have been validated. Intended
 * as a final positive confirmation, distinct from the per-document
 * document_validated.php template.
 *
 * The list of validated document labels is rendered as a green-styled bullet
 * list. When $validatedDocuments is empty (defaults to []), the list renders
 * nothing but the surrounding congratulatory message is still shown.
 * A call-to-action button links to the student's folder.
 * All user-supplied values are escaped with ENT_QUOTES / UTF-8.
 *
 * @var string             $logoUrl            Absolute URL of the AMU logo image to embed in the email header
 * @var string             $studentName        Full name of the student, displayed in the greeting
 * @var string             $numEtu             Student number, referenced in the body to identify the folder
 * @var array<int, string> $validatedDocuments List of human-readable labels for the validated documents; defaults to []
 * @var string             $folderLink         Absolute URL to the student's folder on the platform
 */
$validatedDocuments = $validatedDocuments ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Documents Validés</title></head>
<body>
<div class="email-wrapper">
    <div class="email-header email-header--green">
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="AMU">
        <h2>✓ Documents Validés</h2>
    </div>
    <div class="email-body">
        <p>Bonjour <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>,</p>
        <p>Bonne nouvelle ! Votre dossier <strong><?= htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8') ?></strong> a été examiné et les documents suivants ont été <strong class="text-green-bold">validés</strong> :</p>

        <ul class="list--green">
            <?php foreach ($validatedDocuments as $doc): ?>
                <li><strong>✓ <?= htmlspecialchars($doc, ENT_QUOTES, 'UTF-8') ?></strong></li>
            <?php endforeach; ?>
        </ul>

        <p class="text-green-bold">Votre dossier est maintenant complet et approuvé !</p>
        <div class="email-cta">
            <a href="<?= htmlspecialchars($folderLink, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Voir mon dossier</a>
        </div>
        <p class="email-note">Si vous avez des questions, contactez le service RI.</p>
        <p class="email-note--small">Notification automatique • Service RI - IUT Aix</p>
    </div>
</div>
</body>
</html>