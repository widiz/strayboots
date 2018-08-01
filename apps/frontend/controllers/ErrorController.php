<?php

namespace Play\Frontend\Controllers;

class ErrorController extends ControllerBase
{

	public function e404Action()
	{
		if ($loginPage = \LoginPages::findFirstBySlug(trim($_GET['_url'], ' /'))) {
			define('ORDER_HUNT_OVERRIDE', $loginPage->order_hunt_id);
			define('OVERRIDE_STANDARDLOGIN', 1);
			if (!empty($loginPage->title))
				define('TITLE_OVERRIDE', $loginPage->title);
			if (!empty($loginPage->welcome_title))
				define('OVERRIDE_WELCOME_TITLE', $loginPage->welcome_title);
			$this->view->currentURL = $this->escaper->escapeHtmlAttr($loginPage->slug);
			return $this->dispatcher->forward([
				'controller'	=> 'index',
				'action'		=> 'index'
			]);
		}
		$this->response->setStatusCode(404);
	}

	public function e500Action()
	{
		$this->response->setStatusCode(500);
	}

}
