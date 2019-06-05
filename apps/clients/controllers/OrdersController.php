<?php

namespace Play\Clients\Controllers;

use \Orders,
	DataTables\DataTable;

class OrdersController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireClient())
			return true;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/clients/orders.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireClient())
			throw new \Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns(
								'id, name, created, ' .
								'(SELECT COUNT(1) FROM \OrderHunts o WHERE o.order_id=Orders.id) AS hunts'
							)
							->from('Orders')
							//->leftJoin('Cities', 'city.id = p.city_id', 'city');
							//->leftJoin('Countries', 'country.id = city.country_id', 'country')
							->where('client_id=' . $this->client->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * customize an order
	 *
	 * @param string $id
	 */
	public function customizeAction($id)
	{
		$order = Orders::findFirstByid((int)$id);
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		$this->view->currentClientId = $order->client_id;
		$this->view->order = $order;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $order->id);
			$customize = $order->getCustomizeArray();
			$this->tag->setDefault('header_color', isset($customize['header_color']) ? $customize['header_color'] : '');
			$this->tag->setDefault('background_color', isset($customize['background_color']) ? $customize['background_color'] : '');
			$this->tag->setDefault('main_color', isset($customize['main_color']) ? $customize['main_color'] : '');
			$this->tag->setDefault('second_color', isset($customize['second_color']) ? $customize['second_color'] : '');
			$this->tag->setDefault('custom_css', isset($customize['custom_css']) ? $customize['custom_css'] : '');
		}
		$this->assets->collection('script')
				//->addJs('/template/js/plugins/select2/select2.full.min.js')
				//->addJs('/template/js/plugins/colorpicker/bootstrap-colorpicker.min.js')
				->addJs('/js/plugins/jscolor.min.js')
				//->addJs('/template/js/plugins/dropzone/dropzone.js')
				->addJs('/js/plugins/bootbox.min.js')
				->addJs('/js/clients/order.customize.js');
		/*$this->assets->collection('style')
				//->addCss('/template/css/plugins/dropzone/dropzone.css')
				//->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/template/css/plugins/colorpicker/bootstrap-colorpicker.min.css');*/
	}

	/**
	 * Saves an order edited
	 */
	public function saveAction()
	{
		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$order = Orders::findFirstByid((int)$id);
		
		if ($this->requireUser(false)) {
			if ($this->requireClient(false) || !$order || $order->client_id != $this->client->id)
				$order = false;
		}

		if (!$order) {
			$this->flash->error('Order was not found');
			$this->response->redirect('orders');

			return;
		}

		$customize = $order->getCustomizeArray();

		$headerColor = $this->request->getPost('header_color', 'trim');
		$backgroundColor = $this->request->getPost('background_color', 'trim');
		$mainColor = $this->request->getPost('main_color', 'trim');
		$secondColor = $this->request->getPost('second_color', 'trim');
		$removedImages = $this->request->getPost('removed_images', 'trim');
		$customCSS = $this->request->getPost('custom_css');

		$valid = true;
		if (!(empty($headerColor) || preg_match('/^#[0-9a-f]{6}$/i', $headerColor))) {
			$valid = false;
			$this->flash->error('Header color is invalid');
		}
		if (!(empty($backgroundColor) || preg_match('/^#[0-9a-f]{6}$/i', $backgroundColor))) {
			$valid = false;
			$this->flash->error('Background color is invalid');
		}
		if (!(empty($mainColor) || preg_match('/^#[0-9a-f]{6}$/i', $mainColor))) {
			$valid = false;
			$this->flash->error('Main color is invalid');
		}
		if (!(empty($secondColor) || preg_match('/^#[0-9a-f]{6}$/i', $secondColor))) {
			$valid = false;
			$this->flash->error('Second color is invalid');
		}

		if (!empty($customCSS)) {
			$config = \HTMLPurifier_Config::createDefault();
			$config->set('Filter.ExtractStyleBlocks', true);
			$config->set('CSS.AllowImportant', true);
			$config->set('CSS.AllowTricky', true);

			$purifier = new \HTMLPurifier($config);
			$css = $purifier->purify('<style>' . $customCSS . '</style>');

			$output_css = $purifier->context->get('StyleBlocks');

			if (is_array($output_css) && count($output_css) == 1)
				$customCSS = $output_css[0];
		}

		$images = [
			'logo' => 140, 
			'header' => 120, 
			'background' => 1280
		];
		$uploadBase = $this->config->application->clientsUploadsDir->path . 'order.' . $order->id . '.';
		if (!empty($removedImages)) {
			$removedImages = array_filter(explode(',', $removedImages));
			foreach ($removedImages as $i) {
				if (isset($images[$i]) && file_exists($uploadBase . $i . '.png'))
					@unlink($uploadBase . $i . '.png');
			}
		}

		if ($this->request->hasFiles()) {
			foreach ($this->request->getUploadedFiles() as $file) {
				try {
					$key = $file->getKey();
					$tmp = $file->getTempName();
					if (empty($tmp) || !isset($images[$key]))
						continue;

					$imageMimeCheck = preg_match('/^image\//i', $file->getRealType());
					$suffix = $file->getExtension();
					$imageExtensionCheck = in_array(strtolower($suffix), ['jpg', 'jpeg', 'gif', 'png']);
					if ($imageMimeCheck  && $imageExtensionCheck && ($img_info = getimagesize($tmp)) !== false) {
						switch ($img_info[2]) {
							case IMAGETYPE_GIF: $src = imagecreatefromgif($tmp); break;
							case IMAGETYPE_JPEG:
							case IMAGETYPE_JPEG2000: $src = imagecreatefromjpeg($tmp); break;
							case IMAGETYPE_PNG:
								$src = imagecreatefrompng($tmp);
								imagealphablending($src, false);
								imagesavealpha($src, true);
								break;
							default: continue;
						}
						$f = $uploadBase . $key . '.png';
						if ($img_info[0] > $images[$key]) {
							if ($img_info[2] == IMAGETYPE_PNG) {
								$tmp = $src;
								$ratio = $img_info[0] / $img_info[1];
								$newHeight = floor($images[$key] / $ratio);
								$src = imagecreatetruecolor($images[$key], $newHeight);
								imagealphablending($src, false);
								imagesavealpha($src, true);
								imagecopyresampled($src, $tmp, 0, 0, 0, 0, $images[$key], $newHeight, $img_info[0], $img_info[1]);
							} else {
								$src = imagescale($src, $images[$key]);
							}
						}
						imagepng($src, $f, 0);
						if (!empty($err = $this->pngquant($f))) {
							try {
								$this->logger->error('pngquant failed on "' . $f . '\" ' . $err);
							} catch(Exception $e) { }
						}
					}
				} catch(Exception $e) {}
			}
		}

		if (!empty($headerColor))
			$customize['header_color'] = $headerColor;
		else if (isset($customize['header_color']))
			unset($customize['header_color']);
		if (!empty($backgroundColor))
			$customize['background_color'] = $backgroundColor;
		else if (isset($customize['background_color']))
			unset($customize['background_color']);
		if (!empty($mainColor))
			$customize['main_color'] = $mainColor;
		else if (isset($customize['main_color']))
			unset($customize['main_color']);
		if (!empty($secondColor))
			$customize['second_color'] = $secondColor;
		else if (isset($customize['second_color']))
			unset($customize['second_color']);
		if (!empty($customCSS))
			$customize['custom_css'] = $customCSS;
		else if (isset($customize['custom_css']))
			unset($customize['custom_css']);

		$order->customize = empty($customize) ? null : json_encode($customize, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($valid && $order->save()) {
			$this->redis->delete(SB_PREFIX . 'css:' . $order->id);

			$this->flash->success('Order was updated successfully');

			$this->response->redirect('orders');

		} else {
			if ($valid) {
				foreach ($order->getMessages() as $message)
					$this->flash->error($message);
			}

			$this->dispatcher->forward([
				'controller' => 'orders',
				'action' => 'customize',
				'params' => [$order->id]
			]);
		}
	}

}
