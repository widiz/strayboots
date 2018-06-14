<?php

namespace Play\Admin\Controllers;

use \Orders,
	\Hunts,
	\Clients,
	\OrderHunts,
	\Exception,
	\OrderHuntPDF,
	DataTables\DataTable;

class OrdersController extends \ControllerBase
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		if ($this->requireUser())
			return true;

		$client = (int)$this->request->getQuery('client', 'int');
		$this->view->client = $client > 0 ? Clients::findFirstById($client) : false;

		$hunt = (int)$this->request->getQuery('hunt', 'int');
		$this->view->hunt = $hunt > 0 ? Hunts::findFirstById($hunt) : false;

		$this->assets->collection('script')
				->addJs('/js/plugins/bootbox.min.js')
				->addJs('/template/js/plugins/dataTables/datatables.min.js')
				->addJs('/template/js/plugins/tabletools/js/dataTables.tableTools.js')
				->addJs('/js/admin/orders.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/dataTables/datatables.min.css')
				->addCss('/template/js/plugins/tabletools/css/dataTables.tableTools.css');
	}

	public function datatableAction()
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		$client = (int)$this->request->getQuery('client', 'int');
		$client = $client > 0 ? Clients::findFirstById($client) : false;

		$hunt = (int)$this->request->getQuery('hunt', 'int');
		$hunt = $hunt > 0 ? Hunts::findFirstById($hunt) : false;

		/*$builder = $this->modelsManager->createBuilder()
							->columns(
								'id, name, client_id, created, ' .
								'(SELECT CONCAT(c.first_name, \' \', c.last_name, \' (\', c.company, \')\') FROM Clients c WHERE c.id=Orders.client_id) AS first_name,' .
								'(SELECT COUNT(1) FROM \OrderHunts o WHERE o.order_id=Orders.id) AS hunts'
							)
							->from('Orders');*/
							//->leftJoin('Cities', 'city.id = p.city_id', 'city');
							//->leftJoin('Countries', 'country.id = city.country_id', 'country');

		$builder = $this->modelsManager->createBuilder()
							->columns([
								'o.id', 'o.name', 'o.client_id', 'o.created', 'c.company', 'c.first_name', 'c.last_name',
								'hunts' => '(SELECT COUNT(1) FROM OrderHunts oh WHERE oh.order_id=o.id)'
							])
							->from(['o' => 'Orders'])
							->leftJoin('Clients', 'c.id = o.client_id', 'c');
		if ($hunt) {
			$builder->leftJoin('OrderHunts', 'h.order_id = o.id', 'h')
					->where('h.hunt_id=' . $hunt->id);
		}
		if ($client)
			$builder->{$hunt ? 'andWhere' : 'where'}('c.id=' . $client->id);

		$dataTables = new DataTable();
		$dataTables->fromBuilder($builder)->sendResponse();
		exit;
	}

	public function mailAction($id = 0)
	{
		if ($this->requireUser())
			throw new Exception(403, 403);

		try {
			$order = Orders::findFirstByid((int)$id);
			if (!$order)
				throw new Exception(404, 404);

			$client = $order->Client;

			set_time_limit(0);
			ignore_user_abort(true);
			ini_set('memory_limit', '256M');

			$attachments = [];
			foreach ($order->OrderHunts as $oh) {
				$pdf = new OrderHuntPDF($oh, $this->view->timeFormat);
				if (file_exists($pdf = $pdf->savePDF()))
					$attachments[] = '@' . $pdf;
			}
			
			$to = $this->request->getPost('email', 'email');
			if (empty($to))
				$to = $client->email;

			extract(OrdersController::mailPDF($client));

			return $this->jsonResponse([
				'success' => $this->sendMail($to, 'Your Strayboots Hunt Instructions', $text, $html, $attachments, ['bcc' => '077cca161d@invite.trustpilot.com'])
			]);
		} catch(Exception $e) {
			return $this->jsonResponse([
				'success' => false
			]);
		}
	}

	public static function mailPDF(Clients $client)
	{

		$di = \Phalcon\Di::getDefault();
		$config = $di->get('config');

		$text = <<<EOF
Hi {$client->first_name}!

I hope you and your team are getting excited for your hunt...we’re definitely getting excited for you! Next steps are super simple – I’ve attached everything you’ll need to get started, along with some helpful notes below. With both of those things and a phone per team, you’re good to go!

Client Access:

You can access our clients area here {$config->fullUri}/clients
Login with your email ({$client->email}) and the following password: {$client->password}

Getting Started:

First and foremost, DO NOT play through the app. You’ll be playing through your phone’s web browser.
Each Captain will get an Instruction Sheet with a unique activation code. You can either email the Instruction Sheets out, or hand them out right before the hunt starts. When you’re ready to start playing, have each Captain go to $config->fullUri on their phone and enter their unique code. Now they’re ready to start!
Make sure you add some swag to your hunt here: https://www.straybootsgear.com/

Good to Know:

Everyone will start in the same spot, but then go separate ways. Each team’s code represents the unique route they’ll take. No two routes are the same.
There won’t be anyone from our team on the ground for your hunt. That’s the beauty of Strayboots -- we give you everything you need to run the hunt on your own! We’ll, of course, be on standby for questions/issues.
The score is based on getting every point done right, not on time.
If there is a tie, use the time to decide who should be in first!
Into social media? Us too! To see all of your hunt photos in one place (and see what teams in other cities are up to), upload pictures with #strayboots on Instagram.

Wrapping Up:

Once you reach your official end time, teams should stop hunting (even if they haven't finished all of their challenges) and go to the end location for the celebration! All of this is indicated on the Instruction Sheet.
Each team's score will appear on their device, but we'll email you the final scores, recorded promptly at the official end time.
If you run into any issues, email me here or give us a call at (877) 787-2929. Oh yeah, and have fun!!


~ The Strayboots Squad ~
(877) 787-2929
events@strayboots.com

EOF;
		$html = <<<EOF
Hi {$client->first_name}!<br>
<br>
I hope you and your team are getting excited for your hunt...we’re definitely getting excited for you! Next steps are super simple – I’ve attached everything you’ll need to get started, along with some helpful notes below. With both of those things and a phone per team, you’re good to go!<br>
<br>
<b>Client Access:</b><br>
<ul>
<li>You can access our clients area here <a href="{$config->fullUri}/clients">{$config->fullUri}/clients</a></li>
<li>Login with your email ({$client->email}) and the following password: {$client->password}</li>
</ul>
<br>
<b>Getting Started:</b><br>
<ul>
<li>First and foremost, DO NOT play through the app. You’ll be playing through your phone’s web browser.</li>
<li>Each Captain will get an Instruction Sheet with a unique activation code. You can either email the Instruction Sheets out, or hand them out right before the hunt starts. When you’re ready to start playing, have each Captain go to <a href="{$config->fullUri}/">{$config->fullUri}</a> on their phone and enter their unique code. Now they’re ready to start!</li>
<li>Make sure you add some swag to your hunt here: <a href="https://www.straybootsgear.com">https://www.straybootsgear.com/</a></li>
</ul>
<br>
<b>Good to Know:</b><br>
<ul>
<li>Everyone will start in the same spot, but then go separate ways. Each team’s code represents the unique route they’ll take. No two routes are the same.</li>
<li>There won’t be anyone from our team on the ground for your hunt. That’s the beauty of Strayboots -- we give you everything you need to run the hunt on your own! We’ll, of course, be on standby for questions/issues.</li>
<li>The score is based on getting every point done right, not on time.</li>
<li>If there is a tie, use the time to decide who should be in first!</li>
<li>Into social media? Us too! To see all of your hunt photos in one place (and see what teams in other cities are up to), upload pictures with #strayboots on Instagram.</li>
</ul>
<br>
<b>Wrapping Up:</b><br>
<ul>
<li>Once you reach your official end time, teams should stop hunting (even if they haven't finished all of their challenges) and go to the end location for the celebration! All of this is indicated on the Instruction Sheet.</li>
<li>Each team's score will appear on their device, but we'll email you the final scores, recorded promptly at the official end time.</li>
<li>If you run into any issues, email me here or give us a call at (877) 787-2929. Oh yeah, and have fun!!</li>
</ul>
<br>
<br>

~ The Strayboots Squad ~<br>
(877) 787-2929<br>
<a href="mailto:events@strayboots.com">events@strayboots.com</a><br>
EOF;
	
		return [
			'text' => $text,
			'html' => $html
		];
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
	 * Edits a order
	 *
	 * @param string $id
	 */
	public function editAction($id)
	{
		if ($this->requireUser())
			return true;

		$order = Orders::findFirstByid($id);
		if (!$order) {
			$this->flash->error("Order was not found");
			$this->response->redirect('orders');

			return;
		}

		$this->view->currentClientId = $order->client_id;
		$this->view->id = $order->id;

		if (!$this->request->isPost()) {

			$this->tag->setDefault('id', $order->id);
			$this->tag->setDefault('name', $order->name);
			$this->tag->setDefault('client_id', $order->client_id);
			$this->tag->setDefault('code_prefix', $order->code_prefix);
			$this->tag->setDefault('created', $order->created);
			
		}

		$this->addEdit();
		$this->assets->collection('script')->addJs('/js/plugins/bootbox.min.js');
	}

	private function addEdit()
	{
		$clients = new \Phalcon\Mvc\Model\Query\Builder();

        $clients = $this->db->fetchAll("SELECT id, CONCAT(first_name,' ',last_name,' (',company,')') AS name FROM clients WHERE active=1 ORDER BY ID ASC", \Phalcon\Db::FETCH_ASSOC);
		$clients = array_combine(array_map(function(&$c){
			return $c['id'];
		}, $clients), array_map(function(&$c){
			return $c['name'];
		}, $clients));
		$this->view->clients = $clients;

		$this->assets->collection('script')
				->addJs('/js/admin/orders.addedit.js')
				->addJs('/template/js/plugins/select2/select2.full.min.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css');
	}

	/**
	 * Creates a new order
	 */
	public function createAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$order = new Orders();
		$order->client_id = $this->request->getPost('client_id', 'int');
		$order->name = $this->request->getPost('name', 'trim');
		$order->code_prefix = $this->request->getPost('code_prefix', 'trim');
		
		if (!$order->save()) {
			foreach ($order->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => 'orders',
				'action' => 'new'
			]);

			return;
		}

		$this->flash->success('Order was created successfully');

		$this->response->redirect('order_hunts/' . $order->id);
	}

	/**
	 * Saves a order edited
	 *
	 */
	public function saveAction()
	{
		if ($this->requireUser())
			return true;

		if (!$this->request->isPost()) {
			$this->response->redirect('orders');

			return;
		}

		$id = $this->request->getPost('id', 'int');
		$order = Orders::findFirstByid($id);

		if (!$order) {
			$this->flash->error('Order does not exist ' . $id);

			$this->response->redirect('orders');

			return;
		}

		$order->client_id = $this->request->getPost('client_id', 'int');
		$order->name = $this->request->getPost('name', 'trim');
		$order->code_prefix = $this->request->getPost('code_prefix', 'trim');
		
		if (!$order->save()) {

			foreach ($order->getMessages() as $message)
				$this->flash->error($message);

			$this->dispatcher->forward([
				'controller' => "orders",
				'action' => 'edit',
				'params' => [$order->id]
			]);

			return;
		}

		$this->flash->success("Order was updated successfully");

		$this->response->redirect('orders');
	}

	/**
	 * Deletes a order
	 *
	 * @param string $id
	 */
	public function deleteAction($id)
	{
		if ($this->requireUser())
			return true;

		$order = Orders::findFirstByid($id);
		if (!$order) {
			$this->flash->error("Order was not found");

			$this->response->redirect('orders');

			return;
		}

		if ($order->delete()) {
			$this->flash->success("Order was deleted successfully");
		} else {
			foreach ($order->getMessages() as $message)
				$this->flash->error($message);
		}

		$this->response->redirect('orders');
	}

	public function XLSXAction()
	{
		if ($this->requireUser())
			return true;

		$orderHunts = new \Phalcon\Mvc\Model\Query\Builder([
			'models'	=> ['oh' => 'OrderHunts'],
			'columns'	=> [
				'oh.id', 'oh.order_id', 'oh.start', 'oh.finish',
				'oh.expire', 'h.name as huntname', 'cl.first_name',
				'cl.last_name', 'cl.company', 'o.name as ordername',
				'oh.flags'
			],
			'order'		=> ['oh.order_id ASC', 'oh.id ASC']
		]);
		$orderHunts->leftJoin("Orders", 'o.id = oh.order_id', 'o');
		$orderHunts->leftJoin("Clients", 'cl.id = o.client_id', 'cl');
		$orderHunts->leftJoin("Hunts", 'h.id = oh.hunt_id', 'h');
		$orderHunts->leftJoin("Cities", 'c.id = h.city_id', 'c');

		$writer = new \XLSXWriter();
		$writer->writeSheetHeader('Orders', [
			'Order ID' => 'integer',
			'Order' => 'string',
			'OrderHunt ID' => 'integer',
			'Start' => 'string',
			'Finish' => 'string',
			'Expire' => 'string',
			'Hunt' => 'string',
			'Client' => 'string',
			'Players' => 'string',
			'Teams' => 'string',
			'Winning Team' => 'string',
			'Win time' => 'string',
			'Win score' => 'integer',
			'Public Page' => 'string'
		]);

		$tmpOh = new OrderHunts();
		$orderHunts = $orderHunts->getQuery()->execute();
		foreach ($orderHunts as $oh) {
			$tmpOh->id = $oh->id;
			$tmpOh->order_id = $oh->order_id;
			$tmpOh->flags = $oh->flags;
			$status = $tmpOh->getTeamsStatus();

			$tids = [];
			$ohids = [$oh->id => 0];
			foreach ($status as $t => $team) {
				$tids[] = $team['id'];
				$ohids[$team['order_hunt_id']] = 0;
			}
			$multihunt = $tmpOh->isMultiHunt() || count($ohids) > 1;
			$ohids = implode(',', array_keys($ohids));

			$teamname = $teamscore = $wintime = '';
			if (!empty($status)) {
				$teamname = $status[0]['name'];
				$teamscore = $status[0]['score'];
				$mm = \Answers::findFirst([
					'team_id=' . $status[0]['id'],
					'columns' => 'team_id, MAX(created) AS maxi',
					'group' => 'team_id'
				]);
				if ($mm)
					$wintime = $mm->maxi ? (new \DateTime($mm->maxi))->diff(new \DateTime($status[0]['activation']))->format('%H:%I:%S') : '00:00:00';
			}

			$writer->writeSheetRow('Orders', [
				(int)$oh->order_id,
				$oh->ordername,
				(int)$oh->id,
				$oh->start,
				$oh->finish,
				$oh->expire,
				$oh->huntname,
				$oh->first_name . ' ' . $oh->last_name . ' (' . $oh->company . ')',
				(empty($tids) ? 0 : $this->db->fetchColumn('SELECT COUNT(1) FROM players WHERE team_id IN (' . implode(',', $tids) . ')')) . '/' . $this->db->fetchColumn('SELECT COUNT(1) FROM players WHERE team_id IN (SELECT id FROM teams WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ')'),
				count($status) . '/' . $this->db->fetchColumn('SELECT COUNT(1) FROM teams WHERE order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids))),
				$teamname,
				$wintime,
				(int)$teamscore,
				'=HYPERLINK("' . $this->config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($this->crypt->encryptBase64($oh->id)) . '", "Public Page")'
			]);
		}

		header('Content-disposition: attachment; filename="orders.xlsx"');
		header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		header('Content-Transfer-Encoding: binary');
		$writer->writeToStdOut();
		exit;
	}

}
