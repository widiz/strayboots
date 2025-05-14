<?php
// Script to debug admin user type issue

// Include Phalcon loader
require_once __DIR__ . '/index.php';

// Log file for the test
$logFile = '/var/www/newplay/logs2/admin_debug.txt';

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

// Check admin user types in the system
try {
    $di = \Phalcon\Di::getDefault();
    $db = $di->get('db');
    
    logMessage("Checking admin users in the system");
    
    // Check if session is active with an admin user
    $session = $di->get('session');
    $userID = $session->get('userID');
    
    logMessage("Current session user ID", $userID ?: "Not logged in");
    
    if ($userID) {
        // Get user details
        $query = "SELECT id, email, type, first_name, last_name FROM users WHERE id = :id";
        $result = $db->query($query, ['id' => $userID]);
        $currentUser = $result->fetch();
        
        if ($currentUser) {
            logMessage("Current user details", $currentUser);
            logMessage("User type", $currentUser['type']);
            
            // Try logging for this user directly
            logMessage("Testing direct logging for current user");
            $logMessage = "Test log for user '{$currentUser['email']}'";
            
            // Try to log with direct SQL
            $timestamp = date('Y-m-d H:i:s');
            $insertSql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                          VALUES ('access', :message, :user_id, '127.0.0.1', :created)";
            
            $success = $db->execute($insertSql, [
                'message' => $logMessage,
                'user_id' => $currentUser['id'],
                'created' => $timestamp
            ]);
            
            logMessage("Direct SQL log for current user", $success ? "Success" : "Failed");
        } else {
            logMessage("No user found for session ID", $userID);
        }
    }
    
    // Get all admin users
    $query = "SELECT id, email, type, first_name, last_name FROM users WHERE type = 'admin'";
    $result = $db->query($query);
    $adminUsers = $result->fetchAll();
    
    logMessage("Admin users in the system", $adminUsers);
    
    // Check recent successful logins in system_logs
    $query = "SELECT * FROM system_logs WHERE message LIKE '%logged in%' ORDER BY id DESC LIMIT 10";
    $result = $db->query($query);
    $recentLogins = $result->fetchAll();
    
    logMessage("Recent successful logins in system_logs", $recentLogins);
    
    // Check recent failed logins in system_logs
    $query = "SELECT * FROM system_logs WHERE message LIKE '%Failed login%' ORDER BY id DESC LIMIT 10";
    $result = $db->query($query);
    $recentFailedLogins = $result->fetchAll();
    
    logMessage("Recent failed logins in system_logs", $recentFailedLogins);
    
    // Test the condition in the controller
    logMessage("Trying to reproduce the condition check in LoginController");
    
    foreach ($adminUsers as $user) {
        logMessage("User: {$user['email']} - Type: {$user['type']}");
        logMessage("Is admin check", ($user['type'] == 'admin') ? "Yes" : "No");
        
        // Try to insert a log entry for this admin user
        $timestamp = date('Y-m-d H:i:s');
        $testMessage = "Test login for admin user '{$user['email']}'";
        
        // Try to log with direct SQL for this admin
        $insertSql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                      VALUES ('access', :message, :user_id, '127.0.0.1', :created)";
        
        $success = $db->execute($insertSql, [
            'message' => $testMessage,
            'user_id' => $user['id'],
            'created' => $timestamp
        ]);
        
        logMessage("Direct log insertion for admin {$user['email']}", $success ? "Success" : "Failed");
    }
    
} catch (\Exception $e) {
    logMessage("ERROR", $e->getMessage());
    logMessage("Stack trace", $e->getTraceAsString());
}

logMessage("Debug completed");
?> 