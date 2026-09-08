<?php
/**
 * Partial: Quick-decision banner (department head only)
 *
 * Renders two POST form buttons that allow the department head to record their
 * opinion on a student folder directly from the folder view:
 * - "Accept" sets avis_chef_departement to 'accepte'
 * - "Refuse" sets avis_chef_departement to 'refuse'
 *
 * The active button (matching $currentStatus) receives the CSS class
 * 'btn-decision-active'. Both forms post to the chef-departement page with
 * the set_avis_chef action. A status indicator span (#decision_indicator)
 * is available for JavaScript feedback.
 *
 * @var string  $numEtu        Student number used to identify the folder and build form action URLs
 * @var string  $currentStatus Current value of avis_chef_departement: 'accepte', 'refuse', or ''
 * @var string  $lang          Current language code, appended to form action URLs
 * @var Closure $t             Translation callable — accepts ['fr' => '...', 'en' => '...']
 */

$numEtuRaw = strval($studentData['NumEtu'] ?? '');
?>
<div class="banniere-decision">
    <span class="banniere-decision-label"><?= $t(['fr' => 'Décision sur le dossier :', 'en' => 'Decision on profile:']) ?></span>
    <div class="banniere-decision-buttons">

        <!-- Bouton Accepter -->
        <form method="POST" action="index.php?page=chef-departement&action=view&numetu=<?= urlencode($numEtuRaw) ?>&lang=<?= htmlspecialchars($lang) ?>" style="display:inline;">
            <input type="hidden" name="set_avis_chef" value="1">
            <input type="hidden" name="numetu" value="<?= htmlspecialchars($numEtuRaw) ?>">
            <input type="hidden" name="avis" value="accepte">
            <button type="submit"
                    class="btn-decision btn-accepter <?= $currentStatus === 'accepte' ? 'btn-decision-active' : '' ?>">
                ✅ <?= $t(['fr' => 'Accepter le dossier', 'en' => 'Accept Profile']) ?>
            </button>
        </form>

        <!-- Bouton Refuser -->
        <form method="POST" action="index.php?page=chef-departement&action=view&numetu=<?= urlencode($numEtuRaw) ?>&lang=<?= htmlspecialchars($lang) ?>" style="display:inline;">
            <input type="hidden" name="set_avis_chef" value="1">
            <input type="hidden" name="numetu" value="<?= htmlspecialchars($numEtuRaw) ?>">
            <input type="hidden" name="avis" value="refuse">
            <button type="submit"
                    class="btn-decision btn-refuser <?= $currentStatus === 'refuse' ? 'btn-decision-active' : '' ?>">
                ❌ <?= $t(['fr' => 'Refuser le dossier', 'en' => 'Refuse Profile']) ?>
            </button>
        </form>

        <span id="decision_indicator" class="status-indicator"></span>
    </div>
</div>