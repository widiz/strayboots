<?php

namespace Play\Frontend\Controllers;

use \Players,
	\OrderHunts,
	\Exception;

class ControllerBase extends \Phalcon\Mvc\Controller
{
	protected $player;
	protected $team;
	protected $orderHunt;
	protected $hunt;
	private $multiLang = '';
	public $dateFormat = 'm/d/Y';
	public $timeFormat = 'm/d/Y h:i A \E\S\T';

	public function initialize()
	{
		$time = time();

		if (preg_match('/^\d+$/', ($lang = $this->request->getQuery('lang')))) {
			if ($lang == 0 || isset($this->config->altLang[$lang - 1]))
				$this->cookies->set('lang', (int)$lang, $time + 864e4, '/', false/*is secure?*/, $_SERVER['HTTP_HOST'], true);
		}

		$orderHunt = $this->team = $this->player = false;

		if (defined('ORDER_HUNT_OVERRIDE')) {
			$orderHunt = OrderHunts::findFirstById(ORDER_HUNT_OVERRIDE);
			if ($orderHunt && (defined('OVERRIDE_LOGIN_EMAIL') || $orderHunt->isCustomLogin()))
				define('ORDER_HUNT_CUSTOM_LOGIN_ID_OVERRIDE', $orderHunt->id);
		}

		// check session for valid player id and get player info
		if (preg_match('/^\d+$/', ($id = $this->session->get('playerID')))) {
		//$id = mt_rand(40, 200);
			if ($this->player = Players::findFirstById($id)) {
				$this->team = $this->player->Team;
				$orderHunt = OrderHunts::findFirstById($this->team->order_hunt_id);
				if ($time >= (is_null($orderHunt->expire) ? strtotime($orderHunt->start) + 604800 /* one week */ : strtotime($orderHunt->expire))) {
					Players::logout();
					$orderHunt = $this->team = $this->player = false;
				}
			}
		//if (!$this->player)return $this->initialize();
		}
		// if we have no session check cookie and set session if succeed
		if ($this->player === false) {
			try {
				if (preg_match('/^\d+$/', ($identifier = $this->cookies->get('pid')->getValue()))) {
					$identifier = (int)$identifier;
					if ($identifier > 0) {
						if (preg_match('/^[a-z0-9]{32}$/', ($verifier = $this->cookies->get('pverifier')->getValue()))) {
							if ($this->redis->get(SB_PREFIX . 'pverifier:' . $verifier) === $identifier) {
								if ($this->player = Players::findFirstByid($identifier)) {
									$this->team = $this->player->Team;
									$orderHunt = OrderHunts::findFirstById($this->team->order_hunt_id);
									if ($time < (is_null($orderHunt->expire) ? strtotime($orderHunt->start) + 604800 /* one week */ : strtotime($orderHunt->expire)))
										$this->session->set('playerID', $identifier);
									else throw new Exception();
								}
								else throw new Exception();
							} else throw new Exception();
						} else throw new Exception();
					}
				}
			} catch (\Exception $e) {
				// remove cookie on error and set player to false to be safe
				$secure = false;//$di->get('request')->isSecureRequest();
				$this->cookies->set('pid', '', 0, '/', $secure, '.strayboots.com', true)
								->set('pverifier', '', 0, '/', $secure, '.strayboots.com', true);
				$orderHunt = $this->team = $this->player = false;
			}
		}

		$this->setOrderHunt($orderHunt);

		$this->response
				->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
				//->setHeader('Cache-Control', 'post-check=0, pre-check=0')
				->setHeader('Pragma', 'no-cache');
	}

	public function setOrderHunt($orderHunt)
	{
		if ($orderHunt !== false) {

			$this->hunt = $orderHunt->Hunt;

			$multilang = $this->hunt->multilang;

			if ($orderHunt->isCanceled()) {
				Players::logout();
				$orderHunt = $this->team = $this->player = false;
				$this->view->leaderboardDisabled = $this->view->loggedIn = $this->view->isMultiHunt = false;
				$multilang = 0;
			} else {
				if ($orderHunt->isDurationFinish()) {
					$orderHunt->finish = date('Y-m-d H:i:s', ($this->team ? strtotime($this->team->activation) : time()) + $this->hunt->getDurationMinutes() * 60);
				}
				$this->view->leaderboardDisabled = $orderHunt->isLeaderBoardDisabled();
				$this->view->isMultiHunt = $orderHunt->isMultiHunt();
			}
		} else {
			$this->view->leaderboardDisabled = $this->view->loggedIn = $this->view->isMultiHunt = false;
			$multilang = 0;
		}

		$this->orderHunt = $orderHunt;

		$translation = [];
		$lang = $this->cookies->get('lang')->getValue();
		$multilang = $lang !== null ? (int)$lang : ($multilang > 0 ? $multilang : $this->config->defLang);
		if ($multilang > 0 && isset($this->config->altLang->{$multilang - 1})) {
			if ($this->multiLang = $this->config->altLang->{$multilang - 1}) {
				$translation = require __DIR__ . '/../translations/' . $this->multiLang . '.php';
				$this->assets->collection('script')->addJs('/js/app/translate.' . $this->multiLang . '.js');
			} else {
				$this->multiLang = '';
			}
		}

		$this->view->multiLang = $this->multiLang ? $multilang : 0;

		$translation['lang.en'] = 'English';
		$translation['lang.pt'] = 'Português';
		$translation['lang.he'] = 'עברית';
		//$translation['lang.in'] = 'हिन्दी';

		$this->view->t = new \Phalcon\Translate\Adapter\NativeArray([
			'content' => $translation
		]);
	}

	public function afterExecuteRoute($event, $dispatcher)
	{
		$status = $this->response->getStatusCode();
		if (($status === false || substr($status, 0, 2) !== '30') && !$this->view->isDisabled()) {
			$this->view->controllerName = $controllerName = $this->dispatcher->getControllerName();

			$time = time();

			if (defined('TITLE_OVERRIDE') && !empty(TITLE_OVERRIDE))
				$this->view->customTitle = TITLE_OVERRIDE;

			if ($this->orderHunt !== false) {
				if ($this->player !== false) {
					if (!isset($this->view->isLeader))
						$this->view->isLeader = $this->player->isLeader();
					$this->view->user_info = [
						'email' => $this->player->email,
						'activation' => $this->view->isLeader ? $this->team->activation_leader : $this->team->activation_player
					];
					$this->view->loggedIn = true;
					if ($time >= strtotime($this->orderHunt->start)) {
						if (($timeToEnd = strtotime($this->orderHunt->finish) - $time - 3) > 0)
							$this->view->timeToEnd = $timeToEnd;
					}
					if ($controllerName != 'chat' && isset($this->view->firebase)) {
						$this->view->order_hunt_id = $this->orderHunt->id;
						$this->assets->collection('script')->addJs('/js/app/chat.listener.js');
					}
				}
				$clientPaths = $this->config->application->clientsUploadsDir;
				$uploadBase = $clientPaths->path . 'order.' . $this->orderHunt->order_id . '.';
				$cache = $this->redis;
				$key = SB_PREFIX . 'css:' . $this->orderHunt->order_id;
				if (($huntCss = $cache->get($key)) === false) {
					$huntCss = $this->orderHunt->Order->getCSS($clientPaths);
					$cache->set($key, $huntCss, 7200);
				}
				if ($this->orderHunt->order_id == 1176 || /* TODO remove that */ $this->orderHunt->order_id == NcrController::ORDER_ID) {
					$this->view->ncr = true;
					$this->view->headerHTML = '<link href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,700" rel="stylesheet">';
				}
				if (defined('OVERRIDE_STANDARDLOGIN'))
					$huntCss .= '#activation-email{visibility:visible;display:block}#activation-fb{display:none}';
				if (!empty($huntCss))
					$this->view->huntCss = $huntCss;
				if (file_exists($uploadBase . 'logo.png'))
					$this->view->customLogo = $clientPaths->uri . 'order.' . $this->orderHunt->order_id . '.logo.png';
			} else {
				$this->view->isLeader = false;
			}

			if ($this->multiLang)
				$this->assets->collection('style')->addCss('/css/app/custom.' . $this->multiLang . '.css');

			if (SB_PRODUCTION || !isset($_COOKIE['_sb_'])) {
				/**/$cacheFile = 'cache/' . dechex(crc32(SB_PREFIX . $this->multiLang . STRAYBOOTS_BUILD . '_' . strtolower($controllerName . '_' . $this->dispatcher->getActionName()) . (defined('cacheFileId') ? cacheFileId : '')));
				$cssTime = $this->config->application->debug ? '' : @filemtime(PUBLIC_PATH  . $cacheFile . '.css');
				if (is_int($cssTime) && ($time - $cssTime < 7200)) {
					$this->view->assetsCache = $cacheFile;
				} else {
					$this->assets->collection('style')
							->setTargetPath(PUBLIC_PATH  . $cacheFile . '.css')
							->setTargetUri($cacheFile . '.css')
							->join(true)
							->addFilter(new \Phalcon\Assets\Filters\Cssmin());
					$this->assets->collection('script')
							->setTargetPath(PUBLIC_PATH  . $cacheFile . '.js')
							->setTargetUri($cacheFile . '.js')
							->join(true)
							->addFilter(new \Phalcon\Assets\Filters\Jsmin());
				}
				header('Link: </' .$cacheFile . '.css>; rel=preload; as=style', false);
				header('Link: </' .$cacheFile . '.js>; rel=preload; as=script', false);/**/
			}
			header('Link: </img/logo.png>; rel=preload; as=image', false);

			/*$this->view->player = $this->player;
			$this->view->team = $this->team;*/
		}
	}

	public function jsonResponse($json)
	{
		$this->view->disable();
		$response = new \Phalcon\Http\Response();
		//$response->setContentType('application/json', 'UTF-8');
		$response->setJsonContent($json);
		return $response;
	}

	public function requirePlayer()
	{
		if (!($this->player instanceof Players)) {
			/*$this->dispatcher->forward([
				'controller' => 'index',
				'action' => 'index'
			]);*/
			$this->response->redirect('/');
			return true;
		}
		return false;
	}

	/**
	 * pngquant
	 * @param string $path
	 *
	 * @return bool
	 */
	public function pngquant($path)
	{
		$path = escapeshellarg($path);
		return @shell_exec("/usr/local/bin/pngquant --force --skip-if-larger --quality 40-100 --speed 1 --output {$path} {$path} 2>&1");
	}

	/**
	 * jpegtran
	 * @param string $path
	 *
	 * @return bool
	 */
	public function jpegtran($path, $remoteMetadata = false)
	{
		$path2 = $path . '.jtmp' . mt_rand(1e3, 1e6);
		$cmd = '/usr/bin/jpegtran -optimize -progressive -copy ' . ($remoteMetadata ? 'none' : 'all') . ' -outfile ' . escapeshellarg($path2) . ' ' . escapeshellarg($path) . ' 2>&1';
		$response = @shell_exec($cmd);
		if (empty($response) && file_exists($path2)) {
			rename($path2, $path);
		} else {
			$response = @shell_exec($cmd);
			if (file_exists($path2)) {
				if (empty($response))
					rename($path2, $path);
				else
					unlink($path2);
			} else if (empty($response)) {
				$response = 'File wasn\'t created';
			}
		}
		return $response;
	}

	public function sendMail($to, $subject, $text = '', $html = null, $attachments = [], $options = []) {
		if (is_array($to) ? in_array('support@strayboots.com', $to) : strpos($to, 'support@strayboots.com') !== false) {
			if (isset($options['bcc'])) {
				$options['bcc'] = implode(',', array_unique(array_merge(['support@strayboots.com'], is_array($options['bcc']) ? $options['bcc'] : explode(',', $options['bcc']))));
			} else {
				$options['bcc'] = 'support@strayboots.com';
			}
		}
		try {
			return $this->mailer->sendMessage($this->config->mailgun->domain, [
				'from'		=> $this->config->mailgun->from, 
				'to'		=> $to,
				'subject'	=> $subject,
				'text'		=> $text,
				'html'		=> $html
			] + $options, [
				'attachment' => $attachments
			]);
		} catch(\Exception $e) {
			return false;
		}
	}
}
