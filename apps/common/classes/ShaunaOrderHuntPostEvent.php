<?php

use Phalcon\Db;

class ShaunaOrderHuntPostEvent extends OrderHuntMailBase {

	public function __construct(OrderHunts $orderHunt, bool $toPlayers = false)
	{
		parent::__construct($orderHunt);

		$data = EventEmails::findFirstByEmailId(EventEmails::ShaunaPostEventEmail);

		$translate = $this->translate;

		$this->client = $orderHunt->Order->Client;
		$this->title = $data ? $data->title : $translate->_('What if you could motivate your employees with ONE word...');

		$di = Phalcon\Di::getDefault();
		$db = $di->get('db');

		if ($toPlayers) {
			$ohids = [$orderHunt->id => 0];
			if ($orderHunt->isMultiHunt())
				$ohids = $ohids + array_flip(array_map('array_pop', $db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$ohids = implode(',', array_keys($ohids));
			$this->cc = array_map('array_pop', $db->fetchAll('SELECT email FROM players WHERE team_id IN (SELECT id FROM teams WHERE order_hunt_id IN (' . $ohids . ')) AND email IS NOT NULL', Db::FETCH_ASSOC));
		}

		$redis = $di->get('redis');
		$config = $di->get('config');
		
		if (!$eurl = $redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$endlink = $config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($di->get('crypt')->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($endlink, $config->bitly))
				$redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $endlink;
		}

		$this->text = $data ? str_replace('%url%', $eurl, $data->text) : $translate->_('ShaunaOrderHuntPostEventText', [
			'url' => $eurl
		]);
		$this->html = $data ? str_replace('%url%', $eurl, $data->html) : $translate->_('ShaunaOrderHuntPostEventHTML', [
			'url' => $eurl
		]);

	}
}
