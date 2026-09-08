<?php

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class Autoloader
{
    private static array $prefixes = [
        // On intercepte les namespaces qui contiennent "site" et on les pointe vers /app/
        'Controllers\\site\\' => __DIR__ . '/app/Controllers/',
        'Controllers\\'       => __DIR__ . '/app/Controllers/',
        'Site\\'              => __DIR__ . '/app/',
        'Model\\'             => __DIR__ . '/app/Model/',
        'Service\\'           => __DIR__ . '/app/Service/',
        'View\\'              => __DIR__ . '/app/View/',
        'UseCase\\'           => __DIR__ . '/app/Model/UseCase/',
        'Model\\Folder\\'     => __DIR__ . '/app/Model/Folder/',
        'View\\Folder\\'      => __DIR__ . '/app/View/Folder/',
        'Core\\'              => __DIR__ . '/app/Core/',
    ];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): bool
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    return true;
                } else {
                    echo "Fichier introuvable : $file<br>";
                    error_log("Fichier introuvable : $file");
                }
            }
        }
        return false;
    }
}

Autoloader::register();
