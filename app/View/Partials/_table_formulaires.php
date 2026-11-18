<?php
/**
 * Partial: End-of-internship form table grouped by component
 *
 * @var array<int, array<string, mixed>> $paginatedData Students with 'form_response' / 'form_submitted' keys
 * @var int                              $totalCount
 * @var Closure                          $t
 * @var Closure                          $buildUrl
 */
?>
<p class="results-count">
    <?= $totalCount ?> <?= $t(['fr' => 'étudiant(s) trouvé(s)', 'en' => 'student(s) found']) ?>
</p>

<?php
$groupedData = [];
foreach ($paginatedData as $etudiant) {
    $comp = strval($etudiant['Composante'] ?? '');
    if (empty($comp) || $comp === '-') {
        $comp = $t(['fr' => 'Autre / Non assignée', 'en' => 'Other / Unassigned']);
    }
    $groupedData[$comp][] = $etudiant;
}
?>

<div class="conteneur-composantes">
    <?php foreach ($groupedData as $compName => $students) : ?>
        <?php $safeCompId = md5(strval($compName)); ?>
        <div class="section-composante">
            <div class="barre-titre" data-target="dossiers-<?= $safeCompId ?>">
                <span><?= htmlspecialchars(strval($compName)) ?> (<?= count($students) ?>)</span>
                <span class="fleche">▼</span>
            </div>

            <div id="dossiers-<?= $safeCompId ?>" class="contenu-dossiers">
                <table class="table-etudiants">
                    <thead><tr>
                        <th><?= $t(['fr' => 'Nom', 'en' => 'Last Name']) ?></th>
                        <th><?= $t(['fr' => 'Prénom', 'en' => 'First Name']) ?></th>
                        <th><?= $t(['fr' => 'Type', 'en' => 'Type']) ?></th>
                        <th><?= $t(['fr' => 'Département', 'en' => 'Department']) ?></th>
                        <th><?= $t(['fr' => 'Formulaire', 'en' => 'Form']) ?></th>
                        <th><?= $t(['fr' => "Établissement d'accueil", 'en' => 'Host institution']) ?></th>
                        <th><?= $t(['fr' => 'Dates', 'en' => 'Dates']) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($students as $etudiant) : ?>
                        <?php
                        $response = $etudiant['form_response'] ?? null;
                        $eNumEtu  = strval($etudiant['NumEtu'] ?? '');
                        $eType    = strval($etudiant['Type'] ?? '');
                        $rowUrl   = $buildUrl('index.php', ['page' => 'folders-admin', 'action' => 'view', 'numetu' => $eNumEtu]);
                        ?>
                        <tr class="clickable-row" onclick="window.location.href='<?= $rowUrl ?>'">
                            <td><?= htmlspecialchars(strval($etudiant['Nom'] ?? '')) ?></td>
                            <td><?= htmlspecialchars(strval($etudiant['Prenom'] ?? '')) ?></td>
                            <td><?= $t(['fr' => ($eType === 'entrant' ? 'Entrant' : 'Sortant'), 'en' => ($eType === 'entrant' ? 'Incoming' : 'Outgoing')]) ?></td>
                            <td><?= htmlspecialchars(strval($etudiant['CodeDepartement'] ?? '-')) ?></td>
                            <td>
                                <?= !empty($etudiant['form_submitted'])
                                    ? $t(['fr' => '✅ Soumis', 'en' => '✅ Submitted'])
                                    : $t(['fr' => '⏳ En attente', 'en' => '⏳ Pending']) ?>
                            </td>
                            <td><?= htmlspecialchars(strval($response['host_institution'] ?? '—')) ?></td>
                            <td>
                                <?= $response
                                    ? htmlspecialchars($response['start_date']) . ' → ' . htmlspecialchars($response['end_date'])
                                    : '—' ?>
                            </td>
                            <td class="eval-toggle-cell">
                                <button type="button" class="eval-toggle"
                                        onclick="event.stopPropagation(); toggleEval(this);"
                                        aria-expanded="false">▼</button>
                            </td>
                        </tr>
                        <tr class="eval-detail-row" style="display:none;">
                            <td colspan="8">
                                <strong><?= $t(['fr' => 'Évaluation', 'en' => 'Evaluation']) ?></strong>
                                <div class="eval-full">
                                    <?php if ($response && !empty($response['evaluation'])) : ?>
                                        <?= nl2br(htmlspecialchars($response['evaluation'])) ?>
                                    <?php else : ?>
                                        <?= $t(['fr' => 'Aucune évaluation disponible.', 'en' => 'No evaluation available.']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination accordion-pagination"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
