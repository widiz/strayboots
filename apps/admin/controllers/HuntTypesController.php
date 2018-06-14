<?php

namespace Play\Admin\Controllers;

use \HuntTypes,
	DataTables\DataTable;

class HuntTypesController extends \ControllerBase
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
				->addJs('/js/admin/hunttypes.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, (SELECT COUNT(1) FROM \Hunts WHERE type_id=hunttypes.id) AS hunts')
							->from(['hunttypes' => 'HuntTypes']);
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
	}
	/**
	 * Edits a hunt_type
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$hunt_type = HuntTypes::findFirstByid($id);
		if (!$hunt_type) {
			$this->flash->error("Hunt type was not found");

			$this->response->redirect('hunt_types');
			return;
		}
		
		$this->view->id = $hunt_type->id;
		
		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $hunt_type->id);
			$this->tag->setDefault("name", $hunt_type->name);
			
		}
	}

	/**
	 * Creates a new hunt_type
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('hunt_types');

			return;
		}

		$hunt_type = new HuntTypes();
		$hunt_type->name = $this->request->getPost("name", 'trim');
		
		if (!$hunt_type->save()) {
			foreach ($hunt_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "hunt_types",
				'action' => 'new'
			]);
			return;
		}

		$this->flash->success("Hunt type was created successfully");

		$this->response->redirect('hunt_types');
	}

	/**
	 * Saves a hunt_type edited
	 *
	 */
	public function saveAction()
	{

		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('hunt_types');

			return;
		}

		$id = $this->request->getPost("id", 'int');
		$hunt_type = HuntTypes::findFirstByid($id);

		if (!$hunt_type) {
			$this->flash->error("Hunt type does not exist " . $id);

			$this->response->redirect('hunt_types');

			return;
		}

		$hunt_type->name = $this->request->getPost("name", 'trim');
		
		if (!$hunt_type->save()) {

			foreach ($hunt_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "hunt_types",
				'action' => 'edit',
				'params' => [$hunt_type->id]
			]);

			return;
		}

		$this->flash->success("Hunt type was updated successfully");

		$this->response->redirect('hunt_types');
	}

	/**
	 * Deletes a hunt_type
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$hunt_type = HuntTypes::findFirstByid($id);
		if (!$hunt_type) {
			$this->flash->error("Hunt type was not found");
			$this->response->redirect('hunt_types');

			return;
		}

		if ($hunt_type->delete()) {
			$this->flash->success("Hunt type was deleted successfully");
		} else {
			foreach ($hunt_type->getMessages() as $message) {
				$this->flash->error($message);
			}
		}
		
		$this->response->redirect('hunt_types');

	}

}
