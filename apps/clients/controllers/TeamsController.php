<?php

namespace Play\Clients\Controllers;

use \OrderHunts,
	\Teams,
	\Players,
	\Exception,
	Phalcon\Mvc\Model\Transaction\Failed as TxFailed,
	Phalcon\Mvc\Model\Transaction\Manager as TxManager;

class TeamsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{

		$id = (int)$id;

		$orderHunt = OrderHunts::findFirstByid($id);
		$order = $orderHunt ? $orderHunt->Order : false;
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error("Order was not found");
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$this->view->order = $order;
		$this->view->orderHunt = $orderHunt;

		$teams = $orderHunt->getTeams()->toArray();
		
		if (!$this->request->isPost()) {
			$this->tag->setDefault("id", $orderHunt->id);
			$tids = array_map(function($t){
				return $t['id'];
			}, $teams);
			$teamLeaders = [];
			foreach ($teams as $t)
				$teamLeaders[$t['id']] = $t['leader'];
			$players = empty($tids) ? [] : array_map(function($p) use ($teamLeaders){
				$p['leader'] = $teamLeaders[$p['team_id']] == $p['id'];
				return $p;
			}, Players::find('team_id IN (' . implode(',', $tids) . ')')->toArray());
			$this->tag->setDefault('players', json_encode($players, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}

		$this->view->teams = $teams;
		$this->view->huntStarted = $orderHunt->isStarted();

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/iCheck/icheck.min.js')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/validate/jquery.validate.min.js')
				->addJs('/js/plugins/bootbox.min.js')
				->addJs('/js/clients/teams.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/template/css/plugins/iCheck/custom.css');
	}

	/**
	 * CSV action
	 */
	public function csvAction($id = 0)
	{
		$orderHunt = OrderHunts::findFirstByid((int)$id);

		if ($this->requireUser(false)) {
			if ($this->requireClient())
				return true;

			$order = $orderHunt ? $orderHunt->Order : false;
			if ($order === false || $order->client_id != $this->client->id) {
				$this->flash->error('Order was not found');
				$this->response->redirect('orders');

				return;
			}
		}

		if (!$orderHunt) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$csv = '"Team","Email","Name","Role"';

		$teams = $orderHunt->getTeams();
		if (!empty($teams)) {
			$tids = [];
			$teamLeaders = $teamNames = [];
			foreach ($teams as $t => $team) {
				$tids[] = $team->id;
				$teamNames[$team->id] = is_null($team->name) ? 'Team ' . ($t + 1) : str_replace('"', '""', $team->name);
				$teamLeaders[$team->id] = $team->leader;
			}

			$players = empty($tids) ? [] : Players::find('team_id IN (' . implode(',', $tids) . ')');
			foreach ($players as $player) {
				$csv .= "\r\n\"" . $teamNames[$player->team_id] . '","' . $player->email . '","' . str_replace('"', '""', trim($player->first_name . ' ' . $player->last_name)) . '","' . ($player->id == $teamLeaders[$player->team_id] ? 'Leader' : 'Player') . '"';
			}
		}

		$this->view->disable();

		$this->response
					->setContentType('text/csv', 'UTF-8')
					->setHeader('Content-Disposition', 'attachment; filename="' . $orderHunt->id . '.csv"')
					->setContent($csv)
					->send();
		exit;
	}

	/**
	 * Save
	 */
	public function saveAction()
	{

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

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

		$teams = $orderHunt->getTeams();
		$tids = array_map(function($t){
			return (int)$t['id'];
		}, $teams->toArray());
		$teamsToSave = [];

		$currentPlayers =  empty($tids) ? [] : Players::find('team_id IN (' . implode(',', $tids) . ')');

		$players = json_decode($this->request->getPost('players'), true);
		if (!is_array($players)) $players = [];

		$teamNames = json_decode($this->request->getPost('teams'), true);
		if (!is_array($teamNames)) $teamNames = [];

		try {

			if ($orderHunt->isStarted()) {
				$this->flash->error('Can\'t make changes after the hunt has begun');
				throw new TxFailed('Can\'t make changes after the hunt has begun');
			}
			
			$manager = new TxManager();
			$transaction = $manager->get();

			$players = array_filter(array_map(function(&$p) use (&$orderHunt, &$currentPlayers, &$teamsToSave, &$tids, &$teams){
				if (is_array($p) && isset($p['team_id']) && in_array((int)$p['team_id'], $tids)) {
					$found = false;
					if ($p['id'] > 0) {
						foreach ($currentPlayers as $c => $pl) {
							if ($pl->id == $p['id']) {
								$found = $c;
								break;
							}
						}
					}
					$pl = $found === false ? new Players() : $currentPlayers[$found];
					$pl->team_id = $p['team_id'];
					$pl->first_name = empty($p['first_name']) ? null : $p['first_name'];
					$pl->last_name = empty($p['last_name']) ? null : $p['last_name'];
					$pl->email = empty($p['email']) ? null : mb_strtolower($p['email']);
					if ($p['leader'] === true) {
						foreach ($teams as $t) {
							if ($t->id == $pl->team_id) {
								$t->leader = &$pl;
								$teamsToSave[$t->id] = $t;
								break;
							}
						}
					}
					return $pl;
				}
				return false;
			}, $players));

			if (count($players) > $orderHunt->max_players) {
				$this->flash->error('Too many participants');
				$transaction->rollback();
			}

			$plUsed = [];
			foreach ($players as &$pl) {
				$pl->setTransaction($transaction);
				if ($pl->save()) {
					$plUsed[] = $pl->id;
				} else {
					// TODO use $pl->getMessages??
					foreach ($pl->getMessages() as $msg)
						$this->flash->error($msg);
					$this->flash->error('Something went wrong #x1');
					$transaction->rollback();
				}
			}
			foreach ($currentPlayers as $pl) {
				if (!in_array($pl->id, $plUsed)) {
					$pl->setTransaction($transaction);
					if (!$pl->delete()) {
						$this->flash->error('Something went wrong #x2');
						$transaction->rollback();
					}
				}
			}
			foreach ($teamNames as $tid => $name) {
				$name = is_null($name) ? null : htmlspecialchars($name);
				if (isset($teamsToSave[$tid])) {
					$teamsToSave[$tid]->name = $name;
				} else {
					foreach ($teams as $t) {
						if ($t->id == $tid) {
							$t->name = $name;
							$teamsToSave[$t->id] = $t;
							break;
						}
					}
				}
			}
			foreach ($teamsToSave as &$t) {
				$t->setTransaction($transaction);
				if (is_object($t->leader) && $t->leader instanceof Players)
					$t->leader = $t->leader->id;
				if (!$t->save()) {
					// TODO use $t->getMessages??
					$this->flash->error('Something went wrong #x3');
					$transaction->rollback();
				}
			}

			$transaction->commit();

			$this->flash->success('Teams updated successfully');

			$this->response->redirect('teams/' . $orderHunt->id);

		} catch (TxFailed $e) {
			$this->dispatcher->forward([
				'controller' => 'teams',
				'action' => 'index',
				'params' => [$orderHunt->id]
			]);
		}
	}

}
