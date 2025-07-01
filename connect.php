<?php
// Prevent direct access to this file
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
} else {
    exit('Direct access not allowed.');
}


$dbHost = 'localhost'; // Database host
$dbName = 'projectmanagementsystem'; // Database name
$dbUser = 'root'; // Database user
$dbPassword = ''; // Database password

// Define DatabaseConnection class
if (!class_exists('DatabaseConnection')) {
    class DatabaseConnection {
        private static $instance = null;
        private $connection;

        // Private constructor to prevent direct instantiation
        private function __construct($host, $dbname, $user, $password) {
            try {
                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
                $this->connection = new PDO($dsn, $user, $password);

                // Test the connection
                $this->connection->query('SELECT 1');

                // Set error mode to exception
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Ensure results are fetched as associative arrays
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed: " . $e->getMessage());
            }
        }

        // Singleton pattern to ensure only one instance of the connection exists
        public static function getInstance($host = null, $dbname = null, $user = null, $password = null) {
            if (!self::$instance) {
                // Use fallback values if arguments are not provided
                $host = $host ?? $_ENV['DB_HOST'] ?? 'localhost';
                $dbname = $dbname ?? $_ENV['DB_NAME'] ?? 'projectmanagementsystem';
                $user = $user ?? $_ENV['DB_USER'] ?? 'root';
                $password = $password ?? $_ENV['DB_PASSWORD'] ?? '';

                self::$instance = new DatabaseConnection($host, $dbname, $user, $password);
            }
            return self::$instance;
        }

        // Get the database connection
        public function getConnection() {
            return $this->connection;
        }

        // Destructor to close the connection
        public function __destruct() {
            $this->connection = null;
        }
    }
}

// Initialize the database connection using hardcoded credentials or environment variables
try {
    $db = DatabaseConnection::getInstance(
        $_ENV['DB_HOST'] ?? $dbHost, // Use the environment variable or hardcoded database host
        $_ENV['DB_NAME'] ?? $dbName, // Use the environment variable or hardcoded database name
        $_ENV['DB_USER'] ?? $dbUser, // Use the environment variable or hardcoded database user
        $_ENV['DB_PASSWORD'] ?? $dbPassword // Use the environment variable or hardcoded database password
    )->getConnection();
} catch (Exception $e) {
    error_log("Failed to initialize database connection: " . $e->getMessage());
    die("Failed to initialize database connection: " . $e->getMessage());
}
?>