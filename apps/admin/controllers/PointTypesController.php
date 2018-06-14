<?php

namespace Play\Admin\Controllers;

use \PointTypes,
	DataTables\DataTable;

class PointTypesController extends \ControllerBase
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
				->addJs('/js/admin/pointtypes.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, (SELECT COUNT(1) FROM \Points WHERE type_id=pointtypes.id) AS points')
							->from(['pointtypes' => 'PointTypes']);
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
	 * Edits a point_type
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$point_type = PointTypes::findFirstByid($id);
		if (!$point_type) {
			$this->flash->error("Point type was not found");

			$this->response->redirect('point_types');

			return;
		}

		$this->view->id = $point_type->id;
		
		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $point_type->id);
			$this->tag->setDefault("name", $point_type->name);
			
		}
	}

	/**
	 * Creates a new point_type
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('point_types');

			return;
		}


		$point_type = new PointTypes();
		$point_type->name = $this->request->getPost("name", 'trim');
		

		if (!$point_type->save()) {
			foreach ($point_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "point_types",
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success("Point type was created successfully");

		$this->response->redirect('point_types');
	}

	/**
	 * Saves a point_type edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('point_types');

			return;
		}

		$id = $this->request->getPost("id", 'int');
		$point_type = PointTypes::findFirstByid($id);

		if (!$point_type) {
			$this->flash->error("Point type does not exist " . $id);

			$this->response->redirect('point_types');

			return;
		}

		$point_type->name = $this->request->getPost("name", 'trim');
		

		if (!$point_type->save()) {

			foreach ($point_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "point_types",
				'action' => 'edit',
				'params' => [$point_type->id]
			]);

			return;
		}

		$this->flash->success("Point type was updated successfully");

		$this->response->redirect('point_types');
	}

	/**
	 * Deletes a point_type
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$point_type = PointTypes::findFirstByid($id);
		if (!$point_type) {
			$this->flash->error("Point type was not found");

			$this->response->redirect('point_types');

			return;
		}

		if ($point_type->delete()) {
			$this->flash->success("Point type was deleted successfully");
		} else {
			foreach ($point_type->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('point_types');
	}

}
