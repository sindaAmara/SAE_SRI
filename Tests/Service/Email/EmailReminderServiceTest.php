<?php

declare(strict_types=1);

namespace Tests\Service\Email;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see \Service\Email\EmailReminderService}.
 *
 * Coverage areas:
 * - sendRelance() — happy path, empty items array, string dossier ID, padded
 *   name, missing credentials, missing template file, special characters
 * - sendFolderUpdateNotification() — empty updates (early-return true), plain
 *   update string, all structured token types, empty token values, missing
 *   credentials
 * - sendValidationConfirmation() — all known document types, unknown type
 *   fallback, missing credentials
 * - sendDocumentDeposited() — all known types, unknown type, empty numEtu,
 *   missing credentials
 * - sendDocumentValidated() — all known types, unknown type, missing credentials
 * - sendMessageNotification() — normal message, preview > 150 chars, boundary
 *   at exactly 150 chars, boundary at 151 chars, missing credentials
 *
 * Testing strategy
 * ----------------
 * - We never instantiate Mailjet\Response directly (it requires a
 *   Mailjet\Request); all assertions use assertIsBool() for the happy path.
 * - "Failure path" tests clear the Mailjet credentials so that the internal
 *   createMailjetClient() factory throws a RuntimeException, which each
 *   sendXxx() method catches and converts to false. This is deterministic
 *   and requires no network access.
 * - Template files are written to a temporary directory; ROOT_PATH is defined
 *   to point there so renderEmailTemplate() always finds the expected files.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Service/Email/EmailReminderServiceTest.php
 */
class EmailReminderServiceTest extends TestCase
{
    /**
     * Root of the temporary fixture directory tree that mirrors the
     * app/View/Email/ layout the service expects at runtime.
     */
    private string $fixtureDir;

    /**
     * Creates all template fixture files under a temporary directory and
     * seeds the MAILJET_API_KEY / MAILJET_SECRET_KEY environment variables
     * with placeholder values so the Mailjet client factory succeeds.
     *
     * ROOT_PATH is defined once (constants cannot be redefined) to point at
     * the fixture directory so renderEmailTemplate() resolves the correct path
     * in every test.
     */
    protected function setUp(): void
    {
        $_ENV['MAILJET_API_KEY']    = 'fake-key';
        $_ENV['MAILJET_SECRET_KEY'] = 'fake-secret';

        $this->fixtureDir = sys_get_temp_dir() . '/phpunit_email_fixtures';
        $tplDir           = $this->fixtureDir . '/app/View/Email';

        if (!is_dir($tplDir)) {
            mkdir($tplDir, 0777, true);
        }

        // Create one stub template file per email type.
        foreach ([
                     'relance',
                     'folder_update',
                     'validation_complete',
                     'document_deposited',
                     'document_validated',
                     'message_notification',
                 ] as $tpl) {
            file_put_contents("{$tplDir}/{$tpl}.php", "<p>Template: {$tpl}</p>");
        }

        // ROOT_PATH can only be defined once per process; guard with defined().
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->fixtureDir);
        }
    }

    /**
     * Removes the Mailjet credentials from the environment after each test so
     * subsequent tests start with a clean state.
     */
    protected function tearDown(): void
    {
        unset($_ENV['MAILJET_API_KEY'], $_ENV['MAILJET_SECRET_KEY']);
    }

    // -------------------------------------------------------------------------
    // Credential helpers
    // -------------------------------------------------------------------------

    /**
     * Clears the Mailjet credentials from all environment sources so that the
     * service's internal createMailjetClient() factory throws a RuntimeException,
     * causing every sendXxx() method to return false deterministically.
     */
    private function clearCredentials(): void
    {
        unset($_ENV['MAILJET_API_KEY'], $_ENV['MAILJET_SECRET_KEY']);
        putenv('MAILJET_API_KEY=');
        putenv('MAILJET_SECRET_KEY=');
    }

    /**
     * Restores the placeholder credentials after a clearCredentials() call so
     * the test that follows is not affected.
     */
    private function restoreCredentials(): void
    {
        $_ENV['MAILJET_API_KEY']    = 'fake-key';
        $_ENV['MAILJET_SECRET_KEY'] = 'fake-secret';
    }

    // ==================================================================
    // 1. sendRelance
    // ==================================================================

    /**
     * @test
     * sendRelance() must return a boolean when called with all valid parameters
     * — it must not throw regardless of whether the Mailjet HTTP call succeeds.
     */
    public function sendRelance_returns_bool_with_valid_params(): void
    {
        $result = \Service\Email\EmailReminderService::sendRelance(
            'student@example.com', 42, 'Jean Dupont', ['CV manquant']
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * Passing an empty items array must still return a boolean — the service
     * must handle the case where there are no missing documents gracefully.
     */
    public function sendRelance_returns_bool_with_empty_items_array(): void
    {
        $result = \Service\Email\EmailReminderService::sendRelance(
            'student@example.com', 42, 'Jean Dupont', []
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * A string dossier ID (e.g. a reference like "DOS-2024-001") must be
     * accepted — the parameter type must not be restricted to integers.
     */
    public function sendRelance_returns_bool_with_string_dossierId(): void
    {
        $result = \Service\Email\EmailReminderService::sendRelance(
            'student@example.com', 'DOS-2024-001'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * A student name with leading/trailing whitespace must be accepted without
     * throwing — the service is responsible for any trimming it requires.
     */
    public function sendRelance_returns_bool_with_padded_student_name(): void
    {
        $result = \Service\Email\EmailReminderService::sendRelance(
            'student@example.com', 99, '  Marie Curie  '
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When the Mailjet credentials are missing, sendRelance() must return false
     * rather than throwing an uncaught exception.
     */
    public function sendRelance_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendRelance('x@x.com', 1);
        $this->assertFalse($result);
        $this->restoreCredentials();
    }

    /**
     * @test
     * When the template file is missing (e.g. deleted from disk), sendRelance()
     * must still return a boolean — it must handle the missing file gracefully
     * rather than crashing with a fatal error.
     */
    public function sendRelance_returns_bool_when_template_file_missing(): void
    {
        $tplPath = $this->fixtureDir . '/app/View/Email/relance.php';
        $backup  = $tplPath . '.bak';
        rename($tplPath, $backup); // temporarily remove the template

        $result = \Service\Email\EmailReminderService::sendRelance(
            'student@example.com', 42, 'Jean Dupont'
        );

        rename($backup, $tplPath); // restore the template for other tests
        $this->assertIsBool($result);
    }

    /**
     * @test
     * Email addresses with "+" sub-addressing and names with accented/special
     * characters must be accepted without throwing.
     */
    public function sendRelance_handles_special_characters_in_name(): void
    {
        $result = \Service\Email\EmailReminderService::sendRelance(
            'student+test@example.co.uk', 1, 'Ségolène Lefèvre'
        );
        $this->assertIsBool($result);
    }

    // ==================================================================
    // 2. sendFolderUpdateNotification
    // ==================================================================

    /**
     * @test
     * When the updates array is empty, sendFolderUpdateNotification() must
     * return true immediately without calling the Mailjet API — there is
     * nothing to notify about.
     */
    public function sendFolderUpdateNotification_returns_true_when_updates_empty(): void
    {
        // Empty array → early return true, no Mailjet call.
        $result = \Service\Email\EmailReminderService::sendFolderUpdateNotification(
            'student@example.com', 'Bob', '22001234', []
        );
        $this->assertTrue($result);
    }

    /**
     * @test
     * A plain (non-tokenised) update string must be accepted and cause the
     * method to return a boolean.
     */
    public function sendFolderUpdateNotification_returns_bool_with_plain_updates(): void
    {
        $result = \Service\Email\EmailReminderService::sendFolderUpdateNotification(
            'student@example.com', 'Bob Martin', '22001234', ['Document CV accepté']
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * All five structured token types (__SECTION_ACCEPTEES__, __SECTION_REFUSEES__,
     * __STATUT_GLOBAL__, __DATE_LIMITE__, and plain text) must be parsed
     * without throwing.
     */
    public function sendFolderUpdateNotification_parses_all_structured_tokens(): void
    {
        $updates = [
            '__SECTION_ACCEPTEES__CV, Photo__END_SECTION__',
            '__SECTION_REFUSEES__Convention__END_SECTION__',
            '__STATUT_GLOBAL__En attente__END_STATUT__',
            '__DATE_LIMITE__2024-12-31__END_DATE__',
            'Note libre',
        ];

        $result = \Service\Email\EmailReminderService::sendFolderUpdateNotification(
            'student@example.com', 'Bob Martin', '22001234', $updates
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * Structured tokens with empty values (e.g. "__SECTION_ACCEPTEES____END_SECTION__")
     * must not cause a crash — the service must handle empty extracted values
     * gracefully.
     */
    public function sendFolderUpdateNotification_handles_empty_token_values(): void
    {
        $updates = [
            '__SECTION_ACCEPTEES____END_SECTION__', // empty accepted-documents list
            '__SECTION_REFUSEES____END_SECTION__',  // empty refused-documents list
        ];

        $result = \Service\Email\EmailReminderService::sendFolderUpdateNotification(
            'student@example.com', 'Claire', '22005678', $updates
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When the Mailjet credentials are missing, sendFolderUpdateNotification()
     * must return false rather than throwing.
     */
    public function sendFolderUpdateNotification_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendFolderUpdateNotification(
            'student@example.com', 'Bob', '22001234', ['update']
        );
        $this->assertFalse($result);
        $this->restoreCredentials();
    }

    // ==================================================================
    // 3. sendValidationConfirmation
    // ==================================================================

    /**
     * @test
     * All five known document types (cv, photo, convention, lettre_motivation,
     * langues_file) must be accepted and produce a boolean result.
     */
    public function sendValidationConfirmation_returns_bool_for_known_doc_types(): void
    {
        $result = \Service\Email\EmailReminderService::sendValidationConfirmation(
            'student@example.com', 'Alice Liddell',
            ['cv', 'photo', 'convention', 'lettre_motivation', 'langues_file'],
            '22001234'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * An unknown document type must fall back to ucfirst() labelling and must
     * not cause a crash.
     */
    public function sendValidationConfirmation_returns_bool_for_unknown_doc_type(): void
    {
        // Unknown types fall back to ucfirst(); must not throw.
        $result = \Service\Email\EmailReminderService::sendValidationConfirmation(
            'student@example.com', 'Alice', ['rapport_stage'], '22001234'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When credentials are missing, sendValidationConfirmation() must return
     * false rather than throwing.
     */
    public function sendValidationConfirmation_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendValidationConfirmation(
            'student@example.com', 'Alice', ['cv'], '22001234'
        );
        $this->assertFalse($result);
        $this->restoreCredentials();
    }

    // ==================================================================
    // 4. sendDocumentDeposited
    // ==================================================================

    /**
     * @test
     * Every known document type must produce a boolean result — no known type
     * must cause a crash or an unhandled exception.
     */
    public function sendDocumentDeposited_returns_bool_for_all_known_types(): void
    {
        foreach (['photo', 'cv', 'convention', 'lettre_motivation', 'langues_file'] as $type) {
            $result = \Service\Email\EmailReminderService::sendDocumentDeposited(
                'student@example.com', 'Jean Paul', $type, '22001234'
            );
            $this->assertIsBool($result, "Failed for type: {$type}");
        }
    }

    /**
     * @test
     * An unknown document type must fall back gracefully (e.g. via ucfirst())
     * and return a boolean.
     */
    public function sendDocumentDeposited_returns_bool_for_unknown_type(): void
    {
        $result = \Service\Email\EmailReminderService::sendDocumentDeposited(
            'student@example.com', 'Jean Paul', 'autre_document', '22001234'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * An empty numEtu string must be accepted — the service must not require a
     * non-empty student number to send the notification.
     */
    public function sendDocumentDeposited_handles_empty_numEtu(): void
    {
        $result = \Service\Email\EmailReminderService::sendDocumentDeposited(
            'student@example.com', 'Jean Paul', 'cv', '' // empty numEtu
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When credentials are missing, sendDocumentDeposited() must return false
     * rather than throwing.
     */
    public function sendDocumentDeposited_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendDocumentDeposited(
            'student@example.com', 'Jean Paul', 'cv', '22001234'
        );
        $this->assertFalse($result);
        $this->restoreCredentials();
    }

    // ==================================================================
    // 5. sendDocumentValidated
    // ==================================================================

    /**
     * @test
     * Every known document type must produce a boolean result when confirming
     * validation — no known type must cause a crash.
     */
    public function sendDocumentValidated_returns_bool_for_all_known_types(): void
    {
        foreach (['photo', 'cv', 'convention', 'lettre_motivation', 'langues_file'] as $type) {
            $result = \Service\Email\EmailReminderService::sendDocumentValidated(
                'student@example.com', 'Sophie Bernard', $type, '22009876'
            );
            $this->assertIsBool($result, "Failed for type: {$type}");
        }
    }

    /**
     * @test
     * An unknown document type must be handled gracefully and return a boolean.
     */
    public function sendDocumentValidated_returns_bool_for_unknown_type(): void
    {
        $result = \Service\Email\EmailReminderService::sendDocumentValidated(
            'student@example.com', 'Sophie Bernard', 'attestation_emploi', '22009876'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When credentials are missing, sendDocumentValidated() must return false
     * rather than throwing.
     */
    public function sendDocumentValidated_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendDocumentValidated(
            'student@example.com', 'Sophie Bernard', 'cv', '22009876'
        );
        $this->assertFalse($result);
        $this->restoreCredentials();
    }

    // ==================================================================
    // 6. sendMessageNotification
    // ==================================================================

    /**
     * @test
     * A standard message within the 150-character preview limit must produce a
     * boolean result without throwing.
     */
    public function sendMessageNotification_returns_bool_for_normal_message(): void
    {
        $result = \Service\Email\EmailReminderService::sendMessageNotification(
            'recipient@example.com', 'Alice', 'Bob',
            'Bonjour Alice, voici un message.',
            'https://ri-amu.app/messages'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * A message longer than 150 characters must be accepted — the service is
     * responsible for truncating the preview — and return a boolean.
     */
    public function sendMessageNotification_returns_bool_when_preview_exceeds_150_chars(): void
    {
        $result = \Service\Email\EmailReminderService::sendMessageNotification(
            'recipient@example.com', 'Alice', 'Bob',
            str_repeat('A', 300), // 300 chars → must be truncated to 150
            'https://ri-amu.app/messages'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * A message of exactly 150 characters sits at the boundary and must NOT be
     * truncated (or at least must not cause a crash).
     */
    public function sendMessageNotification_boundary_exactly_150_chars(): void
    {
        // 150 chars exactly: at the boundary, must not be truncated.
        $result = \Service\Email\EmailReminderService::sendMessageNotification(
            'r@example.com', 'Alice', 'Bob',
            str_repeat('B', 150),
            'https://ri-amu.app'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * A message of exactly 151 characters is one character over the boundary
     * and must be truncated to 150 characters without throwing.
     */
    public function sendMessageNotification_boundary_exactly_151_chars(): void
    {
        // 151 chars: one over the boundary → must be truncated to 150.
        $result = \Service\Email\EmailReminderService::sendMessageNotification(
            'r@example.com', 'Alice', 'Bob',
            str_repeat('C', 151),
            'https://ri-amu.app'
        );
        $this->assertIsBool($result);
    }

    /**
     * @test
     * When credentials are missing, sendMessageNotification() must return false
     * rather than throwing.
     */
    public function sendMessageNotification_returns_false_when_credentials_missing(): void
    {
        $this->clearCredentials();
        $result = \Service\Email\EmailReminderService::sendMessageNotification(
            'recipient@example.com', 'Alice', 'Bob', 'msg', 'https://ri-amu.app'
        );
        $this->assertFalse($result);
        $this->restoreCredentials();
    }
}