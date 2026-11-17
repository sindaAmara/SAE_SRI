<?php

namespace Controllers\ContactController;

use Controllers\ControllerInterface;
use Model\Persistence\ConversationPDO;
use Service\ContactService;
use Core\View;

class ContactControllerStudent implements ControllerInterface
{
    private ContactService $contactService;

    public function __construct()
    {
        $repository = new ConversationPDO();
        $this->contactService = new ContactService($repository);
    }

    public static function support(string $page, string $method): bool
    {
        return $page === 'contact-student';
    }

    /**
     * Starts the session if it is not already started.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirects the user to a specific URL.
     * Protected to allow mocking during tests.
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
     * Main controller logic for the student contact page.
     * Handles sending messages, replying to conversations,
     * language selection, and displaying student conversations.
     */
    public function control(): void
    {
        $this->startSession();

        // Ensure the student is authenticated
        if (!isset($_SESSION['numetu'])) {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        $lang   = $_SESSION['lang'] ?? 'fr';
        $numEtu = $_SESSION['numetu'];
        $action = $_GET['action'] ?? 'form';

        // Translation helper
        $t = fn(array $translations) => $translations[$lang] ?? $translations['fr'] ?? '';

        // Helper to build URLs with the language parameter
        $buildUrl = function (string $url, array $params = []) use ($lang) {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        // Handle reply to an existing conversation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply') {
            $conversationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $reply = trim($_POST['student_reply'] ?? '');

            if ($conversationId > 0 && !empty($reply)) {
                $this->contactService->addMessage($conversationId, 'student', $reply);
                $_SESSION['message'] = $lang === 'fr' ? 'Votre message a été envoyé !' : 'Message sent!';
            }

            $this->redirect('index.php?page=contact-student&lang=' . $lang . '#messagesPanel');
        }

        $messageSent = false;
        $error = null;

        // Handle the submission of the contact form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'form') {
            try {
                $this->contactService->createConversation(
                    studentNumEtu: $numEtu,
                    name: trim($_POST['name'] ?? ''),
                    email: trim($_POST['email'] ?? ''),
                    subject: trim($_POST['subject'] ?? ''),
                    initialMessage: trim($_POST['message'] ?? '')
                );
                $messageSent = true;
            } catch (\Exception $e) {
                $error = $lang === 'fr'
                    ? "Erreur lors de l'envoi."
                    : "Error sending message.";
            }
        }

        // Retrieve all conversations for the current student
        $studentConversations = $this->contactService->getStudentConversations($numEtu);

        // Contact information displayed on the page
        $contactInfo = [
            'email' => 'jocelyne.vial@univ-amu.fr',
            'phone' => ' +33 4 13 94 65 02',
            'address' => [
                'fr' => '413 Avenue Gaston Berger<br>13625 Aix-en-Provence',
                'en' => '413 Avenue Gaston Berger<br>13625 Aix-en-Provence',
            ],
            'hours' => [
                'fr' => 'Lundi - Vendredi : 9h00 - 17h00',
                'en' => 'Monday - Friday: 9:00 AM - 5:00 PM',
            ],
        ];

        // Render the contact page
        $this->renderView('Contact/contact_student', [
            'lang' => $lang,
            'numEtu' => $numEtu,
            'action' => $action,
            't' => $t,
            'buildUrl' => $buildUrl,
            'userRole' => 'student',
            'messageSent' => $messageSent,
            'error' => $error,
            'contactInfo' => $contactInfo,
            'studentConversations' => $studentConversations,
        ]);
    }
}