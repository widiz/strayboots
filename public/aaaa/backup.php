<?php
// Database credentials
$host = 'mysql.go.strayboots.com';
$user = 'newplay';
$pass = 'YFI1W9m$CYi4sd.h';
$db = 'newplay';

// Set PHP execution time to unlimited (for large databases)
set_time_limit(0);
ini_set('memory_limit', '512M');

// Timestamp for file naming
$timestamp = date('Y-m-d_H-i-s');
$filename = "{$db}_backup_{$timestamp}.sql";

try {
    // Connect to database
    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    // Start output buffering for SQL content
    ob_start();
    
    // Add SQL header
    echo "-- PHP MySQL Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Host: " . $host . "\n";
    echo "-- Database: " . $db . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Get all tables
    $tables = [];
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Process each table
    foreach ($tables as $table) {
        echo "-- Table structure for table `$table`\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        
        $res = $mysqli->query("SHOW CREATE TABLE `$table`");
        $row = $res->fetch_row();
        echo $row[1] . ";\n\n";
        
        // Get table data
        $res = $mysqli->query("SELECT * FROM `$table`");
        $column_count = $res->field_count;
        
        if ($res->num_rows > 0) {
            echo "-- Dumping data for table `$table`\n";
            echo "INSERT INTO `$table` VALUES ";
            
            $row_count = 0;
            while ($row = $res->fetch_row()) {
                if ($row_count % 100 == 0 && $row_count > 0) {
                    echo ";\nINSERT INTO `$table` VALUES ";
                } else if ($row_count > 0) {
                    echo ",";
                }
                
                echo "\n(";
                for ($i = 0; $i < $column_count; $i++) {
                    if ($i > 0) {
                        echo ",";
                    }
                    
                    if ($row[$i] === null) {
                        echo "NULL";
                    } else {
                        echo "'" . $mysqli->real_escape_string($row[$i]) . "'";
                    }
                }
                echo ")";
                $row_count++;
            }
            
            if ($row_count > 0) {
                echo ";\n";
            }
        }
        
        echo "\n\n";
    }
    
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Get SQL content
    $sql_content = ob_get_clean();
    
    // Create temporary files with random names to prevent conflicts
    $temp_dir = sys_get_temp_dir();
    $temp_sql_file = $temp_dir . '/' . uniqid('sql_') . '.sql'; 
    $temp_zip_file = $temp_dir . '/' . uniqid('zip_') . '.zip';
    
    // Write SQL to file
    file_put_contents($temp_sql_file, $sql_content);
    
    // Create ZIP file
    $zip = new ZipArchive();
    if ($zip->open($temp_zip_file, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Cannot create ZIP file");
    }
    
    $zip->addFile($temp_sql_file, $filename);
    $zip->close();
    
    // Output headers for download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '.zip"');
    header('Content-Length: ' . filesize($temp_zip_file));
    header('Cache-Control: no-cache, must-revalidate');
    
    // Output ZIP file
    readfile($temp_zip_file);
    
    // Clean up
    @unlink($temp_sql_file);
    @unlink($temp_zip_file);
    
} catch (Exception $e) {
    // If an error occurs, show it
    header('Content-Type: text/html');
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Show more details if in debug mode
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
} finally {
    // Close database connection
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>