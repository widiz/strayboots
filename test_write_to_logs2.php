<?php
// Test script to check if PHP can write to the logs2 directory

// Define the directory and file paths
$logDir = '/var/www/newplay/logs2';
$logFile = $logDir . '/test_log.txt';

echo "Testing ability to write to logs2 directory...\n";

// Try to create the directory if it doesn't exist
if (!is_dir($logDir)) {
    echo "Directory doesn't exist. Trying to create it...\n";
    try {
        if (mkdir($logDir, 0775, true)) {
            echo "Directory created successfully.\n";
        } else {
            echo "Failed to create directory. Check permissions.\n";
        }
    } catch (Exception $e) {
        echo "Error creating directory: " . $e->getMessage() . "\n";
    }
} else {
    echo "Directory exists.\n";
}

// Check if directory is writable
if (is_writable($logDir)) {
    echo "Directory is writable.\n";
} else {
    echo "Directory is NOT writable! This is a problem.\n";
    // Get current user and group
    echo "Current PHP user: " . exec('whoami') . "\n";
    echo "Directory owner: " . posix_getpwuid(fileowner($logDir))['name'] . "\n";
    echo "Directory group: " . posix_getgrgid(filegroup($logDir))['name'] . "\n";
    echo "Directory permissions: " . substr(sprintf('%o', fileperms($logDir)), -4) . "\n";
    exit;
}

// Try to write to a test file
$timestamp = date('Y-m-d H:i:s');
$content = "Test log entry created at $timestamp\n";

try {
    if (file_put_contents($logFile, $content, FILE_APPEND)) {
        echo "Successfully wrote to log file: $logFile\n";
        echo "Contents of log file:\n";
        echo file_get_contents($logFile) . "\n";
    } else {
        echo "Failed to write to log file. Check file permissions.\n";
    }
} catch (Exception $e) {
    echo "Error writing to log file: " . $e->getMessage() . "\n";
}

// Display file permissions
if (file_exists($logFile)) {
    echo "File permissions: " . substr(sprintf('%o', fileperms($logFile)), -4) . "\n";
}

echo "Test completed.\n";
?> 