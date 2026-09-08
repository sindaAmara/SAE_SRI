<?php
/**
 * Partial: Student list table grouped by component (composante)
 *
 * Groups the paginated student records by their Composante value and renders
 * one collapsible accordion section per group. Students whose Composante is
 * empty or '-' are grouped under a fallback "Other / Unassigned" label.
 *
 * Each group header shows the component name and student count. Each student
 * row is clickable and navigates to the folder detail view for that student,
 * with the URL built by the $buildUrl closure.
 *
 * Each row displays: last name, first name, mobility direction (incoming/outgoing),
 * component, department, mobility type (stage/études), reminder badge count,
 * and a colour-coded status badge (depot / instruction / accepte / refuse).
 *
 * @var array<int, array<string, mixed>> $paginatedData  Current page of student records from the repository
 * @var int                              $totalCount     Total number of matching students (across all pages)
 * @var string                           $viewPage       Page identifier used when building row click URLs (e.g. 'coordinateur-stage')
 * @var string                           $lang           Current language code, passed to $buildUrl
 * @var Closure                          $t              Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure                          $buildUrl       URL builder callable — accepts a base URL string and an array of query parameters
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
                        <th><?= $t(['fr' => 'Nom',                 'en' => 'Last Name'])             ?></th>
                        <th><?= $t(['fr' => 'Prénom',              'en' => 'First Name'])            ?></th>
                        <th><?= $t(['fr' => 'Type',                'en' => 'Type'])                  ?></th>
                        <th><?= $t(['fr' => 'Composante',          'en' => 'Component'])             ?></th>
                        <th><?= $t(['fr' => 'Département',         'en' => 'Department'])            ?></th>
                        <th><?= $t(['fr' => 'Mobilité',            'en' => 'Mobility'])              ?></th>
                        <th><?= $t(['fr' => 'Relances',            'en' => 'Reminders'])             ?></th>
                        <th><?= $t(['fr' => 'Statut',              'en' => 'Status'])                ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($students as $etudiant) : ?>
                        <?php
                        $rawPieces    = strval($etudiant['PiecesJustificatives'] ?? '{}');
                        $decoded      = json_decode($rawPieces, true);
                        $ePieces      = is_array($decoded) ? $decoded : [];
                        $dbMobilite = strtolower(trim(strval($etudiant['Mobilite'] ?? '')));
                        if ($dbMobilite === 'stage') {
                            $mobilityType = $t(['fr' => 'Stage', 'en' => 'Internship']);
                        } elseif ($dbMobilite === 'etude' || $dbMobilite === 'etudes') {
                            $mobilityType = $t(['fr' => 'Études', 'en' => 'Studies']);
                        } else {
                            $mobilityType = '-';
                        }
                        $eNumEtu      = strval($etudiant['NumEtu']          ?? '');
                        $eNom         = strval($etudiant['Nom']             ?? '');
                        $ePrenom      = strval($etudiant['Prenom']          ?? '');
                        $eType        = strval($etudiant['Type']            ?? '');
                        $eComposante  = strval($etudiant['Composante']      ?? '-');
                        $eDepartement = strval($etudiant['CodeDepartement'] ?? '-');
                        $nbRelances = isset($etudiant['nb_relances']) && is_numeric($etudiant['nb_relances']) 
                                    ? (int)$etudiant['nb_relances'] 
                                    : 0;
                        $eStatus      = strval($etudiant['status']          ?? 'depot');
                        $rowUrl       = $buildUrl('index.php', ['page' => $viewPage, 'action' => 'view', 'numetu' =>$eNumEtu]);
                        ?>
                        <tr class="clickable-row"
                            data-numetu="<?= htmlspecialchars($eNumEtu) ?>"
                            onclick="window.location.href='<?= $rowUrl ?>'">
                            <td><?= htmlspecialchars($eNom) ?></td>
                            <td><?= htmlspecialchars($ePrenom) ?></td>
                            <td><?= $t(['fr' => ($eType === 'entrant' ? 'Entrant' : 'Sortant'), 'en' => ($eType === 'entrant' ? 'Incoming' : 'Outgoing')]) ?></td>
                            <td><?= htmlspecialchars($eComposante  ?: '-') ?></td>
                            <td><?= htmlspecialchars($eDepartement ?: '-') ?></td>
                            <td><?= htmlspecialchars($mobilityType) ?></td>
                            <td style="text-align: center;">
                                <?php if ($nbRelances > 0) : ?>
                                    <span class="badge-relance"><?= $nbRelances ?></span>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($eStatus === 'accepte') : ?>
                                    <span class="status-badge accepte">Accepté</span>
                                <?php elseif ($eStatus === 'refuse') : ?>
                                    <span class="status-badge refuse">Refusé</span>
                                <?php elseif ($eStatus === 'instruction') : ?>
                                    <span class="status-badge instruction">En instruction</span>
                                <?php else : ?>
                                    <span class="status-badge depot">Dépôt</span>
                                <?php endif; ?>
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