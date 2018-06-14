<?php

namespace Play\Clients\Controllers;

class ChatController extends \ControllerBase
{

	public function indexAction($id)
	{
		$orderHunt = \OrderHunts::findFirstByid((int)$id);
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

		$p = $this->db->fetchAll(
			'SELECT p.id, p.team_id, p.email, s.thumbnail, p.first_name as pfname,' .
			'p.last_name as plname, s.first_name as sfname, s.last_name as slname ' .
			'FROM players p LEFT JOIN teams t ON (p.team_id = t.id) ' .
			'LEFT JOIN social_players s ON (s.player_id = p.id) WHERE t.order_hunt_id = ' . $orderHunt->id,
		\Phalcon\Db::FETCH_ASSOC);

		$players = [];

		foreach ($p as $player) {
			$players[$player['id']] = [
				'team'		=> (int)$player['team_id'],
				'email'		=> $player['email'],
				'thumb'		=> $player['thumbnail'],
				'fname'		=> is_null($player['pfname']) ? $player['sfname'] : $player['pfname'],
				'lname'		=> is_null($player['plname']) ? $player['slname'] : $player['plname'],
			];
		}

		$teamsStatus = $orderHunt->getTeamsStatus();
		$teams = [];
		foreach ($teamsStatus as $ts)
			$teams[$ts['id']] = $ts['name'];
		
		$this->view->orderHunt = $orderHunt;

		$this->view->firebase = [
			'config' => $this->config->firebase,
			'appLoc' => [
				'orderHunt' => (int)$orderHunt->id,
				'players' => $players,
				'pid' => 0,
				'teams' => $teams
			]
		];
		$this->assets->collection('style')
				->addCss('/css/app/chat.css');
		$this->assets->collection('script')
				//->addJs('/js/plugins/jquery.nicescroll.min.js')
				->addJs('/js/plugins/firebase-util.min.js')
				->addJs('/js/plugins/Autolinker.min.js')
				->addJs('/js/plugins/moment.min.js')
				->addJs('/js/plugins/emoji.min.js')
				->addJs('/js/plugins/bootbox.min.js')
				//->addJs('/js/plugins/jquery.emoji.js')
				->addJs('/js/clients/chat.js');

	}

}
