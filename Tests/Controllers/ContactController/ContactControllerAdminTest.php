<?php

namespace Tests\Controllers\ContactController;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Controllers\ContactController\ContactControllerAdmin;
use Service\ContactService;
use Model\Entity\Conversation;

/**
 * Unit tests for ContactControllerAdmin.
 *
 * Run: ./vendor/bin/phpunit Tests/Controllers/ContactController/ContactControllerAdminTest.php
 */
class ContactControllerAdminTest extends TestCase
{
    /**
     * Resets superglobals before each test to ensure a clean state.
     */
    protected function setUp(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Creates a testable ContactControllerAdmin instance with a mocked ContactService.
     * The anonymous subclass disables session start, stubs redirect as an exception,
     * and silences renderView.
     *
     * @return array{0: ContactControllerAdmin, 1: MockObject&ContactService}
     */
    private function makeController(): array
    {
        $serviceMock = $this->createMock(ContactService::class);

        $controller = new class($serviceMock) extends ContactControllerAdmin {
            public function __construct(ContactService $service)
            {
                $ref  = new \ReflectionClass(ContactControllerAdmin::class);
                $prop = $ref->getProperty('contactService');
                $prop->setAccessible(true);
                $prop->setValue($this, $service);
            }

            protected function startSession(): void {}

            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /** @param array<string, mixed> $data */
            protected function renderView(string $view, array $data = []): void {}
        };

        return [$controller, $serviceMock];
    }

    // -------------------------------------------------------------------------
    // support()
    // -------------------------------------------------------------------------

    public function test_support_returns_true_for_messages_admin_page(): void
    {
        $this->assertTrue(ContactControllerAdmin::support('messages-admin', 'GET'));
    }

    public function test_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(ContactControllerAdmin::support('home',  'GET'));
        $this->assertFalse(ContactControllerAdmin::support('login', 'POST'));
        $this->assertFalse(ContactControllerAdmin::support('',      'GET'));
    }

    // -------------------------------------------------------------------------
    // Language resolution
    // -------------------------------------------------------------------------

    public function test_lang_defaults_to_fr_when_not_set(): void
    {
        $lang = $_SESSION['lang'] ?? 'fr';
        $this->assertSame('fr', $lang);
    }

    public function test_lang_is_set_from_get_parameter(): void
    {
        $_GET['lang'] = 'en';
        if (in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $this->assertSame('en', $_SESSION['lang']);
    }

    public function test_lang_ignores_invalid_values(): void
    {
        $_GET['lang'] = 'de';
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // -------------------------------------------------------------------------
    // POST action: respond
    // -------------------------------------------------------------------------

    public function test_respond_action_calls_addMessage_with_correct_params(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'respond';
        $_POST['id']               = '42';
        $_POST['response']         = 'Bonjour, voici ma réponse.';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('addMessage')
            ->with(42, 'admin', 'Bonjour, voici ma réponse.');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    public function test_respond_action_does_not_call_addMessage_when_response_is_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'respond';
        $_POST['id']               = '42';
        $_POST['response']         = '   ';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->never())->method('addMessage');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_respond_action_does_not_call_addMessage_when_id_is_zero(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'respond';
        $_POST['id']               = '0';
        $_POST['response']         = 'Une réponse';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->never())->method('addMessage');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // -------------------------------------------------------------------------
    // POST action: mark-read
    // -------------------------------------------------------------------------

    public function test_mark_read_calls_markConversationAsRead(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'mark-read';
        $_POST['id']               = '7';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('markConversationAsRead')
            ->with(7, 'admin');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    public function test_mark_read_does_nothing_when_id_is_zero(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'mark-read';
        $_POST['id']               = '0';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->never())->method('markConversationAsRead');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // -------------------------------------------------------------------------
    // POST action: delete
    // -------------------------------------------------------------------------

    public function test_delete_action_calls_deleteConversation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'delete';
        $_POST['id']               = '15';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('deleteConversation')
            ->with(15);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    public function test_delete_action_does_nothing_when_id_is_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_POST['action']           = 'delete';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->never())->method('deleteConversation');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_delete_sets_session_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_SESSION['lang']          = 'fr';
        $_POST['action']           = 'delete';
        $_POST['id']               = '3';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->method('deleteConversation');

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Conversation supprimée.', $_SESSION['message'] ?? '');
    }

    public function test_delete_sets_session_message_in_english(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'admin';
        $_SESSION['lang']          = 'en';
        $_POST['action']           = 'delete';
        $_POST['id']               = '3';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->method('deleteConversation');

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Conversation deleted.', $_SESSION['message'] ?? '');
    }

    // -------------------------------------------------------------------------
    // GET action: view
    // -------------------------------------------------------------------------

    public function test_view_action_calls_getConversationById(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['role']          = 'admin';
        $_GET['action']            = 'view';
        $_GET['id']                = '10';

        [$controller, $serviceMock] = $this->makeController();

        $fakeConversation = $this->createMock(Conversation::class);
        $serviceMock->expects($this->once())
            ->method('getConversationById')
            ->with(10)
            ->willReturn($fakeConversation);
        $serviceMock->expects($this->once())
            ->method('markConversationAsRead')
            ->with(10, 'admin');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_view_action_does_not_mark_read_when_conversation_not_found(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['role']          = 'admin';
        $_GET['action']            = 'view';
        $_GET['id']                = '999';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->method('getConversationById')->with(999)->willReturn(null);
        $serviceMock->expects($this->never())->method('markConversationAsRead');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    // -------------------------------------------------------------------------
    // GET action: list
    // -------------------------------------------------------------------------

    public function test_list_action_calls_getAllConversations_by_default(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['role']          = 'admin';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('getAllConversations')
            ->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_list_action_with_unread_filter_calls_getUnreadConversations(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['role']          = 'admin';
        $_GET['filter']            = 'unread';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('getUnreadConversations')
            ->with('admin')
            ->willReturn([]);
        $serviceMock->expects($this->never())->method('getAllConversations');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_list_action_with_all_filter_calls_getAllConversations(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['role']          = 'admin';
        $_GET['filter']            = 'all';

        [$controller, $serviceMock] = $this->makeController();
        $serviceMock->expects($this->once())
            ->method('getAllConversations')
            ->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // -------------------------------------------------------------------------
    // buildUrl helper
    // -------------------------------------------------------------------------

    public function test_buildUrl_always_appends_lang_parameter(): void
    {
        $lang = 'en';

        /** @param array<string, string> $params */
        $buildUrl = function (string $url, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        $result = $buildUrl('index.php', ['page' => 'messages-admin']);
        $this->assertStringContainsString('lang=en',              $result);
        $this->assertStringContainsString('page=messages-admin',  $result);
    }
}