<?php
/**
 * View: Admin Folder Management (list / detail)
 *
 * Dual-mode view controlled by $action:
 *
 * ── LIST MODE ($action !== 'view') ──────────────────────────────────────────
 * Renders a searchable, filterable, paginated list of all student folders.
 *
 * Toolbar:
 * - Text search input (name, first name, email) with a loupe button that
 *   triggers the search via JavaScript.
 * - Excel/CSV import button that opens a hidden file input and auto-submits
 *   the form to index.php?page=import_folders on file selection.
 *
 * Filters (via the _filters.php partial):
 * - Direction: entrant / sortant
 * - Zone: Europe / Non-Europe
 * - Status: all / complete / incomplete
 * - Component: all / AMU CIVIS / IUT
 * - Agreement (admin-only): all / Erasmus / Bilatéral ($showAccordFilter = true)
 * - Reset link shown when at least one filter is active ($hasActiveFilters).
 *
 * Student table is rendered by the _table_etudiants.php partial, grouped by
 * component, with clickable rows navigating to the detail view.
 *
 * $hasActiveFilters is computed locally from the $filters array by checking
 * all known filter keys against their default 'all' / empty values.
 * $buildUrl is defined locally (falls back to injected $t when absent) and
 * always appends the current $lang to every generated URL.
 *
 * ── DETAIL MODE ($action === 'view') ────────────────────────────────────────
 * Renders the full admin edit form for a single student folder.
 *
 * When $studentData is null, a "Student not found" notice is displayed.
 * Otherwise the following sections are composed from partials:
 *
 * - _banniere_modifie_par.php  : last-modified-by banner (ModifiePar / ModifieLe).
 * - _banniere_date_limite.php  : submission deadline banner with inline edit form.
 * - Department head opinion    : a read-only badge showing avis_chef_departement
 *   (accepte ✅ / refuse ❌ / pending ⏳) with matching CSS class.
 * - _form_fields.php           : full student data form, all fields active
 *   ($allEditable = true, $editableFields = []).
 * - _doc_review.php            : document review panel; all file types editable
 *   ($allFilesEditable = true, $languesEditable = false).
 * - _global_status.php         : global workflow status dropdown + update button.
 * - Form action buttons        : "Save and Notify" (#btn-enregistrer, wired to the
 *   validation modal via JavaScript) and a "Back" button.
 * - _modal_validation.php      : confirmation modal shown before final save.
 * - Inline <script>            : calls analyserDocuments() via a temporary
 *   FolderRepositoryPDO instance and exposes the result as
 *   window.analyseDocumentsData for use by folders.js.
 *
 * The mobility type is resolved first from the Mobilite column, then falls
 * back to detecting presence of convention vs. motivation-letter files.
 *
 * The rendered HTML is captured via output buffering into $content and passed
 * to the base layout with styles (index.css, folders.css, chatbot.css),
 * scripts (folders.js), active menu key 'folders-admin', and role 'admin'.
 *
 * @var string                           $lang          Current language code (e.g. 'fr' or 'en')
 * @var string                           $action        View mode: 'view' for the detail form, any other value for the list
 * @var array<string, mixed>|null        $studentData   Full folder data array from the repository (including 'pieces', 'statuts', and metadata); null when the student was not found
 * @var array<string, mixed>             $filters       Current active filter values keyed by filter name (type, zone, complet, composante, accord, date_debut, date_fin, search)
 * @var int                              $totalCount    Total number of folders matching the active filters (used by the student table partial)
 * @var array<int, array<string, mixed>> $paginatedData Current page of folder records for the student table partial
 * @var string                           $message       Optional feedback message displayed at the top of either mode (may be empty)
 */

if (!isset($t)) {
    $t = function(array $translations) use ($lang) {
        return $translations[$lang] ?? $translations['fr'] ?? '';
    };
}

$buildUrl = function(string $path, array $params = []) use ($lang): string {
    $params['lang'] = $lang;
    $separator = (strpos($path, '?') === false) ? '?' : '&';
    return $path . $separator . http_build_query($params);
};

$PAGE = 'folders-admin';

$hasActiveFilters = (strval($filters['type']       ?? 'all')) !== 'all'
    || (strval($filters['zone']       ?? 'all')) !== 'all'
    || (strval($filters['complet']    ?? 'all')) !== 'all'
    || (strval($filters['composante'] ?? 'all')) !== 'all'
    || (strval($filters['accord']     ?? 'all')) !== 'all'
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
        $mobiliteCol  = strtolower(trim(strval($studentData['Mobilite'] ?? '')));
        if ($mobiliteCol === 'stage') {
            $detectedType = 'stage';
        } elseif ($mobiliteCol === 'etude' || $mobiliteCol === 'etudes') {
            $detectedType = 'etude';
        } else {
            $detectedType = !empty($pieces['convention']['file'])         ? 'stage'
                : (!empty($pieces['lettre_motivation']['file']) ? 'etudes' : '');
        }
        $numEtu        = htmlspecialchars(strval($studentData['NumEtu'] ?? ''));
        $dateLimite    = $studentData['DateLimite'] ?? null;
        $currentStatus = $studentData['status'] ?? 'depot';
        ?>

        <?php
        $modifiePar = $studentData['ModifiePar'] ?? null;
        $modifieLe  = $studentData['ModifieLe']  ?? null;
        include __DIR__ . '/../Partials/_banniere_modifie_par.php';
        ?>

        <h1><?= $t(['fr' => 'Dossier étudiant', 'en' => 'Student Profile']) ?></h1>
        <div class="form-back-button">
            <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => $PAGE]) ?>'" class="btn-secondary">
                <?= $t(['fr' => 'Retour à la liste', 'en' => 'Back to List']) ?>
            </button>
        </div>

        <div class="export-csv-container">
            <a href="<?= $buildUrl('index.php', ['page' => 'export_student_csv', 'numetu' => $numEtu]) ?>"
               class="btn-export-csv" target="_blank" rel="noopener">
                <?= $t(['fr' => '📄 Exporter en CSV', 'en' => '📄 Export as CSV']) ?>
            </a>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php
        $redirectPage = $PAGE;
        include __DIR__ . '/../Partials/_banniere_date_limite.php';
        ?>

        <!-- ── AVIS CHEF DE DÉPARTEMENT (lecture seule) ── -->
        <?php
        $avisChef  = strval($studentData['avis_chef_departement'] ?? '');
        $avisLabel = match($avisChef) {
            'accepte' => ['fr' => 'Dossier accepté par le chef de département',  'en' => 'Profile accepted by department head'],
            'refuse'  => ['fr' => 'Dossier refusé par le chef de département',   'en' => 'Profile refused by department head'],
            default   => ['fr' => 'Aucune décision du chef de département',       'en' => 'No decision from department head'],
        };
        $avisIcon = match($avisChef) {
            'accepte' => '✅',
            'refuse'  => '❌',
            default   => '⏳',
        };
        $avisClass = match($avisChef) {
            'accepte' => 'avis-chef avis-chef--accepte',
            'refuse'  => 'avis-chef avis-chef--refuse',
            default   => 'avis-chef avis-chef--pending',
        };
        ?>
        <div class="<?= $avisClass ?>">
            <span class="avis-chef-icon"><?= $avisIcon ?></span>
            <span class="avis-chef-label"><?= $t(['fr' => 'Avis chef de département :', 'en' => 'Department head decision:']) ?></span>
            <span class="avis-chef-value"><?= $t($avisLabel) ?></span>
        </div>

        <form method="post" action="index.php?page=update_student&lang=<?= htmlspecialchars($lang) ?>" enctype="multipart/form-data" class="creation-form">
            <div class="form-section">
                <?php
                $editableFields = [];
                $allEditable    = true;
                include __DIR__ . '/../Partials/_form_fields.php';
                ?>
            </div>

            <h2><?= $t(['fr' => 'Revue des Pièces Justificatives', 'en' => 'Documents Review']) ?></h2>
            <div class="form-section documents-section full-width">
                <?php
                $languesEditable  = false;
                $allFilesEditable = true;
                include __DIR__ . '/../Partials/_doc_review.php';
                ?>
            </div>

            <?php include __DIR__ . '/../Partials/_global_status.php'; ?>

            <div class="form-actions">
                <button type="button" id="btn-enregistrer" class="btn-secondary">
                    <?= $t(['fr' => '✉️ Enregistrer et notifier', 'en' => '✉️ Save and Notify']) ?>
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

    <h1><?= $t(['fr' => 'Liste des étudiants', 'en' => 'Students List']) ?></h1>

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
        <div class="toolbar-actions">
            <button id="btn-import-excel" class="btn-search"
                    onclick="document.getElementById('file-import').click()">
                <?= $t(['fr' => 'Importer Excel/CSV', 'en' => 'Import Excel/CSV']) ?>
            </button>
            <form id="form-import" method="post"
                  action="index.php?page=import_folders&lang=<?= htmlspecialchars($lang) ?>"
                  enctype="multipart/form-data" class="hidden-element">
                <input type="file" id="file-import" name="excel_file" accept=".csv,.xlsx,.xls"
                       onchange="document.getElementById('form-import').submit()">
            </form>
        </div>
    </div>

    <div class="filters-container">
        <p class="filters-title"><?= $t(['fr' => 'Filtres', 'en' => 'Filters']) ?></p>
        <?php
        $resetUrl         = $buildUrl('index.php', ['page' => $PAGE]);
        $showAccordFilter = true;
        $viewPage         = $PAGE;
        include __DIR__ . '/../Partials/_filters.php';
        include __DIR__ . '/../Partials/_table_etudiants.php';
        ?>
    </div>

<?php endif; ?>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="admin"
         style="display:none;">
    </div>
<?php
$content = ob_get_clean();

$title      = $t(['fr' => 'Gestion des dossiers - Admin', 'en' => 'Profiles Management - Admin']);
$styles     = ['styles/index.css', 'styles/folders.css', 'styles/chatbot.css'];
$scripts    = ['js/folders.js'];
$activeMenu = $PAGE;
$userRole   = 'admin';

include __DIR__ . '/../Layout/base.php';