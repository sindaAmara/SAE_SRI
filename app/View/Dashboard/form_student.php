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

    <p class="form-notice">
        <?= $t([
                'fr' => 'Attention : ce formulaire ne doit être rempli qu’à la fin de votre stage.',
                'en' => 'Please note: this form should only be completed at the end of your internship.'
        ]) ?>
    </p>

    <form method="post" action="index.php?page=stage-form&action=store" class="stage-form">

        <div class="form-group">
            <label for="host_institution">
                <?= $t(['fr' => "Établissement d'accueil", 'en' => 'Host institution']) ?>
            </label>
            <input type="text" id="host_institution" name="host_institution"
                   value="<?= htmlspecialchars($old['host_institution'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="start_date"><?= $t(['fr' => 'Date de début', 'en' => 'Start date']) ?></label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= htmlspecialchars($old['start_date'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="end_date"><?= $t(['fr' => 'Date de fin', 'en' => 'End date']) ?></label>
            <input type="date" id="end_date" name="end_date"
                   value="<?= htmlspecialchars($old['end_date'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="evaluation">
                <?= $t(['fr' => "Évaluation de l'expérience", 'en' => 'Evaluation of the experience']) ?>
            </label>
            <textarea id="evaluation" name="evaluation" rows="6"><?= htmlspecialchars($old['evaluation'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit">
                <?= !empty($old)
                        ? $t(['fr' => 'Mettre à jour', 'en' => 'Update'])
                        : $t(['fr' => 'Envoyer', 'en' => 'Submit']) ?>
            </button>
        </div>

    </form>

<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Formulaire de fin de stage', 'en' => 'End-of-internship form']);
$styles = ['styles/dashboard.css', 'styles/index.css'];
$scripts = [];
$activeMenu = 'dashboard';
$userRole = 'student';

include __DIR__ . '/../Layout/base.php';