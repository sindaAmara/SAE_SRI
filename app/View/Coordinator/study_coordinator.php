<?php
/**
 * View: Study Coordinator — Student List / Folder Detail
 *
 * Dual-mode view for the coordinateur_etude role, controlled by $action.
 * Structurally identical to internship_coordinator.php with two differences:
 * - The list mode title is "Study Mobility Students" and the page identifier
 *   is 'coordinateur-etude' (vs. 'coordinateur-stage').
 * - The $hasActiveFilters check does not include date_debut / date_fin
 *   (only type, zone, complet, composante, search are evaluated).
 *
 * ── LIST MODE ($action !== 'view') ──────────────────────────────────────────
 * Displays only study-mobility students. Provides a text search toolbar and
 * a filter bar via _filters.php ($showAccordFilter = false). The student table
 * is rendered by _table_etudiants.php, grouped by component.
 *
 * ── DETAIL MODE ($action === 'view') ────────────────────────────────────────
 * Renders a partially editable folder form for a single student.
 * When $studentData is null, a "Student not found" notice is shown.
 * Otherwise the following sections are composed:
 *
 * - _banniere_date_limite.php : submission deadline banner ($redirectPage = $PAGE).
 * - _form_fields.php          : folder form with only 'niveau_etude' and
 *   'moyenne_sans_bac' editable ($EDITABLE list, $allEditable = false).
 * - _doc_review.php           : document review panel with $languesEditable = true.
 * - _global_status.php        : global workflow status dropdown + update button.
 * - _modal_validation.php     : confirmation modal for save-and-notify.
 *
 * The form posts to index.php?page=update_student with a hidden redirect_to
 * field set to $PAGE. Mobility type is detected from the presence of
 * convention vs. motivation-letter files in $pieces.
 *
 * The user role falls back to $_SESSION['role'] (defaults to
 * 'coordinateur_etude'). The rendered HTML is passed to the base layout
 * with styles (index.css, folders.css, chatbot.css, coordinators_extra.css)
 * and scripts (folders.js).
 *
 * @var string                           $lang          Current language code (e.g. 'fr' or 'en')
 * @var Closure                          $t             Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure                          $buildUrl      URL builder callable — accepts a base URL and an optional query-parameter array
 * @var array<string, mixed>             $filters       Current active filter values (type, zone, complet, composante, search)
 * @var array<int, array<string, mixed>> $paginatedData Current page of student records for the table partial
 * @var int                              $totalCount    Total number of matching students across all pages
 * @var array<string, mixed>|null        $studentData   Full folder data from the repository (including 'pieces', 'statuts', metadata); null when not found
 * @var string                           $action        View mode: 'view' for the detail form, any other value for the list
 * @var string                           $message       Optional feedback message displayed at the top of either mode (may be empty)
 */

$PAGE     = 'coordinateur-etude';
$EDITABLE = ['niveau_etude', 'moyenne_sans_bac'];

$hasActiveFilters = (strval($filters['type']       ?? 'all')) !== 'all'
    || (strval($filters['zone']       ?? 'all')) !== 'all'
    || (strval($filters['complet']    ?? 'all')) !== 'all'
    || (strval($filters['composante'] ?? 'all')) !== 'all'
    || !empty($filters['search']);

ob_start();
?>

<?php if ($action === 'view') : ?>

    <?php if (!$studentData) : ?>
        <p><?= $t(['fr' => 'Étudiant non trouvé', 'en' => 'Student not found']) ?></p>
    <?php else : ?>
        <?php
        $pieces        = (isset($studentData['pieces'])  && is_array($studentData['pieces']))  ? $studentData['pieces']  : [];
        $statuts       = (isset($studentData['statuts']) && is_array($studentData['statuts'])) ? $studentData['statuts'] : [];
        $detectedType  = !empty($pieces['convention']['file']) ? 'stage' : (!empty($pieces['lettre_motivation']['file']) ? 'etudes' : '');
        $numEtu        = htmlspecialchars(strval($studentData['NumEtu'] ?? ''));
        $dateLimite    = $studentData['DateLimite'] ?? null;
        $currentStatus = $studentData['status'] ?? 'depot';
        ?>

        <h1><?= $t(['fr' => 'Dossier étudiant', 'en' => 'Student Profile']) ?></h1>
        <div class="form-back-button">
            <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => $PAGE]) ?>'" class="btn-secondary">
                <?= $t(['fr' => 'Retour à la liste', 'en' => 'Back to List']) ?>
            </button>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php
        $redirectPage = $PAGE;
        include __DIR__ . '/../Partials/_banniere_date_limite.php';
        ?>

        <form method="post" action="index.php?page=update_student&lang=<?= htmlspecialchars($lang) ?>" enctype="multipart/form-data" class="creation-form">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($PAGE) ?>">
            <div class="form-section">
                <?php
                $editableFields = $EDITABLE;
                $allEditable    = false;
                include __DIR__ . '/../Partials/_form_fields.php';
                ?>
            </div>

            <h2><?= $t(['fr' => 'Revue des Pièces Justificatives', 'en' => 'Documents Review']) ?></h2>
            <div class="form-section documents-section full-width">
                <?php
                $languesEditable = true;
                include __DIR__ . '/../Partials/_doc_review.php';
                ?>
            </div>

            <?php include __DIR__ . '/../Partials/_global_status.php'; ?>

            <div class="form-actions">
                <button type="submit" class="btn-secondary">
                    <?= $t(['fr' => 'Enregistrer les modifications', 'en' => 'Save Changes']) ?>
                </button>
                <button type="button" class="btn-secondary"
                        onclick="window.location.href='<?= $buildUrl('index.php', ['page' => $PAGE]) ?>'">
                    <?= $t(['fr' => 'Retour', 'en' => 'Back']) ?>
                </button>
            </div>
        </form>

        <?php include __DIR__ . '/../Partials/_modal_validation.php'; ?>


    <?php endif; ?>

<?php else : ?>

    <h1><?= $t(['fr' => 'Étudiants en mobilité d\'étude', 'en' => 'Study Mobility Students']) ?></h1>

    <?php if (!empty($message)) : ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="student-toolbar">
        <div class="search-container-toolbar">
            <label for="search" class="search-label"><?= $t(['fr' => 'Rechercher', 'en' => 'Search']) ?></label>
            <input type="text" id="search" name="search" placeholder="Nom, prénom, email..."
                   value="<?= htmlspecialchars(strval($filters['search'] ?? '')) ?>">
            <button type="button" id="btn-search-loupe" class="btn-search">
                <img src="img/loupe.png" alt="Rechercher">
            </button>
        </div>
    </div>

    <div class="filters-container">
        <p class="filters-title"><?= $t(['fr' => 'Filtres', 'en' => 'Filters']) ?></p>
        <?php
        $resetUrl         = $buildUrl('index.php', ['page' => $PAGE]);
        $showAccordFilter = false;
        $viewPage         = $PAGE;
        include __DIR__ . '/../Partials/_filters.php';
        include __DIR__ . '/../Partials/_table_etudiants.php';
        ?>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();

$title           = $t(['fr' => 'Coordinateur d\'étude - Relations Internationales AMU', 'en' => 'Study Coordinator - International Relations AMU']);
$styles          = ['styles/index.css', 'styles/folders.css', 'styles/chatbot.css', 'styles/coordinators_extra.css'];
$scripts         = ['js/folders.js'];
$activeMenu      = $PAGE;
$userRole        = $_SESSION['role'] ?? 'coordinateur_etude';
$metaDescription = $t(['fr' => 'Espace coordinateur d\'étude — gestion des mobilités étudiantes.', 'en' => 'Study coordinator space — student mobility management.']);

include __DIR__ . '/../Layout/base.php';