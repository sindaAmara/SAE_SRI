<?php
/**
 * View: Admin — End-of-Internship Form Responses
 *
 * Same search bar, filters and component-grouped table as the folders page,
 * with form-specific columns (see Partials/_table_formulaires.php).
 *
 * @var string                                $lang
 * @var Closure(array<string,string>): string $t
 * @var Closure(string, array=): string       $buildUrl
 * @var array<string, mixed>                  $filters
 * @var array<int, array<string, mixed>>      $paginatedData Students with 'form_response' / 'form_submitted' keys
 * @var int                                   $totalCount
 */

ob_start();

$PAGE = 'stage-form-admin';

$hasActiveFilters = (strval($filters['type']       ?? 'all')) !== 'all'
        || (strval($filters['zone']       ?? 'all')) !== 'all'
        || (strval($filters['complet']    ?? 'all')) !== 'all'
        || (strval($filters['composante'] ?? 'all')) !== 'all'
        || (strval($filters['accord']     ?? 'all')) !== 'all'
        || !empty($filters['search']);
?>

    <h1><?= $t(['fr' => 'Formulaires de fin de stage', 'en' => 'End-of-internship forms']) ?></h1>

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
        $showAccordFilter = true;
        include __DIR__ . '/../Partials/_filters.php';
        include __DIR__ . '/../Partials/_table_formulaires.php';
        ?>
    </div>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="admin"
         style="display:none;">
    </div>

    <script>
        function toggleEval(btn) {
            const detailRow = btn.closest('tr').nextElementSibling;
            if (!detailRow || !detailRow.classList.contains('eval-detail-row')) return;
            const open = detailRow.style.display === 'none';
            detailRow.style.display = open ? 'table-row' : 'none';
            btn.textContent = open ? '▲' : '▼';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    </script>

<?php
$content = ob_get_clean();

$title      = $t(['fr' => 'Formulaires de fin de stage', 'en' => 'End-of-internship forms']);
$styles     = ['styles/index.css', 'styles/folders.css', 'styles/chatbot.css'];
$scripts    = ['js/folders.js'];
$activeMenu = 'dashboard';
$userRole   = 'admin';

include __DIR__ . '/../Layout/base.php';