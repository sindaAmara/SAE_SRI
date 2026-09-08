<?php
/**
 * Partial: Global folder status section (dropdown + update button)
 *
 * Renders a select element pre-set to the folder's current workflow status.
 * The available statuses are: 'depot' (Submitted), 'instruction' (Under Review),
 * 'accepte' (Accepted), and 'refuse' (Refused).
 *
 * The select is disabled and the update button is hidden for non-admin roles.
 * For admins, clicking the button triggers window.folderManager.updateGlobalStatus()
 * via JavaScript to persist the new status asynchronously, with a status indicator
 * span (#global_status_indicator) available for feedback.
 *
 * The active role is resolved from $_SESSION['role'] with $userRole as a fallback.
 *
 * @var string  $numEtu        Student number passed to the JavaScript update call
 * @var string  $currentStatus Current global workflow status of the folder
 * @var Closure $t             Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var string  $userRole      Role of the current user, used as a fallback when the session role is absent
 */

$isAdmin = (($_SESSION['role'] ?? $userRole) === 'admin');

$statusLabels = [
    'depot'       => $t(['fr' => 'Dépôt',          'en' => 'Submitted']),
    'instruction' => $t(['fr' => 'En instruction', 'en' => 'Under Review']),
    'accepte'     => $t(['fr' => 'Accepté',        'en' => 'Accepted']),
    'refuse'      => $t(['fr' => 'Refusé',         'en' => 'Refused']),
];
?>
<div class="form-section global-status-section full-width">
    <div class="global-status-block">
        <strong class="global-status-title"><?= $t(['fr' => 'Statut GLOBAL du dossier :', 'en' => 'GLOBAL Folder status:']) ?></strong>
        <div class="global-status-controls">
            <select id="global_status_select" class="global-status-select" <?= !$isAdmin ? 'disabled' : '' ?>>
                <option value="depot"       <?= $currentStatus === 'depot'       ? 'selected' : '' ?>><?= $statusLabels['depot']       ?></option>
                <option value="instruction" <?= $currentStatus === 'instruction' ? 'selected' : '' ?>><?= $statusLabels['instruction'] ?></option>
                <option value="accepte"     <?= $currentStatus === 'accepte'     ? 'selected' : '' ?>><?= $statusLabels['accepte']     ?></option>
                <option value="refuse"      <?= $currentStatus === 'refuse'      ? 'selected' : '' ?>><?= $statusLabels['refuse']      ?></option>
            </select>
            <?php if ($isAdmin) : ?>
                <button type="button" class="btn-secondary"
                        onclick="window.folderManager.updateGlobalStatus('<?= $numEtu ?>')">
                    <?= $t(['fr' => 'Mettre à jour le statut', 'en' => 'Update Status']) ?>
                </button>
                <span id="global_status_indicator" class="status-indicator"></span>
            <?php endif; ?>
        </div>
    </div>
</div>