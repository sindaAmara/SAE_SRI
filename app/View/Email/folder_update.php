<?php
/**
 * Email template: Folder review update
 *
 * Transactional email sent to a student after an administrator has reviewed
 * their folder. Summarises the outcome of the review across three optional
 * sections:
 *
 * - Validated documents ($sectionAcceptees): pre-rendered HTML list items
 *   for documents that were accepted (shown with a green title when non-empty).
 * - Rejected documents ($sectionRefusees): pre-rendered HTML list items for
 *   documents that were refused, typically including admin comments
 *   (shown with a red title when non-empty).
 * - Additional lines ($autresLignes): arbitrary pre-rendered HTML paragraphs
 *   for any supplementary information (defaults to an empty array).
 *
 * When $statutGlobal is non-empty, the current global workflow status of the
 * folder is displayed in a colour-coded badge (depot / instruction / accepte /
 * refuse). When $dateLimite is non-empty, the document submission deadline is
 * shown below the status badge.
 *
 * A call-to-action button links to the student's space at ri-amu.app.
 * All scalar user-supplied values are escaped with ENT_QUOTES / UTF-8;
 * the pre-rendered HTML variables ($sectionAcceptees, $sectionRefusees,
 * $autresLignes) are output raw and must be sanitised by the caller.
 *
 * @var string         $logoUrl          Absolute URL of the AMU logo image to embed in the email header
 * @var string         $studentName      Full name of the student, displayed in the greeting
 * @var string         $sectionAcceptees Pre-rendered HTML <li> items for accepted documents; empty string to hide the section
 * @var string         $sectionRefusees  Pre-rendered HTML <li> items for refused documents; empty string to hide the section
 * @var array<int, string> $autresLignes Additional pre-rendered HTML paragraph strings; defaults to []
 * @var string         $statutGlobal     Human-readable global folder status (e.g. 'Accepté', 'Refusé'); empty string to hide the badge
 * @var string         $dateLimite       Formatted document submission deadline string; empty string to hide the deadline line
 */
$autresLignes = $autresLignes ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Mise à jour dossier</title></head>
<body>
<div class="email-wrapper">
    <div class="email-header email-header--blue">
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="AMU">
        <h2>Mise à jour de votre dossier</h2>
    </div>
    <div class="email-body">
        <p>Bonjour <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>,</p>
        <p>Votre dossier a été examiné par l'administration. Voici le récapitulatif :</p>

        <div class="email-summary">
            <?php if (!empty($sectionAcceptees)): ?>
                <p class="summary-title--green">✅ Documents validés :</p>
                <ul><?= $sectionAcceptees ?></ul>
            <?php endif; ?>

            <?php if (!empty($sectionRefusees)): ?>
                <p class="summary-title--red">❌ Documents non validés :</p>
                <ul><?= $sectionRefusees ?></ul>
            <?php endif; ?>

            <?php foreach ($autresLignes as $ligne): ?>
                <p class="summary-line"><?= $ligne ?></p>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($statutGlobal)):
            $statutClasses = [
                'Dépôt'          => 'email-status--depot',
                'En instruction' => 'email-status--instruction',
                'Accepté'        => 'email-status--accepte',
                'Refusé'         => 'email-status--refuse',
            ];
            $statutClass = $statutClasses[$statutGlobal] ?? '';
            ?>
            <div class="email-status <?= $statutClass ?>">
                <span class="status-label">Statut de votre dossier :</span>
                <span class="status-value"><?= htmlspecialchars($statutGlobal, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($dateLimite)): ?>
            <p class="email-deadline">📅 Date limite de remise des pièces : <strong><?= htmlspecialchars($dateLimite, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php endif; ?>

        <div class="email-cta email-cta--wide">
            <a href="https://ri-amu.app/" class="btn-primary btn-primary--lg">Consulter mon espace</a>
        </div>
        <p class="email-note">Pour toute question, contactez le service Relations Internationales.</p>
        <p class="email-footer">Email automatique • Service RI AMU</p>
    </div>
</div>
</body>
</html>