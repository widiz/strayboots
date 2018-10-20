<?php

namespace Play\Admin\Controllers;

use \Orders,
	\OrderHunts,
	\Answers,
	\Players,
	\Routes,
	\Hunts,
	\Teams,
	\Exception,
	\OrderHuntPDF,
	\BonusQuestions,
	Phalcon\Db,
	DataTables\DataTable,
	Phalcon\Mvc\Model\Message;

class OrderHuntsController extends \ControllerBase
{

	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$order = Orders::findFirstByid((int)$id);
		if (!$order)
			return $this->response->redirect('orders');

		$countries = \Countries::find()->toArray();
		$countries = array_combine(array_map(function($c){
			return $c['id'];
		}, $countries), array_map(function($c){
			return $c['name'];
		}, $countries));
		$this->view->countries = $countries;
		$cities = \Cities::find()->toArray();
		$cities = array_combine(array_map(function($c){
			return $c['id'];
		}, $cities), array_map(function($c){
			return [$c['name'], (int)$c['country_id']];
		}, $cities));
		$this->view->cities = $cities;

		$this->view->order = $order;
		$this->view->client = $order->Client;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/plugins/bootbox.min.js')
				->addJs('/js/admin/orderhunts.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($id = 0)
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		$id = (int)$id;

		$order = Orders::findFirstByid($id);
		if (!$order)
			throw new Exception(404, 404);

		$builder = $this->modelsManager->createBuilder()
							->columns('oh.id, oh.hunt_id, oh.max_players, oh.max_teams, oh.start, oh.finish, oh.expire, h.name, h.city_id')
							->from(['oh' => 'OrderHunts'])
							->leftJoin('Hunts', 'h.id = oh.hunt_id', 'h')
							->where('order_id = ' . $order->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$id = (int)$id;

		$order = Orders::findFirstByid($id);
		if (!$order)
			return $this->response->redirect('orders');

		$this->tag->setDefault('order_id', $order->id);

		$this->view->order = $order;
		$this->view->client = $order->Client;
		$this->addEdit();
	}

	/**
	 * Edits a order hunt
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid($id);
		if (!$orderHunt) {
			$this->flash->error('Order hunt was not found');

			$this->response->redirect('orders');

			return;
		}

		$this->view->id = $orderHunt->id;
		$order = $orderHunt->Order;
		$this->view->order = $order;
		$this->view->hunt = $orderHunt->Hunt;
		$this->view->client = $order->Client;

		if (!$this->request->isPost()) {
			$this->tag->setDefault('id', $orderHunt->id);
			$this->tag->setDefault('order_id', $orderHunt->order_id);
			$this->tag->setDefault('hunt_id', $orderHunt->hunt_id);
			$this->tag->setDefault('max_players', $orderHunt->max_players);
			$this->tag->setDefault('max_teams', $orderHunt->max_teams);
			$this->tag->setDefault('start', date($this->view->timeFormat, strtotime($orderHunt->start)));
			$this->tag->setDefault('finish', date($this->view->timeFormat, strtotime($orderHunt->finish)));
			$this->tag->setDefault('expire', date($this->view->timeFormat, strtotime($orderHunt->expire)));
			$this->tag->setDefault('pdf_start', $orderHunt->pdf_start);
			$this->tag->setDefault('pdf_finish', $orderHunt->pdf_finish);
			$this->tag->setDefault('redirect', $orderHunt->redirect);
			$this->tag->setDefault('video', $orderHunt->video);
			$this->tag->setDefault('custom_login', $orderHunt->isCustomLogin());
			$this->tag->setDefault('duration_finish', $orderHunt->isDurationFinish());
			$this->tag->setDefault('canceled', $orderHunt->isCanceled());
			$this->tag->setDefault('multi_hunt', $orderHunt->isMultiHunt());
			$this->tag->setDefault('survey_disabled', $orderHunt->isSurveyDisabled());
			$this->tag->setDefault('leaderboard_disabled', $orderHunt->isLeaderBoardDisabled());
		}
		$this->addEdit();
	}

	private function addEdit()
	{
		$this->assets->collection('script')
				->addJs('/js/admin/orderhunts.addedit.js')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/moment/moment.min.js')
				->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css');
	}

	/**
	 * Creates a new order hunt
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$orderHunt = new OrderHunts();
		$orderHunt->order_id = $this->request->getPost('order_id', 'int');
		$orderHunt->hunt_id = $this->request->getPost('hunt_id', 'int');
		$orderHunt->max_players = $this->request->getPost('max_players', 'int');
		$orderHunt->max_teams = $this->request->getPost('max_teams', 'int');

		if ($orderHunt->start = strtotime($this->request->getPost('start', 'trim')))
			$orderHunt->start = date('Y-m-d H:i:s', $orderHunt->start);
		else
			$orderHunt->start = '';

		if ($orderHunt->finish = strtotime($this->request->getPost('finish', 'trim')))
			$orderHunt->finish = date('Y-m-d H:i:s', $orderHunt->finish);
		else
			$orderHunt->finish = '';

		if ($orderHunt->expire = strtotime($this->request->getPost('expire', 'trim')))
			$orderHunt->expire = date('Y-m-d H:i:s', $orderHunt->expire);
		else
			$orderHunt->expire = '';

		$orderHunt->pdf_start = $this->request->getPost('pdf_start', 'trim');
		$orderHunt->pdf_finish = $this->request->getPost('pdf_finish', 'trim');
		$orderHunt->redirect = $this->request->getPost('redirect', 'trim');
		if (empty($orderHunt->expire))
			$orderHunt->expire = null;
		if (empty($orderHunt->pdf_start))
			$orderHunt->pdf_start = null;
		if (empty($orderHunt->pdf_finish))
			$orderHunt->pdf_finish = null;
		if (empty($orderHunt->redirect))
			$orderHunt->redirect = null;
		
		$orderHunt->setCustomLogin($this->request->getPost('custom_login'));
		$orderHunt->setDurationFinish($this->request->getPost('duration_finish'));
		$orderHunt->setCanceled($this->request->getPost('canceled'));
		$orderHunt->setMultiHunt($this->request->getPost('multi_hunt'));
		$orderHunt->setSurveyDisabled($this->request->getPost('survey_disabled'));
		$orderHunt->setLeaderBoardDisabled($this->request->getPost('leaderboard_disabled'));

		$hunt = $orderHunt->Hunt;

		$isOk = true;

		if (!$orderHunt->isCustomLogin() && $hunt && !$hunt->isStrategyHunt() && $orderHunt->max_teams > ($ar = $hunt->countRoutes('active=1'))) {
			$orderHunt->appendMessage(new Message(
				"Number of teams is bigger than number of available routes ({$ar})",
				'max_teams',
				'error'
			));
			$isOk = false;
		}
		
		if (!($isOk && $orderHunt->save())) {
			foreach ($orderHunt->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'order_hunts',
				'action' => 'new',
				'params' => [$orderHunt->order_id]
			]);

			return;
		}
		
		try {
			$orderHunt->addTeams($orderHunt->max_teams);

			if (SBENV !== 'europe' && SBENV !== 'india') {
				try {
					///// Adding BonusQuestions  //////
					$bonus = new BonusQuestions();
					$bonus->order_hunt_id = $orderHunt->id;
					$bonus->type = BonusQuestions::TypeTeam;
					$bonus->question = 'What country was the fortune cookie invented?';
					$bonus->answers = "USA";
					$bonus->score = 25;
					$bonus->save();

					$bonus = new BonusQuestions();
					$bonus->order_hunt_id = $orderHunt->id;
					$bonus->type = BonusQuestions::TypeTeam;
					$bonus->question = 'What name did Theodore Geisel pen his books under?';
					$bonus->answers = "Dr. Seuss";
					$bonus->score = 25;
					$bonus->save();
					
					$bonus = new BonusQuestions();
					$bonus->order_hunt_id = $orderHunt->id;;
					$bonus->type = BonusQuestions::TypeTeam;
					$bonus->question = 'The statue of liberty was given to the US by which country?';
					$bonus->answers = "France";
					$bonus->score = 25;
					$bonus->save();
				} catch (Exception $e) {
					$this->flash->error('Error creating bonus questions: ' . $e->getMessage());
				}
			}
			

			if (substr($orderHunt->start, 0, 10) == date('Y-m-d') && $orderHunt->finish > date("Y-m-d H:i:s")) {
				$preevent = new \PreeventTask();
				$preevent->mainAction([0, 0]);
			}

			$this->flash->success('Order hunt was created successfully');
		} catch (Exception $e) {
			$this->flash->error('Error creating teams: ' . $e->getMessage());
		}
		$this->response->redirect('order_hunts/' . $orderHunt->order_id);
	}

	/**
	 * Saves a order hunt edited
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$orderHunt = OrderHunts::findFirstByid($id);

		if (!$orderHunt) {
			$this->flash->error('Order hunt does not exist ' . $id);

			$this->response->redirect('orders');

			return;
		}

		$oldIsCustomLogin = $orderHunt->isCustomLogin();
		$oldHuntId = $orderHunt->hunt_id;

		$orderHunt->order_id = $this->request->getPost('order_id', 'int');
		$orderHunt->hunt_id = $this->request->getPost('hunt_id', 'int');
		$orderHunt->max_players = $this->request->getPost('max_players', 'int');
		$orderHunt->max_teams = $this->request->getPost('max_teams', 'int');

		if ($orderHunt->start = strtotime($this->request->getPost('start', 'trim')))
			$orderHunt->start = date('Y-m-d H:i:s', $orderHunt->start);
		else
			$orderHunt->start = '';

		if ($orderHunt->finish = strtotime($this->request->getPost('finish', 'trim')))
			$orderHunt->finish = date('Y-m-d H:i:s', $orderHunt->finish);
		else
			$orderHunt->finish = '';

		if ($orderHunt->expire = strtotime($this->request->getPost('expire', 'trim')))
			$orderHunt->expire = date('Y-m-d H:i:s', $orderHunt->expire);
		else
			$orderHunt->expire = '';

		$orderHunt->pdf_start = $this->request->getPost('pdf_start', 'trim');
		$orderHunt->pdf_finish = $this->request->getPost('pdf_finish', 'trim');
		$orderHunt->redirect = $this->request->getPost('redirect', 'trim');
		$orderHunt->video = strip_tags($this->request->getPost('video', 'trim'));
		if (empty($orderHunt->expire))
			$orderHunt->expire = null;
		if (empty($orderHunt->pdf_start))
			$orderHunt->pdf_start = null;
		if (empty($orderHunt->pdf_finish))
			$orderHunt->pdf_finish = null;
		if (empty($orderHunt->redirect))
			$orderHunt->redirect = null;
		if (empty($orderHunt->video))
			$orderHunt->video = null;

		$orderHunt->setCustomLogin($this->request->getPost('custom_login'));
		$orderHunt->setDurationFinish($this->request->getPost('duration_finish'));
		$orderHunt->setCanceled($this->request->getPost('canceled'));
		$orderHunt->setMultiHunt($this->request->getPost('multi_hunt'));
		$orderHunt->setSurveyDisabled($this->request->getPost('survey_disabled'));
		$orderHunt->setLeaderBoardDisabled($this->request->getPost('leaderboard_disabled'));

		$hunt = $orderHunt->Hunt;
		$isOk = true;

		if (!$orderHunt->isCustomLogin() && $hunt && !$hunt->isStrategyHunt() && $orderHunt->max_teams > ($ar = $hunt->countRoutes('active=1'))) {
			$orderHunt->appendMessage(new Message(
				"Number of teams is bigger than number of available routes ({$ar})",
				'max_teams',
				'error'
			));
			$isOk = false;
		}
		
		if (!($isOk && $orderHunt->save())) {

			foreach ($orderHunt->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'order_hunts',
				'action' => 'edit',
				'params' => [$orderHunt->id]
			]);

			return;
		}

		try {
			if ($orderHunt->hunt_id != $oldHuntId || $oldIsCustomLogin != $orderHunt->isCustomLogin() || $oldIsCustomLogin)
				$orderHunt->resetTeams();
			if (($teamsToAdd = $orderHunt->max_teams - $orderHunt->Teams->count()) > 0)
				$orderHunt->addTeams($teamsToAdd);

			if (substr($orderHunt->start, 0, 10) == date('Y-m-d') && $orderHunt->finish > date('Y-m-d H:i:s')) {
				$preevent = new \PreeventTask();
				$preevent->mainAction([0, 0]);
			}

			$this->flash->success('Order hunt was updated successfully');
		} catch (Exception $e) {
			$this->flash->error('Error creating teams: ' . $e->getMessage());
		}

		$this->response->redirect('order_hunts/' . $orderHunt->order_id);
	}

	/**
	 * Deletes a order hunt
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt) {
			$this->flash->error('Order hunt was not found');

			$this->response->redirect('orders');

			return;
		}

		$order_id = $orderHunt->order_id;

		try {
			if ($orderHunt->delete()) {
				$this->flash->success('Order hunt was deleted successfully');
			} else {
				foreach ($orderHunt->getMessages() as $message) 
					$this->flash->error($message);
			}
		} catch (Exception $e) {
			$this->flash->error('This order hunt already has players');
		}

		$this->response->redirect('order_hunts/' . $order_id);
	}

	public function breakForceAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt) {
			$this->flash->error('Order hunt was not found');
			$this->response->redirect('orders');

			return;
		}

		$breakpoint = $orderHunt->Hunt->checkBreakpoints($orderHunt);

		if ($breakpoint !== false && $breakpoint[0] == $this->request->getPost('bp')) {
			$teamsJson = [0];
			$teamsJsonStr = json_encode($teamsJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			if ($this->redis->get(SB_PREFIX . 'breakfb:' . $orderHunt->id) != $teamsJsonStr) {
				if (($fbr = str_replace('"', '', $this->firebase->set(FB_PREFIX . 'breakfb/' . $orderHunt->id, $teamsJson, [], 5))) != $teamsJsonStr) {
					try {
						$this->logger->critical("Firebase force break error: (ohid {$orderHunt->id}) " . $fbr . ' should be ' . $teamsJsonStr);
					} catch(Exception $e) { }
				} else {
					$this->redis->set(SB_PREFIX . 'breakp:' . $orderHunt->id . ':' . $breakpoint[0], time() + 30, 259200);
					$this->redis->set(SB_PREFIX . 'breakfb:' . $orderHunt->id, $teamsJsonStr, 30 /*1800*/);
					return $this->jsonResponse([
						'success' => true
					]);
				}
			}
		}

		return $this->jsonResponse([
			'success' => false
		]);
	}

	public function summaryAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt) {
			$this->flash->error('Order hunt was not found');
			$this->response->redirect('orders');

			return;
		}

		$this->view->hideHeader = $this->request->isAjax();

		$teamsStatus = $orderHunt->getTeamsStatus(true, false);
		$logs = $teamHints = $teamSkips = $teamNames = $teamAns = $teamPos = $tids = [];
		$colors = \Colors\RandomColor::many(count($teamsStatus), ['luminosity' => 'light']);
		$ohids = [$orderHunt->id => 0];
		foreach ($teamsStatus as $t => $team) {
			$teamNames[$team['id']] = [$team['name'], $colors[$t]];
			$tids[] = $team['id'];
			$ohids[$team['order_hunt_id']] = 0;
		}
		$multihunt = $orderHunt->isMultiHunt() || count($ohids) > 1;
		$ohids = implode(',', array_keys($ohids));
		$tids = implode(',', $tids);
		if (!empty($tids)) {
			$group = $this->db->fetchAll('SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id) t GROUP BY team_id', Db::FETCH_ASSOC);
			foreach ($group as $g)
				$teamPos[$g['team_id']] = (int)$g['rowcount'];
			$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MIN(created) AS mini, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = [$a->mini, $a->mini == $a->maxi ? '' : $a->maxi];
			$answers = $this->db->fetchAll(
				'SELECT a.id, a.team_id, a.created, a.question_id, a.action, a.answer, ' .
				'q.question, IF(q.score IS NULL,qt.score,q.score) as `score` ' .
				'FROM answers a LEFT JOIN questions q ON (a.question_id = q.id) ' .
				'LEFT JOIN question_types qt ON (q.type_id = qt.id) ' .
				'WHERE team_id IN (' . $tids . ') ORDER BY a.id ASC'
			);
			foreach ($answers as $a) {
				if ($a['action'] == Answers::AnsweredWithHint) {
					if (isset($teamHints[$a['team_id']]))
						$teamHints[$a['team_id']]++;
					else
						$teamHints[$a['team_id']] = 1;
					$scored = floor($a['score'] / 2);
				} else if ($a['action'] == Answers::Skipped) {
					if (isset($teamSkips[$a['team_id']]))
						$teamSkips[$a['team_id']]++;
					else
						$teamSkips[$a['team_id']] = 1;
					$scored = 0;
				} else {
					$scored = $a['score'];
				}
				$logs[] = [
					'id'		=> $a['id'],
					'team_id'	=> $a['team_id'],
					'created'	=> $a['created'],
					'question'	=> [$a['question_id'], $a['question'], $a['score']],
					'scored'	=> $scored,
					'action'	=> $a['action'],
					'answer'	=> $a['answer']
				];
			}
			$customQuestions = $this->db->fetchAll(
				'SELECT cq.*, ca.team_id, ca.answer, ca.action, ca.created FROM custom_answers ca FORCE INDEX (orderaction) ' .
				'LEFT JOIN custom_questions cq ON (ca.custom_question_id = cq.id) WHERE cq.order_hunt_id' .
				($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)),
			Db::FETCH_ASSOC);
			foreach ($customQuestions as $i => $cq) {
				if ($cq['action'] == Answers::AnsweredWithHint) {
					if (isset($teamHints[$cq['team_id']]))
						$teamHints[$cq['team_id']]++;
					else
						$teamHints[$cq['team_id']] = 1;
				} else if ($cq['action'] == Answers::Skipped) {
					if (isset($teamSkips[$cq['team_id']]))
						$teamSkips[$cq['team_id']]++;
					else
						$teamSkips[$cq['team_id']] = 1;
					unset($customQuestions[$i]);
				}
			}
			$this->view->customQuestions = $customQuestions;
		} else {
			$this->view->customQuestions = [];
		}

		$files = $ohHunts = [];
		$orderHunts = OrderHunts::find('id in (' . $ohids . ')');
		foreach ($orderHunts as $oh) {
			$ohHunts[$oh->id] = $oh->hunt_id;
			$files[$oh->id] = $oh->getFiles();
		}
		$this->view->files = $files;
		$this->view->breakpoint = $orderHunt->Hunt->checkBreakpoints($orderHunt, true);
		$teamMap = [];

		foreach ($teamsStatus as $t => $team) {
			$teamMap[$team['id']] = $t;
			$teamsStatus[$t]['question'] = isset($teamPos[$team['id']]) ? $teamPos[$team['id']] : 0;
			$teamsStatus[$t]['times'] = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : ['', ''];
			$teamsStatus[$t]['hints'] = isset($teamHints[$team['id']]) ? $teamHints[$team['id']] : 0;
			$teamsStatus[$t]['skips'] = isset($teamSkips[$team['id']]) ? $teamSkips[$team['id']] : 0;
			$teamsStatus[$t]['route'] = Routes::count('hunt_id = ' . $ohHunts[$team['order_hunt_id']] . ' AND id <= ' . $team['route_id']);
			if (!$this->view->hideHeader)
				$teamsStatus[$t]['players'] = Players::count('team_id = ' . $team['id']);
		}
		if ($orderHunt->isMultiHunt()) {
			$max = (int)$this->db->fetchColumn(
				'SELECT SUM(c) FROM (SELECT COUNT(1) as c FROM custom_questions  WHERE order_hunt_id IN (SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8) UNION ALL SELECT COUNT(1) as c FROM hunt_points hp LEFT JOIN order_hunts oh ON (oh.hunt_id = hp.hunt_id) WHERE oh.order_id = ' . (int)$orderHunt->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8) t'
			);
		} else {
			$max = \HuntPoints::count('hunt_id=' . $orderHunt->hunt_id) + $orderHunt->countCustomQuestions();
		}
		$maxAnswers = empty($tids) ? 0 : (int)$this->db->fetchColumn('SELECT MAX(ss.`s`) FROM (SELECT team_id, SUM(t.c) as `s` FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') GROUP BY team_id) t GROUP BY team_id) ss');
		$max = max($max, $maxAnswers);
		
		$this->view->max = $max;
		$this->view->teamMap = $teamMap;
		$this->view->leaderboard = $teamsStatus;
		$this->view->logs = $logs;
		$this->view->orderHunt = $orderHunt;
		$this->view->teamNames = $teamNames;

		$map = [];
		foreach ($teamsStatus as $team) {
			$builder = new \Phalcon\Mvc\Model\Query\Builder([
				'models'		=> 'RoutePoints',
				'columns'		=> 'RoutePoints.idx, p.longitude, p.latitude, p.name',
				'conditions'	=> 'RoutePoints.route_id = ' . $team['route_id'],
				'order'			=> 'RoutePoints.idx ASC',
				'limit'			=> [1, $team['question'] == $max ? max(0, $team['question'] - 1) : $team['question']]
			]);
			$builder->leftJoin('HuntPoints', 'hp.id = RoutePoints.hunt_point_id', 'hp');
			$builder->leftJoin('Points', 'p.id = hp.point_id', 'p');
			$builder = $builder->getQuery()->execute()->toArray();
			if ($builder) $builder = $builder[0];
			if ($builder && $builder['longitude'] <> 0 && $builder['latitude'] <> 0) {
				$k = $builder['longitude'] . 'x' . $builder['latitude'];
				$builder['teams'] = [$team['name']];
				if (isset($map[$k]))
					$map[$k]['teams'] = array_merge($map[$k]['teams'], $builder['teams']);
				else
					$map[$k] = $builder;
			}
		}

		$order = $orderHunt->Order;
		$this->view->huntName = $orderHunt->Hunt->name;
		$this->view->order = $order;
		$this->view->client = $order->Client;
		$this->view->map = array_values($map);

		if (!$eurl = $this->redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$elink = $this->config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($this->crypt->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($elink))
				$this->redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $elink;
		}
		$this->view->eurl = $eurl;

		if ($this->view->hideHeader) {
			$this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
			$this->view->customEvents = $this->view->customQuestions = $this->view->bonusQuestions = [];
		} else {

			$this->view->routes = Routes::find('hunt_id=' . (int)$orderHunt->hunt_id)->toArray();

			$this->view->googleMaps = $this->config->googleapis->maps;

			if (empty($tids)) {
				$this->view->surveyResults = $this->view->customEvents = $this->view->customQuestions = $this->view->bonusQuestions = $this->view->wrongAnswers = $this->view->players = [];
			} else {
				$this->view->players = Players::find([
					'team_id IN (' . $tids . ')',
					//'order' => 'team_id ASC'
				]);

				$this->view->bonusQuestions = $this->db->fetchAll('SELECT bq.*, p.team_id, p.email, p.first_name, p.last_name FROM bonus_questions bq LEFT JOIN players p ON (p.id = bq.winner_id) WHERE bq.order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND bq.winner_id IS NOT NULL', Db::FETCH_ASSOC);
				$this->view->customEvents = $this->db->fetchAll('SELECT * FROM custom_events WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND team_id IS NOT NULL', Db::FETCH_ASSOC);
				$this->view->wrongAnswers = $this->db->fetchAll('SELECT wa.question_id, q.question, wa.answer, wa.player_id, wa.hint, wa.created, p.email, p.team_id FROM wrong_answers wa LEFT JOIN questions q ON (q.id = wa.question_id) LEFT JOIN players p ON (p.id = wa.player_id) WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)), Db::FETCH_ASSOC);

				if (empty($data = $this->redis->get(SB_PREFIX . 'survey:' . $orderHunt->id . ':api'))) {
					$data = file_get_contents('https://app.widiz.com/plugins/survey/api/surveys/api/10?v=c7431d7a622bda87db21093c9745cdcc18da0b69&filterKey=Hunt%20ID&filterVal=' . $orderHunt->id);
					$this->redis->set(SB_PREFIX . 'survey:' . $orderHunt->id . ':api', $data, 600);
				}
				if (!is_array($data = json_decode($data, true)) || $data['success'] !== true)
					$data = [];
				else
					$data = $data['data'];

				$this->view->surveyResults = $data;
			}

			$this->assets->collection('script')
					->addJs('/template/js/plugins/blueimp/jquery.blueimp-gallery.min.js')
					->addJs('/template/js/plugins/dataTables/datatables.min.js')
					->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
					->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/clients/orderhunts.map.js')
					->addJs('/js/plugins/bootbox.min.js')
					->addJs('/js/admin/orderhunts.summary.js')
					->addJs('/js/plugins/jquery.nicescroll.min.js')
					->addJs('/js/clients/end.js');

			$this->assets->collection('style')
					->addCss('/template/css/plugins/dataTables/datatables.min.css')
					->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css')
					->addCss('/template/css/plugins/blueimp/css/blueimp-gallery.min.css');
		}
	}

	public function updateRouteAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$team = Teams::findFirstByid((int)$id);
		if (!$team) {
			return $this->jsonResponse([
				'success' => false
			]);
		}

		$team->route_id = (int)$this->request->getPost('route', 'int');

		return $this->jsonResponse([
			'success' => $team->save(),
			'messages' => array_map(function($m){
				return (string)$m;
			}, $team->getMessages())
		]);
	}

	public function updateAnswerAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$answer = Answers::findFirstByid((int)$id);
		if (!$answer) {
			return $this->jsonResponse([
				'success' => false
			]);
		}

		$answer->action = (int)$this->request->getPost('action', 'int');

		return $this->jsonResponse([
			'success' => $answer->save(),
			'messages' => array_map(function($m){
				return (string)$m;
			}, $answer->getMessages())
		]);
	}

	public function downloadPDFAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat);
		$pdf->downloadPDF();
	}

	public function viewPDFAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat);
		$pdf->displayPDF();
	}

	public function mailAction($id = 0)
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		try {
			$orderHunt = OrderHunts::findFirstByid((int)$id);
			if (!$orderHunt)
				throw new Exception(404, 404);

			$order = $orderHunt->Order;
			$client = $order->Client;

			set_time_limit(0);
			ignore_user_abort(true);
			ini_set('memory_limit', '256M');

			$attachments = [];
			$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat);
			if (file_exists($pdf = $pdf->savePDF()))
				$attachments[] = '@' . $pdf;
			
			$to = $this->request->getPost('email', 'email');
			if (empty($to))
				$to = $client->email;

			extract(OrdersController::mailPDF($client));

			return $this->jsonResponse([
				'success' => $this->sendMail($to, 'Your Strayboots Hunt Instructions', $text, $html, $attachments)
			]);
		} catch(Exception $e) {
			return $this->jsonResponse([
				'success' => false
			]);
		}
	}

	public function mailTeamsAction($id = 0)
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		try {
			$orderHunt = OrderHunts::findFirstByid((int)$id);
			if (!$orderHunt)
				throw new Exception(404, 404);

			$order = $orderHunt->Order;
			$client = $order->Client;

			set_time_limit(0);
			ignore_user_abort(true);
			ini_set('memory_limit', '256M');

			$date = date($this->view->dateFormat, strtotime($orderHunt->start));

			$txt = <<<EOF
Hi %name%,
Attached you can find your instruction sheet for your upcoming scavenger hunt on {$date}.
Should you have any questions, please feel free to reply to this email, or call us at 877-787-2929 ext 1111.

Good luck!
The Strayboots Team
EOF;

			$sent = 0;
			foreach ($orderHunt->Teams as $team) {
				$to = $team->Leader;
				if (!$to) continue;
				$text = str_replace('%name%', trim($to->first_name . ' ' . $to->last_name), $txt);
				$html = nl2br($text);
				$to = $to->email;
				$attachments = [];
				$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat, true, true, $team->id);
				if (file_exists($pdf = $pdf->savePDF()))
					$attachments[] = '@' . $pdf;
				if ($this->sendMail($to, 'You have been chosen to be a team captain for your upcoming Strayboots Scavenger hunt', $text, $html, $attachments))
					$sent++;
			}

			return $this->jsonResponse([
				'success' => true,
				'sent' => $sent
			]);
		} catch(Exception $e) {
			return $this->jsonResponse([
				'success' => false
			]);
		}
	}

	/**
	 * send post event mail
	 */
	public function sendPostEventAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$orderHunt = OrderHunts::findFirstByid($id);

		if (!$orderHunt) {
			$this->flash->error('Order hunt does not exist ' . $id);

			$this->response->redirect('orders');

			return;
		}

		$email = filter_var($this->request->getPost('email', 'email'), FILTER_VALIDATE_EMAIL);

		$pe = new \NewOrderHuntPostEvent($orderHunt, $email ? false : true);

		return $this->jsonResponse([
			'success' => $pe->send([$this, 'sendMail'], $email ? $email : null) === true
		]);
	}

	/**
	 * send post event mail
	 */
	public function getTeamsAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);

		if (!$orderHunt) {
			$this->flash->error('Order hunt does not exist ' . $id);

			$this->response->redirect('orders');

			return;
		}

		$teamStatus = $orderHunt->getTeamsStatus(false, false);

		usort($teamStatus, function($a, $b){
			return $a['num'] > $b['num'] ? 1 : -1;
		});

		foreach ($teamStatus as $t => $ts)
			$teamStatus[$t]['route'] = Routes::count('hunt_id = ' . $orderHunt->hunt_id . ' AND id <= ' . $ts['route_id']);

		return $this->jsonResponse([
			'success' => true,
			'teams' => $teamStatus
		]);
	}

}
