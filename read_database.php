<?php

/**
 * Database Reader Script
 * This script reads and displays all tables, their structure, and data from the database
 */

// Database configuration
$host = 'localhost';
$port = 3306;
$database = 'intellitravel_app';
$username = 'root';
$password = 'admin';

echo "========================================\n";
echo "Database Reader - Full Database Dump\n";
echo "========================================\n\n";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✅ Connected to database: $database\n\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    if (count($tables) === 0) {
        echo "No tables found in database.\n";
        exit(0);
    }
    
    echo "Found " . count($tables) . " tables:\n\n";
    
    // Process each table
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        
        echo "========================================\n";
        echo "TABLE: $tableName\n";
        echo "========================================\n\n";
        
        // Get table structure
        echo "--- Structure ---\n";
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            echo "  {$column['Field']}: {$column['Type']} ";
            echo ($column['Null'] === 'NO' ? 'NOT NULL ' : 'NULL ');
            echo ($column['Key'] ? "KEY({$column['Key']}) " : '');
            echo ($column['Default'] !== null ? "DEFAULT '{$column['Default']}'" : '');
            echo ($column['Extra'] ? " {$column['Extra']}" : '');
            echo "\n";
        }
        
        // Get row count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
        $rowCount = $stmt->fetch()['count'];
        echo "\n--- Data ($rowCount rows) ---\n";
        
        if ($rowCount > 0) {
            // Get all data (limit to 100 rows for readability)
            $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 100");
            $rows = $stmt->fetchAll();
            
            foreach ($rows as $row) {
                echo "  Row: ";
                $rowParts = [];
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $rowParts[] = "$key=NULL";
                    } elseif (is_numeric($value)) {
                        $rowParts[] = "$key=$value";
                    } else {
                        // Truncate long strings
                        $displayValue = strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value;
                        $rowParts[] = "$key='$displayValue'";
                    }
                }
                echo implode(", ", $rowParts) . "\n";
            }
            
            if ($rowCount > 100) {
                echo "  ... (and " . ($rowCount - 100) . " more rows)\n";
            }
        } else {
            echo "  (empty table)\n";
        }
        
        echo "\n";
    }
    
    // Get database summary
    echo "========================================\n";
    echo "DATABASE SUMMARY\n";
    echo "========================================\n\n";
    
    $totalTables = count($tables);
    $totalRows = 0;
    
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
        $count = $stmt->fetch()['count'];
        $totalRows += $count;
    }
    
    echo "Total Tables: $totalTables\n";
    echo "Total Rows: $totalRows\n";
    echo "Database Size: ";
    
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = '$database'");
    $size = $stmt->fetch()['size'];
    echo ($size ? $size . " MB" : "0 MB") . "\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: Failed to connect to database!\n\n";
    echo "Error details:\n";
    echo "  Code: " . $e->getCode() . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    exit(1);
}

echo "========================================\n";
echo "Database read completed!\n";
echo "========================================\n";
