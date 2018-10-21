<?php

namespace Play\Frontend\Controllers;

use \Teams,
	\SocialPlayers,
	\OrderHunts,
	\Exception,
	\Players,
	Phalcon\Db;

class IndexController extends ControllerBase
{

	public function indexAction()
	{
		if ($this->player instanceof Players) {
			if (filter_input(INPUT_POST, 'logout', FILTER_VALIDATE_BOOLEAN)) {
				$this->orderHunt = $this->team = $this->player = false;
				return $this->indexAction();
			}
			$this->view->disable();
			$this->response->redirect(is_null($this->team->activation) ? 'activate' : 'play');
			return true;
		} else if ($this->request->isPost()) {
			$isAjax = (bool)$this->request->getQuery('ajaxlogin');
			$email = mb_strtolower($this->request->getPost('email', 'email'));
			$first_name = $this->request->getPost('first_name', 'trim');
			$last_name = $this->request->getPost('last_name', 'trim');
			$activation = $this->request->getPost('activation', 'trim');
			$id = (int)$this->request->getPost('id', 'int');
			$lpId = (int)$this->request->getPost('lp', 'int');
			$validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
			if ($validEmail && \Blocked::findFirstByEmail($email)) {
				$this->flash->error('Failed. please contact support 877-787-2929');
				return $this->response->redirect($this->request->getHTTPReferer());
			}
			if ($id > 0) {
				$orderHunt = OrderHunts::findFirstById($id);
				if ($lpId > 0 && $validEmail) {
					$lp = \LoginPages::findFirst('id=' . $lpId . ' AND order_hunt_id=' . $id);
					if ($lp && !empty($lp->email_login)) {
						if ($this->sendMail($lp->email_login, 'Strayboots login request ' . $lp->slug, 'Email: ' . $email . PHP_EOL . 'OrderHunt: ' . $id . PHP_EOL . $this->config->fullUri . '/admin/order_hunts/' . $orderHunt->order_id)) {
							$this->view->response_msg = $this->view->t->_('Thank you! The activation code for your FREE scavenger hunt is on its way!');
							define('cacheFileId', 'loginEmailMessage');
							$this->assets->collection('style')->addCss('/css/app/play.css');
							return $this->view->pick('play/message');
						} else {
							$this->flash->error('Failed. please try again or contact support 877-787-2929');
							return $this->response->redirect($this->request->getHTTPReferer());
						}
					}
				}
				if ($orderHunt && !$orderHunt->isCustomLogin())
					$orderHunt = false;
				$team = $orderHunt ? Teams::findFirst([
					'order_hunt_id = ?0 AND activation IS NULL',
					'order' => 'id ASC',
					'bind' => [$id]
				]) : false;
				if ($team)
					$activation = $team->activation_leader;
			} else {
				$team = Teams::findFirst([
					'activation_leader = ?0 OR activation_player = ?0',
					'bind' => [$activation]
				]);
				$orderHunt = $team ? $team->OrderHunt : false;
			}
			if ($team) {
				$found = false;
				foreach ($orderHunt->getTeams() as $t) {
					if ($t->id == $team->id) {
						$found = true;
						break;
					}
				}
				if (!$found)
					$team = false;
			}
			$activationSet = false;
			if ($team && $orderHunt) {
				if (!SB_PRODUCTION && $orderHunt->id == 76)
					$team->resetTeam();
				$now = time();
				$start = strtotime($orderHunt->start);
				if (!$orderHunt->isCanceled()) {
					/*if ($now < $start) {
						$this->flash->error('This hunt hasn't started yet; Come back at ' . date($this->timeFormat, $start));
					} else */if ($now < (is_null($orderHunt->expire) ? $start + 604800 /* one week */ : strtotime($orderHunt->expire))) {
						$player = Players::findFirst([
							'team_id = ' . $team->id . ' AND email = ?0',
							'bind' => [$email]
						]);
						if (!$player) {
							// TODO: REVIEW THIS LOGIC
							/*$player = Players::findFirst([
								'team_id = ?0 AND email IS NULL',
								'bind' => [$team->id]
							]);
							if ($player) {
								$player->email = $email;
								if (!$player->save())
									$player = false;
							}
							if (!$player) {*/
								$player = new Players();
								$player->team_id = $team->id;
								$player->email = $email;
								$player->first_name = empty($first_name) ? null : $first_name;
								$player->last_name = empty($last_name) ? null : $last_name;
								if (!$player->save()) {
									foreach ($player->getMessages() as $msg)
										$this->flash->error((string)$msg);
									try{
										$this->logger->error('Player login failed: team:' . $team->id . ' Email: ' . $email);
									} catch(Exception $e) { }
									$player = false;
								}
							//}
						}
						if ($player) {
							$isLeader = mb_strtolower($activation) == mb_strtolower($team->activation_leader);
							if (is_null($team->activation) && $isLeader) {
								//$team->activation = date('Y-m-d H:i:s');
								// not using team->save because of validation rejection possibility
								$activationSet = /*$good = */true;
								/*$good = $this->db->update(
									'teams',
									['activation'],
									[$team->activation],
									'id=' . $team->id
								);
								if ($good) {
									$activationSet = true;
									$num = Teams::count('order_hunt_id = ' . $orderHunt->id . ' AND id <= ' . $team->id);
									$order = $orderHunt->Order;
									$now = date($this->timeFormat);
									$route = \Routes::count('hunt_id = ' . $orderHunt->hunt_id . ' AND id <= ' . $team->route_id);
									$sLink = $this->config->fullUri . "/admin/order_hunts/summary/" . $orderHunt->id;
									$lname = trim((is_null($player->first_name) ? '' : $player->first_name) . ' ' . (is_null($player->last_name) ? '' : $player->last_name));
									$lname = "Player #" . $player->id . ' ' . (empty($lname) ? $player->email : ($lname . ' ' . $player->email));
									$msg = "A leader activated Team {$num} ({$team->name}) using the code: {$team->activation_leader}\r\n{$lname}\r\nTime: {$now}\r\nOrder: {$order->name}\r\nRoute: {$route}\r\nSummary: " . $sLink;
									$msgHtml = "A leader activated Team {$num} ({$team->name}) using the code: {$team->activation_leader}<br>" . htmlspecialchars($lname) . "<br>Time: {$now}<br>Order: " . htmlspecialchars($order->name) . "<br>Route: {$route}<br>Summary: <a href=\"{$sLink}\">{$sLink}</a>";
									$this->sendMail("support@strayboots.com,ido@strayboots.com,ariel@safronov.co.il", "Leader Activation - {$order->name} / {$orderHunt->Hunt->name} / {$order->Client->company}", $msg, $msgHtml);
								} else {
									try{
										$this->logger->error("Activation timestamp save failed: team:" . $team->id . " Email: " . $email);
									} catch(Exception $e) { }
								}*/
							}
							$network = (int)$this->request->getPost('network', 'int');
							$network_id = (int)$this->request->getPost('network_id', 'int');
							if ($network > 0) {
								try {
									$socialPlayer = $player->SocialPlayer;
									if (!$socialPlayer) {
										$socialPlayer = new SocialPlayers();
										$socialPlayer->player_id = $player->id;
									}
									$socialPlayer->network = $network;
									$socialPlayer->network_id = $network_id;
									$socialPlayer->first_name = $first_name;
									$socialPlayer->last_name = $last_name;
									$socialPlayer->thumbnail = $network == SocialPlayers::Facebook ? 'https://graph.facebook.com/' . $network_id . '/picture?type=square' : null;
									if (!$socialPlayer->save())
										$this->logger->error('Player social login failed: player:' . $player->id . ' network: ' . $network);
								} catch(Exception $E) {}
							}
							$good = $isLeader == $player->isLeader();
							if (!$good) {
								$override = (int)$this->request->getPost('override');
								if ($isLeader && $override !== 1 && !is_null($team->name)) {
									if ($override === 2) {
										$good = true;
									} else {
										$this->view->overrideModal = [
											'email' => $player->email,
											'activation_code' => $activation,
											'team_name' => $team->name
										];
										$good = false;
									}
								} else {
									$team->leader = $isLeader ? $player->id : null;
									// not using team->save because of validation rejection possibility
									$good = $this->db->update(
										'teams',
										['leader'],
										[$team->leader],
										'id=' . $team->id
									);
									if ($good !== true) {
										$this->logger->warning('Login warning; failed to save team leader ' . var_export($good, true));
										$good = $team->save();
										if (!$good) {
											//var_dump($team->toArray(),$player->toArray(), $team->getMessages());die;
											$this->logger->error('Login error; failed to save team leader ' . var_export([$good, array_map(function($m){
												return (string)$m;
											}, $team->getMessages())], true));
											$this->flash->error('Error; please try again');
										}
									}
								}
							}
							if ($good) {
								$this->redis->delete(SB_PREFIX . 'ohloc:' . $orderHunt->id . ':' . $team->id);
								Players::setPlayerLogin($player, true);
								$this->view->disable();
								if ($isAjax) {
									return $this->jsonResponse([
										'success'	=> true,
										'redirect'	=> $activationSet && $orderHunt->isMultiHunt() && $isLeader ? '/index/chooseHunt' : '/play'
									]);
								}
								return $this->response->redirect($activationSet && $orderHunt->isMultiHunt() && $isLeader ? 'index/chooseHunt' : 'play');
							}
						} else {
							$this->flash->error('Failed. please try again or contact support 877-787-2929');
						}
					} else {
						$this->flash->error('This hunt has expired');
					}
				} else {
					$this->flash->error('This hunt has been canceled');
				}
			} else {
				$this->flash->error('Activation code is invalid');
			}
			if ($isAjax) {
				return $this->jsonResponse([
					'success'	=> false,
					'messages'	=> $this->flash->getMessages()
				]);
			}
		}
		$this->view->facebookSDK = true;
		$this->assets->collection('script')
					->addJs('/template/js/plugins/validate/jquery.validate.min.js')
					->addJs('/js/app/login.js');
	}

	public function logoutAction()
	{
		$this->view->disable();
		$redirect = '/';
		if (!($this->orderHunt === false || is_null($this->orderHunt->redirect)))
			$redirect = $this->orderHunt->redirect;
		Players::logout();
		$this->session->destroy(true);
		return $this->response->redirect($redirect);
	}

	public function renameAction()
	{
		if ($this->requirePlayer())
			return true;
		if (!$this->player->isLeader())
			return $this->response->redirect('play');
		
		$teamsStatus = $this->orderHunt->getTeamsStatus();
		foreach ($teamsStatus as $team) {
			if ($team['id'] == $this->team->id) {
				$teamStatus = $this->view->teamStatus = $team;
				break;
			}
		}

		if ($this->request->isPost()) {
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
				$success = $this->team->save();
				if (!$success) {
					foreach ($this->team->getMessages() as $message)
						$this->flash->error($message);
				}
			}
			if ($success) {
						$this->flash->success('Name successfully changed');
				return $this->response->redirect('play');
			}
		} else {
			$this->tag->setDefault('name', $this->team->name);
		}
	}

	public function chooseHuntAction()
	{
		if ($this->requirePlayer())
			return true;
		if (!$this->orderHunt->isMultiHunt())
			return $this->response->redirect('/');

		$this->view->id = $this->orderHunt->id;

		$isLeader = $this->player->isLeader();

		if ($this->request->isPost()) {
			if (!$isLeader)
				return $this->response->redirect('/');
			$ohId = (int)$this->request->getPost('ohid', 'int');
			$answered = $orderHunt = false;
			foreach ($this->getMultiHunts() as $oh) {
				if ($oh['id'] == $ohId) {
					$orderHunt = OrderHunts::findFirstById($oh['id']);
					$answered = $oh['answered'] > 0;
					break;
				}
			}
			if (!$orderHunt)
				return $this->response->redirect('index/chooseHunt');

			if ($this->orderHunt->id != $orderHunt->id) {
				$routes = array_flip(array_map(function($r){
					return $r['id'];
				}, $orderHunt->Hunt->getRoutes()));
				if (empty($routes)) {
					try{
						$this->logger->error('Failed to find routes: team:' . $this->team->id . ' OrderHunt: ' . $this->orderHunt->id);
					} catch(Exception $e) { }
					throw new Exception('No routes', 1);
				}
				
				foreach ($routes as $r => $c)
					$routes[$r] = 0;
				$cr = $this->db->fetchAll('SELECT t.route_id as id, count(t.route_id) as `c` FROM teams t WHERE t.order_hunt_id = ' . $orderHunt->id . ' AND t.activation IS NOT NULL GROUP BY t.route_id', Db::FETCH_ASSOC);
				foreach ($cr as $r) {
					if (isset($routes[$r['id']]))
						$routes[$r['id']] = (int)$r['c'];
				}

				$values = array_values($routes);
				$keys = array_keys($routes);
				array_multisort($values, SORT_NATURAL, $keys, SORT_NATURAL);
				$routes = array_combine($keys, $values);
				reset($routes);
				$r = key($routes);
				//var_dump($routes, \Routes::findFirstById($r)->toArray(), $orderHunt->hunt_id);die;

				if (!($r > 0)) {
					$this->flash->error('Something went wrong; please try again or contact support 877-787-2929');
					try{
						$this->logger->error('Failed choose hunt: route failed; team ' . $this->team->id . ' OrderHunt: ' . $this->orderHunt->id . ' NewOrderHunt: ' . $orderHunt->id . ' NewRoute: ' . $r);
					} catch(Exception $e) { }
				}


				$good = $this->db->update(
					'teams',
					['order_hunt_id', 'route_id', 'activation'],
					[$orderHunt->id, $r, is_null($this->team->activation) ? null : date('Y-m-d H:i:s')],
					'id=' . $this->team->id
				);

				if ($good) {
					$this->redis->delete(SB_PREFIX . 'ohloc:' . $this->orderHunt->id . ':' . $this->team->id);
					$this->redis->delete(SB_PREFIX . 'ohloc:' . $orderHunt->id . ':' . $this->team->id);
				} else {
					$this->flash->error('Something went wrong; please try again or contact support 877-787-2929');
					try{
						$this->logger->error('Failed choose hunt: team ' . $this->team->id . ' OrderHunt: ' . $this->orderHunt->id . ' NewOrderHunt: ' . $orderHunt->id . ' NewRoute: ' . $r);
					} catch(Exception $e) { }
					return $this->response->redirect('index/chooseHunt');
				}
			}
			return $this->response->redirect(is_null($this->team->activation) ? 'activate' : 'play');
		} else {
			$this->view->orderHunts = $this->getMultiHunts();
			if (empty($this->view->orderHunts))
				$this->orderHunt->finish = 0;
			else if (!$isLeader)
				return $this->response->redirect('/');
		}
	}

	private function getMultiHunts()
	{
		$now = date('Y-m-d H:i:s');//, IF(oh.flags & 2 = 2,h.duration,NULL) as duration,
		/*$orderHunts = */ return $this->db->fetchAll(<<<EOF
SELECT x.* FROM (
	SELECT oh.id, h.name,
	(SUM(!ISNULL(a.id)) * 100 / (SELECT COUNT(1) FROM hunt_points hp WHERE hp.hunt_id=h.id)) AS answered
	FROM `order_hunts` oh
	LEFT JOIN hunts h ON (h.id = oh.hunt_id)
	LEFT JOIN answers a ON (a.team_id = {$this->team->id} AND a.hunt_id = h.id)
	WHERE oh.order_id = {$this->orderHunt->order_id}
	AND oh.finish > '{$now}' AND oh.expire > '{$now}' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8
	GROUP BY oh.id
) x WHERE x.answered < 100
EOF
		, Db::FETCH_ASSOC); // AND oh.start <= '{$now}'
		/*$hunt = new \Hunts();
		foreach ($orderHunts as $i => $oh) {
			if (!is_null($oh['duration'])) {
				$hunt->duration = $oh['duration'];
				if (strtotime($this->team->activation) + $hunt->getDurationMinutes() * 60 > $now)
				unset($orderHunts[$i]);
			}
		}
		return array_values($orderHunts);*/
	}

	public function customPreviewAction($id = null)
	{
		$order = \Orders::findFirstById(is_null($id) ? (int)$this->request->getPost('id', 'int') : (int)$id);
		if (($uid = (int)$this->session->get('userID')) && \Users::findFirstById($uid)) {
			if (!$order) {
				return $this->dispatcher->forward([
					'controller' => 'error',
					'action'     => 'e404',
				]);
			}
		} else {
			$client = preg_match('/^\d+$/', ($id = $this->session->get('clientID'))) ? \Clients::findFirstById($id) : false;
			if (!($client && $order && $order->client_id == $client->id)) {
				return $this->dispatcher->forward([
					'controller' => 'error',
					'action'     => 'e404',
				]);
			}
		}

		$this->orderHunt = $this->team = $this->player = false;

		$customize = null;
		$removedImages = [];
		if ($this->request->isPost()) {
			$customize = [];

			$headerColor = $this->request->getPost('header_color', 'trim');
			$backgroundColor = $this->request->getPost('background_color', 'trim');
			$mainColor = $this->request->getPost('main_color', 'trim');
			$secondColor = $this->request->getPost('second_color', 'trim');
			$removedImages = array_flip(explode(',', $this->request->getPost('removed_images', 'trim')));
			$customCSS = $this->request->getPost('custom_css');

			if (preg_match('/^#[0-9a-f]{6}$/i', $headerColor))
				$customize['header_color'] = $headerColor;
			if (preg_match('/^#[0-9a-f]{6}$/i', $backgroundColor))
				$customize['background_color'] = $backgroundColor;
			if (preg_match('/^#[0-9a-f]{6}$/i', $mainColor))
				$customize['main_color'] = $mainColor;
			if (preg_match('/^#[0-9a-f]{6}$/i', $secondColor))
				$customize['second_color'] = $secondColor;

			if (!empty($customCSS)) {
				$config = \HTMLPurifier_Config::createDefault();
				$config->set('Filter.ExtractStyleBlocks', true);
				$config->set('CSS.AllowImportant', true);

				$purifier = new \HTMLPurifier($config);
				$css = $purifier->purify('<style>' . $customCSS . '</style>');

				$output_css = $purifier->context->get('StyleBlocks');

				if (is_array($output_css) && count($output_css) == 1)
					$customize['custom_css'] = $output_css[0];
			}
		}

		$clientPaths = $this->config->application->clientsUploadsDir;

		$uploadBase = $clientPaths->path . 'order.' . $order->id . '.';
		if (file_exists($uploadBase . 'logo.png') && !isset($removedImages['logo']))
			$this->view->customLogo = $clientPaths->uri . 'order.' . $order->id . '.logo.png';

		$this->view->huntCss = $order->getCSS($clientPaths, $customize, $removedImages);
		if ($order->id == 1176 || /* TODO remove that */ $order->id == NcrController::ORDER_ID) {
			$this->view->ncr = true;
			$this->view->headerHTML = '<link href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,700" rel="stylesheet">';
		}
		$this->view->loggedIn = true;
		$this->view->isLeader = false;
		$this->view->timeToEnd = 3600;
		$this->view->user_info = [
			'email' => 'support@strayboots.com',
			'activation' => 'sb-51735'
		];
		$this->view->teamStatus = [
			'position'	=> 2,
			'score'		=> 15,
			'name'		=> 'Preview'
		];
		$this->view->question = [
			'currentPos'	=> 3,
			'numQuestions'	=> 25
		];
		$this->view->firebase = [
			'appLoc' => [
				/*0, 2, true,
				'orderHunt' => (int)1,*/
				'timeLeft' => 999999
			]
		];
		$this->view->qtimeout = [100, 500];

		$this->assets->collection('style')->addCss('/css/app/play.css');
		$this->assets->collection('script')->addJs('/js/app/custom.preview.js');
	}

	public function unsubscribeAction() 
	{
		$m = $this->request->get('m');
		$email = $this->crypt->decryptBase64($m);
		$this->view->msg = 'Something went wrong';
		if (!$email) {
			return;
		}

		$ifIsset = \UnsubscribingList::findFirstByEmail($email);
		if ($ifIsset) {
			$this->view->msg = 'You already unsubscribed';
			return;
		}

		$unsubscribingList = new \UnsubscribingList();
		$unsubscribingList->email = $email;
		
		if ($unsubscribingList->save() !== false) {
			$this->view->msg = 'You unsubscribed from the mailing list';
		}	
	}
}
