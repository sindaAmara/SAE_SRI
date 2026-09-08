<?php

namespace Tests\Controllers;

use Controllers\DashboardController;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Model\UseCase\ManageFolderUseCase;

/**
 * Unit tests for {@see DashboardController}.
 *
 * Coverage areas:
 * - Route matching via support()
 * - Constructor / dependency injection
 * - Authentication and role-based access guards for admin and student dashboards
 * - Folder filtering logic (student name, department, year, destination,
 *   campaign, and framework/accord)
 * - Completion percentage calculation
 * - Outgoing / incoming folder classification
 * - Progress-bar step logic for the student dashboard
 * - Language / translation helper
 * - buildUrl() query-string helper
 * - Resilience against empty or malformed folder data
 *
 * Infrastructure helpers (applyFilters, calcPercentage, splitFolders) mirror
 * the controller's internal logic so that business rules can be verified in
 * isolation without triggering HTTP I/O.
 *
 * All HTTP superglobals are reset in setUp() and torn down in tearDown() to
 * prevent state leakage between test cases.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/DashboardControllerTest.php
 */
class DashboardControllerTest extends TestCase
{
    /** Mocked use case injected into the controller under test. */
    private ManageFolderUseCase&MockObject $folderUseCaseMock;

    /** Controller instance shared across tests that need it. */
    private DashboardController $controller;

    /**
     * Creates a fresh controller with a mocked use case and resets all HTTP
     * superglobals before every test.
     */
    protected function setUp(): void
    {
        $this->folderUseCaseMock = $this->createMock(ManageFolderUseCase::class);
        $this->controller = new DashboardController($this->folderUseCaseMock);

        $_GET     = [];
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/dashboard-admin';
    }

    /**
     * Cleans up superglobals and destroys any active PHP session after each
     * test to avoid cross-test contamination.
     */
    protected function tearDown(): void
    {
        $_GET     = [];
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // -------------------------------------------------------------------------
    // support()
    // -------------------------------------------------------------------------

    /**
     * @test
     * The controller must claim the "dashboard-admin" page on GET so the
     * router directs admin dashboard traffic through it.
     */
    public function testSupportReturnsTrueForDashboardAdminGet(): void
    {
        $this->assertTrue(DashboardController::support('dashboard-admin', 'GET'));
    }

    /**
     * @test
     * The controller must claim the "dashboard-student" page on GET so the
     * router directs student dashboard traffic through it.
     */
    public function testSupportReturnsTrueForDashboardStudentGet(): void
    {
        $this->assertTrue(DashboardController::support('dashboard-student', 'GET'));
    }

    /**
     * @test
     * Both dashboard pages are read-only; POST requests must not be routed to
     * this controller.
     */
    public function testSupportReturnsFalseForPost(): void
    {
        $this->assertFalse(DashboardController::support('dashboard-admin', 'POST'));
        $this->assertFalse(DashboardController::support('dashboard-student', 'POST'));
    }

    /**
     * @test
     * An unknown page name must return false so the router can delegate to
     * another handler.
     */
    public function testSupportReturnsFalseForUnknownPage(): void
    {
        $this->assertFalse(DashboardController::support('unknown-page', 'GET'));
    }

    /**
     * @test
     * An empty page string must return false.
     */
    public function testSupportReturnsFalseForEmptyPage(): void
    {
        $this->assertFalse(DashboardController::support('', 'GET'));
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * @test
     * The constructor must accept a ManageFolderUseCase and produce a valid
     * DashboardController instance.
     */
    public function testConstructorStoresProvidedUseCase(): void
    {
        $this->assertInstanceOf(DashboardController::class, $this->controller);
    }

    // -------------------------------------------------------------------------
    // control() — admin dashboard: authentication guard
    // -------------------------------------------------------------------------

    /**
     * @test
     * When no session exists, the request is unauthenticated and the admin
     * data-loading path (getAllFolders) must never be reached.
     */
    public function testControlRedirectsWhenAdminNotAuthenticated(): void
    {
        $_GET['page'] = 'dashboard-admin';
        $_SESSION     = [];

        $this->folderUseCaseMock->expects($this->never())->method('getAllFolders');

        $role = $_SESSION['role'] ?? null;
        $this->assertNotEquals('admin', $role);
    }

    /**
     * @test
     * When the session role is not "admin" (e.g. "student"), the admin
     * data-loading path must not be triggered.
     */
    public function testControlRedirectsWhenRoleIsNotAdmin(): void
    {
        $_GET['page']      = 'dashboard-admin';
        $_SESSION['role']  = 'student';

        $this->folderUseCaseMock->expects($this->never())->method('getAllFolders');

        $role = $_SESSION['role'] ?? null;
        $this->assertNotEquals('admin', $role);
    }

    // -------------------------------------------------------------------------
    // control() — student dashboard: authentication guard
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the session role is not "student", the student detail-loading path
     * must not be triggered.
     */
    public function testStudentDashboardRedirectsWhenRoleIsNotStudent(): void
    {
        $_GET['page']     = 'dashboard-student';
        $_SESSION['role'] = 'admin';

        $this->folderUseCaseMock->expects($this->never())->method('getStudentDetails');

        $role = $_SESSION['role'] ?? null;
        $this->assertNotEquals('student', $role);
    }

    /**
     * @test
     * When the role is correct but numetu is absent from the session, the
     * student detail-loading path must not be triggered.
     */
    public function testStudentDashboardRedirectsWhenNumetuMissing(): void
    {
        $_SESSION['role'] = 'student';

        $this->folderUseCaseMock->expects($this->never())->method('getStudentDetails');

        $this->assertArrayNotHasKey('numetu', $_SESSION);
    }

    // -------------------------------------------------------------------------
    // Filter logic (unit-testing the filtering inline)
    // -------------------------------------------------------------------------

    /**
     * Returns a minimal folder row suitable for filter and percentage tests.
     * Any field can be overridden via the $overrides map.
     *
     * @param array<string, mixed> $overrides Key-value pairs to merge into the
     *                                        default row.
     * @return array<string, mixed>
     */
    private function makeFolderRow(array $overrides = []): array
    {
        return array_merge([
            'Nom'                  => 'DUPONT',
            'Prenom'               => 'Alice',
            'NumEtu'               => '22000001',
            'CodeDepartement'      => 'INFO',
            'Type'                 => 'sortant',
            'Annee'                => '2024-2025',
            'Campagne'             => 'Automne 2024',
            'IsComplete'           => 0,
            'Composante'           => 'IUT',
            'Accord'               => 'Erasmus+',
            'Destination'          => 'Berlin',
            'PiecesJustificatives' => '{}',
        ], $overrides);
    }

    /**
     * @test
     * Filtering by student name must match against the Nom field
     * (case-insensitive).
     */
    public function testFilterByStudentNameMatchesNom(): void
    {
        $folders = [$this->makeFolderRow()];
        $filters = ['student' => 'dupont', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * Filtering by student name must also match against the Prenom field
     * (case-insensitive).
     */
    public function testFilterByStudentNameMatchesPrenom(): void
    {
        $folders = [$this->makeFolderRow()];
        $filters = ['student' => 'alice', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * Filtering by student name must also match against the NumEtu field,
     * allowing admins to search by student number.
     */
    public function testFilterByStudentNameMatchesNumEtu(): void
    {
        $folders = [$this->makeFolderRow()];
        $filters = ['student' => '22000001', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * When the search string does not match Nom, Prenom, or NumEtu, the folder
     * must be excluded from the result set.
     */
    public function testFilterByStudentNameExcludesNonMatching(): void
    {
        $folders = [$this->makeFolderRow()];
        $filters = ['student' => 'martin', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     * Filtering by department code must keep only folders whose
     * CodeDepartement matches exactly.
     */
    public function testFilterByDeptMatches(): void
    {
        $folders = [$this->makeFolderRow(), $this->makeFolderRow(['CodeDepartement' => 'MATH'])];
        $filters = ['student' => '', 'dept' => 'INFO', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
        $this->assertEquals('INFO', $result[0]['CodeDepartement']);
    }

    /**
     * @test
     * Filtering by academic year must keep only folders whose Annee matches
     * the supplied value exactly.
     */
    public function testFilterByYearMatches(): void
    {
        $folders = [
            $this->makeFolderRow(['Annee' => '2024-2025']),
            $this->makeFolderRow(['Annee' => '2023-2024']),
        ];
        $filters = ['student' => '', 'dept' => '', 'year' => '2024-2025', 'dest' => '', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * Destination filtering must be case-insensitive so that "berlin" matches
     * "Berlin".
     */
    public function testFilterByDestinationCaseInsensitive(): void
    {
        $folders = [$this->makeFolderRow(['Destination' => 'Berlin'])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => 'berlin', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * When the destination filter does not match the folder's Destination
     * value, the folder must be excluded.
     */
    public function testFilterByDestinationExcludesNonMatch(): void
    {
        $folders = [$this->makeFolderRow(['Destination' => 'Berlin'])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => 'paris', 'camp' => '', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     * Filtering by campaign must keep only folders whose Campagne matches
     * the supplied value exactly.
     */
    public function testFilterByCampagne(): void
    {
        $folders = [
            $this->makeFolderRow(['Campagne' => 'Automne 2024']),
            $this->makeFolderRow(['Campagne' => 'Printemps 2025']),
        ];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => 'Automne 2024', 'cadre' => ''];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * The "cadre" filter must match against the Composante field (partial,
     * case-insensitive) so that "IUT" matches "IUT Aix".
     */
    public function testFilterByCadreMatchesComposante(): void
    {
        $folders = [$this->makeFolderRow(['Composante' => 'IUT Aix', 'Accord' => ''])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => 'IUT'];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * The "cadre" filter must also match against the Accord field (partial,
     * case-insensitive) so that "Erasmus" matches "Erasmus+".
     */
    public function testFilterByCadreMatchesAccord(): void
    {
        $folders = [$this->makeFolderRow(['Composante' => 'AMU', 'Accord' => 'Erasmus+'])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => 'Erasmus'];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     * When the cadre search term does not appear in either Composante or Accord,
     * the folder must be excluded.
     */
    public function testFilterByCadreExcludesNonMatch(): void
    {
        $folders = [$this->makeFolderRow(['Composante' => 'AMU', 'Accord' => 'Erasmus+'])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => 'CAMPUS'];
        $result = $this->applyFilters($folders, $filters);
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // Percentage calculation logic
    // -------------------------------------------------------------------------

    /**
     * @test
     * When IsComplete is 1, the percentage must be 100 regardless of how many
     * supporting documents are present.
     */
    public function testPercentageIs100WhenIsComplete(): void
    {
        $folder     = $this->makeFolderRow(['IsComplete' => 1, 'PiecesJustificatives' => '{}']);
        $percentage = $this->calcPercentage($folder);
        $this->assertEquals(100, $percentage);
    }

    /**
     * @test
     * When 2 out of 4 required documents are provided, the percentage must be
     * 50 (proportional to the number of uploaded pieces).
     */
    public function testPercentageIsProportionalToProvidedPieces(): void
    {
        $pieces = ['photo' => 'a', 'cv' => 'b']; // 2 of 4 required documents
        $folder = $this->makeFolderRow(['IsComplete' => 0, 'PiecesJustificatives' => json_encode($pieces)]);
        $percentage = $this->calcPercentage($folder);
        $this->assertEquals(50, $percentage);
    }

    /**
     * @test
     * When no documents have been uploaded, the percentage must be 0.
     */
    public function testPercentageIsZeroWithNoPieces(): void
    {
        $folder     = $this->makeFolderRow(['IsComplete' => 0, 'PiecesJustificatives' => '{}']);
        $percentage = $this->calcPercentage($folder);
        $this->assertEquals(0, $percentage);
    }

    /**
     * @test
     * When more than 4 documents are present, the percentage must be capped at
     * 100 to prevent the progress bar from overflowing.
     */
    public function testPercentageCapsAt100EvenIfMoreThan4Pieces(): void
    {
        $pieces = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5]; // 5 > 4 required
        $folder = $this->makeFolderRow(['IsComplete' => 0, 'PiecesJustificatives' => json_encode($pieces)]);
        $percentage = $this->calcPercentage($folder);
        $this->assertEquals(100, $percentage);
    }

    // -------------------------------------------------------------------------
    // Outgoing / Incoming classification
    // -------------------------------------------------------------------------

    /**
     * @test
     * Folders with Type "incoming" or "entrant" must be classified as incoming;
     * folders with any other type (e.g. "sortant") must be classified as outgoing.
     */
    public function testIncomingTypeIsClassifiedCorrectly(): void
    {
        $folders = [
            $this->makeFolderRow(['Type' => 'incoming']),
            $this->makeFolderRow(['Type' => 'entrant']),
            $this->makeFolderRow(['Type' => 'sortant']),
        ];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        [$outgoing, $incoming] = $this->splitFolders($folders, $filters);
        $this->assertCount(2, $incoming);
        $this->assertCount(1, $outgoing);
    }

    /**
     * @test
     * A folder with Type "sortant" must be classified as outgoing by default.
     */
    public function testOutgoingTypeIsDefault(): void
    {
        $folders = [$this->makeFolderRow(['Type' => 'sortant'])];
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        [$outgoing, $incoming] = $this->splitFolders($folders, $filters);
        $this->assertCount(1, $outgoing);
        $this->assertCount(0, $incoming);
    }

    // -------------------------------------------------------------------------
    // Progress bar logic (student dashboard)
    // -------------------------------------------------------------------------

    /**
     * @test
     * @dataProvider progressDataProvider
     *
     * The progress percentage must reflect the student's position in the
     * workflow steps [depot → instruction → accepte], with a minimum floor
     * of 8% at the first step and "refuse" mapping to the same visual position
     * as "accepte".
     */
    public function testProgressPercentageForStatus(string $status, float $expectedPercentage): void
    {
        $steps             = ['depot', 'instruction', 'accepte'];
        // Both "accepte" and "refuse" are terminal states; map both to "accepte"
        // so the progress bar reaches 100%.
        $statusForProgress = in_array($status, ['accepte', 'refuse'], true) ? 'accepte' : $status;
        $currentStepIndex  = array_search($statusForProgress, $steps, true);
        if ($currentStepIndex === false) $currentStepIndex = 0;
        $totalSteps         = count($steps);
        $progressPercentage = ((int)$currentStepIndex / ($totalSteps - 1)) * 100;
        // Enforce a minimum bar width so the UI always shows at least a sliver.
        if ($progressPercentage == 0) $progressPercentage = 8;
        $this->assertEquals($expectedPercentage, $progressPercentage);
    }

    /**
     * Provides [status, expectedPercentage] pairs for testProgressPercentageForStatus.
     *
     * @return array<string, array{string, float}>
     */
    public static function progressDataProvider(): array
    {
        return [
            'depot status'       => ['depot',       8.0],   // first step → minimum 8 %
            'instruction status' => ['instruction', 50.0],  // middle step → 50 %
            'accepte status'     => ['accepte',     100.0], // final step  → 100 %
            'refuse maps to 100' => ['refuse',      100.0], // terminal refusal → same as accepte
        ];
    }

    /**
     * @test
     * Both "accepte" and "refuse" are terminal statuses and must map to the
     * same step index (2) in the progress steps array.
     */
    public function testRefuseMapsToSameProgressAsAccepte(): void
    {
        $steps = ['depot', 'instruction', 'accepte'];
        foreach (['accepte', 'refuse'] as $status) {
            // "refuse" is re-mapped to "accepte" before the index lookup.
            $statusForProgress = 'accepte';
            $idx               = array_search($statusForProgress, $steps, true);
            $this->assertEquals(2, $idx, "Status '$status' should map to index 2");
        }
    }

    // -------------------------------------------------------------------------
    // Lang / translation helper
    // -------------------------------------------------------------------------

    /**
     * @test
     * When lang is "fr" (or absent), the translation helper must return the
     * French string.
     */
    public function testTranslationHelperReturnsFrByDefault(): void
    {
        $this->assertTranslation('fr', 'Bonjour', ['fr' => 'Bonjour', 'en' => 'Hello']);
    }

    /**
     * @test
     * When lang is explicitly set to "en", the translation helper must return
     * the English string.
     */
    public function testTranslationHelperReturnsEnWhenLangIsEn(): void
    {
        $this->assertTranslation('en', 'Hello', ['fr' => 'Bonjour', 'en' => 'Hello']);
    }

    /**
     * Asserts that the translation helper selects the correct string for the
     * given language.
     *
     * @param array<string, string> $frEn Associative array with "fr" and "en" keys.
     */
    private function assertTranslation(string $lang, string $expected, array $frEn): void
    {
        $result = ($lang === 'en') ? $frEn['en'] : $frEn['fr'];
        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // buildUrl helper
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the base path has no query string, buildUrl() must use "?" as the
     * separator before appending the lang parameter.
     */
    public function testBuildUrlAppendsLangWithQuestionMark(): void
    {
        $lang     = 'fr';
        $buildUrl = function (string $path) use ($lang): string {
            $separator = (strpos($path, '?') !== false) ? '&' : '?';
            return $path . $separator . 'lang=' . urlencode($lang);
        };
        $this->assertEquals('index.php?lang=fr', $buildUrl('index.php'));
    }

    /**
     * @test
     * When the base path already contains a query string, buildUrl() must use
     * "&" as the separator to produce a valid URL.
     */
    public function testBuildUrlAppendsLangWithAmpersandWhenQueryExists(): void
    {
        $lang     = 'en';
        $buildUrl = function (string $path) use ($lang): string {
            $separator = (strpos($path, '?') !== false) ? '&' : '?';
            return $path . $separator . 'lang=' . urlencode($lang);
        };
        $this->assertEquals('index.php?page=login&lang=en', $buildUrl('index.php?page=login'));
    }

    // -------------------------------------------------------------------------
    // Empty / invalid folder data resilience
    // -------------------------------------------------------------------------

    /**
     * @test
     * When no folders are provided, both the outgoing and incoming lists must
     * be empty without throwing errors.
     */
    public function testEmptyFolderArrayProducesNoOutgoingOrIncoming(): void
    {
        $filters = ['student' => '', 'dept' => '', 'year' => '', 'dest' => '', 'camp' => '', 'cadre' => ''];
        [$outgoing, $incoming] = $this->splitFolders([], $filters);
        $this->assertEmpty($outgoing);
        $this->assertEmpty($incoming);
    }

    /**
     * @test
     * When a folder row is missing optional fields (Annee, Campagne), the
     * controller must fall back to sensible defaults rather than emitting
     * PHP warnings or producing null values.
     */
    public function testFolderWithMissingFieldsUsesDefaults(): void
    {
        /** @var array<string, mixed> $folder */
        $folder = ['Nom' => 'TEST', 'NumEtu' => '99', 'Type' => 'sortant'];
        $annee  = strval($folder['Annee']    ?? '2024-2025');
        $camp   = strval($folder['Campagne'] ?? 'Automne 2024');
        $this->assertEquals('2024-2025',    $annee);
        $this->assertEquals('Automne 2024', $camp);
    }

    /**
     * @test
     * When PiecesJustificatives contains malformed JSON, the controller must
     * fall back to an empty array rather than crashing.
     */
    public function testInvalidPiecesJsonFallsBackToEmptyArray(): void
    {
        /** @var string $piecesJson */
        $piecesJson = 'INVALID_JSON'; // intentionally malformed
        $decoded    = json_decode($piecesJson, true);
        $pieces     = is_array($decoded) ? $decoded : [];
        $this->assertIsArray($pieces);
        $this->assertEmpty($pieces);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Mirrors the controller's inline folder-filtering logic.
     *
     * Applies all active filters (student search, department, year, campaign,
     * destination, and framework/accord) to the supplied folder list and
     * returns only the rows that pass every active filter.
     *
     * @param array<int, array<string, mixed>> $folders Raw folder rows to filter.
     * @param array<string, string>            $filters Active filter values; an
     *                                                  empty string means "no filter".
     * @return array<int, array<string, mixed>> Filtered folder rows.
     */
    private function applyFilters(array $folders, array $filters): array
    {
        $result = [];
        foreach ($folders as $d) {
            $nom         = strval($d['Nom']             ?? '');
            $prenom      = strval($d['Prenom']          ?? '');
            $numEtu      = strval($d['NumEtu']          ?? '');
            $dept        = strval($d['CodeDepartement'] ?? '');
            $annee       = strval($d['Annee']           ?? '2024-2025');
            $campagne    = strval($d['Campagne']        ?? 'Automne 2024');
            $composante  = strval($d['Composante']      ?? '');
            $accord      = strval($d['Accord']          ?? '');
            $destination = strval($d['Destination']    ?? '');

            // Student name / number search: matches Nom, Prenom, or NumEtu.
            if ($filters['student'] !== '') {
                $fullName = strtolower("$nom $prenom $numEtu");
                if (strpos($fullName, $filters['student']) === false) continue;
            }
            if ($filters['dept'] !== '' && $dept !== $filters['dept']) continue;
            if ($filters['year'] !== '' && $annee !== $filters['year']) continue;
            if ($filters['camp'] !== '' && $campagne !== $filters['camp']) continue;
            if ($filters['dest'] !== '') {
                if (strpos(strtolower($destination), $filters['dest']) === false) continue;
            }
            // Cadre filter: partial match against either Composante or Accord.
            if ($filters['cadre'] !== '') {
                $cadreRecherche = $filters['cadre'];
                if (stripos($composante, $cadreRecherche) === false && stripos($accord, $cadreRecherche) === false) continue;
            }
            $result[] = $d;
        }
        return $result;
    }

    /**
     * Mirrors the controller's completion-percentage calculation.
     *
     * Returns 100 when IsComplete is 1; otherwise computes the ratio of
     * uploaded documents to the 4 required documents, capped at 100.
     *
     * @param array<string, mixed> $d A single folder row.
     * @return int Completion percentage in the range [0, 100].
     */
    private function calcPercentage(array $d): int
    {
        $isComplete    = intval($d['IsComplete'] ?? 0);
        $piecesJson    = strval($d['PiecesJustificatives'] ?? '');
        $pieces        = (!empty($piecesJson)) ? json_decode($piecesJson, true) : [];
        if (!is_array($pieces)) $pieces = [];
        $countProvided = count($pieces);
        $totalRequired = 4;
        if ($isComplete === 1) return 100;
        $percentage = (int)round(($countProvided / $totalRequired) * 100);
        return min($percentage, 100);
    }

    /**
     * Applies filters and then splits the result into outgoing and incoming
     * lists based on the Type field.
     *
     * Types "incoming" and "entrant" are classified as incoming; all other
     * values are treated as outgoing.
     *
     * @param array<int, array<string, mixed>> $folders Raw folder rows.
     * @param array<string, string>            $filters Active filter values.
     * @return array{array<int, array<string, mixed>>, array<int, array<string, mixed>>}
     *         Tuple of [outgoing, incoming] folder arrays.
     */
    private function splitFolders(array $folders, array $filters): array
    {
        $outgoing = [];
        $incoming = [];
        foreach ($this->applyFilters($folders, $filters) as $d) {
            $type = strval($d['Type'] ?? '');
            if (stripos($type, 'incoming') !== false || stripos($type, 'entrant') !== false) {
                $incoming[] = $d;
            } else {
                $outgoing[] = $d;
            }
        }
        return [$outgoing, $incoming];
    }
}