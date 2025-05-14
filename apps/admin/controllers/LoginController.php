<?php

namespace Play\Admin\Controllers;

use \Users,
	\SystemLogs,
	\Exception;

class LoginController extends \ControllerBase
{

	public function indexAction()
	{
		$this->view->bodyClass = 'gray-bg';
		$this->view->hiddenWrapper = true;
	}

	/**
	 * Debug function to write to a log file
	 */
	private function debugLog($message, $data = null)
	{
		$logFile = '/var/www/newplay/logs2/login_debug.log';
		$timestamp = date('Y-m-d H:i:s');
		$ip = $this->request->getClientAddress();
		$logEntry = "[{$timestamp}] [{$ip}] {$message}";
		
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

	public function loginAction()
	{
		/*$x = new Users;
		$x->email = "shay12tg@gmail.com";
		$x->first_name='Shay';
		$x->last_name='Hananashvili';
		$x->password=$this->security->hash('123123');
		$x->save();*/

		$this->debugLog("Login action started");

		if (!$this->request->isPost()) {
			$this->debugLog("Not a POST request, redirecting to login page");
			return $this->response->redirect('login');
		}

		$email = $this->request->getPost('email', 'email');
		$password = $this->request->getPost('password');
		$this->debugLog("Login attempt for email", $email);
		
		try {
			if (!$this->security->checkToken()) {
				$this->debugLog("Security token check failed");
				throw new Exception();
			}
			
			if (empty($email) || empty($password)) {
				$this->debugLog("Email or password empty");
				throw new Exception();
			}
			
			$user = Users::findFirstByEmail($email);
			$this->debugLog("User lookup result", $user ? "User found (ID: {$user->id})" : "User not found");
			
			if ($user && $user->checkLogin($password, true)) {
				$this->debugLog("Login successful for user {$user->email} (ID: {$user->id}), type: " . (isset($user->type) ? $user->type : 'N/A'));
				
				// Log successful login for all admin panel users
				// Since we're in the admin panel, treat all successful logins as admin logins
				// Don't depend on the type field which might not exist
				$this->debugLog("User is in admin panel, logging admin login");
				$logMessage = "Admin user '{$user->email}' logged in";
				
				// First try direct database logging (most reliable method)
				try {
					$db = $this->getDI()->get('db');
					$this->debugLog("Database connection retrieved successfully");
					
					// Insert log directly using SQL
					$timestamp = date('Y-m-d H:i:s');
					$ip = $this->request->getClientAddress();
					$directSql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
								VALUES ('admin', :message, :user_id, :ip_address, :created)";
					
					$directSuccess = $db->execute($directSql, [
						'message' => $logMessage,
						'user_id' => $user->id,
						'ip_address' => $ip,
						'created' => $timestamp
					]);
					
					$this->debugLog("Direct SQL logging result", $directSuccess ? "Success" : "Failed");
					
					// If direct SQL fails, fall back to custom admin log method
					if (!$directSuccess) {
						// Fall back to custom admin log method
						$logResult = $this->logAdminEvent($logMessage, $user->id);
						$this->debugLog("Fallback logAdminEvent result", $logResult ? "Success" : "Failed");
					}
				} catch (\Exception $dbEx) {
					$this->debugLog("Database logging exception", $dbEx->getMessage());
					
					// Fall back to custom admin log method
					$logResult = $this->logAdminEvent($logMessage, $user->id);
					$this->debugLog("Fallback logAdminEvent result", $logResult ? "Success" : "Failed");
				}
				
				$this->flash->success("Welcome!");
				if (!empty($redirect = $this->session->get('redirect', '', true))) {
					$this->debugLog("Redirecting to", $redirect);
					header("Location: " . $redirect);
					exit;
				}
				$this->debugLog("Redirecting to index");
				return $this->response->redirect('index');
			}
			$this->debugLog("Login verification failed");
			throw new Exception();
		} catch(Exception $e) {
			// Log failed login attempt
			if (!empty($email)) {
				$this->debugLog("Attempting to log failed login via SystemLogs::access");
				$logResult = SystemLogs::access("Failed login attempt for email '{$email}'");
				$this->debugLog("SystemLogs::access result for failed login", $logResult ? "Success" : "Failed");
				
				if (!$logResult) {
					// If logging fails, record it in PHP error log
					$this->debugLog("WARNING: Failed to log failed login attempt");
				}
			}
			
			$this->flash->error("Login failed");
			$this->debugLog("Login failed, redirecting to login page");
			return $this->response->redirect('login');
		}
	}

	public function logoutAction()
	{
		$this->debugLog("Logout action started");
		
		// Log logout for admin users
		$userId = $this->session->get('userID');
		if ($userId) {
			$user = Users::findFirst($userId);
			$this->debugLog("User found for logout", $user ? "Yes (ID: {$user->id})" : "No");
			
			if ($user) {
				// Since we're in the admin panel, all users are considered admin
				$this->debugLog("User is in admin panel, logging admin logout");
				$logMessage = "Admin user '{$user->email}' logged out";
				
				// First try direct database logging (most reliable method)
				try {
					$db = $this->getDI()->get('db');
					$this->debugLog("Database connection retrieved for logout");
					
					// Insert log directly using SQL
					$timestamp = date('Y-m-d H:i:s');
					$ip = $this->request->getClientAddress();
					$directSql = "INSERT INTO system_logs (type, message, user_id, ip_address, created) 
								VALUES ('admin', :message, :user_id, :ip_address, :created)";
					
					$directSuccess = $db->execute($directSql, [
						'message' => $logMessage,
						'user_id' => $user->id,
						'ip_address' => $ip,
						'created' => $timestamp
					]);
					
					$this->debugLog("Direct SQL logout logging result", $directSuccess ? "Success" : "Failed");
					
					// If direct SQL fails, fall back to custom admin log method
					if (!$directSuccess) {
						// Fall back to custom admin log method
						$logResult = $this->logAdminEvent($logMessage, $user->id);
						$this->debugLog("Fallback logAdminEvent result for logout", $logResult ? "Success" : "Failed");
					}
				} catch (\Exception $dbEx) {
					$this->debugLog("Database logout logging exception", $dbEx->getMessage());
					
					// Fall back to custom admin log method
					$logResult = $this->logAdminEvent($logMessage, $user->id);
					$this->debugLog("Fallback logAdminEvent result for logout", $logResult ? "Success" : "Failed");
				}
			}
		}
		
		Users::logout();
		$this->debugLog("User logged out, redirecting to login page");
		return $this->response->redirect('login');
	}

	/**
	 * Custom method to log admin events with type 'admin' instead of 'access'
	 */
	private function logAdminEvent($message, $userId = null)
	{
		$this->debugLog("Using custom logAdminEvent method with type='admin'");
		
		try {
			$di = \Phalcon\Di::getDefault();
			$request = $di->get('request');
			$ip = $request->getClientAddress();
			
			if (empty($ip)) {
				$ip = '0.0.0.0';
			}
			
			$log = new \SystemLogs();
			$log->type = 'admin';  // Use 'admin' type instead of 'access'
			$log->message = $message;
			$log->user_id = $userId;
			$log->ip_address = $ip;
			$log->created = date('Y-m-d H:i:s');
			
			return $log->save();
		} catch (\Exception $e) {
			$this->debugLog("Exception in logAdminEvent: " . $e->getMessage());
			return false;
		}
	}

}

