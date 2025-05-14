<?php
// Simple direct test script that should bypass Phalcon routing
// Located in a subdirectory with its own index.php

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Direct Test Page</h1>";
echo "This page should bypass the Phalcon router.<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "<hr>";

// Test database connection directly
echo "<h2>Direct Database Test</h2>";

// Try with different connection strings to ensure we can connect
$connections = [
    [
        'host' => 'localhost',
        'username' => 'newplay',
        'password' => 'YFI1W9m$CYi4sd.h', 
        'dbname' => 'newplay'
    ],
    [
        'host' => '127.0.0.1',
        'username' => 'newplay',
        'password' => 'YFI1W9m$CYi4sd.h',
        'dbname' => 'newplay'
    ]
];

$connected = false;

foreach ($connections as $index => $dbConfig) {
    try {
        echo "Trying connection #" . ($index + 1) . ": {$dbConfig['host']}...<br>";
        $mysqli = new mysqli(
            $dbConfig['host'], 
            $dbConfig['username'], 
            $dbConfig['password'], 
            $dbConfig['dbname']
        );
        
        if ($mysqli->connect_error) {
            echo "Connection failed: " . $mysqli->connect_error . "<br>";
        } else {
            echo "Connection successful!<br>";
            $connected = true;
            
            // Test the system_logs table
            $result = $mysqli->query("SHOW TABLES LIKE 'system_logs'");
            if ($result->num_rows > 0) {
                echo "system_logs table exists.<br>";
                
                // Try to insert a test record
                $timestamp = date('Y-m-d H:i:s');
                $sql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                        VALUES ('test', 'Test from direct_test/index.php', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$timestamp}')";
                
                if ($mysqli->query($sql)) {
                    echo "Successfully inserted record into system_logs!<br>";
                    
                    // Show the most recent records
                    $result = $mysqli->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 10");
                    echo "<h3>Recent System Logs:</h3>";
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
                    echo "Failed to insert record: " . $mysqli->error . "<br>";
                }
            } else {
                echo "system_logs table does not exist.<br>";
            }
            
            $mysqli->close();
            break; // Stop trying other connections if successful
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "<br>";
    }
}

if (!$connected) {
    echo "Could not connect to the database with any of the attempted connections.<br>";
}

echo "<hr>";
echo "<h2>Test Database Connection and Login Info</h2>";
echo "Now that we've confirmed database access, the issue is likely in the login controller.<br>";
echo "The main solution is to update LoginController.php to use direct SQL for logging successful logins.<br>";
echo "Check if the file has been updated and then try logging in again.<br>";
?> 