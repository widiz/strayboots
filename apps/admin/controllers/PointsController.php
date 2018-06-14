<?php

namespace Play\Admin\Controllers;

use \Points,
	\Countries,
	\Cities,
	\PointTypes,
	DataTables\DataTable;

class PointsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$pointTypes = PointTypes::find()->toArray();
		$pointTypes = array_combine(array_map(function($c){
			return $c['id'];
		}, $pointTypes), array_map(function($c){
			return $c['name'];
		}, $pointTypes));
		$this->view->pointTypes = $pointTypes;
		$countries = Countries::find()->toArray();
		$countries = array_combine(array_map(function($c){
			return $c['id'];
		}, $countries), array_map(function($c){
			return $c['name'];
		}, $countries));
		$this->view->countries = $countries;
		$cities = Cities::find()->toArray();
		$cities = array_combine(array_map(function($c){
			return $c['id'];
		}, $cities), array_map(function($c){
			return [$c['name'], (int)$c['country_id']];
		}, $cities));
		$this->view->cities = $cities;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/points.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns(
								'id, internal_name, name, type_id, city_id, longitude, latitude, ' .
								//'CONCAT_WS(\' / \', country.name, city.name) as countrycity, ' .
								'(SELECT COUNT(1) FROM \Questions q WHERE q.point_id=Points.id) AS questions, ' .
								'(SELECT COUNT(1) FROM \HuntPoints hp WHERE hp.point_id=Points.id) AS hunt_points'
							)
							->from('Points');
							//->leftJoin('Cities', 'city.id = p.city_id', 'city');
							//->leftJoin('Countries', 'country.id = city.country_id', 'country');
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

		if (!$this->request->isPost()) {

			$this->tag->setDefault("latitude", 0);
			$this->tag->setDefault("longitude", 0);
			
		}

		$this->addEdit();
	}

	/**
	 * Edits a point
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$point = Points::findFirstByid($id);
		if (!$point) {
			$this->flash->error("Point was not found");

			$this->response->redirect('points');

			return;
		}

		$this->view->id = $point->id;

		if (!$this->request->isPost()) {
			
			$this->tag->setDefault("id", $point->id);
			$this->tag->setDefault("city_id", $point->city_id);
			$this->tag->setDefault("type_id", $point->type_id);
			$this->tag->setDefault("name", $point->name);
			$this->tag->setDefault("internal_name", $point->internal_name);
			$this->tag->setDefault("subtitle", $point->subtitle);
			$this->tag->setDefault("latitude", $point->latitude);
			$this->tag->setDefault("longitude", $point->longitude);
			$this->tag->setDefault("address", $point->address);
			$this->tag->setDefault("phone", $point->phone);
			$this->tag->setDefault("hours", $point->hours);
			$this->tag->setDefault("notes", $point->notes);
			
		}

		$this->addEdit();
	}

	private function addEdit()
	{
		$countries = Countries::find([
			'order' => 'name ASC'
		])->toArray();
		$countries = array_combine(array_map(function($c){
			return $c['id'];
		}, $countries), array_map(function($c){
			return $c['name'];
		}, $countries));
		$cities = Cities::find([
			'order' => 'name ASC'
		])->toArray();
		$countrycities = [];
		foreach ($cities as $city) {
			$cname = $countries[$city['country_id']];
			if (isset($countrycities[$cname]))
				$countrycities[$cname][$city['id']] = $city['name'];
			else
				$countrycities[$cname] = [$city['id'] => $city['name']];
		}
		$this->view->countrycities = $countrycities;

		$this->view->googleMaps = $this->config->googleapis->maps;

		$this->assets->collection('script')
					->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/admin/points.addedit.js')
					->addJs('/template/js/plugins/select2/select2.full.min.js')
					->addJs('/template/js/plugins/clockpicker/clockpicker.js');
		$this->assets->collection('style')
					->addCss('/template/css/plugins/select2/select2.min.css')
					->addCss('/template/css/plugins/clockpicker/clockpicker.css');
	}

	/**
	 * Creates a new point
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('points');

			return;
		}

		$point = new Points();
		$point->city_id = $this->request->getPost("city_id", 'int');
		$point->type_id = $this->request->getPost("type_id", 'int');
		$point->name = $this->request->getPost("name", 'trim');
		$point->internal_name = $this->request->getPost("internal_name", 'trim');
		$point->subtitle = $this->request->getPost("subtitle", 'trim');
		$point->latitude = $this->request->getPost("latitude", 'float');
		$point->longitude = $this->request->getPost("longitude", 'float');
		$point->address = $this->request->getPost("address", 'trim');
		$point->phone = $this->request->getPost("phone", 'trim');
		$point->hours = $this->request->getPost("hours", 'trim');
		$point->notes = $this->request->getPost("notes", 'trim');
		if (empty($point->address))
			$point->address = null;
		if (empty($point->phone))
			$point->phone = null;
		if (empty($point->hours))
			$point->hours = null;
		if (empty($point->internal_name))
			$point->internal_name = $this->slugify($point->name);
		
		if (!$point->save()) {
			foreach ($point->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "points",
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success("Point was created successfully");

		$this->response->redirect('points');
	}

	/**
	 * Saves a point edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('points');

			return;
		}

		$id = $this->request->getPost("id", 'int');
		$point = Points::findFirstByid($id);

		if (!$point) {
			$this->flash->error("Point does not exist " . $id);

			$this->response->redirect('points');

			return;
		}

		$point->city_id = $this->request->getPost("city_id", 'int');
		$point->type_id = $this->request->getPost("type_id", 'int');
		$point->name = $this->request->getPost("name", 'trim');
		$point->internal_name = $this->request->getPost("internal_name", 'trim');
		$point->subtitle = $this->request->getPost("subtitle", 'trim');
		$point->latitude = $this->request->getPost("latitude", 'float');
		$point->longitude = $this->request->getPost("longitude", 'float');
		$point->address = $this->request->getPost("address", 'trim');
		$point->phone = $this->request->getPost("phone", 'trim');
		$point->hours = $this->request->getPost("hours", 'trim');
		$point->notes = $this->request->getPost("notes", 'trim');
		if (empty($point->address))
			$point->address = null;
		if (empty($point->phone))
			$point->phone = null;
		if (empty($point->hours))
			$point->hours = null;
		if (empty($point->internal_name))
			$point->internal_name = $this->slugify($point->name);

		if (!$point->save()) {

			foreach ($point->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "points",
				'action' => 'edit',
				'params' => [$point->id]
			]);

			return;
		}

		$this->flash->success("Point was updated successfully");

		$this->response->redirect('points');
	}

	/**
	 * Deletes a point
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$point = Points::findFirstByid($id);
		if (!$point) {
			$this->flash->error("Point was not found");

			$this->response->redirect('points');

			return;
		}

		if ($point->delete()) {
			$this->flash->success("Point was deleted successfully");
		} else {
			foreach ($point->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('points');
	}

	public function getPointsByCityAction($id)
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$results = [
			['id' => '', 'text' => '']
		];
		$points = Points::find("city_id = " . (int)$id);
		$coordinates = [];
		foreach ($points as $point) {
			$results[] = [
				'id' => (int)$point->id,
				'text' => $point->name
			];
			$coordinates[$point->id] = [$point->latitude, $point->longitude];
		}
		
		return $this->jsonResponse([
			'success' => true,
			'results' => $results,
			'coordinates' => $coordinates
		]);
	}

}
