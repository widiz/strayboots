<?php

namespace Play\Admin\Controllers;

use \Exception,
	DataTables\DataTable;

class LogsController extends \ControllerBase
{
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		// Pagination parameters
		$currentPage = $this->request->getQuery('page', 'int', 1);
		$limit = 20; // Items per page
		$offset = ($currentPage - 1) * $limit;
		
		// Direct database query to get logs
		try {
			$connection = $this->getDI()->get('db');
			
			// Get total count for pagination
			$countResult = $connection->query("SELECT COUNT(*) as total FROM system_logs");
			$totalItems = $countResult->fetch()['total'];
			$totalPages = ceil($totalItems / $limit);
			
			// Get paginated logs
			$sql = "SELECT l.id, l.type, l.message, l.user_id, l.ip_address, l.created, 
			       CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) as username
			       FROM system_logs l
			       LEFT JOIN users u ON l.user_id = u.id
			       ORDER BY l.id DESC
			       LIMIT :limit OFFSET :offset";
			
			$statement = $connection->prepare($sql);
			$statement->bindParam(':limit', $limit, \PDO::PARAM_INT);
			$statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
			$statement->execute();
			$logs = $statement->fetchAll();
			
			// Pass data to view
			$this->view->logs = $logs;
			$this->view->currentPage = $currentPage;
			$this->view->totalPages = $totalPages;
			$this->view->totalItems = $totalItems;
			
		} catch (Exception $e) {
			$this->flash->error("Error loading logs: " . $e->getMessage());
			$this->view->logs = [];
			$this->view->currentPage = 1;
			$this->view->totalPages = 1;
			$this->view->totalItems = 0;
		}
	}
	
	public function datatableAction()
	{
		if ($this->requireUser())
			throw new Exception(403, 403);
		
		// Create a debug log file
		$debugLog = fopen(BASE_PATH . '/logs_debug.txt', 'a');
		fwrite($debugLog, "\n-------- " . date('Y-m-d H:i:s') . " --------\n");
		
		try {
			// Use direct database query instead of ORM for simplicity
			$connection = $this->getDI()->get('db');
			
			// Get the system logs with user information
			$sql = "SELECT l.id, l.type, l.message, l.user_id, l.ip_address, l.created, 
			        CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) as username
			        FROM system_logs l
			        LEFT JOIN users u ON l.user_id = u.id
			        ORDER BY l.id DESC";
			
			$logs = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
			
			fwrite($debugLog, "Found " . count($logs) . " logs\n");
			if (count($logs) > 0) {
				fwrite($debugLog, "First log: " . json_encode($logs[0]) . "\n");
			}
			
			// Return data in DataTables format
			$response = [
				'draw' => intval($this->request->get('draw', 'int', 1)),
				'recordsTotal' => count($logs),
				'recordsFiltered' => count($logs),
				'data' => $logs
			];
			
			$this->response->setContentType('application/json', 'UTF-8');
			$this->response->setContent(json_encode($response));
			$this->response->send();
			
		} catch (Exception $e) {
			fwrite($debugLog, "ERROR: " . $e->getMessage() . "\n");
			
			// Return error response
			$response = [
				'draw' => intval($this->request->get('draw', 'int', 1)),
				'recordsTotal' => 0,
				'recordsFiltered' => 0,
				'data' => [],
				'error' => $e->getMessage()
			];
			
			$this->response->setStatusCode(500, 'Internal Server Error');
			$this->response->setContentType('application/json', 'UTF-8');
			$this->response->setContent(json_encode($response));
			$this->response->send();
		} finally {
			fclose($debugLog);
		}
		
		exit;
	}
	
	/**
	 * Create a new log entry
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
			
		if ($this->request->isPost()) {
			$message = $this->request->getPost('message', 'string');
			
			$user = $this->getDI()->getSession()->get('auth');
			$userId = isset($user['id']) ? $user['id'] : null;
			
			if (empty($message)) {
				$this->flash->error("Message is required");
			} else {
				if (\SystemLogs::access($message, $userId)) {
					$this->flash->success("Log entry created successfully");
					return $this->response->redirect('logs');
				} else {
					$this->flash->error("Failed to create log entry");
				}
			}
		}
	}
} 