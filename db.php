<?php
/**
 * ALMAF BAKERY - PDO Database Connection Configuration
 * Optimized for InfinityFree Hosting
 * 
 * This file centralizes all database connectivity using PDO with prepared statements
 * to prevent SQL Injection and ensure secure data handling.
 */

// Database connection parameters for InfinityFree
$host = 'sql211.infintyfree.com';
$dbname = 'if0_41406535_aym';
$username = 'if0_41406535';
$password = 'Aliyuyunus3971';

try {
    // Create PDO connection with error mode exception
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Connection successful
    $connection_status = true;
    
} catch (PDOException $e) {
    // Log error for security (don't expose details to user)
    error_log("ALMAF BAKERY DB Connection Error: " . $e->getMessage());
    
    // Set connection status flag
    $connection_status = false;
    
    // Display user-friendly error (in production, log only)
    if (getenv('APP_ENV') === 'development') {
        die("Database Connection Failed: " . $e->getMessage());
    } else {
        die("We encountered a database issue. Please try again later.");
    }
}

/**
 * Helper function: Generate unique alphanumeric ID
 * Used for staff, products, and other entities
 * 
 * @param int $length The desired length of the ID
 * @return string Unique alphanumeric string
 */
function generateUniqueID($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return time() . '_' . $randomString;
}

/**
 * Helper function: Execute a prepared statement safely
 * 
 * @param PDO $pdo Database connection
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement Executed statement
 */
function executeQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        throw new Exception("Database query failed. Please try again.");
    }
}

?>