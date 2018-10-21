<?php
 
namespace Play\Admin\Controllers;

use \Blocked,
	\PDO,
	DataTables\DataTable;

class BlockedController extends \ControllerBase
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
				->addJs('/js/admin/blocked.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('email')
							->from('Blocked');
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
	}

	/**
	 * Edits a blocked email
	 *
	 * @param string $email
	 */
	public function editAction($email)
	{
		if ($this->requireUser())
			return true;

		$blocked = Blocked::findFirstByEmail($email);
		if (!$blocked) {
			$this->flash->error('Email not found');

			$this->response->redirect('blocked');

			return;
		}

		if (!$this->request->isPost())
			$this->tag->setDefault('email', $blocked->email);

		$this->view->email = $blocked->email;
	}

	/**
	 * Creates a new blocked email
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('blocked');

			return;
		}

		$blocked = new Blocked();
		$blocked->email = $this->request->getPost('email', 'email');

		if (!$blocked->save()) {
			foreach ($blocked->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'blocked',
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success('Email blocked successfully');

		$this->response->redirect('blocked');
	}

	/**
	 * Saves a blocked email edited
	 *
	 * @param string $email
	 */
	public function saveAction($email)
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('blocked');

			return;
		}

		$blocked = Blocked::findFirstByEmail($email);
		if (!$blocked) {
			$this->flash->error('Email does not exist ' . $email);

			$this->response->redirect('blocked');

			return;
		}

		$oldEmail = $blocked->email;
		$blocked->email = $this->request->getPost('email', 'email');

		if (!$blocked->validation()) {
			foreach ($blocked->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'blocked',
				'action' => 'edit',
				'params' => [$blocked->email]
			]);

			return;
		}

		$this->db->update('blocked', ['email'], [$blocked->email], [
			'conditions'=> 'email = ?',
			'bind'		=> [$oldEmail],
			'bindTypes'	=> [PDO::PARAM_STR]
		], [PDO::PARAM_STR]);


		$this->flash->success('Email updated successfully');

		$this->response->redirect('blocked');
	}

	/**
	 * Deletes a blocked email
	 *
	 * @param string $email
	 */
	public function deleteAction($email)
	{
		if ($this->requireUser())
			return true;

		$blocked = Blocked::findFirstByEmail($email);
		if (!$blocked) {
			$this->flash->error('Email not found');

			$this->response->redirect('blocked');

			return;
		}

		if ($blocked->delete()) {
			$this->flash->success('Email deleted successfully');
		} else {
			foreach ($blocked->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('blocked');
	}

}
