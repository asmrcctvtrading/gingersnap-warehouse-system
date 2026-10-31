<?php
/**
 * Database Connection (PDO)
 *
 * Establishes secure PDO connection to the MySQL database.
 * All database queries must use prepared statements.
 */

try {
    // PDO connection string
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    // PDO options for security and performance
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use actual prepared statements
    ];

    // Create PDO connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Set character set for all queries
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");

} catch (PDOException $e) {
    // Log error securely (no credentials in logs)
    error_log('Database connection failed: ' . $e->getMessage());

    // Display user-friendly error
    if (ENVIRONMENT === 'production') {
        die('Database connection error. Please contact support.');
    } else {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
}
