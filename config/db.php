<?php
/**
 * Database Configuration
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Uses PDO with prepared statements for secure database access.
 * Configured for XAMPP local development environment.
 */

// Database credentials for XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'food_mania');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_NAME', 'Food-Mania');
define('SITE_URL', 'http://localhost/Food-Mania');

/**
 * Create and return a PDO database connection
 * 
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log this error instead of displaying it
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
