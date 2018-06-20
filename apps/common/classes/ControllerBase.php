<?php

class ControllerBase extends Phalcon\Mvc\Controller
{
	protected $user;
	protected $client;
	protected $supplier;

	public function initialize()
	{
		//$this->session->remove("userID");die;
		//$this->flash->clear();
		$this->view->bodyClass = '';
		$this->view->hiddenWrapper = false;
		$this->view->dateFormat = 'm/d/Y';
		$this->view->timeFormat = 'm/d/Y h:i A \E\S\T';

		$this->user = false;
		// check session for valid user id and get user info
		if (preg_match('/^\d+$/', ($id = $this->session->get('userID'))))
			$this->user = Users::findFirstById($id);
		// if we have no session check cookie and set session if succeed
		if ($this->user === false) {
			try {
				if (preg_match('/^\d+$/', ($identifier = $this->cookies->get('uid')->getValue()))) {
					$identifier = (int)$identifier;
					if ($identifier > 0) {
						if (preg_match('/^[a-z0-9]{32}$/', ($verifier = $this->cookies->get('uverifier')->getValue()))) {
							if ($this->redis->get(SB_PREFIX . 'uverifier:' . $verifier) === $identifier) {
								if ($this->user = Users::findFirstById($identifier)) {
									if (!$this->user->active) // TODO: remove this?
										throw new Exception();
									$this->session->set('userID', $identifier);
								} else throw new Exception();
							} else throw new Exception();
						} else throw new Exception();
					}
				}
			} catch (\Exception $e) {
				// remove cookie on error and set user to false to be safe
				$secure = false;//$di->get('request')->isSecureRequest();
				$this->cookies->set('uid', '', 0, '/', $secure, '.strayboots.com', true)
								->set('uverifier', '', 0, '/', $secure, '.strayboots.com', true);
				$this->user = false;
			}
		}

		$this->client = false;
		// check session for valid client id and get client info
		if (preg_match('/^\d+$/', ($id = $this->session->get('clientID'))))
			$this->client = Clients::findFirstById($id);
		// if we have no session check cookie and set session if succeed
		if ($this->client === false) {
			try {
				if (preg_match('/^\d+$/', ($identifier = $this->cookies->get('cid')->getValue()))) {
					$identifier = (int)$identifier;
					if ($identifier > 0) {
						if (preg_match('/^[a-z0-9]{32}$/', ($verifier = $this->cookies->get('cverifier')->getValue()))) {
							if ($this->redis->get(SB_PREFIX . 'cverifier:' . $verifier) === $identifier) {
								if ($this->client = Clients::findFirstById($identifier)) {
									if (!$this->client->active) // TODO: remove this?
										throw new Exception();
									$this->session->set('clientID', $identifier);
								} else throw new Exception();
							} else throw new Exception();
						} else throw new Exception();
					}
				}
			} catch (\Exception $e) {
				// remove cookie on error and set client to false to be safe
				$secure = false;//$di->get('request')->isSecureRequest();
				$this->cookies->set('cid', '', 0, '/', $secure, '.strayboots.com', true)
								->set('cverifier', '', 0, '/', $secure, '.strayboots.com', true);
				$this->client = false;
			}
		}
		$this->view->clientLogin = $this->client !== false;

		$this->supplier = false;
		// check session for valid supplier id and get supplier info
		if (preg_match('/^\d+$/', ($id = $this->session->get('supplierID'))))
			$this->supplier = Suppliers::findFirstById($id);
		// if we have no session check cookie and set session if succeed
		if ($this->supplier === false) {
			try {
				if (preg_match('/^\d+$/', ($identifier = $this->cookies->get('sid')->getValue()))) {
					$identifier = (int)$identifier;
					if ($identifier > 0) {
						if (preg_match('/^[a-z0-9]{32}$/', ($verifier = $this->cookies->get('sverifier')->getValue()))) {
							if ($this->redis->get(SB_PREFIX . 'sverifier:' . $verifier) === $identifier) {
								if ($this->supplier = Suppliers::findFirstById($identifier)) {
									if (!$this->supplier->active) // TODO: remove this?
										throw new Exception();
									$this->session->set('supplierID', $identifier);
								} else throw new Exception();
							} else throw new Exception();
						} else throw new Exception();
					}
				}
			} catch (\Exception $e) {
				// remove cookie on error and set supplier to false to be safe
				$secure = false;//$di->get('request')->isSecureRequest();
				$this->cookies->set('sid', '', 0, '/', $secure, '.strayboots.com', true)
								->set('sverifier', '', 0, '/', $secure, '.strayboots.com', true);
				$this->supplier = false;
			}
		}
		$this->view->supplierLogin = $this->supplier !== false;
	}

	public function afterExecuteRoute($event, $dispatcher)
	{
		$status = $this->response->getStatusCode();
		if (($status === false || substr($status, 0, 2) !== '30') && !$this->view->isDisabled()) {
			$this->view->controllerName = $controllerName = $this->dispatcher->getControllerName();
			$this->view->actionName = $actionName = $this->dispatcher->getActionName();

			if (SB_PRODUCTION || !isset($_COOKIE['_sb_'])) {
				/**/$cacheFile = 'cache/' . dechex(crc32('_' . STRAYBOOTS_BUILD . '_' . strtolower($this->dispatcher->getModuleName() . $controllerName . '_' . $actionName)));
				$cssFile = PUBLIC_PATH  . $cacheFile . '.css';
				$cssTime = $this->config->application->debug ? '' : (file_exists($cssFile) ? @filemtime($cssFile) : '');
				if (is_int($cssTime) && (time() - $cssTime < 7200)) {
					$this->view->assetsCache = $cacheFile;
				} else {
					$this->assets->collection('style')
							->setTargetPath($cssFile)
							->setTargetUri($cacheFile . '.css')
							->join(true)
							->addFilter(new \Phalcon\Assets\Filters\Cssmin());
					$this->assets->collection('script')
							->setTargetPath(PUBLIC_PATH  . $cacheFile . '.js')
							->setTargetUri($cacheFile . '.js')
							->join(true)
							->addFilter(new \Phalcon\Assets\Filters\Jsmin());
				}
				header("Link: </{$cacheFile}.css>; rel=preload; as=style", false);
				header("Link: </{$cacheFile}.js>; rel=preload; as=script", false);/**/
			}
			if ($this->router->getModuleName() === 'clients')
				header("Link: </img/logo.png>; rel=preload; as=image", false);

			/*foreach ($this->assets->getCss() as $resource)
				header("Link: <" . $resource->getPath() . ">; rel=preload; as=style", false);
			foreach ($this->assets->getJs() as $resource)
				header("Link: <" . $resource->getPath() . ">; rel=preload; as=script", false);
			header("Link: </img/logo.png>; rel=preload; as=image", false);*/
		}
	}

	public function jsonResponse($json)
	{
		$this->view->disable();
		//$response = new \Phalcon\Http\Response();
		//$response->setContentType('application/json', 'UTF-8');
		$this->response->setJsonContent($json);
		return $this->response;
	}

	public function generatePassword($length = 8)
	{
		$chars = 'abcdefghijklmnpqrstuvwxyz1234567890ABCDEFGHIJKLMNPQRSTUVWXYZ1234567890';
		return substr(str_shuffle($chars), 0, $length);
	}

	public function requireUser($redirect = true)
	{
		if (!($this->user instanceof Users && $this->user->active)) {
			/*$this->dispatcher->forward([
				'controller' => 'login',
				'action' => 'index'
			]);*/
			if ($redirect) {
				$this->session->set('redirect', $this->router->getRewriteUri());
				$this->response->redirect('login');
			}
			return true;
		}
		return false;
	}

	public function requireClient($redirect = true)
	{
		if (!($this->client instanceof Clients && $this->client->active)) {
			/*$this->dispatcher->forward([
				'controller' => 'login',
				'action' => 'index'
			]);*/
			if ($redirect) {
				$this->session->set('redirect', $this->router->getRewriteUri());
				$this->response->redirect('login');
			}
			return true;
		}
		return false;
	}

	public function requireSupplier($redirect = true)
	{
		if (!($this->supplier instanceof Suppliers && $this->supplier->active)) {
			/*$this->dispatcher->forward([
				'controller' => 'login',
				'action' => 'index'
			]);*/
			if ($redirect) {
				$this->session->set('redirect', $this->router->getRewriteUri());
				$this->response->redirect('login');
			}
			return true;
		}
		return false;
	}

	public function sendMail($to, $subject, $text = '', $html = null, $attachments = [], $options = [], $attachmentsOpts = []) {
		if (!is_array($to))
			$to = explode(',', $to);
		if (isset($options['bcc'])) {
			// hunt mails trustpilot kombina
			$to = array_unique(array_merge($to, is_array($options['bcc']) ? $options['bcc'] : explode(',', $options['bcc'])));
			unset($options['bcc']);
			if (isset($options['cc'])) {
				$options['bcc'] = $options['cc'];
				unset($options['cc']);
			}
		}
		ignore_user_abort(true);
		set_time_limit(120);
		$success = true;
		foreach ($to as $e) {
			try {
				$success = $this->mailer->sendMessage($this->config->mailgun->domain, [
					'from'		=> $this->config->mailgun->from, 
					'to'		=> $e,
					'subject'	=> $subject,
					'text'		=> $text,
					'html'		=> $html
				] + $options, [
					'attachment' => $attachments
				] + $attachmentsOpts) && $success;
			} catch(\Exception $e) {
				$success =  false;
			}
		}
		return /*$success*/ true;
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
		$cmd = "/usr/bin/jpegtran -optimize -progressive -copy " . ($remoteMetadata ? 'none' : 'all') . " -outfile " . escapeshellarg($path2) . ' ' . escapeshellarg($path) . " 2>&1";
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
				$response = "File wasn't created";
			}
		}
		return $response;
	}

	public function slugify($text) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// trim
		$text = trim($text, '-');

		// remove duplicate -
		$text = preg_replace('~-+~', '-', $text);

		return mb_strtolower($text);
	}
	
	/**
	 * Convert a long url to a short url using Bitly API
	 * @param $url
	 * @return String a Bit.ly url | bool false
	 */
	public function bitly($url)
	{
		$get = 'https://api-ssl.bitly.com/v3/shorten?login=' . $this->config->bitly->login . '&apiKey=' . $this->config->bitly->APIKey . '&longUrl=' . urlencode($url);
		if (is_object($response = json_decode(file_get_contents($get))))
			return $response->status_code == 200 ? $response->data->url : $url;
		return $url;
	}
}
