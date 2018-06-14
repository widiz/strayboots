<?php

namespace Play\Clients\Controllers;

use Play\Frontend\Controllers\NcrController,
	\OrderHunts;

class IndexController extends \ControllerBase
{

	public function indexAction()
	{
		if ($this->requireClient())			
			return true;

		$now = date('Y-m-d H:i:s');
		$orderHunts = OrderHunts::query()
								->columns('OrderHunts.id, OrderHunts.order_id, OrderHunts.start, Orders.name, Hunts.name as huntname')
								->leftJoin('Orders')
								->leftJoin('Hunts')
								->where('Orders.client_id = ' . (int)$this->client->id . ' AND OrderHunts.finish > \'' . $now . '\' AND !(OrderHunts.start > \'' . $now . '\')')
								->orderBy('OrderHunts.id DESC')
								->limit(25)
								->execute()
								->toArray();

		$this->view->activeHunts = $orderHunts;

		$this->assets->collection('style')
					->addCss('/template/css/plugins/dataTables/datatables.min.css')
					->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css')
					->addCss('/template/css/plugins/blueimp/css/blueimp-gallery.min.css');
		$script = $this->assets->collection('script')
					->addJs('/template/js/plugins/blueimp/jquery.blueimp-gallery.min.js')
					->addJs('/template/js/plugins/dataTables/datatables.min.js')
					->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
					->addJs('/js/clients/index.js')
					->addJs('/js/clients/orders.js');

		if (!empty($orderHunts)) {
			$script->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/clients/orderhunts.map.js');
			$this->view->googleMaps = $this->config->googleapis->maps;
		}
	}

	public function thumbnailAction()
	{
		ignore_user_abort(true);
		set_time_limit(90);
		$file = dirname($this->config->application->frontUploadsDir->path, 2) . '/';
		if (preg_match('/^(uploads\/[^\/]+\/\d+\/(\d+|chat)\/[0-9a-z_]+\.)thumbnail\.(jpg|png|gif)$/', $f = $this->request->getQuery('f'), $matches)) {
			$thumbnail = $file . $matches[0];
			$file .= $matches[1] . $matches[3];
			if (file_exists($file) && $imgsize = getimagesize($file)) {
				$im = imagecreatefromstring(file_get_contents($file));
				if ($im !== false) {
					$im = imagescale($im, 320);
					switch ($imgsize[2]) {
						case IMAGETYPE_GIF:
							imagegif($im, $thumbnail);
							header('Content-Type: image/gif');
							break;
						case IMAGETYPE_PNG:
							if (imagepng($im, $thumbnail, 9)) {
								if (!empty($err = $this->pngquant($thumbnail))) {
									usleep(5000);
									if (imagepng($im, $thumbnail, 9)) {
										if (!empty($err = $this->pngquant($thumbnail))) {
											try {
												$this->logger->error('pngquant failed on "' . $thumbnail . '" ' . $err);
											} catch(Exception $e) { }
										}
									}
								}
							}
							header('Content-Type: image/png');
							break;
						case IMAGETYPE_JPEG:
						case IMAGETYPE_JPEG2000:
							if (imagejpeg($im, $thumbnail, 85)) {
								if (!empty($err = $this->jpegtran($thumbnail, true))) {
									usleep(5000);
									if (imagejpeg($im, $thumbnail, 85)) {
										if (!empty($err = $this->jpegtran($thumbnail, true))) {
											try {
												$this->logger->error('jpegtran failed on "' . $thumbnail . '" ' . $err);
											} catch(Exception $e) { }
										}
									}
								}
							}
							header('Content-Type: image/jpeg');
							break;
					}
					imagedestroy($im);
					if (file_exists($thumbnail)) {
						$this->view->disable();
						if (!$this->request->isHead()) {
							$handle = fopen($thumbnail, 'rb');
							while (!feof($handle)) {
								echo fread($handle, 8192);
								ob_flush();
								flush();
							}
							fclose($handle);
						}
						return;
					}
				}
			}
		}

		throw new \Exception('Not Found', 404);

	}

	public function watermarkAction()
	{
		if (IndexController::watermark($this->request->getQuery('f'), $this, true))
			$this->view->disable();
		else
			throw new \Exception('Not Found', 404);
	}

	public static function watermark($f, $ctx, $out = false)
	{
		$di = \Phalcon\Di::getDefault();
		$appConfig = $di->get('config')->application;
		ignore_user_abort(true);
		set_time_limit(90);
		$file = dirname($appConfig->frontUploadsDir->path, 2) . '/';
		if (preg_match('/^(uploads\/[^\/]+\/(\d+)\/(\d+|chat)\/[0-9a-z_]+\.)wm\.(jpg|png|gif)$/', $f, $matches)) {
			$isNCR = (bool)OrderHunts::count('id=' . (int)$matches[2] . ' AND order_id=' . NcrController::ORDER_ID);
			$wm = $file . $matches[0];
			$file .= $matches[1] . $matches[4];
			if (file_exists($wm)) {
				if ($out) {
					header('Content-Type: ' . image_type_to_mime_type(exif_imagetype($wm)));
					$handle = fopen($wm, 'rb');
					while (!feof($handle)) {
						echo fread($handle, 8192);
						ob_flush();
						flush();
					}
					fclose($handle);
				}
				return true;
			}
			if (file_exists($file) && $imgsize = getimagesize($file)) {
				$src = imagecreatefromstring(file_get_contents($file));
				if ($src !== false) {
					$sourceX = imagesx($src);
					$sourceY = imagesy($src);
					$im = imagecreatetruecolor($sourceX, $sourceY);
					imagecolorallocatealpha($im, 255, 255, 255, 127); 
					imagealphablending($im, true);
					imagesavealpha($im, true);
					imagecopyresampled($im, $src, 0, 0, 0, 0, $sourceX, $sourceY, $sourceX, $sourceY);
					$watermark = imagecreatefromstring(file_get_contents($appConfig->publicDir . 'img/watermark' . ($isNCR ? '.ncr' : '') . '.png'));
					$watermarkX = imagesx($watermark);
					$watermarkY = imagesy($watermark);
					$watermarkRatio = max($watermarkX, $watermarkY) / min($watermarkX, $watermarkY);
					$margin = max(8, min($sourceX, $sourceY) * 0.02);
					$ratio = $isNCR ? 0.12 : 0.18;
					$maxRatio = $isNCR ? 0.25 : 0.4;
					if ($sourceX > $sourceY) {
						$maxWidth = floor($sourceX * $ratio);
						do {
							$maxHeight = floor($maxWidth / $watermarkRatio);
						} while ($maxHeight >= $sourceY * $maxRatio - $margin && --$maxWidth);
					} else {
						$maxHeight = floor($sourceY * $ratio);
						do {
							$maxWidth = floor($maxHeight * $watermarkRatio);
						} while ($maxWidth >= $sourceX * $maxRatio - $margin && --$maxHeight);
					}
					imagecopyresampled($im, $watermark, $margin, $sourceY - $maxHeight - $margin, 0, 0, $maxWidth, $maxHeight, $watermarkX, $watermarkY);
					imagedestroy($watermark);
					/*header('Content-Type: image/png');
					imagepng($im, null);
					die;*/
					switch ($imgsize[2]) {
						case IMAGETYPE_GIF:
							imagegif($im, $wm);
							if ($out)
								header('Content-Type: image/gif');
							break;
						case IMAGETYPE_PNG:
							if (imagepng($im, $wm, 9)) {
								if (!empty($err = $ctx->pngquant($wm))) {
									usleep(5000);
									if (imagepng($im, $wm, 9)) {
										if (!empty($err = $ctx->pngquant($wm))) {
											try {
												$ctx->logger->error('pngquant failed on "' . $wm . '" ' . $err);
											} catch(Exception $e) { }
										}
									}
								}
							}
							if ($out)
								header('Content-Type: image/png');
							break;
						case IMAGETYPE_JPEG:
						case IMAGETYPE_JPEG2000:
							if (imagejpeg($im, $wm, 85)) {
								if (!empty($err = $ctx->jpegtran($wm, true))) {
									usleep(5000);
									if (imagejpeg($im, $wm, 85)) {
										if (!empty($err = $ctx->jpegtran($wm, true))) {
											try {
												$ctx->logger->error('jpegtran failed on "' . $wm . '" ' . $err);
											} catch(Exception $e) { }
										}
									}
								}
							}
							if ($out)
								header('Content-Type: image/jpeg');
							break;
					}
					imagedestroy($im);
					if (file_exists($wm)) {
						if ($out && !$ctx->request->isHead()) {
							$handle = fopen($wm, 'rb');
							while (!feof($handle)) {
								echo fread($handle, 8192);
								ob_flush();
								flush();
							}
							fclose($handle);
						}
						return true;
					}
				}
			}
		}

		return false;

	}

}

