<?php

namespace Controllers;

use Controllers\ControllerInterface;
use Core\View;

/**
 * Controller for handling 404 Not Found pages.
 * This controller is a fallback and does not support any specific route.
 */
class NotFoundController implements ControllerInterface
{
    /**
     * Main method that prepares data and renders the 404 view.
     */
    public function control(): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if tritanopia mode is enabled
        $isTritanopia = !empty($_SESSION['tritanopia']) && ((bool) $_SESSION['tritanopia'] === true);

        // Page title
        $title = 'Page Not Found';

        // Render the 404 view, passing title and tritanopia flag
        View::render('404', [
            'title'        => $title,
            'isTritanopia' => $isTritanopia,
        ]);
    }

    /**
     * This controller does not support any specific page.
     */
    public static function support(string $page, string $method): bool
    {
        return false;
    }
}