<?php
// Simple test script that doesn't require Phalcon bootstrapping

// Disable routing for this script
// This will help bypass the Phalcon router
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a test directory and file
$dir = '/var/www/newplay/logs2';
$file = $dir . '/simple_test.log';

echo "PHP Version: " . phpversion() . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Script path: " . __FILE__ . "<br>";
echo "<hr>";

echo "<h2>Testing Directory Access</h2>";

// Test directory operations
if (!is_dir($dir)) {
    echo "Creating directory $dir...<br>";
    if (mkdir($dir, 0775, true)) {
        echo "Created directory successfully.<br>";
        chmod($dir, 0775);
        echo "Set permissions to 775.<br>";
    } else {
        echo "Failed to create directory!<br>";
        echo "Error: " . error_get_last()['message'] . "<br>";
    }
} else {
    echo "Directory $dir already exists.<br>";
}

// Test file write operations
echo "<h2>Testing File Write Access</h2>";
$content = "Test entry at " . date('Y-m-d H:i:s') . "\n";

try {
    if (file_put_contents($file, $content, FILE_APPEND)) {
        echo "Successfully wrote to log file.<br>";
        echo "File content: <pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
    } else {
        echo "Failed to write to log file!<br>";
        echo "Error: " . error_get_last()['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
}

// Try to connect to database directly
echo "<h2>Testing Database Connection</h2>";

// Get database configuration from config file if possible
$configFile = '/var/www/newplay/config/config.php';
$dbConfig = [];

if (file_exists($configFile)) {
    echo "Found config file, trying to parse.<br>";
    try {
        $config = include($configFile);
        if (isset($config->database)) {
            $dbConfig = [
                'host' => $config->database->host,
                'username' => $config->database->username,
                'password' => $config->database->password,
                'dbname' => $config->database->dbname
            ];
            echo "Loaded database config from file.<br>";
        } else {
            echo "Config file exists but doesn't contain database section.<br>";
        }
    } catch (Exception $e) {
        echo "Error parsing config file: " . $e->getMessage() . "<br>";
    }
}

// Fallback to direct connection
if (empty($dbConfig)) {
    echo "Using backup database connection string.<br>";
    // This assumes your database connection hasn't changed from what's in the code
    $dbConfig = [
        'host' => 'localhost',
        'username' => 'newplay',
        'password' => 'YFI1W9m$CYi4sd.h',
        'dbname' => 'newplay'
    ];
}

// Try to connect to the database directly
try {
    echo "Connecting to database {$dbConfig['dbname']} on {$dbConfig['host']}...<br>";
    $mysqli = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']);
    
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error . "<br>";
    } else {
        echo "Database connection successful!<br>";
        
        // Check for system_logs table
        $result = $mysqli->query("SHOW TABLES LIKE 'system_logs'");
        if ($result->num_rows > 0) {
            echo "system_logs table exists.<br>";
            
            // Count records
            $result = $mysqli->query("SELECT COUNT(*) as count FROM system_logs");
            $row = $result->fetch_assoc();
            echo "Total system_logs records: " . $row['count'] . "<br>";
            
            // Try a manual insert
            $timestamp = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("INSERT INTO system_logs (type, message, user_id, ip_address, created) VALUES (?, ?, ?, ?, ?)");
            $type = 'test';
            $message = 'Test from simple_test.php';
            $userId = null;
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param('ssiss', $type, $message, $userId, $ip, $timestamp);
            
            if ($stmt->execute()) {
                echo "Successfully inserted test record into system_logs!<br>";
            } else {
                echo "Failed to insert test record: " . $mysqli->error . "<br>";
            }
            
            // List the most recent records
            echo "<h3>Recent System Logs:</h3>";
            $result = $mysqli->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 10");
            echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Message</th><th>User ID</th><th>IP</th><th>Created</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['type'] . "</td>";
                echo "<td>" . $row['message'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['ip_address'] . "</td>";
                echo "<td>" . $row['created'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "system_logs table does not exist. Creating it...<br>";
            
            // Create the table
            $createTable = "CREATE TABLE IF NOT EXISTS system_logs (
                id int unsigned NOT NULL AUTO_INCREMENT,
                type varchar(20) NOT NULL,
                message text NOT NULL,
                user_id int unsigned NULL DEFAULT NULL,
                ip_address varchar(45) NOT NULL,
                created datetime NOT NULL,
                PRIMARY KEY (id)
            )";
            
            if ($mysqli->query($createTable)) {
                echo "Created system_logs table successfully!<br>";
            } else {
                echo "Failed to create table: " . $mysqli->error . "<br>";
            }
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "Database exception: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Completed</h2>";
?> 