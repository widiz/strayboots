<?php

namespace Play\Admin\Controllers;

use \Exception,
	DataTables\DataTable,
	Users;

class AdminUsersController extends \ControllerBase
{
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		// Ensure jQuery is loaded first
		$this->assets->collection('script')
					->addJs('/template/js/jquery-2.1.1.js', true)
					->addJs('/template/js/bootstrap.min.js')
					->addJs('/template/js/plugins/metisMenu/jquery.metisMenu.js')
					->addJs('/template/js/plugins/slimscroll/jquery.slimscroll.min.js')
					->addJs('/template/js/inspinia.js')
					->addJs('/js/plugins/bootbox.min.js');
					
		$this->assets->collection('style')
					->addCss('/template/css/bootstrap.min.css')
					->addCss('/template/font-awesome/css/font-awesome.css')
					->addCss('/template/css/animate.css')
					->addCss('/template/css/style.css');
					
		// Load all users and pass to view
		try {
			$this->view->users = Users::find([
				'order' => 'id DESC'
			]);
		} catch (\Exception $e) {
			// Log the error
			error_log("Error loading users: " . $e->getMessage());
			
			// Send empty array if there's an error
			$this->view->users = [];
			
			// Add flash message
			$this->flash->error("Error loading users: " . $e->getMessage());
		}
	}
	
	public function datatableAction()
	{
		if ($this->requireUser())
			throw new Exception(403, 403);
			
		$builder = $this->modelsManager->createBuilder()
					->columns([
						'Users.id', 'Users.name', 'Users.email',
						'Users.first_name', 'Users.last_name',
						'Users.active', 'Users.created', 'Users.type'
					])
					->from('Users')
					->orderBy('Users.id DESC');
					
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}
	
	public function createAction()
	{
		if ($this->requireUser())
			return true;
			
		// Ensure jQuery is loaded
		$this->assets->collection('script')
					->addJs('/template/js/jquery-2.1.1.js', true)
					->addJs('/template/js/bootstrap.min.js');
					
		if ($this->request->isPost()) {
			try {
				$data = $this->request->getPost();
				
				// Get user type
				$userType = isset($data['type']) ? $data['type'] : 'user';
				
				// Validate required fields
				if (empty($data['first_name']) || empty($data['last_name']) || 
					empty($data['email']) || empty($data['password'])) {
					throw new Exception("First name, last name, email and password are required");
				}
					
				// Check if email already exists
				$exists = Users::findFirst([
					'email = :email:',
					'bind' => ['email' => $data['email']]
				]);
				
				if ($exists)
					throw new Exception("Email already exists");
					
				$user = new Users();
				
				// Set all fields
				$user->first_name = $data['first_name'];
				$user->last_name = $data['last_name'];
				$user->name = !empty($data['name']) ? $data['name'] : null;
				$user->email = $data['email'];
				$user->password = $this->security->hash($data['password']);
				$user->type = $userType;
				$user->active = isset($data['active']) ? 1 : 0;
				$user->created = date('Y-m-d H:i:s');
				
				if (!$user->save()) {
					$errorMessages = $user->getMessages();
					error_log("Failed to save user: " . implode(', ', $errorMessages));
					throw new Exception("Failed to save user: " . implode(', ', $errorMessages));
				}
					
				$this->flash->success("User created successfully");
				return $this->response->redirect('admin_users');
			} catch (\Exception $e) {
				$this->flash->error($e->getMessage());
			}
		}
	}
	
	public function editAction($id = null)
	{
		if ($this->requireUser())
			return true;
			
		// Ensure jQuery is loaded
		$this->assets->collection('script')
					->addJs('/template/js/jquery-2.1.1.js', true);
			
		if (!$id) {
			$this->flash->error("Invalid ID");
			return $this->response->redirect('admin_users');
		}
		
		$user = Users::findFirstById($id);
		
		if (!$user) {
			$this->flash->error("User not found");
			return $this->response->redirect('admin_users');
		}
		
		$this->view->user = $user;
		
		if ($this->request->isPost()) {
			try {
				$data = $this->request->getPost();
				
				// Validate required fields based on whether form shows name or first_name/last_name
				if ($user->type == 'admin' && empty($user->first_name) && empty($user->last_name)) {
					// Admin with name field
					if (empty($data['name']) || empty($data['email']))
						throw new Exception("Name and email are required");
				} else {
					// Regular user or admin with first_name/last_name
					if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']))
						throw new Exception("First name, last name and email are required");
				}
					
				// Check if email already exists for different user
				$exists = Users::findFirst([
					'email = :email: AND id != :id:',
					'bind' => [
						'email' => $data['email'],
						'id' => $id
					]
				]);
				
				if ($exists)
					throw new Exception("Email already exists");
				
				// Set fields based on form fields shown	
				if ($user->type == 'admin' && empty($user->first_name) && empty($user->last_name)) {
					// Admin with name field
					$user->name = $data['name'];
				} else {
					// Regular user or admin with first_name/last_name
					$user->first_name = $data['first_name'];
					$user->last_name = $data['last_name'];
				}
				
				$user->email = $data['email'];
				
				// Update password only if provided
				if (!empty($data['password'])) {
					$user->password = $this->security->hash($data['password']);
				}
				
				$user->active = isset($data['active']) ? 1 : 0;
				
				if (!$user->save())
					throw new Exception("Failed to update user: " . implode(', ', $user->getMessages()));
					
				$this->flash->success("User updated successfully");
				return $this->response->redirect('admin_users');
			} catch (\Exception $e) {
				$this->flash->error($e->getMessage());
			}
		}
	}
	
	public function deleteAction($id = null)
	{
		if ($this->requireUser())
			return true;
			
		if (!$id) {
			$this->flash->error("Invalid ID");
			return $this->response->redirect('admin_users');
		}
		
		// Don't allow users to delete themselves
		if ($id == $this->session->get('userID')) {
			$this->flash->error("You cannot delete your own account");
			return $this->response->redirect('admin_users');
		}
		
		$user = Users::findFirstById($id);
		
		if (!$user) {
			$this->flash->error("User not found");
			return $this->response->redirect('admin_users');
		}
		
		try {
			if (!$user->delete())
				throw new Exception("Failed to delete user: " . implode(', ', $user->getMessages()));
				
			$this->flash->success("User deleted successfully");
		} catch (\Exception $e) {
			$this->flash->error($e->getMessage());
		}
		
		return $this->response->redirect('admin_users');
	}
	
	public function setupAction()
	{
		// Check if there's already at least one admin
		$existingAdmin = Users::findFirst([
			'type = :type:',
			'bind' => ['type' => 'admin']
		]);
		
		if ($existingAdmin) {
			$this->flash->notice("Admin user already exists");
			return $this->response->redirect('admin_users');
		}
		
		// Create a default admin user
		try {
			$security = $this->getDI()->get('security');
			
			$user = new Users();
			$user->name = 'Admin User';
			$user->email = 'admin@example.com';
			$user->password = $security->hash('admin123');
			$user->type = 'admin';
			$user->active = 1;
			$user->created = date('Y-m-d H:i:s');
			
			if (!$user->save()) {
				throw new Exception("Failed to save user: " . implode(', ', $user->getMessages()));
			}
			
			$this->flash->success("Default admin user created successfully");
		} catch (\Exception $e) {
			$this->flash->error($e->getMessage());
		}
		
		return $this->response->redirect('admin_users');
	}
	
	public function debugAction()
	{
		if ($this->requireUser())
			return true;
			
		echo "<h1>Users Database Debug</h1>";
		echo "<pre>";
		
		// Get all users from database
		$users = Users::find();
		
		echo "Total users found: " . count($users) . "\n\n";
		
		foreach ($users as $user) {
			echo "ID: {$user->id}\n";
			echo "Type: {$user->type}\n";
			echo "Email: {$user->email}\n";
			
			if ($user->type == 'admin') {
				echo "Name: {$user->name}\n";
			} else {
				echo "First Name: {$user->first_name}\n";
				echo "Last Name: {$user->last_name}\n";
			}
			
			echo "Active: " . ($user->active ? 'Yes' : 'No') . "\n";
			echo "Created: {$user->created}\n";
			echo "----------------------\n";
		}
		
		echo "</pre>";
		
		// Also dump the raw database table structure
		echo "<h2>Database Table Structure</h2>";
		echo "<pre>";
		$connection = $this->getDI()->get('db');
		$result = $connection->query("DESCRIBE users");
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		while ($field = $result->fetch()) {
			print_r($field);
		}
		echo "</pre>";
		
		// Show sample SQL query
		echo "<h2>Sample Query</h2>";
		echo "<pre>";
		$result = $connection->query("SELECT * FROM users LIMIT 3");
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		while ($row = $result->fetch()) {
			print_r($row);
		}
		echo "</pre>";
	}
} 