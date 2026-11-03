<?php
/**
 * View: Admin — End-of-Internship Form Responses
 *
 * @var string $lang
 * @var Closure(array<string,string>): string $t
 * @var Closure(string): string $buildUrl
 * @var string $studentFilter
 * @var array<int, array<string, mixed>> $incoming
 * @var array<int, array<string, mixed>> $outgoing
 */

ob_start();

$renderTable = function (array $folders) use ($t, $lang): void {
    if (empty($folders)) {
        echo '<p>' . $t(['fr' => 'Aucun étudiant.', 'en' => 'No students.']) . '</p>';
        return;
    }
    ?>
    <table class="admin-table">
        <thead>
        <tr>
            <th><?= $t(['fr' => 'Étudiant', 'en' => 'Student']) ?></th>
            <th><?= $t(['fr' => 'Statut formulaire', 'en' => 'Form status']) ?></th>
            <th><?= $t(['fr' => "Établissement d'accueil", 'en' => 'Host institution']) ?></th>
            <th><?= $t(['fr' => 'Dates', 'en' => 'Dates']) ?></th>
            <th><?= $t(['fr' => 'Soumis le', 'en' => 'Submitted on']) ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($folders as $d):
            $response = $d['form_response'] ?? null;
            $numEtu    = strval($d['NumEtu'] ?? '');
            $detailUrl = "index.php?page=folders-admin&action=view&numetu=" . urlencode($numEtu) . "&lang=" . urlencode($lang);
            ?>

            <tr onclick="window.location.href='<?= $detailUrl ?>'" class="clickable-row">
                <td>
                    <strong><?= htmlspecialchars(strval($d['Nom'] ?? '') . ' ' . strval($d['Prenom'] ?? '')) ?></strong>
                    <br><small><?= htmlspecialchars($numEtu) ?></small>
                </td>
                <td>
                    <?= !empty($d['form_submitted'])
                            ? $t(['fr' => '✅ Soumis', 'en' => '✅ Submitted'])
                            : $t(['fr' => '⏳ En attente', 'en' => '⏳ Pending']) ?>
                </td>
                <td><?= htmlspecialchars($response['host_institution'] ?? '—') ?></td>
                <td>
                    <?= $response
                            ? htmlspecialchars($response['start_date']) . ' → ' . htmlspecialchars($response['end_date'])
                            : '—' ?>
                </td>
                <td><?= htmlspecialchars($response['submitted_at'] ?? '—') ?></td>
                <td class="eval-toggle-cell">
                    <button type="button" class="eval-toggle"
                        onclick="event.stopPropagation(); toggleEval(this);"
                        aria-expanded="false"
                        title="<?= $t(['fr' => "Voir l'évaluation", 'en' => 'Show evaluation']) ?>">▼</button>
                </td>
            <tr class="eval-detail-row" style="display:none;">
                <td colspan="6">
                    <strong><?= $t(['fr' => 'Évaluation', 'en' => 'Evaluation']) ?></strong>
                    <div class="eval-full">
                        <?php if ($response && !empty($response['evaluation'])): ?>
                            <?= nl2br(htmlspecialchars($response['evaluation'])) ?>
                        <?php else: ?>
                            <?= $t(['fr' => 'Aucune évaluation disponible.', 'en' => 'No evaluation available.']) ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
};
?>

    <h1 class="suivi-global"> <?= $t(['fr' => 'Réponses aux formulaires de fin de stage', 'en' => 'End-of-internship form responses']) ?></h1>

    <form method="get" action="index.php" class="filter-form">
        <input type="hidden" name="page" value="stage-form-admin">
        <input type="text" name="student"
               placeholder="<?= $t(['fr' => 'Rechercher un étudiant...', 'en' => 'Search a student...']) ?>"
               value="<?= htmlspecialchars($studentFilter) ?>">
        <button type="submit"><?= $t(['fr' => 'Filtrer', 'en' => 'Filter']) ?></button>
    </form>

    <h2><?= $t(['fr' => 'Étudiants sortants', 'en' => 'Outgoing students']) ?></h2>
<?php $renderTable($outgoing); ?>

    <h2><?= $t(['fr' => 'Étudiants entrants', 'en' => 'Incoming students']) ?></h2>
<?php $renderTable($incoming); ?>

    <script>
        function toggleEval(btn) {
            const detailRow = btn.closest('tr').nextElementSibling;
            const open = detailRow.style.display === 'none';
            detailRow.style.display = open ? 'table-row' : 'none';
            btn.textContent = open ? '▲' : '▼';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    </script>

<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Réponses formulaires de stage', 'en' => 'Stage form responses']);
$styles = ['styles/folders.css', 'styles/dashboard.css', 'styles/index.css', 'styles/chatbot.css'];
$scripts = [];
$activeMenu = 'dashboard';
$userRole = 'admin';

include __DIR__ . '/../Layout/base.php';