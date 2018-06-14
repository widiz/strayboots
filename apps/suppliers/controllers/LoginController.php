<?php

namespace Play\Suppliers\Controllers;

use \Suppliers,
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
		if (!$this->request->isPost())
			return $this->response->redirect('login');

		$email = $this->request->getPost('email', 'email');
		$password = $this->request->getPost('password');
		try {
			if (!$this->security->checkToken())
				throw new Exception();
			if (empty($email) || empty($password))
				throw new Exception();
			$supplier = Suppliers::findFirstByEmail($email);
			if ($supplier && $supplier->checkLogin($password, true)) {
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

	public function adminAction($id)
	{
		if ($this->requireUser())
			return $this->response->redirect('login');

		try {
			$supplier = Suppliers::findFirstById($id);
			if ($supplier && $supplier->active) {
				Suppliers::setSupplierLogin($supplier, false);
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
		Suppliers::logout();
		return $this->response->redirect('login');
	}

}

