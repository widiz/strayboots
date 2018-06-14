<?php

namespace Play\Suppliers\Controllers;

use DataTables\DataTable,
	\SupplierProductCities,
	\SupplierProducts,
	\Countries,
	\Cities,
	\Exception;

class ProductsController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireSupplier())
			return true;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/suppliers/products.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireSupplier())
			throw new Exception(403, 403);

		$builder = $this->modelsManager->createBuilder()
							->columns('id, name, price, address')
							->from('SupplierProducts')
							->where('supplier_id=' . $this->supplier->id);
		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	/**
	 * Displays the creation form
	 */
	public function newAction()
	{
		if ($this->requireSupplier())
			return true;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('latitude', 0);
			$this->tag->setDefault('longitude', 0);

		}

		$this->addEdit();
	}

	/**
	 * Edits a product
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireSupplier())
			return true;

		$product = SupplierProducts::findFirstByid($id);
		if (!$product || $this->supplier->id != $product->supplier_id) {
			$this->flash->error('Product was not found');

			$this->response->redirect('products');

			return;
		}

		$this->view->id = $product->id;

		if (!$this->request->isPost()) {
			$this->tag->setDefault('id', $product->id);
			$this->tag->setDefault('supplier_id', $product->supplier_id);
			$this->tag->setDefault('name', $product->name);
			$this->tag->setDefault('description', $product->description);
			$this->tag->setDefault('price', $product->price);
			$this->tag->setDefault('min_players', $product->min_players);
			$this->tag->setDefault('max_players', $product->max_players);
			$this->tag->setDefault('hours', $product->hours);
			$this->tag->setDefault('address', $product->address);
			$this->tag->setDefault('latitude', $product->latitude);
			$this->tag->setDefault('longitude', $product->longitude);
			$this->tag->setDefault('images', json_encode($product->images));
			$this->view->images = $product->images;
			$this->tag->setDefault('active', $product->active);
			$cities = array_map('array_pop', SupplierProductCities::find([
				'supplier_product_id = ' . (int)$product->id,
				'columns' => 'city_id'
			])->toArray());
			$_POST['cities[]'] = $cities;
		}

		$this->addEdit();
	}

	private function addEdit()
	{
		if ($this->request->isPost())
			$_POST['cities[]'] = $this->request->getPost('cities', 'int');

		$countries = Countries::find([
			'order' => 'name ASC'
		])->toArray();
		$countries = array_combine(array_map(function($c){
			return $c['id'];
		}, $countries), array_map(function($c){
			return $c['name'];
		}, $countries));
		$cities = Cities::find([
			'status = 0',
			'order' => 'name ASC',
		])->toArray();
		$countrycities = [];
		foreach ($cities as $city) {
			$cname = $countries[$city['country_id']];
			if (isset($countrycities[$cname]))
				$countrycities[$cname][$city['id']] = $city['name'];
			else
				$countrycities[$cname] = [$city['id'] => $city['name']];
		}
		$this->view->countrycities = $countrycities;

		$this->view->googleMaps = $this->config->googleapis->maps;

		$this->assets->collection('script')
					->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/suppliers/product.addedit.js')
					->addJs('/template/js/plugins/select2/select2.full.min.js')
					->addJs('/template/js/plugins/clockpicker/clockpicker.js');
		$this->assets->collection('style')
					->addCss('/template/css/plugins/select2/select2.min.css')
					->addCss('/template/css/plugins/clockpicker/clockpicker.css');
	}

	/**
	 * Creates a new product
	 */
	public function createAction()
	{
		if ($this->requireSupplier())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('products');

			return;
		}

		$product = new SupplierProducts();
		$product->supplier_id = $this->supplier->id;
		$product->name = trim($this->request->getPost('name', 'string'));
		$product->description = trim($this->request->getPost('description', 'string'));
		$product->price = $this->request->getPost('price', 'float');
		$product->min_players = $this->request->getPost('min_players', 'int');
		$product->max_players = $this->request->getPost('max_players', 'int');
		$product->hours = $this->request->getPost('hours', 'trim');
		$product->address = trim($this->request->getPost('address', 'string'));
		$product->latitude = $this->request->getPost('latitude', 'float');
		$product->longitude = $this->request->getPost('longitude', 'float');
		$product->images = $this->request->getPost('images');
		$product->active = $this->request->getPost('active', 'int');
		if (empty($product->hours))
			$product->hours = null;
		if (empty($product->active))
			$product->active = 0;

		try {
			if (empty($product->images))
				throw new Exception('Empty string', 1);
			$product->images = json_decode($product->images, true);
			if (!is_array($product->images))
				throw new Exception('Not an array', 2);
			$baseDir = $this->config->application->suppliersUploadsDir->path;
			$product->images = array_values(array_filter($product->images, function($img) use ($baseDir){
				return preg_match('/^pi\d+\.(gif|jpg|png)$/', $img) && file_exists($baseDir . $img);
			}));
		} catch (Exception $e) {
			$product->images = [];
		}
		$product->images = array_merge($product->images, $this->upload());
		
		if ($product->save()) {
			$cities = $this->request->getPost('cities', 'int');
			if (is_numeric($cities)) {
				$cities = [(int)$cities];
			} else if (!is_array($cities)) {
				$cities = [];
			}
			foreach ($cities as $city) {
				$productCity = new SupplierProductCities();
				$productCity->supplier_product_id = $product->id;
				$productCity->city_id = (int)$city;
				$productCity->save();
			}
		} else {
			foreach ($product->getMessages() as $message)
				$this->flash->error($message);

			$_POST['images'] = json_encode($product->images);
			$this->view->images = $product->images;

			$this->dispatcher->forward([
				'controller' => 'products',
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success('Product was created successfully');

		$this->response->redirect('products');
	}

	/**
	 * Saves a product edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireSupplier())
			return true;
		if (!$this->request->isPost()) {
			$this->response->redirect('products');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$product = SupplierProducts::findFirstByid($id);

		if (!$product || $this->supplier->id != $product->supplier_id) {
			$this->flash->error('Product does not exist ' . $id);

			$this->response->redirect('products');

			return;
		}

		$product->name = trim($this->request->getPost('name', 'string'));
		$product->description = trim($this->request->getPost('description', 'string'));
		$product->price = $this->request->getPost('price', 'float');
		$product->min_players = $this->request->getPost('min_players', 'int');
		$product->max_players = $this->request->getPost('max_players', 'int');
		$product->hours = $this->request->getPost('hours', 'trim');
		$product->address = trim($this->request->getPost('address', 'string'));
		$product->latitude = $this->request->getPost('latitude', 'float');
		$product->longitude = $this->request->getPost('longitude', 'float');
		$product->images = $this->request->getPost('images');
		$product->active = $this->request->getPost('active', 'int');
		if (empty($product->hours))
			$product->hours = null;
		if (empty($product->active))
			$product->active = 0;

		try {
			if (empty($product->images))
				throw new Exception('Empty string', 1);
			$product->images = json_decode($product->images, true);
			if (!is_array($product->images))
				throw new Exception('Not an array', 2);
			$baseDir = $this->config->application->suppliersUploadsDir->path;
			$product->images = array_values(array_filter($product->images, function($img) use ($baseDir){
				return preg_match('/^pi\d+\.(gif|jpg|png)$/', $img) && file_exists($baseDir . $img);
			}));
		} catch (Exception $e) {
			$product->images = [];
		}
		$product->images = array_merge($product->images, $this->upload());

		if ($product->save()) {
			$cities = $this->request->getPost('cities', 'int');
			if (is_numeric($cities)) {
				$cities = [(int)$cities];
			} else if (is_array($cities)) {
				$cities = array_map(function($c){
					return (int)$c;
				}, $cities);
			} else {
				$cities = [];
			}

			$productCities = SupplierProductCities::find('supplier_product_id=' . (int)$product->id);
			foreach ($productCities as $pc) {
				$cid = array_search((int)$pc->city_id, $cities, true);
				if ($cid === false)
					$pc->delete();
				else
					unset($cities[$cid]);
			}
			foreach ($cities as $city) {
				$productCity = new SupplierProductCities();
				$productCity->supplier_product_id = $product->id;
				$productCity->city_id = $city;
				$productCity->save();
			}

		} else {

			foreach ($product->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'products',
				'action' => 'edit',
				'params' => [$product->id]
			]);

			return;
		}

		$this->flash->success('Product was updated successfully');

		$this->response->redirect('products');
	}

	/**
	 * upload an image
	 */
	private function upload()
	{
		$photos = [];
		if ($this->request->hasFiles()) {
			$uploadPath = $this->config->application->suppliersUploadsDir->path;
			$allowedEx = ['jpg', 'jpeg', 'gif', 'png'];
			$mt = 'pi' . round(microtime(1), 2) * 100 % 1e8;
			$heightLimit = 1200;
			$widthLimit = 1600;
			foreach ($this->request->getUploadedFiles() as $file) {
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

								if (file_exists($filePath) && getimagesize($filePath) !== false)
									$photos[] = $bname;
							}
						} catch (Exception $e) {}
					}
				}
			}
		}

		return $photos;
	}

}
