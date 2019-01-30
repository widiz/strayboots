<?php

namespace Play\Admin\Controllers;

use \EventEmails,
	DataTables\DataTable;

class EventEmailsController extends \ControllerBase
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
				->addJs('/js/admin/eventemails.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns(['email_id', 'title', 'html', 'text'])
							->from('EventEmails');

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
	 * Edits a event email
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$eventEmail = is_numeric($id) ? EventEmails::findFirstByEmailId($id) : false;
		if (!$eventEmail) {
			$this->flash->error('event email was not found');

			$this->response->redirect('event_emails');

			return;
		}
			
		$this->view->email_id = $eventEmail->email_id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('email_id', $eventEmail->email_id);
			$this->tag->setDefault('title', $eventEmail->title);
			$this->tag->setDefault('html', $eventEmail->html);
			$this->tag->setDefault('text', $eventEmail->text);

		}

		$this->addEdit();
	}

	private function addEdit()
	{
		$this->assets->collection('script')
				->addJs('/js/plugins/summernote/summernote.min.js')
				->addJs('/js/admin/eventemails.addedit.js');

		$this->assets->collection('style')
				->addCss('/js/plugins/summernote/summernote.css');
	}

	/**
	 * Saves a event email edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('event_emails');
			return;
		}

		$id = $this->request->getPost('email_id', 'int');
		$eventEmail = EventEmails::findFirstByEmailId($id);

		if (!$eventEmail) {
			$this->flash->error('event email does not exist ' . $id);
			
			$this->response->redirect('event_emails');
			return;
		}

		$eventEmail->title = $this->request->getPost('title', 'trim');
		$eventEmail->text = $this->request->getPost('text', 'trim');
		$eventEmail->html = $this->request->getPost('html', 'trim');

		if (!$eventEmail->save()) {

			foreach ($eventEmail->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'event_emails',
				'action' => 'edit',
				'params' => [$eventEmail->email_id]
			]);
			return;
		}

		$this->flash->success('event email was updated successfully');

		$this->response->redirect('event_emails');
	}

}
