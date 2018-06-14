<?php

namespace Play\Admin\Controllers;

use \Users,
	\Exception;

class LoginController extends \ControllerBase
{

	public function indexAction()
	{
		$this->view->bodyClass = 'gray-bg';
		$this->view->hiddenWrapper = true;
	}

	public function loginAction()
	{
		/*$x = new Users;
		$x->email = "shay12tg@gmail.com";
		$x->first_name='Shay';
		$x->last_name='Hananashvili';
		$x->password=$this->security->hash('123123');
		$x->save();*/

		if (!$this->request->isPost())
			return $this->response->redirect('login');

		$email = $this->request->getPost('email', 'email');
		$password = $this->request->getPost('password');
		try {
			if (!$this->security->checkToken())
				throw new Exception();
			if (empty($email) || empty($password))
				throw new Exception();
			$user = Users::findFirstByEmail($email);
			if ($user && $user->checkLogin($password, true)) {
				$this->flash->success("Welcome!");
				if (!empty($redirect = $this->session->get('redirect', '', true))) {
					header("Location: " . $redirect);
					exit;
				}
				return $this->response->redirect('index');
			}
			throw new Exception();
		} catch(Exception $e) {
			$this->flash->error("Login failed");
			return $this->response->redirect('login');
		}
	}

	public function logoutAction()
	{
		Users::logout();
		return $this->response->redirect('login');
	}

}

