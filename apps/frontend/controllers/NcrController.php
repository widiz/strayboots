<?php

namespace Play\Frontend\Controllers;

use Phalcon\Db,
	Play\Admin\Controllers\OrdersController,
	\OrderHuntPDFNCR,
	\OrderHunts,
	\Exception,
	\Players,
	\Orders,
	\Teams;

class NcrController extends ControllerBase
{
	const ORDER_ID = SB_PRODUCTION ? 1034 : 31;
	const LIMIT = SB_PRODUCTION ? 500 : 50;

	public function indexAction()
	{

		if ($this->player instanceof Players) {
			$this->view->disable();
			$this->response->redirect(is_null($this->team->activation) ? 'activate' : 'play');
			return true;
		}

		$this->loadAssets();
	}

	public function registerAction()
	{
		$this->view->playersLimit = self::LIMIT;
		$this->view->timeFormat = $this->timeFormat;
		//$this->view->events = OrderHunts::find('order_id = ' . self::ORDER_ID . ' AND flags & 4 = 0');
		$this->view->events = $this->db->fetchAll(
			'SELECT oh.id, oh.start, COUNT(p.id) AS players ' .
			'FROM order_hunts oh ' .
			'LEFT JOIN teams t ON (t.order_hunt_id = oh.id) ' .
			'LEFT JOIN players p ON (p.team_id = t.id) ' .
			'WHERE oh.order_id = ' . self::ORDER_ID . ' AND oh.finish > "' . date('Y-m-d H:i:s') . '" AND oh.flags & 4 = 0 AND oh.id != 1376 ' .
			'GROUP BY oh.id ORDER BY oh.start ASC',
		Db::FETCH_ASSOC);
		$this->view->ncrurl = preg_replace('~^https?://~', '', $this->config->fullUri) . '/ncr';
		$this->loadAssets();
	}

	private function loadAssets()
	{

		$this->view->headerHTML = '<link href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,700" rel="stylesheet">';
		$this->view->ncr = true;

		$clientPaths = $this->config->application->clientsUploadsDir;
		$uploadBase = $clientPaths->path . 'order.' . self::ORDER_ID . '.';
		$cache = $this->redis;
		$key = SB_PREFIX . 'css:' . self::ORDER_ID;
		if (($huntCss = $cache->get($key)) === false) {
			$huntCss = Orders::findFirstById(self::ORDER_ID)->getCSS($clientPaths);
			$cache->set($key, $huntCss, 7200);
		}
		$this->view->huntCss = $huntCss/* . 'html body{background-image:url(/img/ncr/register.jpg) !important}'*/;
		if (file_exists($uploadBase . 'logo.png'))
			$this->view->customLogo = $clientPaths->uri . 'order.' . self::ORDER_ID . '.logo.png';

		$this->assets->collection('style')
					->addCss('/css/app/ncr.css');

		$this->assets->collection('script')
					->addJs('/template/js/plugins/validate/jquery.validate.min.js')
					->addJs('/js/app/ncr.js');
	}

	public function ajaxAction()
	{
		$orderHunt = OrderHunts::findFirstById((int)$this->request->getPost('eventId', 'int'));
		if (!$orderHunt || $orderHunt->order_id != self::ORDER_ID) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'event was not found'
			]);
		}

		$splitName = function($name){
			$name = explode(' ', $name, 2);
			return [isset($name[0]) && !empty($name[0]) ? substr($name[0], 0, 20) : null, isset($name[1]) && !empty($name[1]) ? substr($name[1], 0, 20) : null];
		};

		$leaderEmail = $this->request->getPost('leader', 'email');
		$leaderName = $splitName($this->request->getPost('leaderName', 'trim'));
		$players = json_decode($this->request->getPost('players', 'trim'), true);
		if (!is_array($players))
			$players = [];

		if (!filter_var($leaderEmail, FILTER_VALIDATE_EMAIL)) {
			return $this->jsonResponse([
				'success' => false,
				'error' => htmlspecialchars($leaderEmail) . ' is not a valid email address'
			]);
		}
		foreach ($players as $p) {
			if (!filter_var($p[0], FILTER_VALIDATE_EMAIL)) {
				return $this->jsonResponse([
					'success' => false,
					'error' => htmlspecialchars($p[0]) . ' is not a valid email address'
				]);
			}
		}

		$team = $this->db->fetchOne('SELECT t.id FROM teams t LEFT JOIN players p ON p.team_id=t.id WHERE t.order_hunt_id=' . $orderHunt->id . ' AND t.activation IS NULL AND t.name IS NULL AND p.id IS NULL ORDER BY t.id ASC LIMIT 1', Db::FETCH_ASSOC);
		if (empty($team)) {
			$team = $orderHunt->addTeams(1);
			$team = empty($team) ? false : $team[0];
		} else {
			$team = Teams::findFirstById($team['id']);
		}
		if (!$team) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to find or create a team; please contact support'
			]);
		}

		$player = new Players();
		$player->team_id = $team->id;
		$player->email = $leaderEmail;
		$player->first_name = $leaderName[0];
		$player->last_name = $leaderName[1];
		if (!$player->save()) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to create a player; please contact support'
			]);
		}
		$team->leader = $player->id;
		$teamSave = $this->db->update(
			'teams',
			['leader'],
			[$team->leader],
			'id=' . $team->id
		);
		if (!$teamSave) {
			try {
				$team->resetTeam();
				$player->delete();
			} catch(Exception $E) {}
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to save team; please contact support'
			]);
		}

		$pInstances = [];
		foreach ($players as $pl) {
			$p = new Players();
			$p->team_id = $team->id;
			$p->email = $pl[0];

			$name = $splitName(trim($pl[1]));
			$p->first_name = $name[0];
			$p->last_name = $name[1];
			if ($p->save()) {
				$pInstances[] = $p;
			} else {
				try {
					$team->resetTeam();
					$player->delete();
					foreach ($pInstances as $pp)
						$pp->delete();
				} catch(Exception $E) {}
				return $this->jsonResponse([
					'success' => false,
					'error' => 'failed to create players; please contact support'
				]);
			}
		}

		$this->config->fullUri .= '/ncr';

		//-----

		$date = 'October 24th';//date('F jS', strtotime($orderHunt->start) - 86400);
		$text = "Hello!\r\n\r\nThank you for registering for the NCR All Routes Lead to Midtown Scavenger Hunt. You are officially confirmed! On " . $date . " you will receive a second email with detailed instructions, helpful tips and your hunt activation code. Do not delete this second email! You will need your individual activation code to log into the hunt on your smartphone the day of your event.\r\n\r\nFor now, finish mapping your commute and get ready to get out of the office and into Midtown for lots of fun and incredible prizes!\r\n\r\nThank you!";
		$html = '<table align="left" border="0" dir="ltr" style="max-width:600px;border:0"><tr><td>Hello!<br><br>Thank you for registering for the NCR All Routes Lead to Midtown Scavenger Hunt. You are officially confirmed! On ' . $date . ' you will receive a second email with detailed instructions, helpful tips and your hunt activation code. Do not delete this second email! You will need your individual activation code to log into the hunt on your smartphone the day of your event.<br><br>For now, finish mapping your commute and get ready to get out of the office and into Midtown for lots of fun and incredible prizes!<br><br>Thank you!</td></tr></table>';

		$emails = empty($players) ? [] : array_map(function($p){
			return $p[0];
		}, $players);
		$emails[] = $leaderEmail;
		if (!$this->sendMail($emails, 'Your NCR Scavenger Hunt Registration Confirmation', $text, $html)) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to send instructions, please contact support'
			]);
		}

		/*
		$attachments = [];
		$pdf = new OrderHuntPDFNCR($orderHunt, $this->timeFormat, true, false, $team->id);
		if (file_exists($pdf = $pdf->savePDF())) {
			$attachments[] = '@' . $pdf;
		} else {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to create pdf; please contact support'
			]);
		}

		$text = "Hi there!\r\n\r\nAttached you will find your All Routes Lead to Midtown Scavenger Hunt Instructions. At your hunt time, please click the link and enter your code to start the hunt.\r\n\r\n{$this->config->fullUri}\r\n\r\nHave Fun!";
		$html = 'Hi there!<br><br>Attached you will find your All Routes Lead to Midtown Scavenger Hunt Instructions. At your hunt time, please click the link and enter your code to start the hunt.<br><br><a href="' . $this->config->fullUri . '">' . $this->config->fullUri . '</a><br><br>Have Fun!';

		if (!$this->sendMail($leaderEmail, 'Your NCR Scavenger Hunt Instructions', $text, $html, $attachments)) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to send instructions, please contact support'
			]);
		}
		if (!empty($players)) {

			$attachments = [];
			$pdf = new OrderHuntPDFNCR($orderHunt, $this->timeFormat, false, true, $team->id);
			if (file_exists($pdf = $pdf->savePDF())) {
				$attachments[] = '@' . $pdf;
			} else {
				return $this->jsonResponse([
					'success' => false,
					'error' => 'failed to create pdf; please contact support'
				]);
			}

			$emails = array_map(function($p){
				return $p[0];
			}, $players);
			if (!$this->sendMail($emails, 'Your NCR Scavenger Hunt Instructions', $text, $html, $attachments)) {
				return $this->jsonResponse([
					'success' => false,
					'error' => 'failed to send instructions, please contact support'
				]);
			}
		}*/

		return $this->jsonResponse([
			'success' => true,
			'activation' => $team->activation_leader
		]);
	}

	public function saudiAction()
	{
		$orderHunt = OrderHunts::findFirstById((int)$this->request->getPost('eventId', 'int'));
		if (!$orderHunt/* || $orderHunt->order_id != self::ORDER_ID*/) {
			return $this->jsonResponse([
				'success' => false,
				'error' => 'event was not found'
			]);
		}

		$splitName = function($name){
			$name = explode(' ', $name, 2);
			return [isset($name[0]) && !empty($name[0]) ? substr($name[0], 0, 20) : null, isset($name[1]) && !empty($name[1]) ? substr($name[1], 0, 20) : null];
		};

		$this->db->begin();

		$leaderEmail = $this->request->getPost('leader', 'email');
		$leaderName = $splitName($this->request->getPost('leaderName', 'trim'));
		$leaderPhone = $this->request->getPost('leaderPhone', 'trim');
		$leaderId = $this->request->getPost('leaderId', 'trim');
		$players = json_decode($this->request->getPost('players', 'trim'), true);
		if (!is_array($players))
			$players = [];

		$orderHuntIds = array_column($this->db->fetchAll('SELECT id FROM order_hunts WHERE order_id=2508 OR order_id=2487', Db::FETCH_ASSOC), 'id');
		$orderHuntIds[] = $orderHunt->id;

		ini_set('memory_limit', '256M');
		$emails = array_flip(array_column($this->db->fetchAll('SELECT p.email FROM players p LEFT JOIN teams t ON t.id = p.team_id WHERE t.order_hunt_id IN (' . implode(',', $orderHuntIds) . ')', Db::FETCH_ASSOC), 'email'));
		if (!filter_var($leaderEmail, FILTER_VALIDATE_EMAIL)) {
			$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => htmlspecialchars($leaderEmail) . ' is not a valid email address'
			]);
		}
		if (isset($emails[$leaderEmail])) {
			$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => htmlspecialchars($leaderEmail) . ' already exists'
			]);
		}
		$emails[$leaderEmail] = 0;
		$uniqueFields = array_flip(array_column($this->db->fetchAll('SELECT meta_value FROM player_meta', Db::FETCH_ASSOC), 'meta_value')); // todo change this to match only players with the same hunt
		if (isset($uniqueFields['']))
			unset($uniqueFields['']);
		foreach ($players as $pIdx => $p) {
			if (!empty($p[2])) {
				if (isset($uniqueFields[$p[2]])) {
					$this->db->rollback();
					return $this->jsonResponse([
						'success' => false,
						'error' => htmlspecialchars($p[2]) . ' already exists'
					]);
				}
				$uniqueFields[$p[2]] = 0;
			}
			if (isset($uniqueFields[$p[3]])) {
				$this->db->rollback();
				return $this->jsonResponse([
					'success' => false,
					'error' => htmlspecialchars($p[3]) . ' already exists'
				]);
			}
			$uniqueFields[$p[3]] = 0;
			if (empty($p[0]))
				$players[$pIdx][0] = $p[0] = 'r' . mt_rand(1e2, 1e4) . date('hms') . mt_rand(1e6, 1e7) . '@sb-auto-not-real.com';
			if (!filter_var($p[0], FILTER_VALIDATE_EMAIL)) {
				$this->db->rollback();
				return $this->jsonResponse([
					'success' => false,
					'error' => htmlspecialchars($p[0]) . ' is not a valid email address'
				]);
			}
			if (isset($emails[$p[0]])) {
				$this->db->rollback();
				return $this->jsonResponse([
					'success' => false,
					'error' => htmlspecialchars($p[0]) . ' already exists'
				]);
			}
			$emails[$p[0]] = 0;
		}

		$team = $this->db->fetchOne('SELECT t.id FROM teams t LEFT JOIN players p ON p.team_id=t.id WHERE t.order_hunt_id=' . $orderHunt->id . ' AND t.activation IS NULL AND t.name IS NULL AND p.id IS NULL ORDER BY t.id ASC LIMIT 1', Db::FETCH_ASSOC);
		if (empty($team)) {
			$team = $orderHunt->addTeams(1);
			$team = empty($team) ? false : $team[0];
		} else {
			$team = Teams::findFirstById($team['id']);
		}
		if (!$team) {
			$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to find or create a team; please contact support #1223'
			]);
		}

		$player = new Players();
		$player->team_id = $team->id;
		$player->email = $leaderEmail;
		$player->first_name = $leaderName[0];
		$player->last_name = $leaderName[1];
		if (!$player->save()) {
			$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to create a player; please contact support #7497'
			]);
		}
		$player->setMeta('phone', $leaderPhone);
		$player->setMeta('id', $leaderId);
		$team->leader = $player->id;
		$teamSave = $this->db->update(
			'teams',
			['leader'],
			[$team->leader],
			'id=' . $team->id
		);
		if (!$teamSave) {
			try {
				$team->resetTeam();
				$player->delete();
			} catch(Exception $E) {}
			$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to save team; please contact support'
			]);
		}

		$pInstances = [];
		foreach ($players as $pl) {
			$p = new Players();
			$p->team_id = $team->id;
			$p->email = $pl[0];
			$name = $splitName(trim($pl[1]));
			$p->first_name = $name[0];
			$p->last_name = $name[1];
			if ($p->save()) {
				if ($pl[2])
					$p->setMeta('phone', $pl[2]);
				$p->setMeta('id', $pl[3]);
				$pInstances[] = $p;
			} else {
				try {
					$team->resetTeam();
					$player->delete();
					foreach ($pInstances as $pp)
						$pp->delete();
				} catch(Exception $E) {}
				$this->db->rollback();
				return $this->jsonResponse([
					'success' => false,
					'error' => 'failed to create players; please contact support #6585'
				]);
			}
		}

		//-----

		$text = "Hello!\r\n\r\nThank you for registering for the hunt. Your activation code is: {$team->activation_leader}\r\n\r\nThank you!\r\n\r\n\r\nمرحباً بك!\r\n\r\nنشكرك على التسجيل في البحث عن الكنز. إن رمز التفعيل الخاص بك هو: {$team->activation_leader}\r\n\r\nمع أطيب تحياتنا!";
		$html = '<table align="left" border="0" dir="ltr" style="max-width:600px;border:0"><tr><td>Hello!<br><br>Thank you for registering for the hunt. Your activation code is: ' . $team->activation_leader . '<br><br>Thank you!<br><br><br>مرحباً بك!<br><br>نشكرك على التسجيل في البحث عن الكنز. إن رمز التفعيل الخاص بك هو: ' . $team->activation_leader . '<br><br>مع أطيب تحياتنا!</td></tr></table>';

		$emails = empty($players) ? [] : array_map(function($p){
			return $p[0];
		}, $players);
		$emails[] = $leaderEmail;
		if (!$this->sendMail($emails, 'Your hunt activation code (رمز التفعيل الخاص بك للبحث عن الكنز‎)', $text, $html)) {
			/*$this->db->rollback();
			return $this->jsonResponse([
				'success' => false,
				'error' => 'failed to send instructions, please contact support'
			]);*/
		}

		$this->db->commit();

		return $this->jsonResponse([
			'success' => true,
			'activation' => $team->activation_leader
		]);
	}

}
