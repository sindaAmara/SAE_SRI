<?php

// phpcs:disable Generic.Files.LineLength
// phpcs:disable PSR1.Files.SideEffects

/**
 * Main Entry Point (Front Controller).
 *
 * This file handles all incoming requests, initializes the environment,
 * manages sessions, and dispatches the request to the appropriate Controller.
 */

// --- 1. Environment & Error Reporting ---

// Enable full error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Define the root path of the application
define('ROOT_PATH', dirname(__DIR__));

// --- 2. Session Management ---

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Accessibility: Toggle Tritanopia mode (Color blindness support)
// Checks if the 'toggleTritanopia' parameter is in the URL
if (isset($_GET['toggleTritanopia'])) {
    $_SESSION['tritanopia'] = !($_SESSION['tritanopia'] ?? false);

    // Clean the URL by removing the query parameter and redirecting
    $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $cleanUrl);
    exit;
}

// --- 3. Autoloading & Configuration ---

// Load Composer dependencies (Dotenv, etc.)
require_once ROOT_PATH . '/vendor/autoload.php';

// Load Custom Autoloader and Database singleton
require_once ROOT_PATH . '/Autoloader.php';
require_once ROOT_PATH . '/app/Core/Database.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// Register the custom autoloader
Autoloader::register();

// --- 4. Import Controllers ---

use Controllers\AuthController;
use Controllers\DashboardController;
use Controllers\NotFoundController;
use Controllers\SaveStudentController;

// Folder Controllers
use Controllers\FolderController\FoldersControllerAdmin;
use Controllers\FolderController\FoldersControllerStudent;

// Home Controllers
use Controllers\HomeController\HomeControllerAdmin;
use Controllers\HomeController\HomeControllerStudent;
use Controllers\HomeController\SuperAdminController;
use Controllers\HomeController\HomeControllerCoordinateur;

// Partners Controllers
use Controllers\PartnersController\PartnersControllerStudent;
use Controllers\PartnersController\PartnersControllerAdmin;

// WebPlan (Sitemap) Controllers
use Controllers\WebPlanController\WebPlanControllerAdmin;
use Controllers\WebPlanController\WebPlanControllerStudent;
use Controllers\WebPlanController\WebPlanController;

//contact controllers
use Controllers\ContactController\ContactControllerAdmin;
use Controllers\ContactController\ContactControllerStudent;

// coordinator controllers
use Controllers\CoordinatorController\InternershipCoordinatorController;
use Controllers\CoordinatorController\StudyCoordinatorController;
use Controllers\CoordinatorController\DepartmentHeadController;
//bob controller
use Controllers\BobController\BobController;
// --- 5. Initialize Controllers ---

/**
 * List of available controllers.
 * The order is significant: the router stops at the first controller
 * that confirms it supports the requested page.
 */
$controllers = [
    AuthController::class,
    HomeControllerAdmin::class,
    HomeControllerStudent::class,
    FoldersControllerAdmin::class,
    FoldersControllerStudent::class,
    PartnersControllerAdmin::class,
    PartnersControllerStudent::class,
    WebPlanController::class,
    WebPlanControllerAdmin::class,
    WebPlanControllerStudent::class,
    FoldersControllerAdmin::class,
    FoldersControllerStudent::class,
    DashboardController::class,
    SaveStudentController::class,
    ContactControllerStudent::class,
    ContactControllerAdmin::class,
    SuperAdminController::class,
    HomeControllerCoordinateur::class,
    InternershipCoordinatorController::class,
    StudyCoordinatorController::class,
    DepartmentHeadController::class,
    BobController::class,
];

// --- 6. Routing Logic ---

// PHPStan Fix: Secure parsing of URL to ensure string type for trim()
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
// If parse_url returns false or null, default to empty string
$cleanPath = is_string($parsedPath) ? $parsedPath : '';

// Retrieve the 'page' parameter from the URL, or default to the cleaned path
$page = $_GET['page'] ?? trim($cleanPath, '/');

/**
 * Root Path Redirection Logic.
 * * If the user arrives at the root ('', '/', 'index.php') or the old 'home' route,
 * we automatically redirect them based on their authentication status.
 */
if ($page === '' || $page === 'index.php' || $page === 'home') {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        // User is Admin -> Redirect to Admin Home
        header('Location: index.php?page=home-admin');
        exit;
    } elseif (isset($_SESSION['numetu'])) {
        // User is Student -> Redirect to Student Home
        header('Location: index.php?page=home-student');
        exit;
    } else {
        // User is Guest -> Redirect to Login
        header('Location: index.php?page=login');
        exit;
    }
}

// --- 7. Handle Special Actions ---

// Handle Logout
if ($page === 'logout') {
    // Destroy session and redirect to login
    session_unset();
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// --- 8. Dispatch Request ---

// Loop through controllers to find one that supports the request
foreach ($controllers as $controllerClass) {
    if ($controllerClass::support($page, $_SERVER['REQUEST_METHOD'])) {
        $controller = new $controllerClass();
        $controller->control();
        exit();
    }
}

// --- 9. Fallback (404) ---

// If no controller matched, show the 404 Not Found page
$notFound = new NotFoundController();
$notFound->control();
