<?php

namespace Play\Admin\Controllers;

use \Countries,
	\Cities,
	DataTables\DataTable;

class CitiesController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$id = (int)$id;

		$country = Countries::findFirstByid($id);
		if (!$country)
			return $this->response->redirect('countries');

		$this->view->country = $country;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/cities.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($id = 0)
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$id = (int)$id;

		$country = Countries::findFirstByid($id);
		if (!$country)
			throw new \Exception(404, 404);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, status, (SELECT COUNT(1) FROM \Points WHERE city_id=\Cities.id) AS points')
							->from('Cities')
							->where("country_id = " . $country->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	private function addEdit()
	{
		$timezones = [];
		foreach (timezone_abbreviations_list() as $abbr => $zones) {
			$abbr = strtoupper($abbr);
			if (empty($abbr)) continue;
			foreach ($zones as $zone) {
				if (empty($zone['timezone_id'])) continue;
				$timezones[$abbr][$zone['timezone_id']] = str_replace('_', ' ', $zone['timezone_id']);
			}
		}
		$this->view->timezones = $timezones;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$id = (int)$id;

		$country = Countries::findFirstByid($id);
		if (!$country)
			return $this->response->redirect('countries');

		$this->tag->setDefault("country_id", $country->id);

		$this->view->country = $country;

		$this->addEdit();
	}

	/**
	 * Edits a citie
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$city = Cities::findFirstByid($id);
		if (!$city) {
			$this->flash->error('city was not found');

			$this->response->redirect('countries');

			return;
		}
		$this->tag->setDefault('country_id', $city->country_id);

		$this->view->country = $city->Country;

		$this->addEdit();

		$this->view->id = $city->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $city->id);
			$this->tag->setDefault('name', $city->name);
			$this->tag->setDefault('status', $city->status);
			$this->tag->setDefault('timezone', $city->timezone);
		}
		
	}

	/**
	 * Creates a new citie
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('countries');
			return;
		}

		$city = new Cities();
		$city->country_id = $this->request->getPost('country_id', 'int');
		$city->name = trim($this->request->getPost('name', 'string'));
		$city->status = $this->request->getPost('status', 'int');
		$city->timezone = $this->request->getPost('timezone', 'string');
		
		if (!$city->save()) {
			foreach ($city->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'cities',
				'action' => 'new',
				'params' => [$city->country_id]
			]);

			return;
		}

		$this->flash->success('city was created successfully');

		$this->response->redirect("cities/{$city->country_id}");
	}

	/**
	 * Saves a city edited
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

		$id = $this->request->getPost('id');
		$city = Cities::findFirstByid($id);

		if (!$city) {
			$this->flash->error('city does not exist ' . $id);

			$this->response->redirect('countries');

			return;
		}

		$city->country_id = $this->request->getPost('country_id', 'int');
		$city->name = trim($this->request->getPost('name', 'string'));
		$city->status = $this->request->getPost('status', 'int');
		$city->timezone = $this->request->getPost('timezone', 'string');
		
		if (!$city->save()) {

			foreach ($city->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'cities',
				'action' => 'edit',
				'params' => [$city->id]
			]);

			return;
		}

		$this->flash->success('city was updated successfully');
		
		$this->response->redirect('cities/' . $city->country_id);
	}

	/**
	 * Deletes a citie
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$city = Cities::findFirstByid($id);
		if (!$city) {
			$this->flash->error("city was not found");

			$this->response->redirect('countries');

			return;
		}

		$country_id = $city->country_id;

		if ($city->delete()) {
			$this->flash->success("city was deleted successfully");
		} else {
			foreach ($city->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('cities/' . $country_id);
	}

}
