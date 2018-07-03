<?php

namespace Play\Frontend\Controllers;

class ActivateController extends ControllerBase
{

	public function indexAction()
	{
		if ($this->requirePlayer())
			return true;

		if (!is_null($this->team->activation)) {
			$this->view->disable();
			$this->response->redirect('play');
			return true;
		}

		$timeToStart = strtotime($this->orderHunt->start) - time() - 2;
		if ($timeToStart > 0) {
			$this->view->startTimer = $timeToStart;
		} else if ($this->request->isPost() && $this->player->isLeader()) {
			$teamsStatus = $this->orderHunt->getTeamsStatus();
			$success = true;
			$name = $this->escaper->escapeHtml(trim($this->request->getPost('name')));
			foreach ($teamsStatus as $t) {
				if ($t['name'] == $name && $t['id'] != $this->team->id) {
					$this->flash->error('Name already in use');
					$success = false;
					break;
				}
			}
			if ($success) {
				$this->team->name = $name;
				$this->team->activation = date('Y-m-d H:i:s');
				if (is_null($this->team->first_activation))
					$this->team->first_activation = $this->team->activation;

				if ($this->team->save()) {

					if (SB_PRODUCTION) {
						$num = \Teams::count('order_hunt_id = ' . $this->orderHunt->id . ' AND id <= ' . $this->team->id);
						$order = $this->orderHunt->Order;
						$now = date($this->timeFormat);
						$route = \Routes::count('hunt_id = ' . $this->orderHunt->hunt_id . ' AND id <= ' . $this->team->route_id);
						$sLink = $this->config->fullUri . '/admin/order_hunts/summary/' . $this->orderHunt->id;
						$lname = trim((is_null($this->player->first_name) ? '' : $this->player->first_name) . ' ' . (is_null($this->player->last_name) ? '' : $this->player->last_name));
						$lname = 'Player #' . $this->player->id . ' ' . (empty($lname) ? $this->player->email : ($lname . ' ' . $this->player->email));
						$msg = "A leader activated Team {$num} ({$this->team->name}) using the code: {$this->team->activation_leader}\r\n{$lname}\r\nTime: {$now}\r\nOrder: {$order->name}\r\nRoute: {$route}\r\nSummary: " . $sLink;
						$msgHtml = "A leader activated Team {$num} ({$this->team->name}) using the code: {$this->team->activation_leader}<br>" . htmlspecialchars($lname) . "<br>Time: {$now}<br>Order: " . htmlspecialchars($order->name) . "<br>Route: {$route}<br>Summary: <a href=\"{$sLink}\">{$sLink}</a>";
						$this->sendMail('support@strayboots.com,ido@strayboots.com,ariel@safronov.co.il', "Leader Activation - {$order->name} / {$this->hunt->name} / {$order->Client->company}", $msg, $msgHtml);
					}

					return $this->response->redirect('play');

				} else {
					foreach ($this->team->getMessages() as $message)
						$this->flash->error($message);
				}
			}

		}

		$this->view->teamNameField = is_null($this->team->name);

		if (is_null($this->orderHunt->start_msg))
			$this->view->start_msg = "Welcome to your Strayboots Scavenger hunt! We hope you brought your wit and a sharp eye. <br>Please be considerate of others, respect restaurants or public spaces, look both ways before crossing any street, don't run and most of all, have fun.";
			// $this->view->start_msg = 'Welcome to your scavenger hunt adventure. We hope you brought your wit and a sharp eye.<br>Best of luck!';
		else
			$this->view->start_msg =  nl2br(htmlspecialchars($this->orderHunt->start_msg));

		$this->view->firebase = [
			'config' => $this->config->firebase,
			'appLoc' => [
				(int)$this->team->id, 'actv', false, 
				'orderHunt' => (int)$this->orderHunt->id,
				'timeLeft' => max(-1, strtotime($this->orderHunt->finish) - time())
			]
		];

		$this->assets->collection('style')->addCss('/css/app/play.css');
		$this->assets->collection('script')
					->addJs('/js/app/activate.js');
	}
}
