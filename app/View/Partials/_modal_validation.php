<?php
/**
 * Partial: Folder validation modal
 *
 * Renders a modal overlay (#modal-validation) that presents a summary of the
 * document review before the admin submits the final validation. The modal
 * contains three dynamically populated sections (populated via JavaScript
 * before the modal is opened):
 *
 * - #section-modifications: list of changes made during the current session
 *   (hidden by default, shown by JS when changes exist).
 * - #section-manquants: list of missing supporting documents.
 * - #section-presents: list of documents ready to be validated.
 *
 * Submitting the form posts to index.php?page=valider_documents, which saves
 * the document statuses and triggers student notification. The Cancel button
 * (#btn-modal-cancel) is wired up by the parent page's JavaScript to close
 * the modal without submitting.
 *
 * @var string  $numEtu       Already htmlspecialchars-encoded student number, embedded in the hidden form field
 * @var string  $redirectPage Page identifier placed in the hidden redirect_to field; controls where the controller redirects after saving
 * @var string  $lang         Current language code appended to the form action URL
 * @var Closure $t            Translation callable — accepts ['fr' => '...', 'en' => '...']
 */
?>
<div id="modal-validation" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?= $t(['fr' => '📋 Validation du dossier', 'en' => '📋 Folder Validation']) ?></h2>
        </div>
        <form id="form-validation" method="POST" action="index.php?page=valider_documents&lang=<?= htmlspecialchars($lang) ?>">
            <input type="hidden" name="numetu"      value="<?= $numEtu ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectPage) ?>">

            <div class="modal-section" id="section-modifications" style="display:none;">
                <h3>📝 <?= $t(['fr' => 'Modifications effectuées', 'en' => 'Changes Made']) ?></h3>
                <div class="modifications-list"><ul id="liste-modifications"></ul></div>
            </div>

            <div class="modal-section" id="section-manquants">
                <h3>⚠️ <?= $t(['fr' => 'Documents manquants', 'en' => 'Missing Documents']) ?></h3>
                <div id="liste-manquants"></div>
            </div>

            <div class="modal-section" id="section-presents">
                <h3>✅ <?= $t(['fr' => 'Documents à valider', 'en' => 'Documents to Validate']) ?></h3>
                <div id="liste-presents"></div>
            </div>


            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-cancel" id="btn-modal-cancel">
                    <?= $t(['fr' => 'Annuler', 'en' => 'Cancel']) ?>
                </button>
                <button type="submit" class="btn-modal btn-modal-submit">
                    <?= $t(['fr' => '✉️ Enregistrer et notifier', 'en' => '✉️ Save and Notify']) ?>
                </button>
            </div>
        </form>
    </div>
</div>