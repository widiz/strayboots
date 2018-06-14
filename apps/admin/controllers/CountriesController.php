<?php

namespace Play\Admin\Controllers;

use \Countries,
	DataTables\DataTable;

class CountriesController extends \ControllerBase
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
				->addJs('/js/admin/countries.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, (SELECT COUNT(1) FROM \Cities WHERE country_id=countries.id) AS cities')
							->from(['countries' => 'Countries']);

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
	 * Edits a country
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$country = Countries::findFirstByid($id);
		if (!$country) {
			$this->flash->error("country was not found");

			$this->response->redirect('countries');

			return;
		}
			
		$this->view->id = $country->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $country->id);
			$this->tag->setDefault("name", $country->name);
			
		}
	}

	/**
	 * Creates a new country
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('countries');
			return;
		}

		$country = new Countries();
		$country->name = trim($this->request->getPost("name", 'string'));
		
		if (!$country->save()) {
			foreach ($country->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => "countries",
				'action' => 'new'
			]);
			return;
		}

		$this->flash->success("country was created successfully");

		$this->response->redirect('countries');
	}

	/**
	 * Saves a country edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('countries');
			return;
		}

		$id = $this->request->getPost("id", 'int');
		$country = Countries::findFirstByid($id);

		if (!$country) {
			$this->flash->error("country does not exist " . $id);
			
			$this->response->redirect('countries');
			return;
		}

		$country->name = trim($this->request->getPost("name", 'string'));
		
		if (!$country->save()) {

			foreach ($country->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => "countries",
				'action' => 'edit',
				'params' => [$country->id]
			]);
			return;
		}

		$this->flash->success("country was updated successfully");

		$this->response->redirect('countries');
	}

	/**
	 * Deletes a country
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$country = Countries::findFirstByid($id);
		if (!$country) {
			$this->flash->error("country was not found");

			$this->response->redirect('countries');
			return;
		}

		if ($country->delete()) {
			$this->flash->success("country was deleted successfully");
		} else {
			foreach ($country->getMessages() as $message) {
				$this->flash->error($message);
			}
		}
		
		$this->response->redirect('countries');
	}

}
