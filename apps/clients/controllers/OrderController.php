<?php

namespace Play\Clients\Controllers;

use \Clients,
	\OrderHunts,
	\Exception/*,
	\PayPal\CoreComponentTypes\BasicAmountType,
	\PayPal\EBLBaseComponents\PaymentDetailsItemType,
	\PayPal\EBLBaseComponents\PaymentDetailsType,
	\PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType,
	\PayPal\PayPalAPI\SetExpressCheckoutReq,
	\PayPal\PayPalAPI\SetExpressCheckoutRequestType,
	\PayPal\Service\PayPalAPIInterfaceServiceService*/;

class OrderController extends \ControllerBase
{
	const PricePerPlayer = [
		0 => 40,
		51 => 35
	];
	const MinPrice = 650;
	const MinPlayers = 15;

	/**
	 * Index action
	 */
	public function indexAction()
	{
		$isLoggedIn = $this->client instanceof Clients;

		if ($this->request->isPost() && $this->security->checkToken()) {

			if (!$isLoggedIn) {
				$client = new Clients();
				$client->email = $this->request->getPost('email', 'email');
				if ($client->email && Clients::findFirstByEmail($client->email)) {
					$this->flash->error('A client with this email address already exists. please login and refresh the page');
				} else {
					$client->company = $this->request->getPost('company', 'trim');
					$client->password = $this->generatePassword(8);//$this->request->getPost('password', 'trim');
					$client->first_name = $this->request->getPost('first_name', 'trim');
					$client->last_name = $this->request->getPost('last_name', 'trim');
					$client->phone = $this->request->getPost('phone', 'trim');
					$client->notes = null;
					if (empty($client->phone))
						$client->phone = null;

					if ($client->save()) {
						Clients::setClientLogin($client, true);
						$this->client = $client;
						$isLoggedIn = true;
						$this->sendMail($client->email, 'Your Strayboots Login', "Your password is: {$client->password}\r\nLogin at {$this->config->fullUri}/clients");
					} else {
						foreach ($client->getMessages() as $message)
							$this->flash->error($message);
					}
				}
			}

			if ($isLoggedIn) {

				$aTz = range('A', 'Z'); shuffle($aTz);
				$aTz2 = range('A', 'Z'); shuffle($aTz2);
				$aTz = array_merge($aTz, $aTz2); shuffle($aTz);

				$order = new \Orders();
				$order->client_id = $this->client->id;
				$order->code_prefix = implode('', array_slice($aTz, 0, 5));
				$order->name = 'Client Order';

				if ($order->validation()) {

					$orderHunt = new OrderHunts();
					$orderHunt->Order = $order;
					$orderHunt->hunt_id = $this->request->getPost('hunt_id', 'int');
					$orderHunt->max_players = $this->request->getPost('max_players', 'int');
					$orderHunt->max_teams = $this->request->getPost('max_teams', 'int');

					if ($orderHunt->start = strtotime($this->request->getPost('start', 'trim'))) {
						$orderHunt->finish = date('Y-m-d H:i:s', strtotime('+' . $orderHunt->Hunt->getDurationMinutes() . ' minutes', $orderHunt->start));
						$orderHunt->start = date('Y-m-d H:i:s', $orderHunt->start);
					} else {
						$orderHunt->start = '';
					}

					/*if ($orderHunt->finish = strtotime($this->request->getPost('finish', 'trim'))) {
						$orderHunt->expire = date('Y-m-d H:i:s', strtotime('+7 days', $orderHunt->finish));
						$orderHunt->finish = date('Y-m-d H:i:s', $orderHunt->finish);
					} else {
						$orderHunt->expire = date('Y-m-d H:i:s');
						$orderHunt->finish = '';
					}*/

					$orderHunt->pdf_start = $orderHunt->pdf_finish = $orderHunt->redirect = null;
					
					$orderHunt->setCustomLogin(false);
					$orderHunt->setDurationFinish(false);
					$orderHunt->setCanceled(true);
					$orderHunt->setMultiHunt(false);

					$isOk = true;
					if ($orderHunt->max_teams > ($ar = \Routes::count('hunt_id=' . (int)$orderHunt->hunt_id . ' AND active=1'))) {
						$orderHunt->appendMessage(new \Phalcon\Mvc\Model\Message(
							'Number of teams is bigger than number of available routes (' . $ar . ')',
							'max_teams',
							'error'
						));
						$isOk = false;
					}

					if ($isOk && $orderHunt->save()) {

						$this->sendLead($this->client, $orderHunt);

						if ($this->request->getPost('pay') == 1) {

							$this->flash->success('Ordered created, we\'ll be in touch with you shortly!');
							return $this->response->redirect('order_hunts/' . $orderHunt->order_id);

						} else if ($this->createPayment($orderHunt)) {
							return;
						}

					} else {
						foreach ($orderHunt->getMessages() as $message)
							$this->flash->error($message);
					}
				} else {
					foreach ($order->getMessages() as $message)
						$this->flash->error($message);
					$isOk = false;
				}
			}

		}

		$this->view->isLoggedIn = $isLoggedIn;
		$this->view->pricePerPlayer = self::PricePerPlayer;

		$huntsSelect = $hunts = [];

		$huntsQuery = $this->modelsManager->createBuilder()
							->columns('h.id, h.name, city_id, COUNT(r.id) AS routes')
							->from(['h' => 'Hunts'])
							->leftJoin('Routes', 'r.hunt_id = h.id', 'r')
							->where('h.approved = 1 AND (h.type_id = 2 OR h.type_id = 7)')
							->groupBy('h.id')
							->getQuery()
							->execute();
		foreach ($huntsQuery as $h) {
			if (!isset($hunts[$h->city_id]))
				$hunts[$h->city_id] = [];
			$hunts[$h->city_id][] = [
				'id' => $h->id,
				'max_teams' => $h->routes,
				'text' => $h->name
			];
			$huntsSelect[$h->id] = $h->name;
		}
		$this->view->hunts = $hunts;
		$this->view->huntsSelect = $huntsSelect;

		$countries = \Countries::find([
			'order' => 'name ASC'
		])->toArray();
		$countries = array_combine(array_map(function(&$c){
			return $c['id'];
		}, $countries), array_map(function(&$c){
			return $c['name'];
		}, $countries));
		$cities = \Cities::find([
			'order' => 'name ASC'
		])->toArray();
		$countrycities = [];
		foreach ($cities as $city) {
			if (!isset($hunts[$city['id']]))
				continue;
			$cname = $countries[$city['country_id']];
			if (isset($countrycities[$cname]))
				$countrycities[$cname][$city['id']] = $city['name'];
			else
				$countrycities[$cname] = [$city['id'] => $city['name']];
		}
		$this->view->countrycities = $countrycities;

		$this->assets->collection('script')
				->addJs('/template/js/plugins/select2/select2.full.min.js')
				->addJs('/template/js/plugins/moment/moment.min.js')
				->addJs('/template/js/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js')
				->addJs('/js/clients/order.js');
		$this->assets->collection('style')
				->addCss('/template/css/plugins/select2/select2.min.css')
				->addCss('/template/css/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css')
				->addCss('/css/clients/order.css');
	}

	private function sendLead(Clients $c, OrderHunts $oh)
	{
		$cities = [
			35		=> 'Atlanta',
			36		=> 'Austin',
			6		=> 'Boston',
			19		=> 'Boston',
			9		=> 'Chicago',
			20		=> 'Chicago',
			38		=> 'Indianapolis',
			5		=> 'Las&#x20;Vegas',
			21		=> 'Las&#x20;Vegas',
			3		=> 'Los&#x20;Angeles',
			22		=> 'Los&#x20;Angeles',
			16		=> 'Miami',
			23		=> 'Miami',
			39		=> 'Milwaukee',
			13		=> 'Nashville',
			24		=> 'Nashville',
			14		=> 'New&#x20;Orleans',
			25		=> 'New&#x20;Orleans',
			7		=> 'Philadelphia',
			28		=> 'Philadelphia',
			11		=> 'Portland(OR)',
			29		=> 'Portland(OR)',
			12		=> 'San&#x20;Diego',
			30		=> 'San&#x20;Diego',
			2		=> 'San&#x20;Francisco',
			31		=> 'San&#x20;Francisco',
			10		=> 'Seattle',
			32		=> 'Seattle',
			15		=> 'United&#x20;Kingdom',
			33		=> 'United&#x20;Kingdom',
			8		=> 'Washington&#x20;DC',
			34		=> 'Washington&#x20;DC'
		];

		$playersRange = '-None-';
		if ($oh->max_players >= 200)
			$playersRange = '200+';
		if ($oh->max_players >= 101)
			$playersRange = '101-200';
		if ($oh->max_players >= 51)
			$playersRange = '51-100';
		if ($oh->max_players >= 16)
			$playersRange = '16-50';
		if ($oh->max_players >= 8)
			$playersRange = '8-15';
		$data = [
			'LEADCF22' => 'Yes',
			'Lead Status' => 'New',
			'Lead Source' => '-None-',
			'LEADCF17' => $playersRange,
			'LEADCF13' => 'OrderHunt: ' . $oh->id,
			'Company' => $c->company,
			'First Name' => $c->first_name,
			'Last Name' => $c->last_name,
			'Email' => $c->email,
			'Phone' => is_null($c->phone) ? '-None-' : $c->phone,
			'LEADCF27' => '$30-$50',
			'LEADCF25' => 'Competitive&#x20;scavenger&#x20;hunt',
			'LEADCF9' => isset($cities[$oh->Hunt->city_id]) ? $cities[$oh->Hunt->city_id] : '-None-'
		];

		try {
			$xml = new \SimpleXMLElement('<Leads/>');
			$row = $xml->addChild('row');
			$row->addAttribute('no', '1');
			foreach ($data as $key => $value) {
				$d = $row->addChild('FL', $value);
				$d->addAttribute('val', $key);
			}
			$domxml = dom_import_simplexml($xml);

			file_get_contents('https://crm.zoho.com/crm/private/xml/Leads/insertRecords?newFormat=1&authtoken=0c5b941923485914b8a57c37ecce5541&scope=crmapi&xmlData=' . rawurlencode($domxml->ownerDocument->saveXML($domxml->ownerDocument->documentElement)));
		} catch(Exception $e) {}

		$this->sendMail('support@strayboots.com,ido@strayboots.com,ariel@safronov.co.il', "Online Order - {$oh->Hunt->name} / {$c->company}", print_r($data, true) . "\n\n\n" . $this->config->fullUri . '/order_hunts/edit/' . $oh->id);

	}

	private function getPricePerPlayer($players)
	{
		$pricePerPlayer = self::PricePerPlayer;
		reset($pricePerPlayer);
		$ppp = current($pricePerPlayer);
		while ($p = next($pricePerPlayer)) {
			if ($players >= key($pricePerPlayer))
				$ppp = $p;
			else
				break;
		}
		return $ppp;
	}

	private function createPayment(OrderHunts $oh)
	{
		$pricePerPlayer = $this->getPricePerPlayer($oh->max_players);
		$totalPrice = max(self::MinPrice, $pricePerPlayer * max(self::MinPlayers, $oh->max_players)) * 0.8;
		$orderName = $oh->Order->name . ' ' . $oh->Hunt->name;

		$mode = SB_PRODUCTION ? 'live' : 'sandbox';

		$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/clients/order/paypal?h=' . rawurlencode($this->crypt->encryptBase64($oh->id));

		echo '<form name="pplf" action="' . $this->config->paypal->{$mode}->ep . '" method="post">' .
				'<input type="hidden" name="cmd" value="_cart">' .
				'<input type="hidden" name="upload" value="1">' .
				'<input type="hidden" name="business" value="' . $this->config->paypal->account . '">' .
				'<input type="hidden" name="lc" value="US">' .
				'<input type="hidden" name="item_name_1" value="Strayboots - ' . $this->escaper->escapeHtmlAttr($orderName) . '">' .
				'<input type="hidden" name="amount_1" value="' . $totalPrice . '">' .
				'<input type="hidden" name="notify_url" value="'. $baseURL . '&success=true&notify=true">' .
				'<input type="hidden" name="return" value="'. $baseURL . '&success=true">' .
				'<input type="hidden" name="cancel_return" value="'. $baseURL . '&success=false">' .
				'<input type="hidden" name="on0_1" value="">' .
				'<input type="hidden" name="custom" value="' . $oh->id . '">' .
				'<input type="hidden" name="currency_code" value="USD">' .
				'<input type="hidden" name="button_subtype" value="services">' .
				'<input type="hidden" name="no_note" value="0">' .
				'<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest">' .
				'<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">' .
			'</form><script>document.forms.pplf.submit();</script>';
		exit;
	}

	public function paypalAction()
	{
		$id = $this->crypt->decryptBase64(str_replace(' ', '+', $this->request->getQuery('h')));

		$orderHunt = $id > 0 ? OrderHunts::findFirstByid((int)$id) : false;
		if (!$orderHunt)
			return $this->response->redirect('orders');

		$isOk = $this->request->getQuery('success') === 'true';

		if ($isOk && $this->request->getQuery('notify') === 'true') {

			$mode = SB_PRODUCTION ? 'live' : 'sandbox';

			$_POST['cmd'] = '_notify-validate';
			$req = http_build_query($_POST);
			$ch = curl_init($this->config->paypal->{$mode}->ep);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/x-www-form-urlencoded',
				'Content-Length: ' . strlen($req)
			]);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			$curl_result = @curl_exec($ch);
			curl_close($ch);

			$isOk = ((strpos($curl_result, 'VERIFIED') !== false && $_POST['payment_status'] === 'Completed') || !SB_PRODUCTION)
					&& $_POST['receiver_email'] === $this->config->paypal->account
					&& rtrim($_POST['custom'], ',') == $orderHunt->id;

			if ($isOk && $orderHunt->isCanceled()) {
				$orderHunt->setCanceled(false);
				$isOk = $orderHunt->save();
			}

			$this->logger->log('ppl: ' .($isOk ? 'y':'n') . $curl_result . json_encode($_GET) . json_encode($_POST));

			exit;
		}

		$this->logger->log('ppl: ' .($isOk ? 'y':'n') . json_encode($_GET) . json_encode($_POST));

		if ($isOk && $orderHunt->isCanceled()) {
			$i = 30;
			$isOk = false;
			while (--$i) {
				$canceled = $this->db->fetchColumn('SELECT SQL_NO_CACHE IF(flags&4=4,1,0) FROM order_hunts WHERE id=' . $orderHunt->id);
				if (!$canceled) {
					$isOk = true;
					break;
				}
				sleep(1);
			}
		}

		if ($isOk) {
			$this->flash->success('Ordered created, we\'ll be in touch with you shortly!');
			return $this->response->redirect('order_hunts/' . $orderHunt->order_id);
		} else {
			$this->flash->success('Order failed; please contact support');
			return $this->response->redirect('order');
		}
	}

	/*private function createPayment(OrderHunts $oh)
	{
		try {

			$orderTotal = new BasicAmountType();
			$orderTotal->currencyID = 'USD';
			$orderTotal->value = $totalPrice;

			$taxTotal = new BasicAmountType();
			$taxTotal->currencyID = 'USD';
			$taxTotal->value = '0.0';

			$itemDetails = new PaymentDetailsItemType();
			$itemDetails->Name = $orderName;
			$itemDetails->ItemCategory =  'Digital';
			$itemDetails->Quantity = 1;
			$itemDetails->Amount = $orderTotal;

			$PaymentDetails= new PaymentDetailsType();
			$PaymentDetails->PaymentDetailsItem[0] = $itemDetails;
			$PaymentDetails->PaymentAction = 'Sale';
			$PaymentDetails->OrderTotal = $orderTotal;
			$PaymentDetails->ItemTotal = $orderTotal;
			$PaymentDetails->TaxTotal = $taxTotal;

			$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/clients/order/paypal?h=' . rawurlencode($this->crypt->encryptBase64($oh->id));

			$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
			$setECReqDetails->PaymentDetails[0] = $PaymentDetails;
			$setECReqDetails->CancelURL = $baseURL . '&success=false';
			$setECReqDetails->ReturnURL = $baseURL. '&success=true';
			$setECReqDetails->ReqConfirmShipping = 0;
			$setECReqDetails->NoShipping = 1;

			$setECReqType = new SetExpressCheckoutRequestType();
			$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
			$setECReq = new SetExpressCheckoutReq();
			$setECReq->SetExpressCheckoutRequest = $setECReqType;

			$this->session->set('paypal.currency', $orderTotal->currencyID);
			$this->session->set('paypal.amount', $orderTotal->value);

			$setECResponse = $this->paypal->SetExpressCheckout($setECReq);

			if ($setECResponse->Ack == 'Success') {
				$this->response->redirect('https://www.sandbox.paypal.com/incontext?token=' . $setECResponse->Token);
				return true;
			}

		} catch (Exception $e) {
			//echo $e->getCode() . ' ' . $e->getData();die;
			try {
				$this->logger->critical("Paypal Payment Error #4762: " . $e->getCode() . ' - ' . $e->getMessage());
			} catch(Exception $e) { }
		}
		return false;
	}

	public function paypalAction()
	{
		$id = $this->crypt->decryptBase64(str_replace(' ', '+', $this->request->getQuery('h')));

		$orderHunt = $id > 0 ? OrderHunts::findFirstByid((int)$id) : false;
		if (!$orderHunt)
			return $this->response->redirect('orders');

		$isOk = $this->request->getQuery('success') === 'true';

		if ($isOk) {

			$apiContext = $this->paypal;

			$paymentId = $this->request->getQuery('paymentId');
			$payment = Payment::get($paymentId, $apiContext);

			$execution = new PaymentExecution();
			$execution->setPayerId($this->request->getQuery('PayerID'));

			try {
				$result = $payment->execute($execution, $apiContext);
				$isOk = $payment->getId() !== false;
				try {
					$payment = Payment::get($paymentId, $apiContext);
					$isOk = $payment->getId() == $paymentId;
				} catch (Exception $ex) {
					$isOk = false;
				}
			} catch (Exception $ex) {
				$isOk = false;
			}

		}

		if ($isOk && $orderHunt->isCanceled()) {
			$orderHunt->setCanceled(false);
			$isOk = $orderHunt->save();
		}

		if ($isOk) {
			$this->flash->success("Order completed");
			return $this->response->redirect('order_hunts/' . $orderHunt->order_id);
		} else {
			$this->flash->success("Order failed; please contact support");
			return $this->response->redirect('order');
		}
	}*/
}
