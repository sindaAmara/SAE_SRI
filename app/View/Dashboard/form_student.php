<?php
/**
 * View: Student Dashboard — End-of-Internship Form
 *
 * @var string $lang
 * @var Closure(array<string,string>): string $t
 * @var array<string, mixed> $folder
 * @var array<string, mixed> $errors  Validation errors keyed by field name (empty if none)
 * @var array<string, mixed> $old     Previously submitted values on validation failure
 */

ob_start();
?>

    <h1><?= $t(['fr' => 'Formulaire de fin de stage', 'en' => 'End-of-internship form']) ?></h1>

    <form method="post" action="index.php?page=stage-form&action=store">

        <label for="host_institution">
            <?= $t(['fr' => "Établissement d'accueil", 'en' => 'Host institution']) ?>
        </label>
        <input type="text" id="host_institution" name="host_institution"
               value="<?= htmlspecialchars($old['host_institution'] ?? '') ?>" required>

        <label for="start_date"><?= $t(['fr' => 'Date de début', 'en' => 'Start date']) ?></label>
        <input type="date" id="start_date" name="start_date"
               value="<?= htmlspecialchars($old['start_date'] ?? '') ?>" required>

        <label for="end_date"><?= $t(['fr' => 'Date de fin', 'en' => 'End date']) ?></label>
        <input type="date" id="end_date" name="end_date"
               value="<?= htmlspecialchars($old['end_date'] ?? '') ?>" required>

        <label for="evaluation">
            <?= $t(['fr' => "Évaluation de l'expérience", 'en' => 'Evaluation of the experience']) ?>
        </label>
        <textarea id="evaluation" name="evaluation" rows="6"><?= htmlspecialchars($old['evaluation'] ?? '') ?></textarea>

        <button type="submit"><?= $t(['fr' => 'Envoyer', 'en' => 'Submit']) ?></button>
    </form>

<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Formulaire de fin de stage', 'en' => 'End-of-internship form']);
$styles = ['styles/dashboard.css', 'styles/index.css', 'styles/form.css'];
$scripts = [];
$activeMenu = 'dashboard';
$userRole = 'student';

include __DIR__ . '/../Layout/base.php';