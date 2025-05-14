<?php
// Test script to check if we can insert directly into the system_logs table

// Include Phalcon loader for DB connection
require_once __DIR__ . '/public/index.php';

// Log file for the test
$logFile = '/var/www/newplay/logs2/db_test_log.txt';

// Function to log messages
function logMessage($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logEntry .= " " . json_encode($data);
        } else {
            $logEntry .= " " . $data;
        }
    }
    
    echo $logEntry . "\n";
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
}

logMessage("Starting system_logs table test");

try {
    // Get the DI container
    $di = \Phalcon\Di::getDefault();
    logMessage("DI container retrieved");
    
    // Check if DB service exists
    if (!$di->has('db')) {
        logMessage("ERROR: Database service not found in DI");
        exit;
    }
    
    // Get database connection
    $db = $di->get('db');
    logMessage("Database connection retrieved");
    
    // Test connection with a simple query
    try {
        $result = $db->query("SELECT 1");
        logMessage("Database connection test successful");
    } catch (\Exception $e) {
        logMessage("ERROR: Database connection test failed", $e->getMessage());
        exit;
    }
    
    // Check if system_logs table exists
    try {
        $tableCheck = "SHOW TABLES LIKE 'system_logs'";
        $result = $db->query($tableCheck);
        $tableExists = $result->numRows() > 0;
        logMessage("system_logs table exists", $tableExists ? "Yes" : "No");
        
        if (!$tableExists) {
            logMessage("Creating system_logs table");
            // Try to create the table
            $createTable = "CREATE TABLE IF NOT EXISTS system_logs (
                id int unsigned NOT NULL AUTO_INCREMENT,
                type varchar(20) NOT NULL,
                message text NOT NULL,
                user_id int unsigned NULL DEFAULT NULL,
                ip_address varchar(45) NOT NULL,
                created datetime NOT NULL,
                PRIMARY KEY (id)
            )";
            $db->execute($createTable);
            logMessage("Created system_logs table");
            
            // Verify creation
            $result = $db->query($tableCheck);
            $tableExists = $result->numRows() > 0;
            logMessage("Table creation verification", $tableExists ? "Success" : "Failed");
            
            if (!$tableExists) {
                logMessage("ERROR: Failed to create system_logs table");
                exit;
            }
        }
    } catch (\Exception $e) {
        logMessage("ERROR: Could not check or create system_logs table", $e->getMessage());
        exit;
    }
    
    // Try to insert a test record
    try {
        $timestamp = date('Y-m-d H:i:s');
        $sql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                VALUES (:type, :message, :user_id, :ip_address, :created)";
                
        $params = [
            'type' => 'test',
            'message' => 'Test log entry from direct test script',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'created' => $timestamp
        ];
        
        logMessage("Attempting direct SQL insert with params", $params);
        
        $success = $db->execute($sql, $params);
        logMessage("Direct SQL insert result", $success ? "Success" : "Failed");
        
        if ($success) {
            // Check if the record was actually inserted
            $checkSql = "SELECT * FROM system_logs WHERE type = 'test' AND created = :created";
            $result = $db->query($checkSql, ['created' => $timestamp]);
            $record = $result->fetch();
            
            if ($record) {
                logMessage("Record successfully inserted and retrieved", $record);
            } else {
                logMessage("Record appears to be inserted but could not be retrieved");
            }
        }
    } catch (\Exception $e) {
        logMessage("ERROR: Exception during SQL insert", $e->getMessage());
        logMessage("ERROR: Stack trace", $e->getTraceAsString());
    }
    
    logMessage("Test completed");
    
} catch (\Exception $e) {
    logMessage("ERROR: Unhandled exception", $e->getMessage());
    logMessage("ERROR: Stack trace", $e->getTraceAsString());
}
?> 