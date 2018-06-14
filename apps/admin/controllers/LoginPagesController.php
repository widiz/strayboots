<?php

namespace Play\Admin\Controllers;

use \LoginPages,
	DataTables\DataTable;

class LoginPagesController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/loginpages.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns(['lp.id', 'lp.slug', 'lp.title', 'lp.order_hunt_id', 'o.name as order_name', 'h.name as hunt_name'])
							->from(['lp' => 'LoginPages'])
							->leftJoin('OrderHunts', 'oh.id = lp.order_hunt_id', 'oh')
							->leftJoin('Hunts', 'h.id = oh.hunt_id', 'h')
							->leftJoin('Orders', 'o.id = oh.order_id', 'o');

		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction()
	{
		if ($this->requireUser())
			return true;

		$this->addEdit();
	}

	/**
	 * Edits a login page
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$loginPage = LoginPages::findFirstByid($id);
		if (!$loginPage) {
			$this->flash->error('login page was not found');

			$this->response->redirect('login_pages');

			return;
		}
			
		$this->view->id = $loginPage->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $loginPage->id);
			$this->tag->setDefault('slug', $loginPage->slug);
			$this->tag->setDefault('title', $loginPage->title);
			$this->tag->setDefault('welcome_title', $loginPage->welcome_title);
			$this->tag->setDefault('order_hunt_id', $loginPage->order_hunt_id);

		}

		$this->addEdit();
	}

	private function addEdit()
	{
		$orderHunts = [];
		foreach ($this->db->fetchAll('SELECT oh.id, o.id as orid, o.name as oname, h.name as hname FROM order_hunts oh LEFT JOIN hunts h ON h.id = oh.hunt_id LEFT JOIN orders o ON o.id = oh.order_id ORDER BY oh.id DESC', \Phalcon\Db::FETCH_ASSOC) as $oh) {
			$orderKey = '#' . $oh['orid'] . ' ' . $oh['oname'];
			if (!isset($orderHunts[$orderKey]))
				$orderHunts[$orderKey] = [];
			$orderHunts[$orderKey][$oh['id']] = '#' . $oh['id'] . ' ' . $oh['hname'];
		}
		$this->view->orderHunts = $orderHunts;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/js/admin/loginpages.addedit.js');

		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css');
	}

	/**
	 * Creates a new login page
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('login_pages');
			return;
		}

		$loginPage = new LoginPages();
		$loginPage->slug = strip_tags($this->request->getPost('slug', 'trim'));
		$loginPage->title = trim($this->request->getPost('title', 'string'));
		$loginPage->welcome_title = trim($this->request->getPost('welcome_title', 'string'));
		$loginPage->order_hunt_id = $this->request->getPost('order_hunt_id', 'int');
		
		if (!$loginPage->save()) {
			foreach ($loginPage->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'login_pages',
				'action' => 'new'
			]);
			return;
		}

		$this->flash->success('login page created successfully');

		$this->response->redirect('login_pages');
	}

	/**
	 * Saves a login page edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('login_pages');
			return;
		}

		$id = $this->request->getPost('id', 'int');
		$loginPage = LoginPages::findFirstByid($id);

		if (!$loginPage) {
			$this->flash->error('login page does not exist ' . $id);
			
			$this->response->redirect('login_pages');
			return;
		}

		$loginPage->slug = strip_tags($this->request->getPost('slug', 'trim'));
		$loginPage->title = trim($this->request->getPost('title', 'string'));
		$loginPage->welcome_title = trim($this->request->getPost('welcome_title', 'string'));
		$loginPage->order_hunt_id = $this->request->getPost('order_hunt_id', 'int');
		
		if (!$loginPage->save()) {

			foreach ($loginPage->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'login_pages',
				'action' => 'edit',
				'params' => [$loginPage->id]
			]);
			return;
		}

		$this->flash->success('login page was updated successfully');

		$this->response->redirect('login_pages');
	}

	/**
	 * Deletes a login page
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$loginPage = LoginPages::findFirstByid($id);
		if (!$loginPage) {
			$this->flash->error('login page was not found');

			$this->response->redirect('login_pages');
			return;
		}

		if ($loginPage->delete()) {
			$this->flash->success('login page was deleted successfully');
		} else {
			foreach ($loginPage->getMessages() as $message) {
				$this->flash->error($message);
			}
		}

		$this->response->redirect('login_pages');
	}

}
