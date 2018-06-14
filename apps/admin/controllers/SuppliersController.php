<?php
 
namespace Play\Admin\Controllers;

use \Suppliers,
	DataTables\DataTable;

class SuppliersController extends \ControllerBase
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
				->addJs('/js/admin/suppliers.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('s.id, s.email, s.company, s.first_name, s.last_name, s.phone, s.active, s.created, COUNT(sp.id) AS products')
							->from(['s' => 'Suppliers'])
							->leftJoin('SupplierProducts', 'sp.supplier_id = s.id', 'sp')
							->groupBy('s.id');
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	public function sendpassAction($id)
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$supplier = Suppliers::findFirstByid($id);
		if (!$supplier)
			throw new \Exception(404, 404);

		return $this->jsonResponse([
			'success' => $this->sendMail($supplier->email, 'Your Strayboots Supplier Login', "Your password is: {$supplier->password}\r\nLogin at {$this->config->fullUri}/suppliers")
		]);
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
	 * Edits a supplier
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$supplier = Suppliers::findFirstByid($id);
		if (!$supplier) {
			$this->flash->error("Supplier was not found");

			$this->response->redirect('suppliers');

			return;
		}

		$this->view->id = $supplier->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $supplier->id);
			$this->tag->setDefault("email", $supplier->email);
			$this->tag->setDefault("company", $supplier->company);
			$this->tag->setDefault("password", $supplier->password);
			$this->tag->setDefault("first_name", $supplier->first_name);
			$this->tag->setDefault("last_name", $supplier->last_name);
			$this->tag->setDefault("phone", $supplier->phone);
			$this->tag->setDefault("notes", $supplier->notes);
			$this->tag->setDefault("active", $supplier->active);
			$this->tag->setDefault("created", $supplier->created);
			
		}
	}

	/**
	 * Creates a new supplier
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('suppliers');

			return;
		}

		$supplier = new Suppliers();
		$supplier->email = $this->request->getPost("email", "email");
		$supplier->company = $this->request->getPost("company", 'trim');
		$supplier->password = $this->request->getPost("password", 'trim');
		$supplier->first_name = $this->request->getPost("first_name", 'trim');
		$supplier->last_name = $this->request->getPost("last_name", 'trim');
		$supplier->phone = $this->request->getPost("phone", 'trim');
		$supplier->notes = $this->request->getPost("notes", 'trim');
		$supplier->active = $this->request->getPost("active", 'trim');

		if (empty($supplier->active))
			$supplier->active = 0;
		if (empty($supplier->phone))
			$supplier->phone = null;
		if (empty($supplier->notes))
			$supplier->notes = null;

		if (!$supplier->save()) {
			foreach ($supplier->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "suppliers",
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success("Supplier was created successfully");

		$this->response->redirect('suppliers');
	}

	/**
	 * Saves a supplier edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('suppliers');

			return;
		}

		$id = $this->request->getPost("id", 'int');
		$supplier = Suppliers::findFirstByid($id);

		if (!$supplier) {
			$this->flash->error("Supplier does not exist " . $id);

			$this->response->redirect('suppliers');

			return;
		}

		$supplier->email = $this->request->getPost("email", "email");
		$supplier->company = $this->request->getPost("company", 'trim');
		$supplier->password = $this->request->getPost("password", 'trim');
		$supplier->first_name = $this->request->getPost("first_name", 'trim');
		$supplier->last_name = $this->request->getPost("last_name", 'trim');
		$supplier->phone = $this->request->getPost("phone", 'trim');
		$supplier->notes = $this->request->getPost("notes", 'trim');
		$supplier->active = $this->request->getPost("active", 'int');

		if (empty($supplier->active))
			$supplier->active = 0;
		if (empty($supplier->phone))
			$supplier->phone = null;
		if (empty($supplier->notes))
			$supplier->notes = null;

		if (!$supplier->save()) {
			foreach ($supplier->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "suppliers",
				'action' => 'edit',
				'params' => [$supplier->id]
			]);

			return;
		}

		$this->flash->success("Supplier was updated successfully");

		$this->response->redirect('suppliers');
	}

	/**
	 * Deletes a supplier
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$supplier = Suppliers::findFirstByid($id);
		if (!$supplier) {
			$this->flash->error("Supplier was not found");

			$this->response->redirect('suppliers');

			return;
		}

		if ($supplier->delete()) {
			$this->flash->success("Supplier was deleted successfully");
		} else {
			foreach ($supplier->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('suppliers');
	}

}
