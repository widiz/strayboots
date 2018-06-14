<?php

use Phalcon\Db;

class DanielleOrderHuntPostEvent extends OrderHuntMailBase {

	public function __construct(OrderHunts $orderHunt, bool $toPlayers = true)
	{
		parent::__construct($orderHunt);

		$translate = $this->translate;

		$this->client = $orderHunt->Order->Client;
		$this->title = $translate->_('We hope you had an awesome time yesterday');

		$di = Phalcon\Di::getDefault();
		$db = $di->get('db');

		$redis = $di->get('redis');
		$config = $di->get('config');
		
		if (!$eurl = $redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$endlink = $config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($di->get('crypt')->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($endlink, $config->bitly))
				$redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $endlink;
		}

		if ($toPlayers) {
			$ohids = [$orderHunt->id => 0];
			if ($orderHunt->isMultiHunt())
				$ohids = $ohids + array_flip(array_map('array_pop', $db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$ohids = implode(',', array_keys($ohids));
			$this->cc = array_map('array_pop', $db->fetchAll("SELECT email FROM players WHERE team_id IN (SELECT id FROM teams WHERE order_hunt_id IN (" . $ohids . ")) AND email IS NOT NULL", Db::FETCH_ASSOC));

			$this->text = $translate->_('DanielleOrderHuntPostEventPlayersText', [
				'url' => $eurl
			]);
			$this->html = $translate->_('DanielleOrderHuntPostEventPlayersHTML', [
				'url' => $eurl
			]);
		} else {
			$this->text = $translate->_('DanielleOrderHuntPostEventClientText', [
				'url' => $eurl
			]);
			$this->html = $translate->_('DanielleOrderHuntPostEventClientHTML', [
				'url' => $eurl
			]);
		}
	}

	public function setClientEmail($email)
	{
		$this->client = (object)[
			'email' => $email
		];
	}
}
