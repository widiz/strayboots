<?php
// Special test file with "ignore" in the name to potentially bypass Phalcon router
// Some frameworks check for this pattern to allow direct file access

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Force PHP to execute this file directly
header("Content-Type: text/html");

echo "<h1>Test Ignore File (Direct Access)</h1>";
echo "<p>This file is named to try to bypass Phalcon's router.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Test direct database access
echo "<h2>Database Connection Test</h2>";

// Try to access the database directly with mysqli
try {
    // This connection info is from your config
    $host = "localhost";
    $username = "newplay";
    $password = "YFI1W9m$CYi4sd.h";
    $dbname = "newplay";
    
    echo "Connecting to MySQL...<br>";
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        echo "Failed to connect: " . $mysqli->connect_error . "<br>";
    } else {
        echo "Database connection successful!<br>";
        
        // Check system_logs table
        $result = $mysqli->query("SHOW TABLES LIKE 'system_logs'");
        if ($result->num_rows > 0) {
            echo "system_logs table exists.<br>";
            
            // Count records
            $countResult = $mysqli->query("SELECT COUNT(*) AS count FROM system_logs");
            $row = $countResult->fetch_assoc();
            echo "Total records: " . $row['count'] . "<br>";
            
            // Show recent records
            echo "<h3>Recent Logs:</h3>";
            $logsResult = $mysqli->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 10");
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Type</th><th>Message</th><th>User ID</th><th>IP</th><th>Created</th></tr>";
            
            while ($row = $logsResult->fetch_assoc()) {
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
            
            // Try inserting a test record
            echo "<h3>Inserting Test Record</h3>";
            $insertResult = $mysqli->query("INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                                          VALUES ('test', 'Direct test from test-ignore.php', NULL, '". $_SERVER['REMOTE_ADDR'] ."', '". date('Y-m-d H:i:s') ."')");
            
            if ($insertResult) {
                echo "Successfully inserted test record!<br>";
                echo "Last insert ID: " . $mysqli->insert_id . "<br>";
            } else {
                echo "Failed to insert test record: " . $mysqli->error . "<br>";
            }
        } else {
            echo "system_logs table does not exist!<br>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Debugging Admin Login Issue</h2>";
echo "<p>The original issue is that successful admin logins are not being logged to the system_logs table.</p>";
echo "<p>This is likely due to the fact that the SystemLogs::access() method is failing in the LoginController.php file.</p>";
echo "<p>The solution is to update the LoginController.php to use direct SQL queries instead of the ORM model method.</p>";
?> 