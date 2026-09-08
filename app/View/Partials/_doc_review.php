<?php
/**
 * Partial: Supporting document review panel
 *
 * Renders a review card for each of the five expected document types:
 * photo, CV, internship agreement (convention), motivation letter, and
 * language certificate (langues). For each document the partial shows:
 *
 * - A download link when a file is present (base64 data URI), or a
 *   "Not provided" notice otherwise.
 * - An optional file upload input, shown when $languesEditable is true
 *   (language certificate only) or when $allFilesEditable is true (all types).
 *   Accepted inputs are disabled for already-accepted documents.
 * - Accept / Refuse radio buttons and a comment textarea, both disabled
 *   when no file has been submitted.
 * - A per-document "Save" button that calls
 *   window.folderManager.confirmDocument() via JavaScript, with an
 *   indicator span for async feedback.
 *
 * Documents whose status is 'accepted' display a badge and an undo button
 * that re-enables editing by adding the 'editing' CSS class to the card.
 *
 * @var callable                                                          $t              Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var string                                                            $numEtu         Student number used in download filenames and JavaScript calls
 * @var array<string, array{file?: string, status?: string, comment?: string}> $pieces   Map of document type key to its stored file (base64), status, and admin comment
 * @var array<string, string>                                             $statuts        Map of document type key to its current validation status (overrides $pieces status when present)
 */
$languesEditable = $languesEditable ?? false;

$docTypes = [
    'photo'             => $t(['fr' => 'Photo',                  'en' => 'Photo']),
    'cv'                => $t(['fr' => 'CV',                     'en' => 'CV']),
    'convention'        => $t(['fr' => 'Convention de stage',    'en' => 'Internship Agreement']),
    'lettre_motivation' => $t(['fr' => 'Lettre de motivation',   'en' => 'Motivation Letter']),
    'langues'           => $t(['fr' => 'Attestation de langues', 'en' => 'Language Certificate']),
];
?>


<div class="doc-review-list">
    <?php foreach ($docTypes as $key => $label) :
        $doc        = $pieces[$key] ?? null;
        $hasDoc     = !empty($doc['file']);
        $statut     = $statuts[$key] ?? ($doc['status'] ?? 'pending');
        $comment    = $doc['comment'] ?? '';
        $isAccepted = ($statut === 'accepted');
        $isRefused  = ($statut === 'refused');
        ?>
        <div class="doc-review-item <?= $isAccepted ? 'is-accepted' : '' ?>" data-doctype="<?= $key ?>">
            <div class="doc-info">
                <h4><?= $label ?></h4>
                <?php if ($hasDoc) : ?>
                    <a href="data:application/octet-stream;base64,<?= strval($doc['file']) ?>"
                       download="<?= $key ?>_<?= $numEtu ?>" class="btn-download">
                        <?= $t(['fr' => 'Télécharger le fichier', 'en' => 'Download file']) ?>
                    </a>
                <?php else : ?>
                    <span class="no-document"><?= $t(['fr' => 'Non fourni', 'en' => 'Not provided']) ?></span>
                <?php endif; ?>

                <?php if ($key === 'langues' && $languesEditable) : ?>
                    <br><br>
                    <label class="update-file-label"><?= $t(['fr' => "Mettre à jour l'attestation :", 'en' => 'Update certificate:']) ?></label>
                    <input type="file" name="langues_file" accept=".pdf,.doc,.docx,.jpg,.png" class="file-input-margin">
                <?php elseif (isset($allFilesEditable) && $allFilesEditable) : ?>
                    <br><br>
                    <label class="update-file-label"><?= $t(['fr' => 'Ajouter / Mettre à jour le fichier :', 'en' => 'Add / Update file:']) ?></label>
                    <input type="file"
                           name="<?= $key === 'langues' ? 'langues_file' : $key ?>"
                           accept="<?= $key === 'photo' ? 'image/*' : '.pdf,.doc,.docx' ?>"
                           class="file-input-margin <?= $isAccepted ? 'input-disabled' : '' ?>"
                           <?= $isAccepted ? 'disabled' : '' ?>>
                <?php endif; ?>
            </div>

            <div class="doc-actions <?= (!$hasDoc) ? 'disabled-area' : '' ?>">

                <?php if ($isAccepted) : ?>
                    <div class="doc-status-shown">
                        <span class="doc-status-badge accepted">✅ <?= $t(['fr' => 'Acceptée', 'en' => 'Accepted']) ?></span>
                        <button type="button" class="btn-undo-accept"
                                onclick="document.querySelector('.doc-review-item[data-doctype=\'<?= $key ?>\']').classList.add('editing')">
                            ↩ <?= $t(['fr' => 'Modifier', 'en' => 'Change']) ?>
                        </button>
                    </div>
                <?php elseif ($isRefused) : ?>
                    <span class="doc-status-badge refused">❌ <?= $t(['fr' => 'Refusée', 'en' => 'Refused']) ?></span>
                <?php endif; ?>

                <div class="status-radios-wrap">
                    <div class="status-radios">
                        <label class="radio-accept">
                            <input type="radio" name="status_<?= $key ?>" value="accepted"
                                <?= ($isAccepted || $statut === 'pending') ? 'checked' : '' ?>
                                <?= !$hasDoc ? 'disabled' : '' ?>>
                            <?= $t(['fr' => 'Accepter', 'en' => 'Accept']) ?>
                        </label>
                        <label class="radio-refuse">
                            <input type="radio" name="status_<?= $key ?>" value="refused"
                                <?= $isRefused ? 'checked' : '' ?>
                                <?= !$hasDoc ? 'disabled' : '' ?>>
                            <?= $t(['fr' => 'Refuser', 'en' => 'Refuse']) ?>
                        </label>
                    </div>
                </div>

                <textarea name="comment_<?= $key ?>"
                          placeholder="<?= $t(['fr' => "Ajouter un commentaire pour l'étudiant...", 'en' => 'Add a comment for the student...']) ?>"
                          <?= !$hasDoc ? 'disabled' : '' ?>><?= htmlspecialchars($comment) ?></textarea>

                <div class="doc-confirm-wrapper">
                    <button type="button" class="btn-confirm-doc"
                            data-numetu="<?= $numEtu ?>"
                            data-doctype="<?= $key ?>"
                            onclick="window.folderManager.confirmDocument('<?= $numEtu ?>', '<?= $key ?>')"
                        <?= !$hasDoc ? 'disabled' : '' ?>>
                        <?= $t(['fr' => 'Enregistrer', 'en' => 'Save']) ?>
                    </button>
                    <span class="doc-save-indicator" id="indicator_<?= $key ?>"></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>