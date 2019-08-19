<?php

class WatchdogTask extends TaskBase
{
	public function mainAction($args = [])
	{
		echo 'Watchdog v' . VERSION . PHP_EOL;
		$cache = isset($args[0]) ? (bool)$args[0] : true;

		$start = date('Y-m-d H:i:s', strtotime('-35 minutes'));
		$end = date('Y-m-d H:i:s', strtotime('+20 minutes'));

		echo 'Starting alert' . PHP_EOL;
		echo 'Querying...';
		$flagged = $this->db->fetchAll(<<<EOF
SELECT oh.id, h.name as huntname,
o.name as ordername, c.company
FROM order_hunts oh
LEFT JOIN orders o ON (o.id = oh.order_id)
LEFT JOIN hunts h ON (h.id = oh.hunt_id)
LEFT JOIN clients c ON (c.id = o.client_id)
WHERE oh.start >= '{$start}' AND oh.start <= '{$end}'
AND oh.flags & 4 = 0
EOF
		, Phalcon\Db::FETCH_ASSOC);
		echo 'done' . PHP_EOL;

		$link = $this->config->fullUri . '/admin/order_hunts/summary/';
		$textBase = "Please double check:\r\n1 - Design\r\n2 - Start and end time\r\n3 - Bonus questions\r\n4 - Event host, if needed\r\n5 - Any special requests";
		$htmlBase = nl2br($textBase);
		foreach ($flagged as $i => $orderHunt) {
			echo 'Processing OrderHunt #' . $orderHunt['id'] . '...';
			if ($cache && $this->redis->exists(SB_PREFIX . 'alert:na:' . $orderHunt['id'])) {
				unset($flagged[$i]);
				echo 'cached' . PHP_EOL;
				continue;
			}
			$text = "#" . $orderHunt['id'] . ": " . $orderHunt['company'] . ' / ' . $orderHunt['ordername'] . ' / ' . $orderHunt['huntname'] . ' ' . $link . $orderHunt['id'] . "\r\n\r\n" . $textBase;
			$html = '<a href="' . $link . $orderHunt['id'] . '">#' . $orderHunt['id'] . ': ' . htmlspecialchars($orderHunt['company'] . ' / ' . $orderHunt['ordername'] . ' / ' . $orderHunt['huntname']) . '</a><br><br>' . $htmlBase;
			if ($this->sendMail('ashley@strayboots.com,danielle@strayboots.com,ido@strayboots.com,content@strayboots.com', 'Please verify the upcoming hunt for ' . $orderHunt['company'], $text, $html))
				$this->redis->set(SB_PREFIX . 'alert:na:' . $orderHunt['id'], 1, 7200);
			echo 'done' . PHP_EOL;
		}

		echo 'done' . PHP_EOL . PHP_EOL;
		echo 'Starting Watchdog' . PHP_EOL;

		$hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
		$twentyMinAgo = date('Y-m-d H:i:s', strtotime('-20 minutes'));
		echo 'Querying...';
		$flagged = $this->db->fetchAll(<<<EOF
SELECT z.* FROM (
	SELECT y.id, y.huntname, y.ordername, y.company,
		(SUM(y.activated > 0) * 100 / SUM(1)) AS activated,
		(SUM(y.answered > 0) * 100 / SUM(1)) AS answered
	FROM (
		SELECT oh.id, h.name as huntname,
		o.name as ordername, c.company,
		IF(ISNULL(t.activation),0,1) AS activated,
		SUM(!ISNULL(a.id)) AS answered
		FROM `order_hunts` oh LEFT JOIN teams t ON (t.order_hunt_id=oh.id)
		LEFT JOIN answers a ON (a.team_id = t.id)
		LEFT JOIN orders o ON (o.id = oh.order_id)
		LEFT JOIN hunts h ON (h.id = oh.hunt_id)
		LEFT JOIN clients c ON (c.id = o.client_id)
		WHERE oh.start >= '{$hourAgo}' AND oh.start <= '{$twentyMinAgo}' AND oh.flags & 4 = 0
		GROUP BY t.id
	) y
	GROUP BY y.id
) z WHERE z.activated < 50 OR z.answered < 50
EOF
		, Phalcon\Db::FETCH_ASSOC);
		echo 'done' . PHP_EOL;

		foreach ($flagged as $i => $orderHunt) {
			echo 'Processing OrderHunt #' . $orderHunt['id'] . '...';
			if ($cache && $this->redis->exists(SB_PREFIX . 'watchdog:na:' . $orderHunt['id'])) {
				unset($flagged[$i]);
				echo 'cached' . PHP_EOL;
				continue;
			}
			$this->redis->set(SB_PREFIX . 'watchdog:na:' . $orderHunt['id'], 1, 7200);
			echo 'done' . PHP_EOL;
		}

		if (!empty($flagged)) {
			$text = "The following hunts have been flagged:\r\n";
			$html = 'The following hunts have been flagged:<br>';
			foreach ($flagged as $orderHunt) {
				$link = $this->config->fullUri . '/admin/order_hunts/summary/' . $orderHunt['id'];
				$text .= "\r\n#" . $orderHunt['id'] . ": " . $orderHunt['company'] . ' / ' . $orderHunt['ordername'] . ' / ' . $orderHunt['huntname'] . ' ' . $link;
				$html .= '<br><a href="' . $link . '">#' . $orderHunt['id'] . ': ' . htmlspecialchars($orderHunt['company'] . ' / ' . $orderHunt['ordername'] . ' / ' . $orderHunt['huntname']) . '</a>';
			}
			$this->sendMail('support@strayboots.com,nikki@strayboots.com,ido@strayboots.com', 'Watchdog: Hunts are not activated', $text, $html, [], [
				'cc' => 'ariel@safronov.co.il,danielle@strayboots.com'
			]); 
		}
	}
}
