<?php

namespace Play\Clients\Controllers;

use Play\Frontend\Controllers\NcrController,
	\Orders,
	\OrderHunts,
	\HuntPoints,
	\Hunts,
	\Answers,
	\Teams,
	\ZipArchive,
	\Exception,
	\OrderHuntPDF,
	DataTables\DataTable,
	\Phalcon\Db;

class OrderHuntsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{
		if ($this->requireClient())
			return true;

		$id = (int)$id;

		$order = Orders::findFirstByid($id);
		if (!$order || $order->client_id != $this->client->id) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		$this->view->order = $order;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/clients/orderhunts.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($id = 0)
	{
		if ($this->requireClient())
			throw new Exception(403, 403);

		$id = (int)$id;

		$order = Orders::findFirstByid($id);
		if (!$order || $order->client_id != $this->client->id)
			throw new Exception(404, 404);

		$builder = $this->modelsManager->createBuilder()
							->columns('o.id, o.max_players, o.max_teams, o.start, o.finish, o.expire, o.hunt_id, Hunts.name, o.flags')
							->from(['o' => 'OrderHunts'])
							->leftJoin('Hunts', 'o.hunt_id = Hunts.id')
							->where('order_id = ' . $order->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	public function zipAction($id = 0, $chat = 0)
	{
		$idx = str_replace(' ', '+', $this->request->getQuery('h'));
		if ($idx) {
			$idx = (int)$this->crypt->decryptBase64($idx);
			if (!($idx > 0 && $id == $idx)) {
				$this->flash->error('Page was not found');
				$this->response->redirect('orders');

				return;
			}
		}

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		$order = $orderHunt ? $orderHunt->Order : false;
		$client = $order ? $order->Client : false;
		$hunt = $orderHunt ? $orderHunt->Hunt : false;

		$chat = (bool)$chat;

		$watermark = true;

		if ($this->requireUser(false)) {
			if (!($idx > 0)) {
				if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
					$order = false;
			}
		} else if ($this->request->getQuery('wm') === '0') {
			$watermark = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$ohFiles = [];
		if ($multihunt = $this->request->getQuery('mh') && $orderHunt->isMultiHunt()) {
			$orderHunts = $order->getOrderHunts('flags & 8 = 8');
			foreach ($orderHunts as $oh)
				$ohFiles[$oh->id] = $oh->getFiles(true);
		} else {
			$ohFiles[$orderHunt->id] = $orderHunt->getFiles(true);
		}

		$destination = $this->config->application->tmpDir . $orderHunt->id . ($chat ? '.chat' : '.answers') . ($multihunt ? '.multihunt' : '') . ($watermark ? '.wm' : '') . '.zip';

		$prettyFileName =  filter_var(implode(' - ', array_filter([
			$client ? $client->company : null,
			$orderHunt->isMultiHunt() ? 'Multi Hunt' : $hunt->name,
			$hunt->City->name,
			date('m.d.Y', strtotime($orderHunt->start))
		]))) . '.zip';

		$exists = file_exists($destination);
		if ($exists) {
			$time = filemtime($destination);
			if ($time > 0 && time() - $time < 600) {
				$this->view->disable();
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($destination));
				header('Content-Disposition: attachment; filename="' . $prettyFileName . '"');
				if (!$this->request->isHead()) {
					$handle = fopen($destination, 'rb');
					while (!feof($handle)) {
						echo fread($handle, 8192);
						ob_flush();
						flush();
					}
					fclose($handle);
				}
				exit;
			}
		}

		$empty = true;
		foreach ($ohFiles as $i => $ohf) {
			$ohFiles[$i]['files'] = array_filter(array_map(function($f) use ($chat){
				if (($f[1] == 'chat') != $chat)
					return false;
				return $f[0];
			}, $ohf['files']));
			if (!empty($ohFiles[$i]['files']))
				$empty = false;
		}

		if ($empty) {
			$this->flash->error('No files yet');
			$this->response->redirect('order_hunts/summary/' . $orderHunt->id);

			return;
		}

		$teamsStatus = $orderHunt->getTeamsStatus();
		$teamNames = [];
		foreach ($teamsStatus as $t => $team)
			$teamNames[$team['id']] = htmlspecialchars_decode($team['name'], ENT_QUOTES | ENT_HTML5);
		unset($teamsStatus);

		try {

			if ($exists) {
				if (@unlink($destination))
					$exists = false;
			}

			$zip = new ZipArchive();

			ignore_user_abort(true);
			set_time_limit(120);
			if ($zip->open($destination, $exists ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true)
				throw new Exception('Can\'t open zip', 66);

			foreach ($ohFiles as $ohf) {
				foreach ($ohf['files'] as $file) {
					if ($watermark) {
						$wmFile = preg_replace('/\.(jpg|png|gif)$/', '.wm.$1', $file);
						if (!file_exists($wmFile))
							IndexController::watermark(strstr($wmFile, 'uploads/'), $this, false);
					}
					$fname = substr($file, $ohf['baseDirLen']);
					$dirname = strstr($fname, '/', true);
					$fname = substr($fname, strlen($dirname) + 1);
					if ($watermark && file_exists($wmFile))
						$file = $wmFile;
					if ($chat) {
						$zip->addFile($file, substr($fname, 1));
					} else {
						if (is_numeric($dirname) && isset($teamNames[$dirname]))
							$dirname = str_replace([' ', '"', '\'', '&', '|', ':', '<', '>', '*', '/', '\\', '?', '#'], '_', $teamNames[$dirname]);
						$zip->addFile($file, $dirname . '_' . $fname);
					}
				}
			}

			$zip->close();
			ignore_user_abort(false);

			if (file_exists($destination)) {
				$this->view->disable();
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($destination));
				header('Content-Disposition: attachment; filename="' . $prettyFileName . '"');
				if (!$this->request->isHead()) {
					$handle = fopen($destination, 'rb');
					while (!feof($handle)) {
						echo fread($handle, 8192);
						ob_flush();
						flush();
					}
					fclose($handle);
				}
				exit;
			} else {
				throw new Exception('Zip doesn\'t exists', 77);
			}

		} catch (Exception $e) {
			$this->flash->error('An unknown error occurred #' . $e->getCode() . '; please try again later or contact support');
			$this->response->redirect('order_hunts/summary/' . $orderHunt->id);
		}
	}

	public function endAction()
	{
		$h = $id = str_replace(' ', '+', $this->request->getQuery('h'));
		if ($id)
			$id = (int)$this->crypt->decryptBase64($id);
		$orderHunt = $id > 0 ? OrderHunts::findFirstByid($id) : false;
		if (!$orderHunt || $orderHunt->isCanceled()) {
			$this->flash->error('Page was not found');
			$this->response->redirect('orders');

			return;
		}
		$this->view->h = rawurlencode($h);
		$order = $orderHunt->Order;

		$this->view->NCR = $order->id == NcrController::ORDER_ID;

		$teamsStatus = $orderHunt->getTeamsStatus();

		$logs = $teamHints = $teamNames = $teamAns = $teamPos = $tids = [];
		$colors = \Colors\RandomColor::many(count($teamsStatus), ['luminosity' => 'light']);
		$orderHunts = [$orderHunt->id => 0];

		foreach ($teamsStatus as $t => $team) {
			$teamNames[$team['id']] = [$team['name'], $colors[$t]];
			$tids[] = $team['id'];
			$orderHunts[$team['order_hunt_id']] = 0;
		}
		$multihunt = $orderHunt->isMultiHunt() || count($orderHunts) > 1;
		if ($multihunt) {
			$oh = array_flip(array_map('array_pop', $this->db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$orderHunts += $oh;
		}
		$ohids = implode(',', array_keys($orderHunts));
		if (!empty($tids)) {
			$tids = implode(',', $tids);
			$group = $this->db->fetchAll('SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id) t GROUP BY team_id', Db::FETCH_ASSOC);
			foreach ($group as $g)
				$teamPos[$g['team_id']] = (int)$g['rowcount'];
			$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = $a->maxi;
			/*$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MIN(created) AS mini, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = [$a->mini, $a->mini == $a->maxi ? '' : $a->maxi];*/

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
					$scored = 0;
				} else {
					$scored = $a['score'];
				}
				$logs[] = [
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
					unset($customQuestions[$i]);
				}
			}
			$this->view->customQuestions = $customQuestions;
		}
		if ($multihunt) {
			$max = (int)$this->db->fetchColumn(
				'SELECT SUM(c) FROM (SELECT COUNT(1) as c FROM custom_questions  WHERE order_hunt_id IN (SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8) UNION ALL SELECT COUNT(1) as c FROM hunt_points hp LEFT JOIN order_hunts oh ON (oh.hunt_id = hp.hunt_id) WHERE oh.order_id = ' . (int)$orderHunt->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8) t'
			);
		} else {
			$max = HuntPoints::count('hunt_id=' . $orderHunt->hunt_id) + $orderHunt->countCustomQuestions();
		}
		foreach ($teamsStatus as $t => $team) {
			if (isset($teamPos[$team['id']])) {
				$teamsStatus[$t]['question'] = $teamPos[$team['id']];
				if ($teamPos[$team['id']] > $max)
					$max = $teamPos[$team['id']];
			} else {
				$teamsStatus[$t]['question'] = 0;
			}
			$teamsStatus[$t]['lastAnswer'] = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : '';
			//$teamsStatus[$t]['times'] = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : ['', ''];
			$teamsStatus[$t]['hints'] = isset($teamHints[$team['id']]) ? $teamHints[$team['id']] : 0;
		}
		$this->view->max = $max;
		$this->view->leaderboard = $teamsStatus;
		$this->view->logs = $logs;
		$this->view->orderHunt = $orderHunt;
		$this->view->teamNames = $teamNames;

		$PlayersInfo = $files = [];
		$orderHunts = OrderHunts::find($multihunt ? 'id IN (' . $ohids . ')' : ('id=' . $orderHunt->id));
		foreach ($orderHunts as $oh) {
			$files[$oh->id] = $oh->getFiles();
			foreach ($files[$oh->id] as $f => $ff) {
				if ($ff[1] == 'chat' && preg_match('/^chat\/(\d+)_/', $ff[0], $m) && $m[1] > 0) {
					$files[$oh->id][$f][3] = $m[1];
					$PlayersInfo[$m[1]] = 0;
				}
			}
		}
		$this->view->files = $files;

		if (empty($PlayersInfo)) {
			$this->view->playersInfo = [];
		} else {
			$players = \Players::find('id in (' . implode(',', array_keys($PlayersInfo)) . ')');
			$playersInfo = [];
			foreach ($players as $p) {
				$playersInfo[$p->id] = [$p->team_id, is_null($p->first_name) ? '' : htmlspecialchars(trim($p->first_name . ' ' . $p->last_name))];
				//if (empty($playersInfo[$p->id][1]))
				//	$playersInfo[$p->id][1] = $p->email;
			}
			$this->view->playersInfo = $playersInfo;
		}

		/*$map = [];
		foreach ($teamsStatus as $team) {
			$builder = new \Phalcon\Mvc\Model\Query\Builder([
				'models'		=> 'RoutePoints',
				'columns'		=> 'RoutePoints.idx, p.longitude, p.latitude, p.name',
				'conditions'	=> 'RoutePoints.route_id = ' . $team['route_id'],
				'order'			=> 'RoutePoints.idx ASC',
				'limit'			=> [1, $team['question'] == $max ? $team['question'] - 1 : $team['question']]
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

		if (!$eurl = $this->redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$elink = $this->config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($this->crypt->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($elink))
				$this->redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $elink;
		}
		$this->view->eurl = $eurl;
		$this->view->googleMaps = $this->config->googleapis->maps;
		$this->view->map = array_values($map);*/
		$this->view->facebookSDK = $this->view->hiddenWrapper = true;

		$this->view->bonusQuestions = $this->db->fetchAll('SELECT bq.*, p.team_id, p.email, p.first_name, p.last_name FROM bonus_questions bq LEFT JOIN players p ON (p.id = bq.winner_id) WHERE bq.order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND bq.winner_id IS NOT NULL', Db::FETCH_ASSOC);

		$this->view->customEvents = $this->db->fetchAll('SELECT * FROM custom_events WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND team_id IS NOT NULL', Db::FETCH_ASSOC);

		if (empty($data = $this->redis->get(SB_PREFIX . 'survey:' . $orderHunt->id . ':api'))) {
			$data = file_get_contents('https://app.widiz.com/plugins/survey/api/surveys/api/10?v=c7431d7a622bda87db21093c9745cdcc18da0b69&filterKey=Hunt%20ID&filterVal=' . $orderHunt->id);
			$this->redis->set(SB_PREFIX . 'survey:' . $orderHunt->id . ':api', $data, 600);
		}
		if (!is_array($data = json_decode($data, true)) || $data['success'] !== true)
			$data = [];
		else
			$data = $data['data'];

		$this->view->surveyResults = $data;

		$this->assets->collection('style')
				->addCss('/template/css/plugins/blueimp/css/blueimp-gallery.min.css')
				->addCss('/css/clients/end.css');
		$this->assets->collection('script')
				->addJs('/template/js/plugins/blueimp/jquery.blueimp-gallery.min.js')
				//->addJs('/js/plugins/maps.initializer.js')
				//->addJs('/js/clients/orderhunts.map.js')
				->addJs('/js/plugins/jquery.nicescroll.min.js')
				->addJs('/js/clients/end.js');
	}

	public function summaryAction($id = 0)
	{
		$orderHunt = OrderHunts::findFirstByid((int)$id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$teamsStatus = $orderHunt->getTeamsStatus();

		$logs = $teamHints = $teamNames = $teamAns = $teamPos = $tids = [];
		$colors = \Colors\RandomColor::many(count($teamsStatus), ['luminosity' => 'light']);
		$orderHunts = [$orderHunt->id => 0];
		foreach ($teamsStatus as $t => $team) {
			$teamNames[$team['id']] = [$team['name'], $colors[$t]];
			$tids[] = $team['id'];
			$orderHunts[$team['order_hunt_id']] = 0;
		}
		$multihunt = $orderHunt->isMultiHunt() || count($orderHunts) > 1;
		if ($multihunt) {
			$oh = array_flip(array_map('array_pop', $this->db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$orderHunts += $oh;
		}
		$ohids = implode(',', array_keys($orderHunts));
		if (!empty($tids)) {
			$tids = implode(',', $tids);
			$group = $this->db->fetchAll('SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id) t GROUP BY team_id');
			foreach ($group as $g)
				$teamPos[$g['team_id']] = (int)$g['rowcount'];
			$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = $a->maxi;
			/*$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MIN(created) AS mini, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = [$a->mini, $a->mini == $a->maxi ? '' : $a->maxi];*/

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
					$scored = 0;
				} else {
					$scored = $a['score'];
				}
				$logs[] = [
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
					unset($customQuestions[$i]);
				}
			}
			$this->view->customQuestions = $customQuestions;
		}
		foreach ($teamsStatus as $t => $team) {
			$teamsStatus[$t]['question'] = isset($teamPos[$team['id']]) ? $teamPos[$team['id']] : 0;
			$teamsStatus[$t]['lastAnswer'] = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : '';
			//$teamsStatus[$t]['times'] = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : ['', ''];
			$teamsStatus[$t]['hints'] = isset($teamHints[$team['id']]) ? $teamHints[$team['id']] : 0;
		}
		if ($multihunt) {
			$max = (int)$this->db->fetchColumn(
				'SELECT SUM(c) FROM (SELECT COUNT(1) as c FROM custom_questions  WHERE order_hunt_id IN (SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8) UNION ALL SELECT COUNT(1) as c FROM hunt_points hp LEFT JOIN order_hunts oh ON (oh.hunt_id = hp.hunt_id) WHERE oh.order_id = ' . (int)$orderHunt->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8) t'
			);
		} else {
			$max = HuntPoints::count('hunt_id=' . $orderHunt->hunt_id) + $orderHunt->countCustomQuestions();
		}
		$this->view->max = $max;
		$this->view->leaderboard = $teamsStatus;
		$this->view->logs = $logs;
		$this->view->orderHunt = $orderHunt;
		$this->view->teamNames = $teamNames;

		$files = [];
		$orderHunts = OrderHunts::find($multihunt ? 'id IN (' . $ohids . ')' : ('id=' . $orderHunt->id));
		foreach ($orderHunts as $oh)
			$files[$oh->id] = $oh->getFiles();
		$this->view->files = $files;

		$map = [];
		foreach ($teamsStatus as $team) {
			$builder = new \Phalcon\Mvc\Model\Query\Builder([
				'models'		=> 'RoutePoints',
				'columns'		=> 'RoutePoints.idx, p.longitude, p.latitude, p.name',
				'conditions'	=> 'RoutePoints.route_id = ' . $team['route_id'],
				'order'			=> 'RoutePoints.idx ASC',
				'limit'			=> [1, $team['question'] == $max ? $team['question'] - 1 : $team['question']]
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

		if (!$eurl = $this->redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$elink = $this->config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($this->crypt->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($elink))
				$this->redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $elink;
		}
		$this->view->eurl = $eurl;
		$this->view->map = array_values($map);

		if ($this->view->hideHeader = $this->request->isAjax()) {
			$this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
			$this->view->surveyResults = $this->view->customEvents = $this->view->customQuestions = $this->view->bonusQuestions = [];
		} else {

			$this->view->bonusQuestions = $this->db->fetchAll('SELECT bq.*, p.team_id, p.email, p.first_name, p.last_name FROM bonus_questions bq LEFT JOIN players p ON (p.id = bq.winner_id) WHERE bq.order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND bq.winner_id IS NOT NULL', Db::FETCH_ASSOC);

			$this->view->customEvents = $this->db->fetchAll('SELECT * FROM custom_events WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND team_id IS NOT NULL', Db::FETCH_ASSOC);

			if (empty($data = $this->redis->get(SB_PREFIX . 'survey:' . $orderHunt->id . ':api'))) {
				$data = file_get_contents('https://app.widiz.com/plugins/survey/api/surveys/api/10?v=c7431d7a622bda87db21093c9745cdcc18da0b69&filterKey=Hunt%20ID&filterVal=' . $orderHunt->id);
				$this->redis->set(SB_PREFIX . 'survey:' . $orderHunt->id . ':api', $data, 600);
			}
			if (!is_array($data = json_decode($data, true)) || $data['success'] !== true)
				$data = [];
			else
				$data = $data['data'];

			$this->view->surveyResults = $data;

			$this->assets->collection('style')
					->addCss('/template/css/plugins/blueimp/css/blueimp-gallery.min.css');
			$this->assets->collection('script')
					->addJs('/template/js/plugins/blueimp/jquery.blueimp-gallery.min.js')
					->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/clients/orderhunts.map.js')
					->addJs('/js/plugins/jquery.nicescroll.min.js')
					->addJs('/js/clients/end.js');
			$this->view->googleMaps = $this->config->googleapis->maps;
		}
	}

	public function downloadPDFAction($id = 0)
	{

		$id = (int)$id;

		$orderHunt = OrderHunts::findFirstByid($id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat);
		$pdf->downloadPDF();
	}

	public function viewPDFAction($id = 0)
	{

		$id = (int)$id;

		$orderHunt = OrderHunts::findFirstByid($id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$pdf = new OrderHuntPDF($orderHunt, $this->view->timeFormat);
		$pdf->displayPDF();
	}

	public function customizeAction($id = 0)
	{

		$id = (int)$id;

		$orderHunt = OrderHunts::findFirstByid($id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if (!$this->request->isPost()) {
			$this->tag->setDefault('id', $orderHunt->id);
			$this->tag->setDefault('start_msg', $orderHunt->start_msg);
			$this->tag->setDefault('end_msg', $orderHunt->end_msg);
			$this->tag->setDefault('timeout_msg', $orderHunt->timeout_msg);
		}

		$this->view->orderHunt = $orderHunt;

		//$this->assets->collection('script')->addJs('/js/clients/orderhunt.customize.js');
	}

	public function viewFileAction($team = 0, $fname = '')
	{
		$team = Teams::findFirstByid((int)$team);
		$orderHunt = $team ? $team->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order)
			return $this->response->setStatusCode(404);

		$file = $this->config->application->frontUploadsDir->path . $orderHunt->id . '/' . $team->id . '/' . $fname;
		if (!(preg_match('/^\d+\.(jpg|png|gif)$/i', $fname) && file_exists($file)))
			return $this->response->setStatusCode(404);
		$this->view->disable();
		$expireDate = new \DateTime();
		$expireDate->modify('+1 year');
		$this->response
				->setHeader('Content-Length', filesize($file))
				->setHeader('E-Tag', md5(filemtime($file) . $file))
				->setCache(525600);
				//->setHeader('Cache-Control', 'max-age=31536000')
				//->setExpires($expireDate);
		if ($mime = mime_content_type($file))
			$this->response->setHeader('Content-Type', $mime);
		if (!$this->request->isHead())
			$this->response->setContent(file_get_contents($file));
		header_remove('Pragma');
		$this->response->send();
		exit;
	}

	public function saveAction()
	{

		$id = (int)$this->request->getPost('id', 'int');

		$orderHunt = OrderHunts::findFirstByid($id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$start_msg = $this->request->getPost('start_msg', 'trim');
		$end_msg = $this->request->getPost('end_msg', 'trim');
		$timeout_msg = $this->request->getPost('timeout_msg', 'trim');

		$orderHunt->start_msg = empty($start_msg) ? null : $start_msg;
		$orderHunt->end_msg = empty($end_msg) ? null : $end_msg;
		$orderHunt->timeout_msg = empty($timeout_msg) ? null : $timeout_msg;

		if ($orderHunt->save()) {

			$this->flash->success('Order hunt was updated successfully');

			$this->response->redirect('order_hunts/' . $order->id);

		} else {
			foreach ($order->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'order_hunts',
				'action' => 'customize',
				'params' => [$orderHunt->id]
			]);
		}

	}

}
