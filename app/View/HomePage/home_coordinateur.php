<?php
/**
 * View: Coordinator Home Page (Dashboard)
 *
 * Landing page for coordinateur_etude, coordinateur_stage, coordinateur, and
 * chef_departement roles. Structurally identical to the admin dashboard but
 * with two key differences:
 *
 * - The folder-status slide shows plain counts (no clickable links to the
 *   folder list, unlike the admin version).
 * - An additional slide 2 displays a department distribution bar chart
 *   (top 5 departments, bar widths scaled relative to the highest count).
 *
 * The user role is read from $userRole and falls back to $_SESSION['role']
 * (defaulting to 'coordinateur_etude') when the controller did not inject it.
 *
 * All display variables are derived from $stats before rendering: folder
 * completion, gender split, mobility direction, Europe/non-Europe split,
 * and department bar scaling. The filter bar and carousel behave identically
 * to the admin view (see home_admin.php for a full description of each slide).
 *
 * The rendered HTML is captured via output buffering into $content and passed
 * to the base layout along with the page title, styles (homepage.css),
 * scripts (carousel.js), active menu key ('home-coordinateur'), and meta
 * description. $noMain = true suppresses the default <main> wrapper.
 *
 * @var string                                         $lang               Current language code (e.g. 'fr' or 'en')
 * @var string                                         $userRole           Role of the current user; falls back to $_SESSION['role'] when empty
 * @var Closure(array<string, string>): string         $t                  Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure(string, array<string, mixed>=): string $buildUrl           URL builder callable — accepts a base URL and an optional query-parameter array
 * @var float|int                                      $completionPercentage Overall folder completion percentage (injected by controller, unused in template — computed locally)
 * @var bool                                           $isLoggedIn         Whether the current user is authenticated
 * @var string|null                                    $mobiliteFilter     Active mobility-type filter: 'etude', 'stage', or null for all
 * @var string|null                                    $departementFilter  Active department filter code, or null for all departments
 * @var array<int, string>                             $allDepartements    Ordered list of all available department codes for the department dropdown
 * @var \Model\Entity\AdminStats|null                  $stats              Aggregated statistics object; null triggers safe fallbacks (zero counts)
 */

$isTritanopia   = !empty($_SESSION['tritanopia']) && ((bool) $_SESSION['tritanopia'] === true);
$mobiliteFilter = $mobiliteFilter ?? null;

// Lire le rôle depuis la session si non transmis par le contrôleur
if (empty($userRole)) {
    $userRole = $_SESSION['role'] ?? 'coordinateur_etude';
}

$dossierStats   = $stats?->getDossierStats();
$genderStats    = $stats?->getGenderStats();
$topCountries   = $stats?->getTopCountries()   ?? [];
$departments    = $stats?->getDepartments()    ?? [];
$zoneStats      = $stats?->getZoneStats()      ?? [];
$incoming       = $stats?->getIncomingStudents() ?? 0;
$outgoing       = $stats?->getOutgoingStudents() ?? 0;
$europeCount    = $stats?->getEuropeCountriesCount()    ?? 0;
$nonEuropeCount = $stats?->getNonEuropeCountriesCount() ?? 0;

$totalFolders   = max($dossierStats?->getTotal() ?? 0, 1);
$completed      = $dossierStats?->getCompleted() ?? 0;
$incomplete     = $totalFolders - $completed;
$completionRate = round($completed / $totalFolders * 100);

$maleCount    = $genderStats?->getMale()   ?? 0;
$femaleCount  = $genderStats?->getFemale() ?? 0;
$totalGender  = $maleCount + $femaleCount;
$malePct      = $totalGender > 0 ? round($maleCount   / $totalGender * 100) : 0;
$femalePct    = $totalGender > 0 ? round($femaleCount / $totalGender * 100) : 0;

$totalMobility = $incoming + $outgoing;
$incomingPct   = $totalMobility > 0 ? round($incoming / $totalMobility * 100) : 0;
$outgoingPct   = $totalMobility > 0 ? round($outgoing / $totalMobility * 100) : 0;

$totalCountries = max($europeCount + $nonEuropeCount, 1);
$europePct      = round($europeCount    / $totalCountries * 100);
$nonEuropePct   = round($nonEuropeCount / $totalCountries * 100);

$maxDept = !empty($departments)
    ? max(array_map(fn($d) => $d->getCount(), $departments))
    : 1;

ob_start();
?>

    <section class="hero-section">
        <img class="hero_logo" src="img/amu.png" alt="AMU Logo">
    </section>

    <section class="pub-section">
        <img id="pub_amu"
             src="<?= $isTritanopia ? 'img/etudiants_daltoniens.png' : 'img/image_etudiants.png' ?>"
             alt="AMU Promotion">
        <div class="pub-text">
            <?= $t([
                'fr' => 'Aix-Marseille Université, une université ouverte sur le monde',
                'en' => 'Aix-Marseille University, a university open to the world',
            ]) ?>
        </div>
    </section>

    <!-- Filtre Mobilité + Département -->
    <section class="mobilite-filter">
        <div class="mobilite-filter__inner">
        <span class="mobilite-filter__label">
            <?= $t(['fr' => 'Statistiques :', 'en' => 'Statistics:']) ?>
        </span>

            <div class="mobilite-filter__buttons">
                <!-- "Tous" réinitialise tout -->
                <a href="<?= $buildUrl('index.php', ['page' => 'home-admin']) ?>"
                   class="mobilite-btn <?= $mobiliteFilter === null && $departementFilter === null ? 'active' : '' ?>">
                    <?= $t(['fr' => 'Tous', 'en' => 'All']) ?>
                </a>

                <!-- Études : préserve le département actif -->
                <a href="<?= $buildUrl('index.php', array_filter([
                    'page'        => 'home-admin',
                    'mobilite'    => $mobiliteFilter === 'etude' ? null : 'etude',
                    'departement' => $departementFilter,
                ])) ?>"
                   class="mobilite-btn mobilite-btn--etude <?= $mobiliteFilter === 'etude' ? 'active' : '' ?>">
                    🎓 <?= $t(['fr' => 'Études', 'en' => 'Studies']) ?>
                </a>

                <!-- Stage : préserve le département actif -->
                <a href="<?= $buildUrl('index.php', array_filter([
                    'page'        => 'home-admin',
                    'mobilite'    => $mobiliteFilter === 'stage' ? null : 'stage',
                    'departement' => $departementFilter,
                ])) ?>"
                   class="mobilite-btn mobilite-btn--stage <?= $mobiliteFilter === 'stage' ? 'active' : '' ?>">
                    💼 <?= $t(['fr' => 'Stage', 'en' => 'Internship']) ?>
                </a>

                <!-- Département : préserve la mobilité active -->
                <div class="dept-dropdown">
                    <button class="mobilite-btn dept-btn <?= $departementFilter !== null ? 'active' : '' ?>">
                        🏛️ <?= $departementFilter ?? $t(['fr' => 'Département', 'en' => 'Department']) ?>
                        <span class="arrow">▼</span>
                    </button>
                    <div class="dept-dropdown-content">
                        <!-- "Tous les départements" préserve la mobilité active -->
                        <a href="<?= $buildUrl('index.php', array_filter([
                            'page'     => 'home-admin',
                            'mobilite' => $mobiliteFilter,
                        ])) ?>">
                            <?= $t(['fr' => 'Tous les départements', 'en' => 'All departments']) ?>
                        </a>
                        <?php foreach ($allDepartements as $dept): ?>
                            <a href="<?= $buildUrl('index.php', array_filter([
                                'page'        => 'home-admin',
                                'mobilite'    => $mobiliteFilter,
                                'departement' => $dept,
                            ])) ?>"
                               class="<?= $departementFilter === $dept ? 'active' : '' ?>">
                                <?= htmlspecialchars($dept) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Carrousel -->
    <section class="stats-section">
        <button class="carousel-btn prev" onclick="window.carousel.changeSlide(-1)">←</button>
        <button class="carousel-btn next" onclick="window.carousel.changeSlide(1)">→</button>

        <div class="stats-carousel">
            <div class="carousel-container">

                <!-- Slide 1 : État des dossiers -->
                <div class="stat-slide active">
                    <h2><?= $t(['fr' => 'État des dossiers', 'en' => 'Folder Status']) ?></h2>
                    <div class="stat-content">
                        <div class="stat-item complete">
                            <div class="stat-number"><?= $completed ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Dossiers complets', 'en' => 'Complete folders']) ?></div>
                        </div>
                        <div class="stat-item incomplete">
                            <div class="stat-number"><?= $incomplete ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Dossiers incomplets', 'en' => 'Incomplete folders']) ?></div>
                        </div>
                    </div>
                    <div class="completion-bar">
                        <div class="completion-fill" style="width: <?= $completionRate ?>%"></div>
                    </div>
                    <div class="stat-percentage">
                        <?= $completionRate ?>% <?= $t(['fr' => 'de complétion', 'en' => 'completion']) ?>
                    </div>
                </div>

                <!-- Slide 2 : Répartition par département -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Répartition par département', 'en' => 'Distribution by Department']) ?></h2>
                    <div class="stat-content departments">
                        <?php foreach (array_slice($departments, 0, 5) as $dept):
                            $barWidth = round($dept->getCount() / max($maxDept, 1) * 100);
                            ?>
                            <div class="dept-item">
                                <span class="dept-name"><?= htmlspecialchars($dept->getName()) ?></span>
                                <div class="dept-bar-container">
                                    <div class="dept-bar" style="width: <?= $barWidth ?>%"></div>
                                </div>
                                <span class="dept-count"><?= $dept->getCount() ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Slide 3 : Répartition par genre -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Répartition par genre', 'en' => 'Gender Distribution']) ?></h2>
                    <div class="stat-content">
                        <div class="stat-item male">
                            <div class="stat-number"><?= $maleCount ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Étudiants', 'en' => 'Male students']) ?></div>
                            <div class="stat-percentage-small"><?= $malePct ?>%</div>
                        </div>
                        <div class="stat-item female">
                            <div class="stat-number"><?= $femaleCount ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Étudiantes', 'en' => 'Female students']) ?></div>
                            <div class="stat-percentage-small"><?= $femalePct ?>%</div>
                        </div>
                    </div>
                    <div class="gender-bar">
                        <div class="gender-male"   style="width: <?= $malePct ?>%"></div>
                        <div class="gender-female" style="width: <?= $femalePct ?>%"></div>
                    </div>
                </div>

                <!-- Slide 4 : Mobilité étudiante -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Mobilité étudiante', 'en' => 'Student Mobility']) ?></h2>
                    <div class="stat-content">
                        <div class="stat-item incoming">
                            <div class="stat-number"><?= $incoming ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Étudiants entrants', 'en' => 'Incoming students']) ?></div>
                            <div class="stat-percentage-small"><?= $incomingPct ?>%</div>
                        </div>
                        <div class="stat-item outgoing">
                            <div class="stat-number"><?= $outgoing ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Étudiants sortants', 'en' => 'Outgoing students']) ?></div>
                            <div class="stat-percentage-small"><?= $outgoingPct ?>%</div>
                        </div>
                    </div>
                    <div class="gender-bar">
                        <div class="gender-male"   style="width: <?= $incomingPct ?>%"></div>
                        <div class="gender-female" style="width: <?= $outgoingPct ?>%"></div>
                    </div>
                </div>

                <!-- Slide 5 : Classement par continent -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Classement par continent', 'en' => 'Ranking by Continent']) ?></h2>
                    <div class="stat-content ranking">
                        <?php foreach (array_slice($zoneStats, 0, 6) as $index => $continent): ?>
                            <div class="ranking-item">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="country-name"><?= htmlspecialchars($continent['name']) ?></span>
                                <span class="country-count"><?= (int) $continent['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Slide 6 : Europe / Hors Europe -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Répartition Europe / Hors Europe', 'en' => 'Europe vs. Non-Europe']) ?></h2>
                    <div class="stat-content">
                        <div class="stat-item complete">
                            <div class="stat-number"><?= $europeCount ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Pays européens', 'en' => 'European countries']) ?></div>
                            <div class="stat-percentage-small"><?= $europePct ?>%</div>
                        </div>
                        <div class="stat-item incomplete">
                            <div class="stat-number"><?= $nonEuropeCount ?></div>
                            <div class="stat-label"><?= $t(['fr' => 'Pays hors Europe', 'en' => 'Non-European countries']) ?></div>
                            <div class="stat-percentage-small"><?= $nonEuropePct ?>%</div>
                        </div>
                    </div>
                    <div class="gender-bar">
                        <div class="gender-male"   style="width: <?= $europePct ?>%"></div>
                        <div class="gender-female" style="width: <?= $nonEuropePct ?>%"></div>
                    </div>
                </div>

                <!-- Slide 7 : Pays les plus demandés -->
                <div class="stat-slide">
                    <h2><?= $t(['fr' => 'Pays les plus demandés', 'en' => 'Most Requested Countries']) ?></h2>
                    <div class="stat-content ranking">
                        <?php foreach (array_slice($topCountries, 0, 5) as $index => $country): ?>
                            <div class="ranking-item">
                                <span class="rank"><?= $index + 1 ?></span>
                                <span class="country-name"><?= htmlspecialchars($country->getName()) ?></span>
                                <span class="country-count"><?= $country->getCount() ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="carousel-dots">
                <span class="dot active" onclick="window.carousel.goToSlide(0)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(1)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(2)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(3)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(4)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(5)"></span>
                <span class="dot"        onclick="window.carousel.goToSlide(6)"></span>
            </div>
        </div>
    </section>

    <div id="app-config"
        data-lang="<?= htmlspecialchars($lang) ?>"
        data-role="admin"
        style="display:none;">
    </div>

<?php
$content = ob_get_clean();

$title = $t([
    'fr' => 'Accueil - Coordinateur - Relations Internationales AMU',
    'en' => 'Home - Coordinator - International Relations AMU',
]);

$styles          = ['styles/homepage.css'];
$scripts         = ['js/carousel.js'];
$activeMenu      = 'home-coordinateur';
$noMain          = true;
$metaDescription = $t([
    'fr' => "Espace coordinateur du service des relations internationales de l'AMU.",
    'en' => 'Coordinator space of the International Relations Service of AMU.',
]);

include __DIR__ . '/../Layout/base.php';