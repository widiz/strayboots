<?php

namespace Play\Admin\Controllers;

use \Hunts,
	\HuntPoints,
	\HuntTypes,
	\Questions,
	\Countries,
	\Points,
	\Cities,
	\Routes,
	\RoutePoints,
	\Exception,
	DataTables\DataTable,
	Phalcon\Mvc\Model\Transaction\Failed as TxFailed,
	Phalcon\Mvc\Model\Transaction\Manager as TxManager;


class HuntsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$huntTypes = HuntTypes::find()->toArray();
		$huntTypes = array_combine(array_map(function($c){
			return $c['id'];
		}, $huntTypes), array_map(function($c){
			return $c['name'];
		}, $huntTypes));
		$this->view->huntTypes = $huntTypes;
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

		$this->tag->setDefault('hunttype', 2);

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/hunts.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($type = 2)
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		$type = (int)$type;
		if (!HuntTypes::findFirstByid($type))
			$type = 2;

		$builder = $this->modelsManager->createBuilder()
							->columns(
								'h.id, city_id, h.name, approved, last_edit, t.activation as last_play, ' . //a.created as last_play, slug, type_id, time
								//'CONCAT_WS(\' / \', country.name, city.name) as countrycity, ' .
								'(SELECT COUNT(1) FROM OrderHunts o WHERE o.hunt_id=h.id) AS orders, ' .
								'(SELECT COUNT(1) FROM HuntPoints hp WHERE hp.hunt_id=h.id) AS questions, ' .
								'(SELECT COUNT(1) FROM Routes r WHERE r.hunt_id=h.id) AS routes'
							)
							->from(['h' => 'Hunts'])
							//->leftJoin('Answers', 'a.id = (SELECT a.id FROM Answers a WHERE a.hunt_id=h.id ORDER BY a.id DESC LIMIT 1)', 'a')
							->leftJoin('Teams', 't.id = (SELECT t.id FROM Teams t LEFT JOIN OrderHunts oh ON oh.id = t.order_hunt_id LEFT JOIN Orders o ON o.id = oh.order_id WHERE oh.hunt_id=h.id AND o.client_id NOT IN (163,136,126,105,91,67,61,27,15,13,10,4) ORDER BY t.activation DESC LIMIT 1)', 't')
							->where('h.type_id=' . $type);
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
		$this->addEdit();
	}

	/**
	 * Edits a hunt
	 *
	 * @param string $id
	 */
	public function duplicateAction($id)
	{
		if ($this->requireUser())
			return true;

		$hunt = Hunts::findFirstByid($id);
		if (!$hunt) {
			$this->flash->error('Hunt was not found');
			return $this->response->redirect('hunts');
		}

		$newhunt = new Hunts();
		$i = 1;
		while (Hunts::count([
			'slug = ?0',
			'bind' => [
				$newhunt->slug = $hunt->slug . '-' . $i++
			]
		]));
		$newhunt->city_id	= $hunt->city_id;
		$newhunt->type_id	= $hunt->type_id;
		$newhunt->name		= $hunt->name;
		$newhunt->time		= $hunt->time;
		$newhunt->approved	= $hunt->approved;
		$newhunt->breakpoints	= $hunt->breakpoints;
		$newhunt->multilang		= $hunt->multilang;
		$newhunt->flags		= $hunt->flags;

		if ($newhunt->save()) {
			$ok = true;
			$huntPointsMap = [];
			foreach ($hunt->HuntPoints as $hp) {
				$nhp = new HuntPoints();
				$nhp->hunt_id		= $newhunt->id;
				$nhp->point_id		= $hp->point_id;
				$nhp->question_id	= $hp->question_id;
				$nhp->idx			= $hp->idx;
				$nhp->is_start		= $hp->is_start;
				$ok = $nhp->save() && $ok;
				$huntPointsMap[$hp->id] = $nhp->id;
			}
			foreach ($hunt->Routes as $r) {
				$nr = new Routes();
				$nr->hunt_id	= $newhunt->id;
				$nr->active		= $r->active;
				$ok = $nr->save() && $ok;
				foreach ($r->RoutePoints as $rp) {
					$nrp = new RoutePoints();
					$nrp->route_id		= $nr->id;
					$nrp->hunt_point_id	= $huntPointsMap[$rp->hunt_point_id];
					$nrp->idx			= $rp->idx;
					$ok = $nrp->save() && $ok;
				}
			}

			if ($ok)
				$this->flash->success('Hunt duplicated');
			else
				$this->flash->warning('Some issues occurred while duplicating; please check the hunt and routes and contact support');

			return $this->response->redirect('hunts/edit/' . $newhunt->id);
		}

		$this->flash->error('Failed to duplicate hunt; please contact support');
		return $this->response->redirect('hunts');
	}


	/**
	 * Edits a hunt
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$hunt = Hunts::findFirstByid($id);
		if (!$hunt) {
			$this->flash->error('Hunt was not found');
			return $this->response->redirect('hunts');
		}

		$this->view->routes = $hunt->countRoutes('active=1');
		$this->view->id = $hunt->id;
		
		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $hunt->id);
			$this->tag->setDefault('city_id', $hunt->city_id);
			$this->tag->setDefault('type_id', $hunt->type_id);
			$this->tag->setDefault('name', $hunt->name);
			$this->tag->setDefault('slug', $hunt->slug);
			$this->tag->setDefault('time', $hunt->time);
			$this->tag->setDefault('approved', $hunt->approved);
			$this->tag->setDefault('breakpoints', $hunt->breakpoints);
			$this->tag->setDefault('multilang', $hunt->multilang);
			$this->tag->setDefault('strategy_hunt', $hunt->isStrategyHunt());
			$questionTypes = \QuestionTypes::find()->toArray();
			$questionTypes = array_combine(array_map(function($c){
				return $c['id'];
			}, $questionTypes), array_map(function($c){
				return [$c['name'], $c['score']];
			}, $questionTypes));
			$huntPoints = array_values(array_filter(array_map(function($hp) use ($questionTypes){
				$p = is_null($hp['point_id']) ? 1 : Points::findFirstByid($hp['point_id']);
				$q = Questions::findFirstByid($hp['question_id']);
				if ($p && $q) {
					return [
						'p' => $p === 1 ? [ 0, ' - Generic - ' ] : [ (int)$p->id, $p->name ],
						'q' => [
							(int)$q->id,
							mb_strimwidth($questionTypes[$q->type_id][0] . ' (' . ($q->score ?? $questionTypes[$q->type_id][1]) . '): ' . $q->question , 0 , 159, '...')
						],
						's' => $hp['is_start'] == 1
					];
				}
				return false;
			}, $hunt->getPoints())));

			$this->tag->setDefault('pq', json_encode($huntPoints, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
		}

		$this->addEdit();
	}

	/**
	 * Set routes for a hunt
	 *
	 * @param string $id
	 */
	public function routesAction($id)
	{
		if ($this->requireUser())
			return true;

		$hunt = Hunts::findFirstByid($id);
		if (!$hunt) {
			$this->flash->error('Hunt was not found');
			return $this->response->redirect('hunts');
		}

		$coordinates = [];

		$huntPoints = array_values(array_filter(array_map(function($hp) use (&$coordinates){
			$p = is_null($hp['point_id']) ? 1 : Points::findFirstByid($hp['point_id']);
			$q = Questions::findFirstByid($hp['question_id']);
			if ($p && $q) {
				if (is_object($p))
					$coordinates[$p->id] = [$p->latitude, $p->longitude];
				return [
					'i' => (int)$hp['id'],
					'p' => $p === 1 ? [ 0, ' - Generic - ' ] : [ (int)$p->id, $p->name ],
					'q' => [ (int)$q->id, $q->question ],
					's' => $hp['is_start'] == 1
				];
			}
			return false;
		}, $hunt->getPoints())));

		$this->view->huntPoints = json_encode($huntPoints, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$this->view->coordinates = json_encode($coordinates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$this->view->id = $hunt->id;
		$this->view->hunt = $hunt;

		if (!$this->request->isPost()) {

			$routes = $hunt->getRoutes(true);
			foreach ($routes as $i => $route) {
				$routes[$i]['active_oh'] = (int)$this->db->fetchColumn('SELECT oh.id FROM teams t LEFT JOIN order_hunts oh ON (oh.id = t.order_hunt_id) WHERE t.route_id = ' . (int)$route['id'] . ' AND (oh.start > \'' . date('Y-m-d') . '\' OR oh.finish > \'' . date('Y-m-d') . '\') AND oh.flags & 4 = 0');
			}

			$this->tag->setDefault('routes', json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$this->tag->setDefault('id', $hunt->id);
			
		}

		$this->assets->collection('script')
				->addJs('/js/plugins/bootbox.min.js')
				->addJs('/js/admin/hunts.routes.js')
				->addJs('/template/js/plugins/nestable/jquery.nestable.js')
				->addJs('/template/js/plugins/dataTables/datatables.min.js');
	}

	private function addEdit()
	{
		$countries = Countries::find([
			'order' => 'name ASC'
		])->toArray();
		$countries = array_combine(array_map(function(&$c){
			return $c['id'];
		}, $countries), array_map(function(&$c){
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

		$this->assets->collection('script')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/clockpicker/clockpicker.js')
				->addJs('/template/js/plugins/nestable/jquery.nestable.js')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/js/admin/hunts.addedit.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/template/css/plugins/clockpicker/clockpicker.css');
	}

	/**
	 * Creates a new hunt
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost())
			return $this->response->redirect('hunts');

		$hunt = new Hunts();
		$hunt->city_id = $this->request->getPost('city_id', 'int');
		$hunt->type_id = $this->request->getPost('type_id', 'int');
		$hunt->name = $this->request->getPost('name', 'trim');
		$hunt->slug = $this->request->getPost('slug', 'trim');
		$hunt->time = $this->request->getPost('time', 'trim');
		$hunt->approved = $this->request->getPost('approved') ? 1 : 0;
		$hunt->breakpoints = $this->request->getPost('breakpoints', 'trim');
		$hunt->multilang = $this->request->getPost('multilang', 'int');
		$hunt->setStrategyHunt($this->request->getPost('strategy_hunt'));
		if (empty($hunt->breakpoints))
			$hunt->breakpoints = null;

		$huntPoints = json_decode($this->request->getPost('pq'), true);
		if (!is_array($huntPoints))
			$huntPoints = [];
		$i = 0;
		$huntPoints = array_filter(array_map(function(&$hp) use (&$i){
			if (is_array($hp) && is_array($hp['q']) && is_array($hp['p'])) {
				$point = (is_null($hp['p'][0]) || $hp['p'][0] === 0) ? null : Points::findFirstByid($hp['p'][0]);
				$question = Questions::findFirstByid($hp['q'][0]);
				if (is_object($point))
					$point = $point->id;
				if (($point || is_null($point)) && $question && $question->point_id == $point) {
					//$isStart = $hp['s'] === true || $i === 0;
					$hp = new HuntPoints();
					$hp->point_id = $point;
					$hp->question_id = $question->id;
					$hp->idx = $i++;
					$hp->is_start = 1;//$isStart;
					return $hp;
				}
			}
			return false;
		}, $huntPoints));

		if (count($huntPoints) < 2) {

			$this->flash->error('Please add at least 2 hunt points');

			$this->dispatcher->forward([
				'controller' => 'hunts',
				'action' => 'new'
			]);

			return;
		}
		
		if (!$hunt->save()) {
			foreach ($hunt->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'hunts',
				'action' => 'new'
			]);

			return;
		}

		foreach ($huntPoints as &$hp) {
			$hp->hunt_id = $hunt->id;
			if (!$hp->save())
				$this->flash->error('Something went wrong. please check the last hunt');
		}

		$this->flash->success('Hunt was created successfully; please add routes');

		$this->response->redirect('hunts/routes/' . $hunt->id);
	}

	/**
	 * Saves a hunt edited
	 *
	 */
	public function saveAction()
	{

		if ($this->requireUser())
			return true;
		if (!$this->request->isPost())
			return $this->response->redirect('hunts');

		$id = $this->request->getPost('id', 'int');
		$hunt = Hunts::findFirstByid($id);

		if (!$hunt) {
			$this->flash->error('Hunt does not exist ' . $id);
			return $this->response->redirect('hunts');
		}

		$hunt->city_id = $this->request->getPost('city_id', 'int');
		$hunt->type_id = $this->request->getPost('type_id', 'int');
		$hunt->name = $this->request->getPost('name', 'trim');
		$hunt->slug = $this->request->getPost('slug', 'trim');
		$hunt->time = $this->request->getPost('time', 'trim');
		$hunt->approved = $this->request->getPost('approved') ? 1 : 0;
		$hunt->breakpoints = $this->request->getPost('breakpoints', 'trim');
		$hunt->multilang = $this->request->getPost('multilang', 'int');
		$hunt->setStrategyHunt($this->request->getPost('strategy_hunt'));
		if (empty($hunt->breakpoints))
			$hunt->breakpoints = null;

		try {

			$manager = new TxManager();
			$transaction = $manager->get();
			$hunt->setTransaction($transaction);
			
			if (!$hunt->save()) {

				foreach ($hunt->getMessages() as $message)
					$this->flash->error($message);

				$this->dispatcher->forward([
					'controller' => 'hunts',
					'action' => 'edit',
					'params' => [$hunt->id]
				]);

				return;
			}

			$currentHuntPoints = HuntPoints::findByHuntId($hunt->id);
			/*$currentHuntPointIds = [];
			foreach ($currentHuntPoints as &$chp)
				$currentHuntPointIds[] = $chp->id;*/

			$huntPoints = json_decode($this->request->getPost('pq'), true);
			if (!is_array($huntPoints))
				$huntPoints = [];

			$i = 0;
			$huntPoints = array_filter(array_map(function(&$hp) use (&$i, &$hunt, &$currentHuntPoints){
				if (is_array($hp) && is_array($hp['q']) && is_array($hp['p'])) {
					$point = (is_null($hp['p'][0]) || $hp['p'][0] === 0) ? null : Points::findFirstByid($hp['p'][0]);
					$question = Questions::findFirstByid($hp['q'][0]);
					if (is_object($point))
						$point = $point->id;
					if (($point || is_null($point)) && $question && $question->point_id == $point) {
						$found = false;
						foreach ($currentHuntPoints as $c => $chp) {
							if ($chp->point_id == $point && ($chp->point_id > 0 || $chp->question_id == $question->id)) {
								$found = $c;
								break;
							}
						}
						//$isStart = $hp['s'] === true || $i === 0;
						if ($found === false) {
							$hp = new HuntPoints();
							$hp->hunt_id = $hunt->id;
							$hp->point_id = $point;
						} else {
							$hp = $currentHuntPoints[$found];
						}
						$hp->question_id = $question->id;
						$hp->idx = $i++;
						$hp->is_start = 1;//$isStart;
						return $hp;
					}
				}
				return false;
			}, $huntPoints));

			if (count($huntPoints) < 2) {
				$this->flash->error('Please add at least 2 hunt points');
				$transaction->rollback();
			}

			$hpUsed = [];
			foreach ($huntPoints as &$hp) {
				$hp->setTransaction($transaction);
				if ($hp->save()) {
					$hpUsed[] = $hp->id;
				} else {
					$this->flash->error('Something went wrong');
					$transaction->rollback();
				}
			}

			foreach ($currentHuntPoints as $hp) {
				if (!in_array($hp->id, $hpUsed)) {
					$hp->setTransaction($transaction);
					if (!$hp->delete()) {
						$this->flash->error('Something went wrong');
						$transaction->rollback();
					}
				}
			}

			$transaction->commit();

			/*try {
				$routes = Routes::findByHuntId($this->id);
				foreach ($routes as &$route) {
					$routePoints = $route->RoutePoints;
					foreach ($huntPoints as &$hp) {

					}
				}
			} catch(Exception $e) {}*/

			$this->flash->success('Hunt was updated successfully; please update routes');

			$this->response->redirect('hunts/routes/' . $hunt->id);

		} catch (TxFailed $e) {
			$this->dispatcher->forward([
				'controller' => 'hunts',
				'action' => 'edit',
				'params' => [$hunt->id]
			]);
		}
	}

	/**
	 * Saves a hunt edited
	 *
	 */
	public function saveRoutesAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost())
			return $this->response->redirect('hunts');

		$id = $this->request->getPost('id', 'int');
		$hunt = Hunts::findFirstByid($id);

		if (!$hunt) {
			$this->flash->error('Hunt does not exist ' . $id);
			return $this->response->redirect('hunts');
		}

		$currentRoutes = Routes::findByHuntId($hunt->id);

		$routes = json_decode($this->request->getPost('routes'), true);
		if (!is_array($routes)) $routes = [];

		try {

			$manager = new TxManager();
			$transaction = $manager->get();

			if (count($routes) < 1) {
				$this->flash->error('Please add at least one route');
				$transaction->rollback();
			}

			foreach ($routes as $r => &$route) {

				$_route = false;
				$currentRoutePoints = [];
				if ($route['id'] > 0) {
					foreach ($currentRoutes as $k => $_r) {
						if ($_r->id == $route['id']) {
							$_route = $k;
							break;
						}
					}
					if ($_route === false) {
						$this->flash->error('Route #' . ($r + 1) . ': Something went wrong #1');
						$transaction->rollback();
					} else {
						$_route = $currentRoutes[$_route];
						$currentRoutePoints = RoutePoints::find([
							'route_id=' . $_route->id,
							'order' => 'idx ASC, id ASC'
						]);
					}
				} else {
					$_route = new Routes();
					$_route->hunt_id = $hunt->id;
				}
				$_route->active = $route['active'] ? 1 : 0;
				$_route->setTransaction($transaction);

				if (count($route['points']) < 2) {
					$this->flash->error('Route #' . ($r + 1) . ': Please add at least 2 hunt points');
					$transaction->rollback();
				}

				if (!$_route->save()) {
					$this->flash->error('Route #' . ($r + 1) . ': Something went wrong #2');
					$transaction->rollback();
				}

				$i = 0;
				$error = false;
				$RoutePoints = array_filter(array_map(function(&$hp) use (&$i, &$_route, &$currentRoutePoints, &$r, &$error){
					if (is_array($hp) && isset($hp['id']) && $hp['id'] > 0) {
						$_hp = HuntPoints::findFirstByid($hp['id']);
						if ($_hp && $_hp->hunt_id == $_route->hunt_id) {
							if ($i === 0 && !$_hp->is_start) {
								$this->flash->error('Route #' . ($r + 1) . ': First point must be a start point');
								$error = true;
								return false;
							}
							$found = false;
							foreach ($currentRoutePoints as $c => $crp) {
								if ($crp->hunt_point_id == $hp['id']) {
									$found = $c;
									break;
								}
							}
							if ($found === false) {
								$hp = new RoutePoints();
								$hp->route_id = $_route->id;
								$hp->hunt_point_id = $_hp->id;
							} else {
								$hp = $currentRoutePoints[$found];
							}
							$hp->idx = $i++;
							return $hp;
						}
					}
					return false;
				}, $route['points']));
				if ($error)
					$transaction->rollback();

				$hpUsed = [];
				foreach ($RoutePoints as &$hp) {
					$hp->setTransaction($transaction);
					if ($hp->save()) {
						$hpUsed[] = $hp->id;
					} else {
						$this->flash->error('Route #' . ($r + 1) . ': Something went wrong #3');
						$transaction->rollback();
					}
				}

				foreach ($currentRoutePoints as $hp) {
					if (!in_array($hp->id, $hpUsed)) {
						$hp->setTransaction($transaction);
						if (!$hp->delete()) {
							$this->flash->error('Route #' . ($r + 1) . ': Something went wrong #4');
							$transaction->rollback();
						}
					}
				}
			}

			foreach ($currentRoutes as $_route) {
				foreach ($routes as $_r) {
					if ($_r['id'] == $_route->id)
						continue 2;
				}
				//$_route->setTransaction($transaction);
				if (!$_route->delete()) {
					$_route->active = 0;
					if ($_route->save())
						$this->flash->error('Route #' . ($r + 1) . ': Can\'t delete route; Deactivating instead');
					else
						$this->flash->error('Route #' . ($r + 1) . ': Can\'t delete route or deactivate');
					//$transaction->rollback();
				}
			}

			$transaction->commit();

			$this->flash->success('Routes updated successfully');

			//$this->response->redirect('hunts/edit/' . $hunt->id);
			$this->response->redirect('hunts/routes/' . $hunt->id);

		} catch (TxFailed $e) {
			$this->dispatcher->forward([
				'controller' => 'hunts',
				'action' => 'routes',
				'params' => [$hunt->id]
			]);
		}
	}

	/**
	 * Deletes a hunt
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		$hunt = Hunts::findFirstByid($id);
		if (!$hunt) {
			$this->flash->error('Hunt was not found');
			return $this->response->redirect('hunts');
		}

		if ($hunt->delete()) {
			$this->flash->success('Hunt was deleted successfully');
		} else {
			foreach ($hunt->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('hunts');
	}

}
