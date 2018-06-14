<?php

namespace Play\Admin\Controllers;

use \OrderHunts,
	\CustomEvents,
	\Phalcon\Db,
	\Exception;

class CustomEventsController extends \ControllerBase
{

	/**
	 * Index action
	 */
	public function indexAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$orderHunt = OrderHunts::findFirstByid((int)$id);
		if (!$orderHunt)
			return $this->response->redirect('orders');

		$this->view->orderHunt = $orderHunt;
		$this->view->order = $order = $orderHunt->Order;
		$this->view->client = $order->Client;

		$this->view->data = $this->db->fetchAll('SELECT e.*, t.name as teamname FROM custom_events e LEFT JOIN teams t ON t.id = e.team_id WHERE e.order_hunt_id=' . $orderHunt->id . ' ORDER BY id ASC', Db::FETCH_ASSOC);

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/customevents.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	/**
	 * Displays the creation form
	 */
	public function newAction($id = 0)
	{
		if ($this->requireUser())
			return true;

		$id = (int)$id;

		$orderHunt = OrderHunts::findFirstByid($id);
		if (!$orderHunt)
			return $this->response->redirect('orders');

		$this->tag->setDefault('order_hunt_id', $orderHunt->id);

		$this->view->orderHunt = $orderHunt;

		$this->addEdit($orderHunt->id);
	}

	/**
	 * Edits a order hunt
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$customEvent = CustomEvents::findFirstByid($id);
		if (!$customEvent) {
			$this->flash->error('Custom event was not found');

			$this->response->redirect('orders');

			return;
		}

		$this->view->id = $customEvent->id;
		$this->view->orderHunt = $orderHunt = $customEvent->OrderHunt;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $customEvent->id);
			$this->tag->setDefault('order_hunt_id', $customEvent->order_hunt_id);
			$this->tag->setDefault('team_id', $customEvent->team_id);
			$this->tag->setDefault('title', $customEvent->title);
			$this->tag->setDefault('score', $customEvent->score);
			
		}
		$this->addEdit($orderHunt->id);
	}

	private function addEdit($id)
	{
		$this->assets->collection('script')
				->addJs('/js/admin/customevents.addedit.js')
				->addJs('/template/js/plugins/select2/select2.full.min.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css');
	}

	/**
	 * Creates a new order hunt
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$customEvent = new CustomEvents();
		$customEvent->order_hunt_id = $this->request->getPost('order_hunt_id', 'int');
		$customEvent->team_id = $this->request->getPost('team_id', 'int');
		$customEvent->title = $this->request->getPost('title', 'trim');
		$customEvent->score = $this->request->getPost('score', 'int');
		if (empty($customEvent->team_id))
			$customEvent->team_id = null;
		
		if ($customEvent->save()) {
			$this->flash->success('Custom event was created successfully');
			$this->response->redirect('custom_events/index/' . $customEvent->order_hunt_id);
		} else {
			foreach ($customEvent->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'custom_events',
				'action' => 'new',
				'params' => [$customEvent->order_hunt_id]
			]);
		}
	}

	/**
	 * Saves a order hunt edited
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$customEvent = CustomEvents::findFirstByid($id);

		if (!$customEvent) {
			$this->flash->error('Custom event does not exist ' . $id);

			$this->response->redirect('orders');

			return;
		}

		$customEvent->order_hunt_id = $this->request->getPost('order_hunt_id', 'int');
		$customEvent->team_id = $this->request->getPost('team_id', 'int');
		$customEvent->title = $this->request->getPost('title', 'trim');
		$customEvent->score = $this->request->getPost('score', 'int');
		if (empty($customEvent->team_id))
			$customEvent->team_id = null;
		
		if ($customEvent->save()) {
			$this->flash->success('Order hunt was updated successfully');
			$this->response->redirect('custom_events/index/' . $customEvent->order_hunt_id);
		} else {
			foreach ($customEvent->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'custom_events',
				'action' => 'edit',
				'params' => [$customEvent->id]
			]);
		}
	}

	/**
	 * Deletes a order hunt
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$customEvent = CustomEvents::findFirstByid((int)$id);
		if (!$customEvent) {
			$this->flash->error('Custom event was not found');

			$this->response->redirect('orders');

			return;
		}

		$order_hunt_id = $customEvent->order_hunt_id;

		try {
			if ($customEvent->delete()) {
				$this->flash->success('Custom event was deleted successfully');
			} else {
				foreach ($customEvent->getMessages() as $message) 
					$this->flash->error($message);
			}
		} catch (Exception $e) {
			$this->flash->error('Failed to delete custom event');
		}

		$this->response->redirect('custom_events/index/' . $order_hunt_id);
	}
}
