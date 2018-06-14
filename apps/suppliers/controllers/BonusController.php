<?php

namespace Play\Clients\Controllers;

use \OrderHunts,
	\BonusQuestions,
	DataTables\DataTable;

class BonusController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{
		$orderHunt = $id > 0 ? OrderHunts::findFirstByid((int)$id) : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id) {
				$this->flash->error("Order hunt was not found");
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$this->view->orderHunt = $orderHunt;
		$this->view->removable = !$orderHunt->isStarted();
		$this->view->tooMany = $orderHunt->countBonusQuestions() >= 5;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/clients/bonus.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($id = 0)
	{

		$orderHunt = $id > 0 ? OrderHunts::findFirstByid((int)$id) : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				throw new \Exception(404, 404);
		}

		$builder = $this->modelsManager->createBuilder()
							->columns('id, type, question, score')
							->from('BonusQuestions')
							->where("order_hunt_id = " . $orderHunt->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction($id = 0)
	{
		$orderHunt = $id > 0 ? OrderHunts::findFirstByid((int)$id) : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id) {
				$this->flash->error("Order hunt was not found");
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if ($orderHunt->countBonusQuestions() >= 5) {
				$this->flash->error("Order hunt is limited to 5 bonus questions");
				$this->response->redirect('orders');

				return;
		}

		$this->view->huntStarted = $orderHunt->isStarted();

		$this->view->orderHunt = $orderHunt;
		
		$this->assets->collection('script')->addJs('/js/clients/bonus.addedit.js');
	}

	/**
	 * Edits a bonus question
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		$bonus = $id > 0 ? BonusQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $bonus ? $bonus->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$bonus = false;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if (!$bonus) {
			$this->flash->error("bonus question doesn't exists " . $id);
			$this->response->redirect('orders');

			return;
		}

		$this->view->id = $bonus->id;
		$this->view->orderHunt = $bonus->OrderHunt;
		$this->view->huntStarted = $orderHunt->isStarted();
		
		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $bonus->id);
			$this->tag->setDefault("type", $bonus->type);
			$this->tag->setDefault("question", $bonus->question);
			$this->tag->setDefault("answers", $bonus->answers);
			$this->tag->setDefault("score", $bonus->score);
			
		}

		$this->assets->collection('script')->addJs('/js/clients/bonus.addedit.js');
	}

	/**
	 * Creates a new bonus
	 */
	public function createAction()
	{
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');
			return;
		}

		$bonus = new BonusQuestions();
		$bonus->order_hunt_id = $this->request->getPost("order_hunt_id", 'int');
		$bonus->type = $this->request->getPost("type", 'int');
		$bonus->question = trim($this->request->getPost("question", 'string'));
		$bonus->answers = $this->request->getPost("answers");
		$bonus->score = $bonus->type == BonusQuestions::TypePrivate ? null : $this->request->getPost("score", 'int');

		$orderHunt = $bonus ? $bonus->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;
		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id) {
				$this->flash->error("Order hunt wasn't found");
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}
		
		if ($orderHunt->countBonusQuestions() >= 5) {
				$this->flash->error("Order hunt is limited to 5 bonus questions");
				$this->response->redirect('orders');

				return;
		}

		if ($orderHunt->isStarted()) {
			$this->flash->error("Bonus question cannot be created");
			$this->response->redirect('bonus/' . $orderHunt->id);
			return;
		} else if ($bonus->save()) {
			if (substr($orderHunt->start, 0, 10) == date('Y-m-d') && $orderHunt->finish > date("Y-m-d H:i:s")) {
				$preevent = new \PreeventTask();
				$preevent->mainAction([0, 0]);
			}
		} else {
			foreach ($bonus->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "bonus",
				'action' => 'new',
				'params' => [$orderHunt->id]
			]);

			return;
		}

		$this->flash->success("Bonus question was created successfully");

		$this->response->redirect('bonus/' . $orderHunt->id);

	}

	/**
	 * Saves a bonus question edited
	 *
	 */
	public function saveAction()
	{
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');
			return;
		}

		$id = $this->request->getPost("id", 'int');
		$bonus = $id > 0 ? BonusQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $bonus ? $bonus->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$bonus = false;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if (!$bonus) {
			$this->flash->error("bonus question doesn't exists " . $id);
			$this->response->redirect('orders');

			return;
		}

		$bonus->type = $this->request->getPost("type", 'int');
		$bonus->score = $bonus->type == BonusQuestions::TypePrivate ? null : $this->request->getPost("score", 'int');
		$bonus->question = trim($this->request->getPost("question", 'string'));
		$bonus->answers = $this->request->getPost("answers");

		if ($orderHunt->isStarted()) {
			$this->flash->error("Bonus question cannot be saved");
			$this->response->redirect('bonus/' . $orderHunt->id);
			return;
		} else if ($bonus->save()) {
			if (substr($orderHunt->start, 0, 10) == date('Y-m-d') && $orderHunt->finish > date("Y-m-d H:i:s")) {
				$preevent = new \PreeventTask();
				$preevent->mainAction([0, 0]);
			}
		} else {

			foreach ($bonus->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "bonus",
				'action' => 'edit',
				'params' => [$bonus->id]
			]);

			return;
		}

		$this->flash->success("Bonus question was updated successfully");

		$this->response->redirect('bonus/' . $orderHunt->id);

	}

	/**
	 * Deletes a bonus question
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		$bonus = $id > 0 ? BonusQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $bonus ? $bonus->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$bonus = false;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error("This hunt was canceled");
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if (!$bonus) {
			$this->flash->error("Bonus question was not found");
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isStarted()) {
			$this->flash->error("Bonus question cannot be deleted");
		} else if ($bonus->delete()) {
			if (substr($orderHunt->start, 0, 10) == date('Y-m-d') && $orderHunt->finish > date("Y-m-d H:i:s")) {
				$preevent = new \PreeventTask();
				$preevent->mainAction([0, 0]);
			}
			$this->flash->success("Bonus question was deleted successfully");
		} else {
			foreach ($bonus->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('bonus/' . $orderHunt->id);
	}

}
