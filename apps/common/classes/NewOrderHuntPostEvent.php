<?php

use \Phalcon\Db;

class NewOrderHuntPostEvent extends OrderHuntMailBase {

	public function __construct(OrderHunts $orderHunt, bool $toPlayers = true, bool $toClient = true)
	{
		parent::__construct($orderHunt);

		$translate = $this->translate;

		$this->client = $orderHunt->Order->Client;
		$this->title = $translate->_('It’s final--the scores are in');

		$di = Phalcon\Di::getDefault();
		$db = $di->get('db');

		$ohids = [$orderHunt->id => 0];
		$files = [];
		if ($orderHunt->isMultiHunt()) {
			$ohids = $ohids + array_flip(array_map('array_pop', $db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$ohids = implode(',', array_keys($ohids));
			$orderHunts = OrderHunts::find('id in (' . $ohids . ')');
			foreach ($orderHunts as $oh)
				$files[$oh->id] = $oh->getFiles();
		} else {
			$ohids = implode(',', array_keys($ohids));
			$files[$orderHunt->id] = $orderHunt->getFiles();
		}

		if ($toPlayers) {
			$this->cc = array_map('array_pop', $db->fetchAll('SELECT email FROM players WHERE team_id IN (SELECT id FROM teams WHERE order_hunt_id IN (' . $ohids . ')) AND email IS NOT NULL', Db::FETCH_ASSOC));
		}
		if (!$toClient) {
			if (count($this->cc))
				$this->client->email = array_shift($this->cc);
			else
				return;
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

		$teamStatus = array_slice($orderHunt->getTeamsStatus(), 0, 3);
		$itxt = ['1st Place', '2nd Place', '3rd Place'];
		$mvp = false;
		for ($ix = count($teamStatus); $ix < 3; $ix++)
			$itxt[$ix] = '';
		$images = ['', '', '']; $ix = 0;
		foreach ($teamStatus as $t => $ts) {
			foreach ($files as $ohid => $ff) {
				foreach ($ff as $f) {
					if ($f[1] == $ts['id']) {
						$images[$ix++] = '<a href="' . $eurl . '#photos"><img src="' . $config->fullUri . $config->application->frontUploadsDir->uri . $ohid . '/' . preg_replace('/\.(jpg|png|gif)$/', '.thumbnail.$1', $f[0]) . '" style="max-width:100%"></a>';
						$mvp = true;
						continue 3;
					}
				}
			}
			$itxt[$ix++] = '';
		}

		$mvpH = $translate->_('MVP Highlights');

		$this->text = $translate->_('NewOrderHuntPostEventText', [
			'url' => $eurl
		]);
		if ($mvp) {
			$mvp = <<<EOF
<br>
<b>{$mvpH}:</b><br>
<br>
<br>
<table cellspacing="0" cellpadding="0" border="0" width="580" style="max-width:100%;margin:0 auto">
	<tr>
		<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[0]}</td>
		<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[1]}</td>
		<td width="33.3%" style="text-align:center;vertical-align:middle">{$images[2]}</td>
	</tr>
	<tr>
		<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;max-width:160px;margin:0 auto">{$itxt[0]}</div></td>
		<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;max-width:160px;margin:0 auto">{$itxt[1]}</div></td>
		<td width="33.3%" style="vertical-align:middle"><div style="text-align:left;font-weight:bold;max-width:160px;margin:0 auto">{$itxt[2]}</div></td>
	</tr>
</table>
EOF;
		} else {
			$mvp = '';
		}
		$this->html = $translate->_('NewOrderHuntPostEventHTML', [
			'url' => $eurl,
			'mvp' => $mvp
		]);
	}
}
