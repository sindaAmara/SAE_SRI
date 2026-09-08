<?php
/**
 * View: Department Head — Student List / Folder Detail
 *
 * Dual-mode view for the chef_departement role, controlled by $action:
 *
 * ── LIST MODE ($action !== 'view') ──────────────────────────────────────────
 * Displays all students assigned to the department head's scope (both study
 * and internship mobility). Provides a text search toolbar (name, first name,
 * email) and a filter bar via _filters.php ($showAccordFilter = false).
 * The student table is rendered by _table_etudiants.php, grouped by component,
 * with clickable rows navigating to the detail view.
 * $hasActiveFilters is computed locally from the $filters array (type, zone,
 * complet, composante, search).
 *
 * ── DETAIL MODE ($action === 'view') ────────────────────────────────────────
 * Renders a partially editable folder form for a single student.
 * When $studentData is null, a "Student not found" notice is shown.
 * Otherwise the following sections are composed:
 *
 * - _banniere_decision.php  : quick accept/refuse decision banner.
 *   $currentStatus is set to avis_chef_departement (the department head's
 *   own opinion column) for the active-button highlight in this partial.
 * - _banniere_date_limite.php : submission deadline banner (read-only for
 *   this role; $redirectPage = $PAGE).
 * - _form_fields.php        : folder form with only 'niveau_etude' and
 *   'moyenne_sans_bac' editable ($EDITABLE list, $allEditable = false).
 * - _doc_review.php         : document review panel with $languesEditable = true
 *   (language certificate can be updated by the department head).
 * - _global_status.php      : global workflow status dropdown.
 *   Note: $currentStatus is reassigned to $globalStatus (the folder's global
 *   status column) before including this partial, since _global_status.php
 *   uses $currentStatus for its own pre-selection.
 * - _modal_validation.php   : confirmation modal for save-and-notify.
 *
 * The form posts to index.php?page=update_student with a hidden redirect_to
 * field set to $PAGE so the controller redirects back here after saving.
 * Mobility type is detected from the presence of convention vs. motivation-
 * letter files in $pieces.
 *
 * The user role is read from $_SESSION['role'] (defaults to 'chef_departement').
 * The rendered HTML is captured into $content and passed to the base layout
 * with styles (index.css, folders.css, chatbot.css) and scripts (folders.js).
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

$PAGE     = 'chef-departement';
$EDITABLE = ['niveau_etude', 'moyenne_sans_bac'];

$userRole = $_SESSION['role'] ?? 'chef_departement';

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

        // Pour _banniere_decision : avis du chef (casse exacte de la colonne BDD)
        $currentStatus = strval($studentData['avis_chef_departement'] ?? '');
        // Pour _global_status : statut global du dossier
        $globalStatus  = strval($studentData['status'] ?? 'depot');
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

        <!-- Bannière décision : reçoit $currentStatus = avis_chef_departement -->
        <?php include __DIR__ . '/../Partials/_banniere_decision.php'; ?>

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

            <?php
            $currentStatus = $globalStatus;
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

        <?php include __DIR__ . '/../Partials/_modal_validation.php'; ?>

    <?php endif; ?>

<?php else : ?>

    <h1><?= $t(['fr' => 'Étudiants en mobilité d\'étude et de stage', 'en' => 'Students on study and internship mobility programs']) ?></h1>

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

$title           = $t(['fr' => 'Chef de département - Relations Internationales AMU', 'en' => 'Department Head - International Relations AMU']);
$styles          = ['styles/index.css', 'styles/folders.css', 'styles/chatbot.css'];
$scripts         = ['js/folders.js'];
$activeMenu      = $PAGE;
$metaDescription = $t(['fr' => 'Espace chef de département — gestion des mobilités étudiantes.', 'en' => 'Department head space — student mobility management.']);

include __DIR__ . '/../Layout/base.php';