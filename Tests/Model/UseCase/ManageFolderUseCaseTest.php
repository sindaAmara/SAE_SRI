<?php

namespace Tests\Model\UseCase;

use Model\Repository\FolderRepositoryInterface;
use Model\UseCase\ManageFolderUseCase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see ManageFolderUseCase}.
 *
 * Coverage areas:
 * - getAllFolders() — delegation to the repository
 * - getStudentDetails() — null when not found, JSON parsing of pieces and
 *   document statuses, empty JSON handling
 * - getByNumetu() — delegation to getStudentDetails()
 * - toggleCompleteStatus() — delegation to the repository
 * - setFolderStatus() — delegation to the repository
 * - cycleFolderStatus() — delegation to the repository
 * - rechercherAvecPagination() — argument forwarding
 * - searchWithoutPagination() — per-page forced to zero
 * - creerDossier() — delegation, empty-string-to-null conversion, default
 *   status, invalid date handling, photo encoding to Base64 in the JSON blob
 * - updateDossier() — missing numetu, folder not found, delegation, photo
 *   merge into existing pieces
 * - analyserDocuments() — delegation
 * - enregistrerValidation() — delegation with all arguments
 * - updateDocumentStatus() — folder not found, success, update failure
 *
 * All tests use a mocked {@see FolderRepositoryInterface} so no database
 * access occurs.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/UseCase/ManageFolderUseCaseTest.php
 */
class ManageFolderUseCaseTest extends TestCase
{
    /**
     * Mocked repository injected into the use case under test.
     *
     * @var FolderRepositoryInterface&MockObject
     */
    private FolderRepositoryInterface $repoMock;

    /** Use case instance shared across all tests. */
    private ManageFolderUseCase $useCase;

    /**
     * Creates a fresh use case backed by a mocked repository before each test.
     */
    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(FolderRepositoryInterface::class);
        $this->useCase  = new ManageFolderUseCase($this->repoMock);
    }

    // -------------------------------------------------------------------------
    // Row fixture
    // -------------------------------------------------------------------------

    /**
     * Returns a minimal folder row with sensible defaults.
     *
     * Override individual fields via $overrides to exercise specific code paths
     * without repeating the full row definition in each test.
     *
     * @param array<string, mixed> $overrides Key-value pairs to merge.
     * @return array<string, mixed>
     */
    private function baseDossier(array $overrides = []): array
    {
        return array_merge([
            'NumEtu'               => '12345678',
            'Nom'                  => 'Dupont',
            'Prenom'               => 'Alice',
            'PiecesJustificatives' => '{}',
            'StatutDocuments'      => '{}',
            'DateLimite'           => null,
            'CommentaireAdmin'     => null,
            'status'               => 'depot',
        ], $overrides);
    }

    // =========================================================
    // getAllFolders()
    // =========================================================

    /**
     * @test
     * getAllFolders() must delegate to the repository's findAll() and return
     * its result unchanged.
     */
    public function testGetAllFoldersDelegatesToRepo(): void
    {
        $rows = [$this->baseDossier()];
        $this->repoMock->expects($this->once())->method('findAll')->willReturn($rows);

        $result = $this->useCase->getAllFolders();
        $this->assertSame($rows, $result);
    }

    // =========================================================
    // getStudentDetails() / getByNumetu()
    // =========================================================

    /**
     * @test
     * When the repository returns null for the given numetu, getStudentDetails()
     * must return null.
     */
    public function testGetStudentDetailsReturnsNullWhenNotFound(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn(null);

        $this->assertNull($this->useCase->getStudentDetails('99999999'));
    }

    /**
     * @test
     * When PiecesJustificatives contains a valid JSON object, getStudentDetails()
     * must parse it into a "pieces" array where each entry has a "file" key
     * with the original filename and a "status" key defaulting to "pending".
     */
    public function testGetStudentDetailsReturnsParsedPieces(): void
    {
        $dossier = $this->baseDossier([
            'PiecesJustificatives' => json_encode(['photo' => 'file.jpg']),
        ]);
        $this->repoMock->method('findByNumEtu')->willReturn($dossier);

        $result = $this->useCase->getStudentDetails('12345678');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pieces', $result);
        /** @var array<string, mixed> $pieces */
        $pieces = $result['pieces'];
        $this->assertArrayHasKey('photo', $pieces);
        /** @var array<string, mixed> $photo */
        $photo = $pieces['photo'];
        $this->assertSame('file.jpg', $photo['file']);
        $this->assertSame('pending', $photo['status']); // default status when none is stored
    }

    /**
     * @test
     * When StatutDocuments contains a valid JSON object, getStudentDetails()
     * must parse it into a "statuts" array and preserve each document status.
     */
    public function testGetStudentDetailsReturnsParsedStatuts(): void
    {
        $dossier = $this->baseDossier([
            'StatutDocuments' => json_encode(['photo' => 'valide']),
        ]);
        $this->repoMock->method('findByNumEtu')->willReturn($dossier);

        $result = $this->useCase->getStudentDetails('12345678');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('statuts', $result);
        /** @var array<string, mixed> $statuts */
        $statuts = $result['statuts'];
        $this->assertSame('valide', $statuts['photo']);
    }

    /**
     * @test
     * When PiecesJustificatives is an empty string (rather than "{}"), the
     * "pieces" key must be an empty array rather than null or causing an error.
     */
    public function testGetStudentDetailsHandlesEmptyPiecesJson(): void
    {
        $dossier = $this->baseDossier(['PiecesJustificatives' => '']); // empty string, not "{}"
        $this->repoMock->method('findByNumEtu')->willReturn($dossier);

        $result = $this->useCase->getStudentDetails('12345678');

        $this->assertIsArray($result);
        $this->assertSame([], $result['pieces']);
    }

    /**
     * @test
     * getByNumetu() must delegate to getStudentDetails() and include the raw
     * NumEtu field in the returned array.
     */
    public function testGetByNuMetuDelegatesToGetStudentDetails(): void
    {
        $dossier = $this->baseDossier();
        $this->repoMock->method('findByNumEtu')->willReturn($dossier);

        $result = $this->useCase->getByNumetu('12345678');

        $this->assertIsArray($result);
        $this->assertSame('12345678', $result['NumEtu']);
    }

    // =========================================================
    // toggleCompleteStatus()
    // =========================================================

    /**
     * @test
     * toggleCompleteStatus() must delegate to the repository with the correct
     * numetu and return the repository's boolean result.
     */
    public function testToggleCompleteStatusDelegatesToRepo(): void
    {
        $this->repoMock->expects($this->once())
            ->method('toggleCompleteStatus')
            ->with('12345678')
            ->willReturn(true);

        $this->assertTrue($this->useCase->toggleCompleteStatus('12345678'));
    }

    // =========================================================
    // setFolderStatus()
    // =========================================================

    /**
     * @test
     * setFolderStatus() must delegate to setStatus() on the repository with
     * the correct numetu and status, and return the repository's result.
     */
    public function testSetFolderStatusDelegatesToRepo(): void
    {
        $this->repoMock->expects($this->once())
            ->method('setStatus')
            ->with('12345678', 'accepte')
            ->willReturn(true);

        $this->assertTrue($this->useCase->setFolderStatus('12345678', 'accepte'));
    }

    // =========================================================
    // cycleFolderStatus()
    // =========================================================

    /**
     * @test
     * cycleFolderStatus() must delegate to cycleStatus() on the repository and
     * return its boolean result.
     */
    public function testCycleFolderStatusDelegatesToRepo(): void
    {
        $this->repoMock->expects($this->once())
            ->method('cycleStatus')
            ->with('12345678')
            ->willReturn(true);

        $this->assertTrue($this->useCase->cycleFolderStatus('12345678'));
    }

    // =========================================================
    // rechercherAvecPagination()
    // =========================================================

    /**
     * @test
     * rechercherAvecPagination() must forward all three arguments (filters,
     * page, perPage) to the repository's searchWithPagination() and return its
     * result unchanged.
     */
    public function testRechercherAvecPaginationDelegatesToRepo(): void
    {
        $expected = ['data' => [], 'total' => 0, 'totalPages' => 1];

        $this->repoMock->expects($this->once())
            ->method('searchWithPagination')
            ->with(['type' => 'sortant'], 2, 10)
            ->willReturn($expected);

        $result = $this->useCase->rechercherAvecPagination(['type' => 'sortant'], 2, 10);
        $this->assertSame($expected, $result);
    }

    // =========================================================
    // searchWithoutPagination()
    // =========================================================

    /**
     * @test
     * searchWithoutPagination() must call searchWithPagination() with perPage
     * set to 0 so the repository returns all matching rows without a LIMIT.
     */
    public function testSearchWithoutPaginationPassesPerPageZero(): void
    {
        $this->repoMock->expects($this->once())
            ->method('searchWithPagination')
            ->with([], 1, 0) // perPage = 0 → no pagination
            ->willReturn(['data' => [], 'total' => 0, 'totalPages' => 1]);

        $this->useCase->searchWithoutPagination([]);
    }

    // =========================================================
    // creerDossier()
    // =========================================================

    /**
     * @test
     * creerDossier() must delegate to the repository's create() and return
     * true when the INSERT succeeds.
     */
    public function testCreerDossierDelegatesToRepoCreate(): void
    {
        $this->repoMock->expects($this->once())
            ->method('create')
            ->willReturn(true);

        $data = ['numetu' => '12345678', 'nom' => 'Dupont', 'prenom' => 'Alice', 'naissance' => '2000-01-15'];
        $this->assertTrue($this->useCase->creerDossier($data));
    }

    /**
     * @test
     * Empty string values in the input array must be converted to null before
     * being persisted so the database stores a proper NULL rather than an empty
     * string.
     */
    public function testCreerDossierConvertsEmptyStringsToNull(): void
    {
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $this->repoMock->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        });

        $this->useCase->creerDossier(['numetu' => '12345678', 'nom' => '', 'prenom' => 'Alice']);

        $this->assertIsArray($captured);
        $this->assertNull($captured['Nom']); // empty string → null
    }

    /**
     * @test
     * When no status is supplied, creerDossier() must set the initial status
     * to "depot" — the first step in the workflow.
     */
    public function testCreerDossierSetsDefaultStatusDepot(): void
    {
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $this->repoMock->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        });

        $this->useCase->creerDossier(['numetu' => '12345678']);

        $this->assertIsArray($captured);
        $this->assertSame('depot', $captured['status']); // default workflow entry point
    }

    /**
     * @test
     * When the "naissance" value is not a valid date string, creerDossier()
     * must store null for DateNaissance rather than persisting an invalid value.
     */
    public function testCreerDossierIgnoresInvalidDate(): void
    {
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $this->repoMock->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        });

        $this->useCase->creerDossier(['numetu' => '12345678', 'naissance' => 'not-a-date']);

        $this->assertIsArray($captured);
        $this->assertNull($captured['DateNaissance']); // invalid date → null
    }

    /**
     * @test
     * When a photo binary is provided, creerDossier() must Base64-encode it and
     * store it inside the PiecesJustificatives JSON blob under the "photo" key.
     */
    public function testCreerDossierEncodesPhotoAsPiecesJson(): void
    {
        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $this->repoMock->method('create')->willReturnCallback(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        });

        $this->useCase->creerDossier(['numetu' => '12345678', 'photo' => 'binarydata']);

        $this->assertIsArray($captured);
        /** @var array<string, mixed> $pieces */
        $pieces = json_decode((string) $captured['PiecesJustificatives'], true);
        $this->assertArrayHasKey('photo', $pieces);
        /** @var array<string, mixed> $photo */
        $photo = $pieces['photo'];
        $this->assertSame(base64_encode('binarydata'), $photo['file']); // raw binary → Base64
    }

    // =========================================================
    // updateDossier()
    // =========================================================

    /**
     * @test
     * When the input array contains no numetu key, updateDossier() must return
     * false immediately without querying the repository.
     */
    public function testUpdateDossierReturnsFalseWhenNumetuMissing(): void
    {
        $this->assertFalse($this->useCase->updateDossier([]));
    }

    /**
     * @test
     * When findByNumEtu() returns null (the folder does not exist yet),
     * updateDossier() must return false rather than attempting an update on a
     * non-existent record.
     */
    public function testUpdateDossierReturnsFalseWhenDossierNotFound(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn(null);

        $this->assertFalse($this->useCase->updateDossier(['numetu' => '12345678']));
    }

    /**
     * @test
     * When the folder exists, updateDossier() must call the repository's
     * update() method exactly once and return true on success.
     */
    public function testUpdateDossierDelegatesToRepoUpdate(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn($this->baseDossier());
        $this->repoMock->expects($this->once())->method('update')->willReturn(true);

        $result = $this->useCase->updateDossier(['numetu' => '12345678', 'nom' => 'Martin']);
        $this->assertTrue($result);
    }

    /**
     * @test
     * When a new photo is provided alongside existing pieces, updateDossier()
     * must merge the new photo into the existing JSON blob — preserving other
     * keys — rather than overwriting them.
     */
    public function testUpdateDossierMergesNewPhotoIntoPieces(): void
    {
        $existing = $this->baseDossier([
            'PiecesJustificatives' => json_encode(['cv' => 'old_cv.pdf']),
        ]);
        $this->repoMock->method('findByNumEtu')->willReturn($existing);

        /** @var array<string, mixed>|null $captured */
        $captured = null;
        $this->repoMock->method('update')->willReturnCallback(function (string $num, array $data) use (&$captured) {
            $captured = $data;
            return true;
        });

        $this->useCase->updateDossier(['numetu' => '12345678', 'photo' => 'newphoto']);

        $this->assertIsArray($captured);
        /** @var array<string, mixed> $pieces */
        $pieces = json_decode((string) $captured[':PiecesJustificatives'], true);
        $this->assertArrayHasKey('photo', $pieces); // new photo added
        $this->assertArrayHasKey('cv',    $pieces); // existing cv preserved
    }

    // =========================================================
    // analyserDocuments()
    // =========================================================

    /**
     * @test
     * analyserDocuments() must delegate to the repository with the correct
     * numetu and return its result unchanged.
     */
    public function testAnalyserDocumentsDelegatesToRepo(): void
    {
        $expected = ['manquants' => ['photo'], 'presents' => ['cv'], 'statuts' => []];
        $this->repoMock->expects($this->once())
            ->method('analyserDocuments')
            ->with('12345678')
            ->willReturn($expected);

        $this->assertSame($expected, $this->useCase->analyserDocuments('12345678'));
    }

    // =========================================================
    // enregistrerValidation()
    // =========================================================

    /**
     * @test
     * enregistrerValidation() must forward all four arguments verbatim to the
     * repository and return its boolean result.
     */
    public function testEnregistrerValidationDelegatesToRepo(): void
    {
        $this->repoMock->expects($this->once())
            ->method('enregistrerValidation')
            ->with('12345678', ['photo' => 'valide'], '2025-06-01', 'OK')
            ->willReturn(true);

        $this->assertTrue(
            $this->useCase->enregistrerValidation('12345678', ['photo' => 'valide'], '2025-06-01', 'OK')
        );
    }

    // =========================================================
    // updateDocumentStatus()
    // =========================================================

    /**
     * @test
     * When the folder is not found, updateDocumentStatus() must return false
     * without attempting any update.
     */
    public function testUpdateDocumentStatusReturnsFalseWhenDossierNotFound(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn(null);

        $this->assertFalse($this->useCase->updateDocumentStatus('12345678', 'photo', 'valide', ''));
    }

    /**
     * @test
     * When both the repository update and the validation recording succeed,
     * updateDocumentStatus() must return true.
     */
    public function testUpdateDocumentStatusReturnsTrueOnSuccess(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn($this->baseDossier());
        $this->repoMock->method('update')->willReturn(true);
        $this->repoMock->method('enregistrerValidation')->willReturn(true);

        $result = $this->useCase->updateDocumentStatus('12345678', 'photo', 'valide', 'OK');
        $this->assertTrue($result);
    }

    /**
     * @test
     * When the repository update() returns false, updateDocumentStatus() must
     * return false even if the validation recording would have succeeded.
     */
    public function testUpdateDocumentStatusReturnsFalseWhenUpdateFails(): void
    {
        $this->repoMock->method('findByNumEtu')->willReturn($this->baseDossier());
        $this->repoMock->method('update')->willReturn(false); // update fails
        $this->repoMock->method('enregistrerValidation')->willReturn(true);

        $this->assertFalse($this->useCase->updateDocumentStatus('12345678', 'photo', 'valide', ''));
    }
}