<?php
/**
 * View: Admin — End-of-Internship Form Responses
 *
 * @var string $lang
 * @var Closure(array<string,string>): string $t
 * @var array<int, array<string, mixed>> $responses
 */

ob_start();
?>

    <h1><?= $t(['fr' => 'Réponses aux formulaires de fin de stage', 'en' => 'End-of-internship form responses']) ?></h1>

<?php if (empty($responses)): ?>
    <p><?= $t(['fr' => 'Aucune réponse pour le moment.', 'en' => 'No responses yet.']) ?></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
        <tr>
            <th><?= $t(['fr' => 'Étudiant', 'en' => 'Student']) ?></th>
            <th><?= $t(['fr' => 'N° étudiant', 'en' => 'Student number']) ?></th>
            <th><?= $t(['fr' => "Établissement d'accueil", 'en' => 'Host institution']) ?></th>
            <th><?= $t(['fr' => 'Dates', 'en' => 'Dates']) ?></th>
            <th><?= $t(['fr' => 'Évaluation', 'en' => 'Evaluation']) ?></th>
            <th><?= $t(['fr' => 'Soumis le', 'en' => 'Submitted on']) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($responses as $r): ?>
            <tr>
                <td><?= htmlspecialchars(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? '')) ?></td>
                <td><?= htmlspecialchars($r['numetu'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['host_institution'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['start_date'] ?? '') ?> → <?= htmlspecialchars($r['end_date'] ?? '') ?></td>
                <td><?= nl2br(htmlspecialchars($r['evaluation'] ?? '')) ?></td>
                <td><?= htmlspecialchars($r['submitted_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Réponses formulaires de stage', 'en' => 'Stage form responses']);
$styles = ['styles/dashboard.css', 'styles/index.css'];
$scripts = [];
$activeMenu = 'dashboard';
$userRole = 'admin';

include __DIR__ . '/../Layout/base.php';