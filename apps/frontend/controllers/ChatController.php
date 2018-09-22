<?php

namespace Play\Frontend\Controllers;

use Exception,
	Phalcon\Db;

class ChatController extends ControllerBase
{

	public function indexAction()
	{
		if ($this->requirePlayer())
			return true;

		$p = $this->db->fetchAll(
			'SELECT p.id, p.team_id, p.email, s.thumbnail, p.first_name as pfname,' .
			'p.last_name as plname, s.first_name as sfname, s.last_name as slname ' .
			'FROM players p LEFT JOIN teams t ON (p.team_id = t.id) ' .
			'LEFT JOIN social_players s ON (s.player_id = p.id) WHERE t.order_hunt_id = ' . $this->orderHunt->id,
		Db::FETCH_ASSOC);

		$players = [];

		foreach ($p as $player) {
			$players[$player['id']] = [
				'team'		=> (int)$player['team_id'],
				'email'		=> $player['email'],
				'thumb'		=> $player['thumbnail'],
				'fname'		=> is_null($player['pfname']) ? $player['sfname'] : $player['pfname'],
				'lname'		=> is_null($player['plname']) ? $player['slname'] : $player['plname'],
			];
		}

		$teamsStatus = $this->orderHunt->getTeamsStatus();
		$teams = [];
		foreach ($teamsStatus as $ts) {
			if ($ts['id'] == $this->team->id)
				$this->view->teamStatus = $ts;
			$teams[$ts['id']] = $ts['name'];
		}

		$this->view->hideHeaderStatus = true;

		$this->view->firebase = [
			'config' => $this->config->firebase,
			'appLoc' => [
				'orderHunt' => (int)$this->orderHunt->id,
				'timeLeft' => strtotime($this->orderHunt->finish) - time(),
				'pid' => (int)$this->player->id,
				'players' => $players,
				'teams' => $teams
			]
		];

		$this->assets->collection('style')
							->addCss('/css/app/chat.css');
		$this->assets->collection('script')
							//->addJs('/js/plugins/jquery.nicescroll.min.js')
							->addJs('/js/plugins/firebase-util.min.js')
							->addJs('/js/plugins/Autolinker.min.js')
							->addJs('/js/plugins/moment.min.js')
							->addJs('/js/plugins/emoji.min.js')
							//->addJs('/js/plugins/jquery.emoji.js')
							->addJs('/js/app/chat.js');

	}

	public function playersAction()
	{
		if ($this->requirePlayer())
			return true;

		$p = $this->db->fetchAll(
			'SELECT p.id, p.team_id, p.email, s.thumbnail, p.first_name as pfname,' .
			'p.last_name as plname, s.first_name as sfname, s.last_name as slname ' .
			'FROM players p LEFT JOIN teams t ON (p.team_id = t.id) ' .
			'LEFT JOIN social_players s ON (s.player_id = p.id) WHERE t.order_hunt_id = ' . $this->orderHunt->id,
		Db::FETCH_ASSOC);

		$players = [];
		foreach ($p as $player) {
			$players[$player['id']] = [
				'team'		=> (int)$player['team_id'],
				'email'		=> $player['email'],
				'thumb'		=> $player['thumbnail'],
				'fname'		=> is_null($player['pfname']) ? $player['sfname'] : $player['pfname'],
				'lname'		=> is_null($player['plname']) ? $player['slname'] : $player['plname'],
			];
		}

		$teamsStatus = $this->orderHunt->getTeamsStatus();
		$teams = [];
		foreach ($teamsStatus as $ts)
			$teams[$ts['id']] = $ts['name'];

		return $this->jsonResponse([
			'success'	=> true,
			'players'	=> $players,
			'teams'		=> $teams
		]);
	}

	public function uploadAction()
	{

		if (!$this->player instanceof \Players) {
			if ($this->orderHunt = \OrderHunts::findFirstById($this->request->getPost('orderHunt', 'int'))) {
				if (($uid = (int)$this->session->get('userID')) && \Users::findFirstById($uid)) {
					$this->player = (object)[
						'id' => 0
					];
				} else if (($cid = (int)$this->session->get('clientID')) && \Clients::findFirstById($cid)) {
					if ($this->orderHunt->Order->client_id == $cid) {
						$this->player = (object)[
							'id' => 0
						];
					} else if ($this->requirePlayer()) {
						return true;
					}
				} else if ($this->requirePlayer()) {
					return true;
				}
			} else if ($this->requirePlayer()) {
				return true;
			}
		} else if ($this->requirePlayer()) {
			return true;
		}

		$files = [];
		$uploadUri = $this->config->application->frontUploadsDir->uri . $this->orderHunt->id . '/chat/';
		$uploadPath = $this->config->application->frontUploadsDir->path . $this->orderHunt->id . '/chat/';

		if (file_exists($uploadPath) || mkdir($uploadPath, 0777, true)) {
			if ($this->request->hasFiles()) {
				$allowedEx = ['jpg', 'jpeg', 'gif', 'png'];
				$i = 1;
				$mt = round(microtime(1), 2) * 100 % 1e8;
				$heightLimit = 1920;
				$widthLimit = 1920;
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
									case IMAGETYPE_JPEG2000: $type = 'jpg'; $src = imagecreatefromjpeg($tmp); /*$src = 0;*/ break;
									default:
								}
								if ($src !== false) {
									do {
										$bname = $this->player->id . '_' . $mt . $i++ . '.' . $type;
										$filePath = $uploadPath . $bname;
									} while (file_exists($filePath));
									/*if ($src) {
										$tmp = imagecreatetruecolor($img_info[0], $img_info[1]);
										imagecopyresampled($tmp, $src, 0, 0, 0, 0, $img_info[0], $img_info[1], $img_info[0], $img_info[1]);
										imagejpeg($tmp, $filePath, 85);
									} else {
										$file->moveTo($filePath);
									}*/

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
										$files[] = $uploadUri . $bname;
									} else {
										try {
											$this->logger->error('Upload error: chat player:' . $this->player->id);
										} catch(Exception $e) { }
									}
								}
							} catch (Exception $e) {}
						}
					}
				}
			}
		} else {
			try {
				$this->logger->error('Upload: failed to create directory: chat player:' . $this->player->id);
			} catch(Exception $e) { }
		}

		return $this->jsonResponse([
			'success' => !empty($files),
			'files' => $files
		]);
	}

}
