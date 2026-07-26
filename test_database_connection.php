<?php

/**
 * Database Connection Test Script
 * This script tests the connection to the MySQL database
 */

// Database configuration
$host = 'localhost';
$port = 3306;
$database = 'intellitravel_app';
$username = 'root';
$password = 'admin';

echo "========================================\n";
echo "Database Connection Test\n";
echo "========================================\n\n";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "Attempting to connect to database...\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Database: $database\n";
    echo "Username: $username\n\n";
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✅ SUCCESS: Connected to database successfully!\n\n";
    
    // Test query to verify connection works
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetchColumn();
    echo "MySQL Version: $version\n\n";
    
    // Get database info
    $stmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Current Database: $currentDb\n\n";
    
    // Count tables
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '$database'");
    $tableCount = $stmt->fetch()['count'];
    echo "Number of tables: $tableCount\n\n";
    
    // List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    if (count($tables) > 0) {
        echo "Tables in database:\n";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "  - $tableName\n";
        }
    } else {
        echo "No tables found in database.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: Failed to connect to database!\n\n";
    echo "Error details:\n";
    echo "  Code: " . $e->getCode() . "\n";
    echo "  Message: " . $e->getMessage() . "\n\n";
    
    // Common troubleshooting tips
    echo "Troubleshooting tips:\n";
    echo "  1. Check if MySQL server is running\n";
    echo "  2. Verify database credentials in .env file\n";
    echo "  3. Ensure database 'intellitravel_app' exists\n";
    echo "  4. Check if user 'root' has necessary permissions\n";
    echo "  5. Verify MySQL is running on port 3306\n";
    exit(1);
}

echo "\n========================================\n";
echo "Test completed successfully!\n";
echo "========================================\n";
