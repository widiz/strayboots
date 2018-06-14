<?php

namespace Play\Admin\Controllers;

use \QuestionTypes,
	DataTables\DataTable;

class QuestionTypesController extends \ControllerBase
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
				->addJs('/js/admin/questiontypes.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCSS('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, type, custom, limitAnswers, score, (SELECT COUNT(1) FROM \Questions WHERE type_id=questiontypes.id) AS questions')
							->from(['questiontypes' => 'QuestionTypes']);
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
	 * Edits a question_type
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$question_type = QuestionTypes::findFirstByid($id);
		if (!$question_type) {
			$this->flash->error('Question type was not found');

			$this->response->redirect('question_types');

			return;
		}

		$this->view->id = $question_type->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $question_type->id);
			$this->tag->setDefault('name', $question_type->name);
			$this->tag->setDefault('type', $question_type->type);
			$this->tag->setDefault('score', $question_type->score);
			$this->tag->setDefault('custom', $question_type->custom);
			$this->tag->setDefault('limitAnswers', $question_type->limitAnswers);
			
		}
	}

	/**
	 * Creates a new question_type
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('question_types');

			return;
		}

		$question_type = new QuestionTypes();
		$question_type->name = $this->request->getPost('name', 'trim');
		$question_type->type = $this->request->getPost('type', 'int');
		$question_type->score = $this->request->getPost('score', 'int');
		$question_type->custom = $this->request->getPost('custom') ? 1 : 0;
		$question_type->limitAnswers = $this->request->getPost('limitAnswers') ? 1 : 0;
		
		if (!$question_type->save()) {
			foreach ($question_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'question_types',
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success('Question type was created successfully');

		$this->response->redirect('question_types');
	}

	/**
	 * Saves a question_type edited
	 *
	 */
	public function saveAction()
	{

		if ($this->requireUser())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('question_types');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$question_type = QuestionTypes::findFirstByid($id);

		if (!$question_type) {
			$this->flash->error('Question type does not exist ' . $id);

			$this->response->redirect('question_types');

			return;
		}

		$question_type->name = $this->request->getPost('name', 'trim');
		$question_type->type = $this->request->getPost('type', 'int');
		$question_type->score = $this->request->getPost('score', 'int');
		$question_type->custom = $this->request->getPost('custom') ? 1 : 0;
		$question_type->limitAnswers = $this->request->getPost('limitAnswers') ? 1 : 0;
		

		if (!$question_type->save()) {

			foreach ($question_type->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'question_types',
				'action' => 'edit',
				'params' => [$question_type->id]
			]);

			return;
		}

		$this->flash->success('Question type was updated successfully');

		$this->response->redirect('question_types');
	}

	/**
	 * Deletes a question_type
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;
		$question_type = QuestionTypes::findFirstByid($id);
		if (!$question_type) {
			$this->flash->error('Question type was not found');

			$this->response->redirect('question_types');

			return;
		}

		if ($question_type->delete()) {
			$this->flash->success('Question type was deleted successfully');
		} else {
			foreach ($question_type->getMessages() as $message) {
				$this->flash->error($message);
			}
		}

		$this->response->redirect('question_types');
	}

}
