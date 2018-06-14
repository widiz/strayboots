<?php

namespace Play\Admin\Controllers;

use \Exception,
	DataTables\DataTable;

class IndexController extends \ControllerBase
{

	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$this->view->googleMaps = $this->config->googleapis->maps;

		$this->assets->collection('script')
					->addJs('/template/js/plugins/dataTables/datatables.min.js')
					->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
					->addJs('/template/js/plugins/blueimp/jquery.blueimp-gallery.min.js')
					->addJs('/js/plugins/maps.initializer.js')
					->addJs('/js/plugins/bootbox.min.js')
					->addJs('/js/clients/orderhunts.map.js')
					->addJs('/js/admin/index.js')
					->addJs('/js/admin/orderhunts.summary.js');
		$this->assets->collection('style')
					->addCss('/template/css/plugins/blueimp/css/blueimp-gallery.min.css')
					->addCss('/template/css/plugins/dataTables/datatables.min.css')
					->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction($action = '')
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		$now = date('Y-m-d H:i:s');
		$est = 'EST';//(new \DateTime('01-01-2010', new \DateTimeZone('America/New_York')))->format('T');
		//(new \DateTime('01-01-2010', new \DateTimeZone('America/New_York')))->format('T');
		$builder = $this->modelsManager->createBuilder()
							->columns([
								'OrderHunts.id', 'OrderHunts.order_id', 'OrderHunts.start',
								'OrderHunts.max_teams', 'OrderHunts.max_players', 'OrderHunts.hunt_id',
								'OrderHunts.finish', 'Cities.name AS cityname', 'Orders.name',
								'Orders.client_id', 'Clients.company', 'Hunts.name AS huntname',
								'tztt.Abbreviation', 'CONVERT_TZ(OrderHunts.start, "America/New_York", Cities.timezone) AS start_local',
								'CONVERT_TZ(OrderHunts.finish, "America/New_York", Cities.timezone) AS finish_local'
							])
							->from('OrderHunts')
							->leftJoin('Orders', 'Orders.id = OrderHunts.order_id')
							->leftJoin('Clients', 'Clients.id = Orders.client_id')
							->leftJoin('Hunts', 'Hunts.id = OrderHunts.hunt_id')
							->leftJoin('Cities', 'Cities.id = Hunts.city_id')
							->leftJoin('TimeZoneName', 'Cities.timezone = tzn.Name', 'tzn')
							->leftJoin('TimeZoneTransitionType', 'tzn.Time_zone_id = tztt.Time_zone_id AND tztt.Transition_type_id', 'tztt')
							->groupBy('OrderHunts.id')
							->orderBy('OrderHunts.id DESC');

		switch ($action) {
			case 'active':
				$builder->where("OrderHunts.finish > '" . $now . "' AND OrderHunts.start <= '" . $now . "' AND OrderHunts.flags & 4 = 0");
				break;
			case 'last':
				$builder->where("OrderHunts.start > '" . date('Y-m-d', min(strtotime("-7 days"), strtotime("first day of last week"))) . " 00:00:00' AND OrderHunts.finish <= '" . $now . "' AND OrderHunts.flags & 4 = 0");
				break;
			case 'coming':
				$builder->where("OrderHunts.start > '" . $now . "' AND OrderHunts.start <= '" . date('Y-m-d', strtotime("+7 days")) . " 23:59:59' AND OrderHunts.flags & 4 = 0");
				break;
			default:
				throw new Exception(404, 404);
		}

		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

}
