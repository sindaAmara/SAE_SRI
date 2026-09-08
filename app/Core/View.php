<?php

namespace Core;

/**
 * View
 *
 * Utility class for rendering PHP template files.
 * Resolves templates relative to the application's root path and
 * exposes the provided data array as local variables within the template.
 */
class View
{
    /**
     * Renders a view template and sends its output to the browser.
     *
     * Extracts the key-value pairs of $data as local variables, resolves
     * the template path using ROOT_PATH (or the project root as a fallback),
     * and requires the corresponding PHP file under app/View/.
     * Terminates execution with an error message if the template file is not found.
     *
     * @param string               $template The template name without extension (e.g. 'login')
     * @param array<string, mixed> $data     Variables to expose inside the template
     * @return void
     */
    public static function render(string $template, array $data = []): void
    {
        // 1. Transforme les clés du tableau en vraies variables ($message, $isLogin, etc.)
        extract($data);

        // Fix pour PHPStan: Si ROOT_PATH n'est pas défini (analyse statique), on met un chemin par défaut
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);

        // 2. Construit le chemin absolu vers le fichier HTML
        $file = $root . '/app/View/' . $template . '.php';

        // 3. Vérifie que le fichier existe avant de l'inclure
        if (file_exists($file)) {
            require $file;
        } else {
            die("Erreur : Le fichier de vue '$template.php' est introuvable.");
        }
    }
}