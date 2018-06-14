<?php

namespace Play\Admin\Controllers;

use \Tags,
	DataTables\DataTable;

class TagsController extends \ControllerBase
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
				->addJs('/js/admin/tags.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, tag, (SELECT COUNT(1) FROM \QuestionTags WHERE tag_id=tags.id) AS questions')
							->from(['tags' => 'Tags']);

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
	 * Edits a tag
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$tag = Tags::findFirstByid($id);
		if (!$tag) {
			$this->flash->error("tag was not found");

			$this->response->redirect('tags');

			return;
		}
			
		$this->view->id = $tag->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault("id", $tag->id);
			$this->tag->setDefault("tag", $tag->tag);
			
		}
	}

	/**
	 * Creates a new tag
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('tags');
			return;
		}

		$tag = new Tags();
		$tag->tag = trim($this->request->getPost("tag", 'string'));
		
		if (!$tag->save()) {
			foreach ($tag->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => "tags",
				'action' => 'new'
			]);
			return;
		}

		$this->flash->success("tag was created successfully");

		$this->response->redirect('tags');
	}

	/**
	 * Saves a tag edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('tags');
			return;
		}

		$id = $this->request->getPost("id", 'int');
		$tag = Tags::findFirstByid($id);

		if (!$tag) {
			$this->flash->error("tag does not exist " . $id);
			
			$this->response->redirect('tags');
			return;
		}

		$tag->tag = trim($this->request->getPost("tag", 'string'));
		
		if (!$tag->save()) {

			foreach ($tag->getMessages() as $message) {
				$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => "tags",
				'action' => 'edit',
				'params' => [$tag->id]
			]);
			return;
		}

		$this->flash->success("tag was updated successfully");

		$this->response->redirect('tags');
	}

	/**
	 * Deletes a tag
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$tag = Tags::findFirstByid($id);
		if (!$tag) {
			$this->flash->error("tag was not found");

			$this->response->redirect('tags');
			return;
		}

		if ($tag->delete()) {
			$this->flash->success("tag was deleted successfully");
		} else {
			foreach ($tag->getMessages() as $message) {
				$this->flash->error($message);
			}
		}
		
		$this->response->redirect('tags');
	}

}
