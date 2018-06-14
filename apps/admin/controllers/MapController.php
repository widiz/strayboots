<?php

namespace Play\Admin\Controllers;

class MapController extends \ControllerBase
{

	public function previewAction()
	{
		if ($this->requireUser())
			return true;

		$this->view->hiddenWrapper = true;
		$this->view->googleMaps = $this->config->googleapis->maps;

		if ($hunt = $this->request->getQuery('hunt', 'int')) {
			if ($hunt = \Hunts::findFirstById((int)$hunt)) {
				$huntPoints = $hunt->getHuntPoints([
					'order' => 'idx ASC'
				]);
				$data = [];
				$numPoints = count($huntPoints) - 1;
				$charCodeAdd = 48;
				foreach ($huntPoints as $p => $hp) {
					$point = $hp->Point;
					$question = $hp->Question;
					if (++$charCodeAdd == 91)
						$charCodeAdd += 6;
					if ($charCodeAdd == 58)
						$charCodeAdd += 7;
					if ($point && $question && $point->latitude <> 0 && $point->longitude <> 0) {
						$data[] = [
							'latitude'	=> $point->latitude,
							'longitude'	=> $point->longitude,
							'label'		=> $p + 1,//$p == 0 ? 'S' : ($p == $numPoints ? 'F' : $p/*chr($charCodeAdd)*/),
							'info'		=> ($p == 0 ? '<h2>First Point</h2>' : ($p == $numPoints ? '<h2>Last Point</h2>' : ('<h2>Point ' . ($p + 1) . '</h2>'))) . $point->name . '<br>' . $question->question
						];
					}
				}
				$this->view->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		} else if (is_array($data = json_decode($this->request->getPost('data'), true))) {
			$this->view->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$this->assets->collection('script')
				->addJs('/js/plugins/maps.initializer.js')
				->addJs('/js/admin/map.preview.js');
	}

}
