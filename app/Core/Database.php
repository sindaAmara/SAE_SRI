<?php

/**
 * Database
 *
 * Singleton class responsible for managing the PDO database connection.
 * Reads connection parameters from environment variables and provides
 * a single shared PDO instance throughout the application lifecycle.
 */
class Database
{
    /** @var Database|null The single instance of this class */
    private static ?Database $instance = null;

    /** @var PDO|null The underlying PDO connection */
    private ?PDO $conn = null;

    /** @var string Database host address */
    private string $host;

    /** @var string Database port */
    private string $port;

    /** @var string Database name */
    private string $dbname;

    /** @var string Database username */
    private string $username;

    /** @var string Database password */
    private string $password;

    /** @var string Connection character set */
    private string $charset;

    /**
     * Private constructor — initializes the PDO connection using environment variables.
     *
     * Reads DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, and DB_CHARSET
     * from $_ENV, falling back to safe defaults. Sets the PHP and MySQL
     * timezone to Europe/Paris. Terminates execution on connection failure.
     */
    private function __construct()
    {
        try {
            // Charger les variables d'environnement (avec valeurs par défaut pour éviter les crashs immédiats)
            $this->host     = $_ENV['DB_HOST'] ?? 'localhost';
            $this->port     = $_ENV['DB_PORT'] ?? '3306';
            $this->dbname   = $_ENV['DB_NAME'] ?? '';
            $this->username = $_ENV['DB_USER'] ?? '';
            $this->password = $_ENV['DB_PASSWORD'] ?? '';
            $this->charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            // Définir le fuseau horaire de PHP
            date_default_timezone_set('Europe/Paris');

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("SET time_zone = 'Europe/Paris'");

            error_log("✅ Connexion à la base de données réussie");
        } catch (PDOException $e) {
            error_log("❌ DB Error: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }

    /**
     * Returns the single instance of the Database class, creating it if necessary.
     *
     * @return Database The shared Database instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the active PDO connection.
     *
     * @return PDO The initialized PDO connection
     * @throws \RuntimeException If the connection has not been initialized
     */
    public function getConnection(): PDO
    {
        if ($this->conn === null) {
            throw new \RuntimeException("La connexion base de données n'est pas initialisée.");
        }
        return $this->conn;
    }

    /**
     * Prevents cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Prevents unserialization of the singleton instance.
     *
     * @throws Exception Always thrown to prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}