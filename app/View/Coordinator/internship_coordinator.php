<?php
/**
 * View: Internship Coordinator — Student List / Folder Detail
 *
 * Dual-mode view for the coordinateur_stage role, controlled by $action:
 *
 * ── LIST MODE ($action !== 'view') ──────────────────────────────────────────
 * Displays only internship-mobility students. Provides a text search toolbar
 * and a filter bar via _filters.php ($showAccordFilter = false). The student
 * table is rendered by _table_etudiants.php, grouped by component, with
 * clickable rows navigating to the detail view.
 * $hasActiveFilters is computed locally from the $filters array (type, zone,
 * complet, composante, date_debut, date_fin, search) — note that this view
 * also checks date_debut and date_fin unlike the study coordinator and
 * department head views.
 *
 * ── DETAIL MODE ($action === 'view') ────────────────────────────────────────
 * Renders a partially editable folder form for a single student.
 * When $studentData is null, a "Student not found" notice is shown.
 * Otherwise the following sections are composed:
 *
 * - _banniere_date_limite.php : submission deadline banner ($redirectPage is
 *   set before the partial is included for the date-edit form action).
 * - _form_fields.php          : folder form with only 'niveau_etude' and
 *   'moyenne_sans_bac' editable ($EDITABLE list, $allEditable = false).
 *   $redirectPage is also set here for the hidden redirect_to field.
 * - _doc_review.php           : document review panel with $languesEditable = true
 *   (language certificate can be updated by the coordinator).
 * - _global_status.php        : global workflow status dropdown + update button.
 * - _modal_validation.php     : confirmation modal for save-and-notify
 *   ($redirectPage is reassigned to $PAGE before this include).
 *
 * The form posts to index.php?page=update_student with a hidden redirect_to
 * field so the controller redirects back here after saving. Mobility type is
 * detected from the presence of convention vs. motivation-letter files.
 *
 * The user role falls back to $_SESSION['role'] (defaults to
 * 'coordinateur_stage'). The rendered HTML is passed to the base layout
 * with styles (index.css, folders.css, chatbot.css, coordinators_extra.css)
 * and scripts (folders.js).
 *
 * @var string                           $lang          Current language code (e.g. 'fr' or 'en')
 * @var Closure                          $t             Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure                          $buildUrl      URL builder callable — accepts a base URL and an optional query-parameter array
 * @var array<string, mixed>             $filters       Current active filter values (type, zone, complet, composante, date_debut, date_fin, search)
 * @var array<int, array<string, mixed>> $paginatedData Current page of student records for the table partial
 * @var int                              $totalCount    Total number of matching students across all pages
 * @var array<string, mixed>|null        $studentData   Full folder data from the repository (including 'pieces', 'statuts', metadata); null when not found
 * @var string                           $action        View mode: 'view' for the detail form, any other value for the list
 * @var string                           $message       Optional feedback message displayed at the top of either mode (may be empty)
 */

$PAGE         = 'coordinateur-stage';
$EDITABLE     = ['niveau_etude', 'moyenne_sans_bac'];

$hasActiveFilters = (strval($filters['type']       ?? 'all')) !== 'all'
    || (strval($filters['zone']       ?? 'all')) !== 'all'
    || (strval($filters['complet']    ?? 'all')) !== 'all'
    || (strval($filters['composante'] ?? 'all')) !== 'all'
    || !empty($filters['date_debut'])
    || !empty($filters['date_fin'])
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

        <?php include __DIR__ . '/../Partials/_banniere_date_limite.php'; ?>

        <form method="post" action="index.php?page=update_student&lang=<?= htmlspecialchars($lang) ?>" enctype="multipart/form-data" class="creation-form">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($PAGE) ?>">
            <div class="form-section">
                <?php
                $editableFields = $EDITABLE;
                $redirectPage   = $PAGE;
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

            <?php
            include __DIR__ . '/../Partials/_global_status.php';
            ?>

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

        <?php
        $redirectPage = $PAGE;
        include __DIR__ . '/../Partials/_modal_validation.php';
        ?>


    <?php endif; ?>

<?php else : ?>

    <h1><?= $t(['fr' => 'Étudiants en mobilité de stage', 'en' => 'Internship Mobility Students']) ?></h1>

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
        $viewPage         = $PAGE;
        $resetUrl         = $buildUrl('index.php', ['page' => $PAGE]);
        $showAccordFilter = false;
        include __DIR__ . '/../Partials/_filters.php';
        include __DIR__ . '/../Partials/_table_etudiants.php';
        ?>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();

$title           = $t(['fr' => 'Coordinateur de stage - Relations Internationales AMU', 'en' => 'Internship Coordinator - International Relations AMU']);
$styles          = ['styles/index.css', 'styles/folders.css', 'styles/chatbot.css', 'styles/coordinators_extra.css'];
$scripts         = ['js/folders.js'];
$activeMenu      = $PAGE;
$userRole        = $_SESSION['role'] ?? 'coordinateur_stage';
$metaDescription = $t(['fr' => 'Espace coordinateur de stage — gestion des mobilités étudiantes.', 'en' => 'Internship coordinator space — student mobility management.']);

include __DIR__ . '/../Layout/base.php';