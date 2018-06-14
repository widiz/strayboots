<?php
 
namespace Play\Admin\Controllers;

use \Clients,
	DataTables\DataTable;

class ClientsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/clients.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns("c.id, c.email, c.company, c.first_name, c.last_name, c.phone, c.active, c.created, COUNT(o.id) AS orders")
							->from(['c' => 'Clients'])
							->leftJoin('Orders', 'o.client_id = c.id', 'o')
							->groupBy('c.id');
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost())
			$this->tag->setDefault("password", $this->generatePassword(8));
	}

	/**
	 * Edits a client
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$client = Clients::findFirstByid($id);
		if (!$client) {
			$this->flash->error("Client was not found");

			$this->response->redirect('clients');

			return;
		}

		$this->view->id = $client->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $client->id);
			$this->tag->setDefault("email", $client->email);
			$this->tag->setDefault("company", $client->company);
			$this->tag->setDefault("password", $client->password);
			$this->tag->setDefault("first_name", $client->first_name);
			$this->tag->setDefault("last_name", $client->last_name);
			$this->tag->setDefault("phone", $client->phone);
			$this->tag->setDefault("notes", $client->notes);
			$this->tag->setDefault("active", $client->active);
			$this->tag->setDefault("created", $client->created);
			
		}
	}

	/**
	 * Creates a new client
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('clients');

			return;
		}

		$client = new Clients();
		$client->email = $this->request->getPost("email", "email");
		$client->company = $this->request->getPost("company", 'trim');
		$client->password = $this->request->getPost("password", 'trim');
		$client->first_name = $this->request->getPost("first_name", 'trim');
		$client->last_name = $this->request->getPost("last_name", 'trim');
		$client->phone = $this->request->getPost("phone", 'trim');
		$client->notes = $this->request->getPost("notes", 'trim');

		if (empty($client->phone))
			$client->phone = null;
		if (empty($client->notes))
			$client->notes = null;

		if (!$client->save()) {
			foreach ($client->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "clients",
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success("Client was created successfully");

		$this->response->redirect('clients');
	}

	/**
	 * Saves a client edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('clients');

			return;
		}

		$id = $this->request->getPost("id", 'int');
		$client = Clients::findFirstByid($id);

		if (!$client) {
			$this->flash->error("Client does not exist " . $id);

			$this->response->redirect('clients');

			return;
		}

		$client->email = $this->request->getPost("email", "email");
		$client->company = $this->request->getPost("company", 'trim');
		$client->password = $this->request->getPost("password", 'trim');
		$client->first_name = $this->request->getPost("first_name", 'trim');
		$client->last_name = $this->request->getPost("last_name", 'trim');
		$client->phone = $this->request->getPost("phone", 'trim');
		$client->notes = $this->request->getPost("notes", 'trim');
		$client->active = $this->request->getPost("active", 'int');

		if (empty($client->active))
			$client->active = 0;
		if (empty($client->phone))
			$client->phone = null;
		if (empty($client->notes))
			$client->notes = null;

		if (!$client->save()) {
			foreach ($client->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "clients",
				'action' => 'edit',
				'params' => [$client->id]
			]);

			return;
		}

		$this->flash->success("Client was updated successfully");

		$this->response->redirect('clients');
	}

	/**
	 * Deletes a client
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$client = Clients::findFirstByid($id);
		if (!$client) {
			$this->flash->error("Client was not found");

			$this->response->redirect('clients');

			return;
		}

		if ($client->delete()) {
			$this->flash->success("Client was deleted successfully");
		} else {
			foreach ($client->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('clients');
	}

}
