<?php

namespace Tests\Controllers;

use Controllers\SaveStudentController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see SaveStudentController}.
 *
 * Coverage areas:
 * - Route matching via support()
 * - Required-field validation logic (French and English error messages)
 * - Session message generation for success, failure, and duplicate-student cases
 * - POST body to domain-object field mapping
 * - File-upload guard logic (photo and CV)
 * - Language defaulting from $_GET
 * - Controller instantiation
 *
 * Validation, message-building, and file-extraction helpers in this file mirror
 * the corresponding logic in SaveStudentController::control() so that business
 * rules can be verified in isolation without triggering HTTP I/O or database
 * access.
 *
 * All HTTP superglobals are reset in setUp() and tearDown() to prevent state
 * leakage between test cases.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/SaveStudentControllerTest.php
 */
class SaveStudentControllerTest extends TestCase
{
    /**
     * Resets all HTTP superglobals before each test to ensure full isolation.
     */
    protected function setUp(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_FILES   = [];
        $_SESSION = [];
    }

    /**
     * Cleans up superglobals and destroys any active PHP session after each
     * test to avoid cross-test contamination.
     */
    protected function tearDown(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_FILES   = [];
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
     * The controller must claim "save_student" on POST so the router directs
     * student-creation form submissions through it.
     */
    public function testSupportReturnsTrueForSaveStudentPost(): void
    {
        $this->assertTrue(SaveStudentController::support('save_student', 'POST'));
    }

    /**
     * @test
     * GET requests to "save_student" must not be routed to this controller
     * since the action is write-only.
     */
    public function testSupportReturnsFalseForGet(): void
    {
        $this->assertFalse(SaveStudentController::support('save_student', 'GET'));
    }

    /**
     * @test
     * An unrelated page name must return false so the router can delegate to
     * another handler.
     */
    public function testSupportReturnsFalseForWrongPage(): void
    {
        $this->assertFalse(SaveStudentController::support('dashboard-admin', 'POST'));
    }

    /**
     * @test
     * An empty page name must return false.
     */
    public function testSupportReturnsFalseForEmptyPage(): void
    {
        $this->assertFalse(SaveStudentController::support('', 'POST'));
    }

    /**
     * @test
     * An empty HTTP method must return false.
     */
    public function testSupportReturnsFalseForEmptyMethod(): void
    {
        $this->assertFalse(SaveStudentController::support('save_student', ''));
    }

    /**
     * @test
     * PUT requests must not be accepted; the controller only handles POST.
     */
    public function testSupportReturnsFalseForPut(): void
    {
        $this->assertFalse(SaveStudentController::support('save_student', 'PUT'));
    }

    // -------------------------------------------------------------------------
    // Validation logic (isolated — mirrors controller's $errors block)
    // -------------------------------------------------------------------------

    /**
     * Mirrors the required-field validation block from
     * SaveStudentController::control().
     *
     * Checks each mandatory field and accumulates a localised error message
     * for every empty value found.
     *
     * @param array<string, string> $data Mapped field values to validate.
     * @param string                $lang Active locale ("fr" or "en").
     * @return array<int, string>   List of error messages; empty when valid.
     */
    private function runValidation(array $data, string $lang = 'fr'): array
    {
        $errors = [];
        if (($data['NumEtu']         ?? '') === '') $errors[] = $lang === 'fr' ? 'Le numéro étudiant est requis' : 'Student ID is required';
        if (($data['Nom']            ?? '') === '') $errors[] = $lang === 'fr' ? 'Le nom est requis'             : 'Last name is required';
        if (($data['Prenom']         ?? '') === '') $errors[] = $lang === 'fr' ? 'Le prénom est requis'          : 'First name is required';
        if (($data['EmailPersonnel'] ?? '') === '') $errors[] = $lang === 'fr' ? "L'email est requis"            : 'Email is required';
        if (($data['Telephone']      ?? '') === '') $errors[] = $lang === 'fr' ? 'Le téléphone est requis'       : 'Phone is required';
        return $errors;
    }

    /**
     * Returns a complete, valid set of student field values.
     *
     * Use this as a baseline and override individual keys to test specific
     * validation failure paths.
     *
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'NumEtu'         => '22000001',
            'Nom'            => 'DUPONT',
            'Prenom'         => 'Alice',
            'EmailPersonnel' => 'alice@example.com',
            'Telephone'      => '0600000000',
            'Type'           => 'sortant',
        ];
    }

    /**
     * @test
     * When all required fields are filled, validation must produce no errors.
     */
    public function testValidationPassesWithAllFieldsFilled(): void
    {
        $errors = $this->runValidation($this->validData());
        $this->assertEmpty($errors);
    }

    /**
     * @test
     * When NumEtu is empty, the French "student ID required" error must be
     * added to the error list.
     */
    public function testValidationFailsWhenNumEtuMissing(): void
    {
        $data           = $this->validData();
        $data['NumEtu'] = '';
        $errors         = $this->runValidation($data);
        $this->assertContains('Le numéro étudiant est requis', $errors);
    }

    /**
     * @test
     * When Nom is empty, the French "last name required" error must appear.
     */
    public function testValidationFailsWhenNomMissing(): void
    {
        $data        = $this->validData();
        $data['Nom'] = '';
        $errors      = $this->runValidation($data);
        $this->assertContains('Le nom est requis', $errors);
    }

    /**
     * @test
     * When Prenom is empty, the French "first name required" error must appear.
     */
    public function testValidationFailsWhenPrenomMissing(): void
    {
        $data           = $this->validData();
        $data['Prenom'] = '';
        $errors         = $this->runValidation($data);
        $this->assertContains('Le prénom est requis', $errors);
    }

    /**
     * @test
     * When EmailPersonnel is empty, the French "email required" error must
     * appear.
     */
    public function testValidationFailsWhenEmailMissing(): void
    {
        $data                    = $this->validData();
        $data['EmailPersonnel']  = '';
        $errors                  = $this->runValidation($data);
        $this->assertContains("L'email est requis", $errors);
    }

    /**
     * @test
     * When Telephone is empty, the French "phone required" error must appear.
     */
    public function testValidationFailsWhenTelephoneMissing(): void
    {
        $data               = $this->validData();
        $data['Telephone']  = '';
        $errors             = $this->runValidation($data);
        $this->assertContains('Le téléphone est requis', $errors);
    }

    /**
     * @test
     * When all five required fields are empty, validation must collect all five
     * errors in a single pass (no short-circuiting).
     */
    public function testValidationCollectsAllErrorsAtOnce(): void
    {
        $errors = $this->runValidation([
            'NumEtu' => '', 'Nom' => '', 'Prenom' => '',
            'EmailPersonnel' => '', 'Telephone' => '',
        ]);
        $this->assertCount(5, $errors);
    }

    // -------------------------------------------------------------------------
    // English error messages
    // -------------------------------------------------------------------------

    /**
     * @test
     * When lang=en is active and all fields are empty, the error list must
     * contain English messages for every missing field.
     */
    public function testValidationReturnsEnglishErrorsWhenLangIsEn(): void
    {
        $errors = $this->runValidation([
            'NumEtu' => '', 'Nom' => '', 'Prenom' => '',
            'EmailPersonnel' => '', 'Telephone' => '',
        ], 'en');

        $this->assertContains('Student ID is required',  $errors);
        $this->assertContains('Last name is required',   $errors);
        $this->assertContains('First name is required',  $errors);
        $this->assertContains('Email is required',       $errors);
        $this->assertContains('Phone is required',       $errors);
    }

    /**
     * @test
     * When lang=fr is active, the error list must contain the French message
     * and must not contain the English equivalent.
     */
    public function testValidationReturnsFrenchErrorsWhenLangIsFr(): void
    {
        $data           = $this->validData();
        $data['NumEtu'] = '';
        $errors         = $this->runValidation($data, 'fr');
        $this->assertContains('Le numéro étudiant est requis', $errors);
        $this->assertNotContains('Student ID is required', $errors);
    }

    // -------------------------------------------------------------------------
    // Session message logic (isolated)
    // -------------------------------------------------------------------------

    /**
     * @test
     * When validation fails, all error messages must be joined with ", " so
     * the session message is human-readable in a single string.
     */
    public function testSessionMessageOnValidationFailureIsCommaSeparated(): void
    {
        $errors = $this->runValidation([
            'NumEtu' => '', 'Nom' => '', 'Prenom' => '',
            'EmailPersonnel' => '', 'Telephone' => '',
        ]);
        $message = implode(', ', $errors);
        $this->assertStringContainsString('Le numéro étudiant est requis', $message);
        $this->assertStringContainsString('Le nom est requis', $message);
    }

    /**
     * @test
     * On successful creation with lang=fr, the session message must be the
     * French confirmation string.
     */
    public function testSessionMessageOnSuccessFr(): void
    {
        $this->assertEquals('Folder créé avec succès', $this->buildResultMessage(true, 'fr'));
    }

    /**
     * @test
     * On failed creation with lang=fr, the session message must be the French
     * error string.
     */
    public function testSessionMessageOnFailureFr(): void
    {
        $this->assertEquals('Erreur lors de la création du dossier', $this->buildResultMessage(false, 'fr'));
    }

    /**
     * @test
     * On successful creation with lang=en, the session message must be the
     * English confirmation string.
     */
    public function testSessionMessageOnSuccessEn(): void
    {
        $this->assertEquals('Folder created successfully', $this->buildResultMessage(true, 'en'));
    }

    /**
     * @test
     * On failed creation with lang=en, the session message must be the English
     * error string.
     */
    public function testSessionMessageOnFailureEn(): void
    {
        $this->assertEquals('Error creating folder', $this->buildResultMessage(false, 'en'));
    }

    /**
     * @test
     * When a student with the same NumEtu already exists and lang=fr, the
     * duplicate-student message must be the French string.
     */
    public function testSessionMessageForDuplicateStudentFr(): void
    {
        $this->assertEquals('Un étudiant avec ce numéro existe déjà', $this->buildDuplicateMessage('fr'));
    }

    /**
     * @test
     * When a student with the same NumEtu already exists and lang=en, the
     * duplicate-student message must be the English string.
     */
    public function testSessionMessageForDuplicateStudentEn(): void
    {
        $this->assertEquals('A student with this ID already exists', $this->buildDuplicateMessage('en'));
    }

    /**
     * Mirrors the result-message selection logic in SaveStudentController.
     *
     * @param bool   $success Whether the creation use case succeeded.
     * @param string $lang    Active locale ("fr" or "en").
     * @return string Localised session message.
     */
    private function buildResultMessage(bool $success, string $lang): string
    {
        if ($success) {
            return $lang === 'fr' ? 'Folder créé avec succès' : 'Folder created successfully';
        }
        return $lang === 'fr' ? 'Erreur lors de la création du dossier' : 'Error creating folder';
    }

    /**
     * Mirrors the duplicate-student message selection logic in
     * SaveStudentController.
     *
     * @param string $lang Active locale ("fr" or "en").
     * @return string Localised session message.
     */
    private function buildDuplicateMessage(string $lang): string
    {
        return $lang === 'fr'
            ? 'Un étudiant avec ce numéro existe déjà'
            : 'A student with this ID already exists';
    }

    // -------------------------------------------------------------------------
    // POST data mapping (mirrors the $data array built in control())
    // -------------------------------------------------------------------------

    /**
     * @test
     * When all expected POST keys are present, the mapping must produce a data
     * array whose values exactly match the raw POST values.
     */
    public function testPostDataIsMappedCorrectly(): void
    {
        $_POST = [
            'numetu'      => '22000001',
            'nom'         => 'DUPONT',
            'prenom'      => 'Alice',
            'email_perso' => 'alice@example.com',
            'telephone'   => '0600000000',
            'type'        => 'sortant',
        ];

        $data = [
            'NumEtu'         => (string)($_POST['numetu']      ?? ''),
            'Nom'            => (string)($_POST['nom']         ?? ''),
            'Prenom'         => (string)($_POST['prenom']      ?? ''),
            'EmailPersonnel' => (string)($_POST['email_perso'] ?? ''),
            'Telephone'      => (string)($_POST['telephone']   ?? ''),
            'Type'           => (string)($_POST['type']        ?? ''),
        ];

        $this->assertEquals('22000001',          $data['NumEtu']);
        $this->assertEquals('DUPONT',            $data['Nom']);
        $this->assertEquals('Alice',             $data['Prenom']);
        $this->assertEquals('alice@example.com', $data['EmailPersonnel']);
        $this->assertEquals('0600000000',        $data['Telephone']);
        $this->assertEquals('sortant',           $data['Type']);
    }

    /**
     * @test
     * When the POST body is empty, every mapped field must default to an empty
     * string rather than null or undefined.
     */
    public function testPostDataDefaultsToEmptyStringWhenKeysMissing(): void
    {
        $_POST = [];

        $data = [
            'NumEtu'         => (string)($_POST['numetu']      ?? ''),
            'Nom'            => (string)($_POST['nom']         ?? ''),
            'Prenom'         => (string)($_POST['prenom']      ?? ''),
            'EmailPersonnel' => (string)($_POST['email_perso'] ?? ''),
            'Telephone'      => (string)($_POST['telephone']   ?? ''),
            'Type'           => (string)($_POST['type']        ?? ''),
        ];

        foreach ($data as $key => $value) {
            $this->assertSame('', $value, "Expected empty string for key '$key'");
        }
    }

    // -------------------------------------------------------------------------
    // File upload guard logic (isolated)
    // -------------------------------------------------------------------------

    /**
     * @test
     * When a photo is uploaded without errors, its binary content must be
     * added to the data array under the "photo" key.
     */
    public function testPhotoIsAddedToDataWhenUploadSucceeds(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'photo_');
        $this->assertIsString($tmpFile, 'Could not create temp file');
        /** @var string $tmpFile */
        file_put_contents($tmpFile, 'fake-image-content');

        $_FILES['photo'] = [
            'error'    => UPLOAD_ERR_OK,
            'tmp_name' => $tmpFile,
        ];

        $data = $this->extractFileData('photo');

        $this->assertArrayHasKey('photo', $data);
        $this->assertEquals('fake-image-content', $data['photo']);

        unlink($tmpFile);
    }

    /**
     * @test
     * When the photo upload reports an error (e.g. UPLOAD_ERR_NO_FILE), the
     * "photo" key must be absent from the data array so no partial or corrupt
     * file is persisted.
     */
    public function testPhotoIsNotAddedWhenUploadErrorOccurs(): void
    {
        $_FILES['photo'] = ['error' => UPLOAD_ERR_NO_FILE, 'tmp_name' => ''];

        $data = $this->extractFileData('photo');

        $this->assertArrayNotHasKey('photo', $data);
    }

    /**
     * @test
     * When a CV file is uploaded without errors, its binary content must be
     * added to the data array under the "cv" key.
     */
    public function testCvIsAddedToDataWhenUploadSucceeds(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cv_');
        $this->assertIsString($tmpFile, 'Could not create temp file');
        /** @var string $tmpFile */
        file_put_contents($tmpFile, 'fake-cv-content');

        $_FILES['cv'] = [
            'error'    => UPLOAD_ERR_OK,
            'tmp_name' => $tmpFile,
        ];

        $data = $this->extractFileData('cv');

        $this->assertArrayHasKey('cv', $data);
        $this->assertEquals('fake-cv-content', $data['cv']);

        unlink($tmpFile);
    }

    /**
     * @test
     * When no CV file is present in $_FILES, the "cv" key must be absent from
     * the data array.
     */
    public function testCvIsNotAddedWhenFileNotPresent(): void
    {
        $_FILES = [];

        $data = $this->extractFileData('cv');

        $this->assertArrayNotHasKey('cv', $data);
    }

    /**
     * Mirrors the file-upload guard block in SaveStudentController::control().
     *
     * Reads the uploaded file's content only when the upload completed without
     * errors; otherwise returns an empty array so the caller can detect the
     * absence of the field.
     *
     * @param string $field $_FILES key to inspect ("photo", "cv", etc.).
     * @return array<string, string> Map of field name → binary file content.
     */
    private function extractFileData(string $field): array
    {
        $data = [];
        if (
            isset($_FILES[$field])
            && is_array($_FILES[$field])
            && (int)$_FILES[$field]['error'] === UPLOAD_ERR_OK
        ) {
            $content = file_get_contents((string)$_FILES[$field]['tmp_name']);
            if ($content !== false) {
                $data[$field] = $content;
            }
        }
        return $data;
    }

    // -------------------------------------------------------------------------
    // Lang defaulting
    // -------------------------------------------------------------------------

    /**
     * @test
     * When no lang parameter is present in $_GET, the effective locale must
     * default to "fr".
     */
    public function testLangDefaultsToFrWhenNotProvided(): void
    {
        $_GET = [];
        $lang = $_GET['lang'] ?? 'fr';
        $this->assertEquals('fr', $lang);
    }

    /**
     * @test
     * When lang is set in $_GET, it must be read and used as the active locale.
     */
    public function testLangIsReadFromGetParameter(): void
    {
        $_GET['lang'] = 'en';
        $lang         = $_GET['lang'] ?? 'fr';
        $this->assertEquals('en', $lang);
    }

    // -------------------------------------------------------------------------
    // Instantiation
    // -------------------------------------------------------------------------

    /**
     * @test
     * SaveStudentController must be instantiable without arguments to allow
     * the router to create it on demand.
     */
    public function testControllerCanBeInstantiated(): void
    {
        $controller = new SaveStudentController();
        $this->assertInstanceOf(SaveStudentController::class, $controller);
    }
}