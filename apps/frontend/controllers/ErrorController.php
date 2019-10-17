<?php

namespace Play\Frontend\Controllers;

class ErrorController extends ControllerBase
{

	public function e404Action()
	{
		if ($loginPage = \LoginPages::findFirstBySlug(trim($_GET['_url'], ' /'))) {
			if ($loginPage->slug === 'jeddah' || $loginPage->slug === 'jedda-en' || $loginPage->slug === 'jeddah-en') {
				define('SAUDI_ARABIA_HUNT', 1);
				define('SAUDI_ARABIA_HUNT_AUTO_ARAB', $loginPage->slug === 'jeddah');
				if (isset($_GET['lang']) && strlen($_GET['lang'])) {
					if ($_GET['lang'] == 0 && $loginPage->slug === 'jeddah') {
						return $this->response->redirect('jedda-en');
					} elseif ($_GET['lang'] == 3 && ($loginPage->slug === 'jedda-en' || $loginPage->slug === 'jeddah-en')) {
						return $this->response->redirect('jeddah');
					}
				}
			}
			$orderHunt = $loginPage->OrderHunt;
			if ($orderHunt && !$orderHunt->isCanceled()) {
				define('ORDER_HUNT_OVERRIDE', $loginPage->order_hunt_id);
				define('ORDER_HUNT_OVERRIDE_USE_ACTIVATION_CODE', $loginPage->isActivationCodeLogin());
				define('OVERRIDE_STANDARDLOGIN', 1);
				if (!empty($loginPage->title))
					define('TITLE_OVERRIDE', $loginPage->title);
				if (!empty($loginPage->sub_title))
					define('SUB_TITLE_OVERRIDE', $loginPage->sub_title);
				if (!empty($loginPage->welcome_title))
					define('OVERRIDE_WELCOME_TITLE', $loginPage->welcome_title);
				if (!empty($loginPage->email_login))
					define('OVERRIDE_LOGIN_EMAIL', $loginPage->id);
				$this->view->currentURL = $this->escaper->escapeHtmlAttr($loginPage->slug);
				return $this->dispatcher->forward([
					'controller'	=> 'index',
					'action'		=> 'index'
				]);
			}
		}
		$this->response->setStatusCode(404);
	}

	public function e500Action()
	{
		$this->response->setStatusCode(500);
	}

}
