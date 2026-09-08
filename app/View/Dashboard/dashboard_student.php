<?php
/**
 * View: Student Dashboard — Folder Progress Tracker
 *
 * Displays the current processing status of a student's mobility folder as a
 * three-step horizontal progress bar, followed by a support contact box.
 *
 * ── PROGRESS BAR (.progress-container) ───────────────────────────────────────
 * Three sequential steps rendered as .progress-step elements:
 * 1. "Dépôt de la demande / Application Submitted" — active for any status.
 * 2. "Instruction en cours / Under Review" — active from 'instruction' onward.
 * 3. "Décision prise / Decision Made" — active only for 'accepte' or 'refuse'.
 *
 * A step is marked active (CSS class 'active') when $status matches its
 * expected set via in_array(). The visual connector line between steps is
 * driven by the inline style injected via $progressStyle (e.g. a CSS width or
 * background-position value calculated by the controller).
 *
 * When the folder has reached a final decision the container receives an
 * additional modifier class:
 * - 'decision-accepted' when $status === 'accepte'
 * - 'decision-refused'  when $status === 'refuse'
 * - '' (empty) for any other status
 *
 * $decisionClass is computed locally at the top of the file from $status.
 *
 * ── CONTACT BOX (.contact-info-box) ──────────────────────────────────────────
 * A static informational card with a hardcoded mailto link to the
 * international relations office (relations.internationale@amu-univ.fr).
 * Not driven by any injected $contactInfo variable.
 *
 * A hidden #app-config div carries data-lang and data-role="student".
 *
 * The rendered HTML is passed to the base layout with styles
 * (dashboard.css, index.css, chatbot.css), no scripts, $activeMenu = 'dashboard',
 * $userRole = 'student'.
 *
 * @var string                             $lang          Current language code (e.g. 'fr' or 'en')
 * @var Closure(array<string, string>): string $t          Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var string                             $progressStyle Inline CSS string for the .progress-line element (controls visual fill width/position); computed by the controller from $status
 * @var string                             $status        Current folder workflow status: 'depot' | 'instruction' | 'accepte' | 'refuse'
 * @var array<string, mixed>               $folder        Full folder data array from the repository (available for future use; not directly referenced in this template)
 */

ob_start();

$decisionClass = '';
if ($status === 'accepte') $decisionClass = 'decision-accepted';
elseif ($status === 'refuse') $decisionClass = 'decision-refused';
?>

    <h1><?= $t(['fr' => 'Suivi du dossier', 'en' => 'File Tracking']) ?></h1>

    <div class="progress-container <?= $decisionClass ?>">
        <div class="progress-line" style="<?= $progressStyle ?>"></div>

        <div class="progress-step <?= in_array($status, ['depot', 'instruction', 'accepte', 'refuse'], true) ? 'active' : '' ?>">
            <div class="progress-icon"><img src="img/depot.png" alt="Dépôt"></div>
            <div class="progress-circle"></div>
            <span><?= $t(['fr' => 'Dépôt de la demande', 'en' => 'Application Submitted']) ?></span>
        </div>

        <div class="progress-step <?= in_array($status, ['instruction', 'accepte', 'refuse'], true) ? 'active' : '' ?>">
            <div class="progress-icon"><img src="img/rafraichir.png" alt="Instruction"></div>
            <div class="progress-circle"></div>
            <span><?= $t(['fr' => 'Instruction en cours', 'en' => 'Under Review']) ?></span>
        </div>

        <div class="progress-step <?= in_array($status, ['accepte', 'refuse'], true) ? 'active' : '' ?>">
            <div class="progress-icon"><img src="img/decision.png" alt="Décision"></div>
            <div class="progress-circle"></div>
            <span><?= $t(['fr' => 'Décision prise', 'en' => 'Decision Made']) ?></span>
        </div>
    </div>

    <div class="contact-info-box">
        <p class="contact-title"><?= $t(['fr' => 'Une question ou besoin d\'assistance ?', 'en' => 'A question or need assistance?']) ?></p>
        <p><?= $t(['fr' => 'Pour toute information complémentaire...', 'en' => 'For any additional information...']) ?></p>
        <p class="contact-email">
            <a href="mailto:relations.internationale@amu-univ.fr">relations.internationale@amu-univ.fr</a>
        </p>
    </div>

<div id="app-config"
     data-lang="<?= htmlspecialchars($lang) ?>"
     data-role="student"
     style="display:none;">
</div>
<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Tableau de bord (Étudiant)', 'en' => 'Student Dashboard']);
$styles = ['styles/dashboard.css', 'styles/index.css', 'styles/chatbot.css'];
$scripts = [];
$activeMenu = 'dashboard';
$userRole = 'student';

include __DIR__ . '/../Layout/base.php';