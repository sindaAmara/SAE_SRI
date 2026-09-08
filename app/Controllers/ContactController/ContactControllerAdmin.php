<?php

namespace Controllers\ContactController;

use Controllers\ControllerInterface;
use Model\Persistence\ConversationPDO;
use Service\ContactService;
use Core\View;

class ContactControllerAdmin implements ControllerInterface
{
    private ContactService $contactService;

    public function __construct()
    {
        $repository = new ConversationPDO();
        $this->contactService = new ContactService($repository);
    }

    public static function support(string $page, string $method): bool
    {
        return $page === 'messages-admin';
    }

    /**
     * Starts the session.
     * Protected method so it can be disabled or mocked during testing.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirects to a given URL.
     * Protected method so it can be mocked during testing.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renders a view.
     *
     * @param array<string, mixed> $data Data passed to the view
     */
    protected function renderView(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Main controller method that handles admin message management.
     */
    public function control(): void
    {
        $this->startSession();

        // Check if the user has the required role
        $allowedRoles = ['admin'];
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
            $this->redirect('index.php?page=login');
        }

        // Language management
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $lang           = $_SESSION['lang'] ?? 'fr';
        $action         = $_POST['action'] ?? $_GET['action'] ?? 'list';
        $conversationId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Send a response to a conversation
            if ($action === 'respond' && $conversationId) {
                $response = trim($_POST['response'] ?? '');
                if (!empty($response)) {
                    $this->contactService->addMessage($conversationId, 'admin', $response);
                    $_SESSION['message'] = $lang === 'fr' ? 'Réponse envoyée !' : 'Response sent!';
                    $this->redirect('index.php?page=messages-admin&action=view&id=' . $conversationId);
                }
            }

            // Mark a conversation as read
            elseif ($action === 'mark-read' && $conversationId) {
                $this->contactService->markConversationAsRead($conversationId, 'admin');
                $this->redirect('index.php?page=messages-admin');
            }

            // Delete a conversation
            elseif ($action === 'delete') {
                $idToDelete = $_POST['id'] ?? $_GET['id'] ?? null;
                if ($idToDelete) {
                    $this->contactService->deleteConversation((int)$idToDelete);
                    $_SESSION['message'] = $lang === 'fr' ? 'Conversation supprimée.' : 'Conversation deleted.';
                    $this->redirect('index.php?page=messages-admin&lang=' . $lang);
                }
            }
        }

        // Translation helper
        $t = fn(array $translations) => $translations[$lang] ?? $translations['fr'] ?? '';

        // Helper to build URLs with language parameter
        $buildUrl = function (string $url, array $params = []) use ($lang) {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        // Display a specific conversation
        if ($action === 'view' && $conversationId) {
            $conversation = $this->contactService->getConversationById($conversationId);
            if (!$conversation) {
                $this->redirect('index.php?page=messages-admin');
            }

            // Mark conversation as read
            $this->contactService->markConversationAsRead($conversationId, 'admin');

            $this->renderView('Contact/messages_admin_view', [
                'conversation'   => $conversation,
                'lang'           => $lang,
                't'              => $t,
                'buildUrl'       => $buildUrl,
                'action'         => $action,
                'conversationId' => $conversationId,
            ]);
        } else {
            // Retrieve conversations (all or unread)
            $filter        = $_GET['filter'] ?? 'all';
            $conversations = $filter === 'unread'
                ? $this->contactService->getUnreadConversations('admin')
                : $this->contactService->getAllConversations();

            // Render the list view
            $this->renderView('Contact/messages_admin_list', [
                'conversations' => $conversations,
                'filter'        => $filter,
                'lang'          => $lang,
                't'             => $t,
                'buildUrl'      => $buildUrl,
                'action'        => $action,
            ]);
        }
    }
}