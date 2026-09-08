<?php

// phpcs:disable Generic.Files.LineLength

namespace Controllers;

/**
 * ControllerInterface
 *
 * Defines the basic structure for all controllers.
 *
 * Responsibilities:
 *  - Every controller must implement a `control()` method to handle request logic.
 *  - Every controller must implement a static `support()` method to indicate
 *    whether it can handle a given page/path and HTTP method.
 */
interface ControllerInterface
{
    /**
     * Main method to handle the request logic for the controller.
     *
     * @return void
     */
    public function control(): void;

    /**
     * Determines if the controller supports a given path and HTTP method.
     *
     * @param string $chemin Requested path/page
     * @param string $method HTTP method (GET, POST, etc.)
     * @return bool True if the controller supports handling the path
     */
    public static function support(string $chemin, string $method): bool;
}
