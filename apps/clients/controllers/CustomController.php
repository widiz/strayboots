<?php

namespace Play\Clients\Controllers;

use \OrderHunts,
	\CustomQuestions,
	\QuestionTypes,
	DataTables\DataTable;

class CustomController extends \ControllerBase
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
				$this->flash->error('Order hunt was not found');
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$this->view->orderHunt = $orderHunt;
		$this->view->removable = !$orderHunt->isStarted();
		$this->view->tooMany = $orderHunt->countCustomQuestions() >= 5;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/clients/custom.js');
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
							->columns('id, question, score')
							->from('CustomQuestions')
							->where('order_hunt_id = ' . $orderHunt->id);
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
				$this->flash->error('Order hunt was not found');
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if ($orderHunt->countCustomQuestions() >= 5) {
				$this->flash->error('Order hunt is limited to 5 custom questions');
				$this->response->redirect('orders');

				return;
		}

		$this->view->orderHunt = $orderHunt;
		$this->view->huntStarted = $orderHunt->isStarted();
		$this->view->maxIdx = $orderHunt->Hunt->countHuntPoints() - 1;
		
		$this->view->vimeo = true;
		
		$this->assets->collection('script')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/moment/moment.min.js')
				->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js')
				->addJs('/js/admin/questions.addedit.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/css/admin/custom.css');

	}

	/**
	 * Edits a custom question
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		$custom = $id > 0 ? CustomQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $custom ? $custom->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$custom = false;
		}

		if (!$custom) {
			$this->flash->error('custom question doesn\'t exists ' . $id);
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		$this->view->id = $custom->id;

		$this->view->vimeo = true;

		$this->view->orderHunt = $orderHunt;
		$this->view->huntStarted = $orderHunt->isStarted();
		$this->view->maxIdx = $orderHunt->Hunt->countHuntPoints() - 1;
		
		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $custom->id);
			$this->tag->setDefault('type_id', $custom->type_id);
			$this->tag->setDefault('name', $custom->name);
			$this->tag->setDefault('score', $custom->score);
			$this->tag->setDefault('question', $custom->question);
			$this->tag->setDefault('qattachment', $custom->qattachment);
			$this->tag->setDefault('hint', $custom->hint);
			$this->tag->setDefault('funfact', $custom->funfact);
			$this->tag->setDefault('response_correct', $custom->response_correct);
			$this->tag->setDefault('answers', $custom->QuestionType->type == QuestionTypes::Choose ? str_replace("\n", "\r\n", $custom->answers) : $custom->answers);
			$this->tag->setDefault('attachment', $custom->attachment);
			$this->tag->setDefault('timeout', is_null($custom->timeout) ? '' : gmdate('H:i:s', $custom->timeout));
			$this->tag->setDefault('idx', $custom->idx);
			
		}

		$this->assets->collection('script')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/moment/moment.min.js')
				->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js')
				->addJs('/js/admin/questions.addedit.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/css/admin/custom.css');
	}

	/**
	 * Creates a new custom
	 */
	public function createAction()
	{
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');
			return;
		}

		$custom = new CustomQuestions();
		$custom->order_hunt_id = $this->request->getPost('order_hunt_id', 'int');
		$custom->type_id = $this->request->getPost('type_id', 'int');
		$custom->name = $this->request->getPost('name', 'trim');
		$custom->score = $this->request->getPost('score', 'int');
		$custom->question = $this->request->getPost('question', 'trim');
		$custom->answers = $this->request->getPost('answers');
		$custom->funfact = $this->request->getPost('funfact', 'trim');
		$custom->response_correct = $this->request->getPost('response_correct', 'trim');
		$custom->hint = $this->request->getPost('hint', 'trim');
		$custom->timeout = $this->request->getPost('timeout', 'trim');
		$custom->idx = $this->request->getPost('idx', 'int');
		if (empty($custom->timeout))
			$custom->timeout = null;
		if (empty($custom->name))
			$custom->name = null;

		$orderHunt = $custom ? $custom->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;
		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id) {
				$this->flash->error('Order hunt wasn\'t found');
				$this->response->redirect('orders');

				return;
			}
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);
			return;
		}

		if ($orderHunt->countCustomQuestions() >= 5) {
			$this->flash->error('Order hunt is limited to 5 custom questions');
			$this->response->redirect('orders');
			return;
		}

		if ($orderHunt->isStarted()) {
			$this->flash->error('Custom question cannot be created');
			$this->response->redirect('custom/' . $orderHunt->id);
			return;
		}

		switch ($this->request->getPost('at2')) {
			case 'youtube':
				$custom->qattachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_YOUTUBE,
					'video' => trim($this->request->getPost('youtube', 'string'))
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'vimeo':
				$custom->qattachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_VIMEO,
					'video' => (int)$this->request->getPost('vimeo', 'int')
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'photo':
				$qattachment = $this->request->getPost('qattachment');
				if (!empty($photo = $this->upload('img2')) || empty($qattachment)) {
					$custom->qattachment = json_encode([
						'type' => CustomQuestions::ATTACHMENT_PHOTO,
						'photo' => $photo
					], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				} else {
					$custom->qattachment = $qattachment;
				}
				break;
			default:
				$custom->qattachment = null;
		}

		switch ($this->request->getPost('at1')) {
			case 'youtube':
				$custom->attachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_YOUTUBE,
					'video' => trim($this->request->getPost('youtube', 'string'))
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'vimeo':
				$custom->attachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_VIMEO,
					'video' => (int)$this->request->getPost('vimeo', 'int')
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'photo':
				$attachment = $this->request->getPost('attachment');
				if (!empty($photo = $this->upload('img1')) || empty($attachment)) {
					$custom->attachment = json_encode([
						'type' => CustomQuestions::ATTACHMENT_PHOTO,
						'photo' => $photo
					], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				} else {
					$custom->attachment = $attachment;
				}
				break;
			default:
				$custom->attachment = null;
				break;
		}

		if (!$custom->save()) {
			$this->tag->setDefault('attachment', $custom->attachment);
			$this->tag->setDefault('qattachment', $custom->qattachment);

			foreach ($custom->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'custom',
				'action' => 'new',
				'params' => [$orderHunt->id]
			]);

			return;
		}

		$this->flash->success('Custom question was created successfully');

		$this->response->redirect('custom/' . $orderHunt->id);

	}

	/**
	 * Saves a custom question edited
	 *
	 */
	public function saveAction()
	{
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');
			return;
		}

		$id = $this->request->getPost('id', 'int');
		$custom = $id > 0 ? CustomQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $custom ? $custom->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$custom = false;
		}

		if (!$custom) {
			$this->flash->error('custom question doesn\'t exists ' . $id);
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}
		/*if ($orderHunt->isStarted()) {
			$this->flash->error('Custom question cannot be saved');
			$this->response->redirect('custom/' . $orderHunt->id);
			return;
		}*/

		$custom->type_id = $this->request->getPost('type_id', 'int');
		$custom->name = $this->request->getPost('name', 'trim');
		$custom->question = $this->request->getPost('question', 'trim');
		$custom->hint = $this->request->getPost('hint', 'trim');
		$custom->score = $this->request->getPost('score', 'int');
		$custom->funfact = $this->request->getPost('funfact', 'trim');
		$custom->response_correct = $this->request->getPost('response_correct', 'trim');
		$custom->answers = $this->request->getPost('answers');
		$custom->idx = $this->request->getPost('idx', 'int');
		$custom->timeout = $this->request->getPost('timeout', 'trim');
		if (empty($custom->timeout))
			$custom->timeout = null;
		if (empty($custom->name))
			$custom->name = null;

		switch ($this->request->getPost('at2')) {
			case 'youtube':
				$custom->qattachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_YOUTUBE,
					'video' => trim($this->request->getPost('youtube', 'string'))
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'vimeo':
				$custom->qattachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_VIMEO,
					'video' => (int)$this->request->getPost('vimeo', 'int')
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'photo':
				$qattachment = $this->request->getPost('qattachment');
				if (!empty($photo = $this->upload('img2')) || empty($qattachment)) {
					$custom->qattachment = json_encode([
						'type' => CustomQuestions::ATTACHMENT_PHOTO,
						'photo' => $photo
					], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				} else {
					$custom->qattachment = $qattachment;
				}
				break;
			default:
				$custom->qattachment = null;
		}

		switch ($this->request->getPost('at1')) {
			case 'youtube':
				$custom->attachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_YOUTUBE,
					'video' => trim($this->request->getPost('youtube', 'string'))
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'vimeo':
				$custom->attachment = json_encode([
					'type' => CustomQuestions::ATTACHMENT_VIMEO,
					'video' => (int)$this->request->getPost('vimeo', 'int')
				], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				break;
			case 'photo':
				$attachment = $this->request->getPost('attachment');
				if (!empty($photo = $this->upload('img1')) || empty($attachment)) {
					$custom->attachment = json_encode([
						'type' => CustomQuestions::ATTACHMENT_PHOTO,
						'photo' => $photo
					], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				} else {
					$custom->attachment = $attachment;
				}
				break;
			default:
				$custom->attachment = null;
				break;
		}

		if (!$custom->save()) {
			$this->tag->setDefault('attachment', $custom->attachment);
			$this->tag->setDefault('qattachment', $custom->qattachment);

			foreach ($custom->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'custom',
				'action' => 'edit',
				'params' => [$custom->id]
			]);

			return;
		}

		$this->flash->success('Custom question was updated successfully');

		$this->response->redirect('custom/' . $orderHunt->id);

	}

	/**
	 * Deletes a custom question
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		$custom = $id > 0 ? CustomQuestions::findFirstByid((int)$id) : false;
		$orderHunt = $custom ? $custom->OrderHunt : false;
		$order = $orderHunt ? $orderHunt->Order : false;

		if (!$order || $this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$custom = false;
		}

		if (!$custom) {
			$this->flash->error('Custom question was not found');
			$this->response->redirect('orders');

			return;
		}

		if ($orderHunt->isCanceled()) {
			$this->flash->error('This hunt was canceled');
			$this->response->redirect('order_hunts/' . $order->id);

			return;
		}

		if ($orderHunt->isStarted()) {
			$this->flash->error('Custom question cannot be deleted');
		} else if ($custom->delete()) {
			$this->flash->success('Custom question was deleted successfully');
		} else {
			foreach ($custom->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('custom/' . $orderHunt->id);
	}

	/**
	 * upload an image
	 */
	private function upload($fname)
	{
		$photo = '';
		if ($this->request->hasFiles()) {
			$uploadUri = $this->config->application->frontUploadsDir->uri . 'ff/';
			$uploadPath = $this->config->application->frontUploadsDir->path . 'ff/';
			$allowedEx = ['jpg', 'jpeg', 'gif', 'png'];
			$mt = round(microtime(1), 2) * 100 % 1e8;
			$heightLimit = 1200;
			$widthLimit = 1600;
			foreach ($this->request->getUploadedFiles() as $file) {
				if ($file->getKey() != $fname)
					continue;
				if ($tmp = $file->getTempName()) {
					$imageMimeCheck = preg_match('/^image\//i', $file->getRealType());
					$suffix = $file->getExtension();
					$imageExtensionCheck = in_array(strtolower($suffix), $allowedEx);
					if ($imageMimeCheck && $imageExtensionCheck && ($img_info = getimagesize($tmp)) !== false) {
						try {
							$type = '';
							$src = false;
							switch ($img_info[2]) {
								case IMAGETYPE_GIF: $type = 'gif'; $src = imagecreatefromgif($tmp); break;
								case IMAGETYPE_PNG: $type = 'png'; $src = imagecreatefrompng($tmp); break;
								case IMAGETYPE_JPEG:
								case IMAGETYPE_JPEG2000: $type = 'jpg'; $src = imagecreatefromjpeg($tmp); break;
								default:
							}
							if ($src !== false) {
								do {
									$bname = $mt . mt_rand(1, 1e5) . '.' . $type;
									$filePath = $uploadPath . $bname;
								} while (file_exists($filePath));

								$width = $img_info[0];
								$height = $img_info[1];

								if ($width > $widthLimit || $height > $heightLimit) {
									$ratio = min($widthLimit / $width, $heightLimit / $height);
									$width = round($width * $ratio);
									$height = round($height * $ratio);
								}
								
								$image = imagecreatetruecolor($width, $height);
								imagecopyresampled($image, $src, 0, 0, 0, 0, $width, $height, $img_info[0], $img_info[1]);

								if ($type == 'jpg' && is_array($exif = @exif_read_data($tmp)) && !empty($exif['Orientation'])) {
									switch($exif['Orientation']) {
										case 8:
											$image = imagerotate($image, 90, 0);
											break;
										case 3:
											$image = imagerotate($image, 180, 0);
											break;
										case 6:
											$image = imagerotate($image, -90, 0);
											break;
									}
								}

								switch($type){
									case 'gif': imagegif($image, $filePath); break;
									case 'jpg': imagejpeg($image, $filePath, 85); break;
									case 'png': imagepng($image, $filePath, 0); break;
									default:
										continue;
								}
								
								imagedestroy($image);

								if ($type == 'jpg') {
									if (!empty($err = $this->jpegtran($filePath, true))) {
										try {
											$this->logger->error('jpegtran failed on "' . $filePath . '" ' . $err);
										} catch(Exception $e) { }
									}
								} else if ($type == 'png') {
									if (!empty($err = $this->pngquant($filePath))) {
										try {
											$this->logger->error('pngquant failed on "' . $filePath . '" ' . $err);
										} catch(Exception $e) { }
									}
								}

								if (file_exists($filePath) && getimagesize($filePath) !== false) {
									$photo = $uploadUri . $bname;
									break;
								}
							}
						} catch (Exception $e) {}
					}
				}
			}
		}

		return $photo;
	}

}
