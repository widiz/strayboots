<?php

class SystemLogs extends \Phalcon\Mvc\Model
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $message;

    /**
     * @var integer
     */
    public $user_id;

    /**
     * @var string
     */
    public $ip_address;
    
    /**
     * @var string
     */
    public $created;

    /**
     * Debug function to write to a log file for tracing issues
     */
    private static function debugLog($message, $data = null)
    {
        $logFile = '/var/www/newplay/logs2/systemlogs_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $logEntry .= " " . json_encode($data);
            } else {
                $logEntry .= " " . $data;
            }
        }
        
        try {
            // Make sure the directory exists with proper permissions
            if (!is_dir('/var/www/newplay/logs2')) {
                mkdir('/var/www/newplay/logs2', 0775, true);
                chmod('/var/www/newplay/logs2', 0775);
            }
            
            // Check if file exists and create with proper permissions if it doesn't
            if (!file_exists($logFile)) {
                touch($logFile);
                chmod($logFile, 0664);
            }
            
            file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
        } catch (\Exception $e) {
            error_log("Failed to write to debug log: " . $e->getMessage());
        }
    }

    /**
     * Initialize method for model
     */
    public function initialize()
    {
        $this->setSource("system_logs");
        
        // Define relationships
        $this->belongsTo('user_id', 'Users', 'id', [
            'alias' => 'User',
            'foreignKey' => [
                'message' => 'User cannot be deleted because it has associated logs'
            ]
        ]);
    }

    /**
     * Log a system event
     * 
     * @param string $type Log type (error, access, activity, system)
     * @param string $message Log message
     * @param int $userId User ID if applicable
     * @return boolean
     */
    public static function log($type, $message, $userId = null)
    {
        self::debugLog("SystemLogs::log called with type={$type}, message={$message}, userId=" . ($userId ?? 'null'));
        
        try {
        $di = \Phalcon\Di::getDefault();
            self::debugLog("DI container retrieved");
            
            // Check if request service exists
            if (!$di->has('request')) {
                self::debugLog("ERROR: Request service not found in DI");
                error_log("SystemLogs::log - Request service not found in DI");
                return false;
            }
            
            $request = $di->get('request');
            $ip = $request->getClientAddress();
            self::debugLog("IP address retrieved", $ip);
            
            // If IP is empty, log this issue but continue with a placeholder
            if (empty($ip)) {
                self::debugLog("WARNING: Client IP address could not be determined, using placeholder");
                error_log("SystemLogs::log - Client IP address could not be determined");
                $ip = '0.0.0.0';
            }
            
            // Try using direct database query instead of ORM
            try {
                if ($di->has('db')) {
                    $db = $di->get('db');
                    self::debugLog("Database connection retrieved");
                    
                    $currentTime = date('Y-m-d H:i:s');
                    
                    // Test the database connection with a simple query
                    try {
                        $testQuery = "SELECT 1";
                        $result = $db->query($testQuery);
                        self::debugLog("Database connection test successful");
                    } catch (\Exception $testEx) {
                        self::debugLog("ERROR: Database connection test failed", $testEx->getMessage());
                    }
                    
                    // Check if the system_logs table exists
                    try {
                        $tableCheck = "SHOW TABLES LIKE 'system_logs'";
                        $result = $db->query($tableCheck);
                        $tableExists = $result->numRows() > 0;
                        self::debugLog("system_logs table exists", $tableExists ? "Yes" : "No");
                        
                        if (!$tableExists) {
                            self::debugLog("ERROR: system_logs table does not exist!");
                            // Try to create the table
                            try {
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
                                self::debugLog("Created system_logs table");
                            } catch (\Exception $createEx) {
                                self::debugLog("ERROR: Failed to create system_logs table", $createEx->getMessage());
                            }
                        }
                    } catch (\Exception $tableEx) {
                        self::debugLog("ERROR: Could not check if system_logs table exists", $tableEx->getMessage());
                    }
                    
                    // Use a prepared statement to insert the log
                    self::debugLog("Attempting direct SQL insert");
                    $sql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
                            VALUES (:type, :message, :user_id, :ip_address, :created)";
                    
                    $params = [
                        'type' => $type,
                        'message' => $message,
                        'user_id' => $userId,
                        'ip_address' => $ip,
                        'created' => $currentTime
                    ];
                    
                    self::debugLog("SQL parameters", $params);
                    
                    // Execute the query
                    try {
                        $success = $db->execute($sql, $params);
                        self::debugLog("Direct SQL insert result", $success ? "Success" : "Failed");
                        
                        if (!$success) {
                            self::debugLog("ERROR: Failed to insert log via direct SQL");
                            error_log("SystemLogs::log - Failed to insert log via direct SQL");
                            // Fall back to ORM if direct query fails
                        } else {
                            return true;
                        }
                    } catch (\Exception $insertEx) {
                        self::debugLog("ERROR: Exception during SQL insert", $insertEx->getMessage());
                    }
                } else {
                    self::debugLog("ERROR: Database service not available in DI");
                }
            } catch (\Exception $sqlEx) {
                self::debugLog("ERROR: SQL Exception", $sqlEx->getMessage());
                error_log("SystemLogs::log - SQL Exception: " . $sqlEx->getMessage());
                // Continue to try the ORM method as fallback
            }
            
            // Fallback to ORM method
            self::debugLog("Attempting ORM insert");
        $log = new SystemLogs();
        $log->type = $type;
        $log->message = $message;
        $log->user_id = $userId;
            $log->ip_address = $ip;
        $log->created = date('Y-m-d H:i:s');
        
            // Check if save successful and log errors if not
            if (!$log->save()) {
                $errors = [];
                foreach ($log->getMessages() as $errorMessage) {
                    $errors[] = $errorMessage->getMessage();
                }
                self::debugLog("ERROR: Failed to save log via ORM", $errors);
                error_log("SystemLogs::log - Failed to save log via ORM: " . json_encode($errors));
                error_log("SystemLogs::log - Data: " . json_encode([
                    'type' => $type,
                    'message' => $message,
                    'user_id' => $userId,
                    'ip_address' => $ip
                ]));
                return false;
            }
            
            self::debugLog("ORM insert success");
            return true;
        } catch (\Exception $e) {
            self::debugLog("ERROR: Unhandled exception", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            error_log("SystemLogs::log - Exception: " . $e->getMessage());
            error_log("SystemLogs::log - Trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Log error event
     * 
     * @param string $message Error message
     * @param int $userId User ID if applicable
     * @return boolean
     */
    public static function error($message, $userId = null)
    {
        return self::log('error', $message, $userId);
    }
    
    /**
     * Log access event (login, logout, etc)
     * 
     * @param string $message Access message
     * @param int $userId User ID if applicable
     * @return boolean
     */
    public static function access($message, $userId = null)
    {
        return self::log('access', $message, $userId);
    }
    
    /**
     * Log activity event (user actions)
     * 
     * @param string $message Activity message
     * @param int $userId User ID if applicable
     * @return boolean
     */
    public static function activity($message, $userId = null)
    {
        return self::log('activity', $message, $userId);
    }
    
    /**
     * Log system event
     * 
     * @param string $message System message
     * @param int $userId User ID if applicable
     * @return boolean
     */
    public static function system($message, $userId = null)
    {
        return self::log('system', $message, $userId);
    }

    /**
     * Independent Column Mapping
     */
    public function columnMap()
    {
        return [
            'id' => 'id',
            'type' => 'type',
            'message' => 'message',
            'user_id' => 'user_id',
            'ip_address' => 'ip_address',
            'created' => 'created'
        ];
    }
} 