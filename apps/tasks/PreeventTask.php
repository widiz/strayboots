<?php

class PreeventTask extends TaskBase
{
	public function mainAction($args = [])
	{
		$cache = isset($args[0]) ? (bool)$args[0] : true;
		$verbose = isset($args[1]) ? (bool)$args[0] : true;
		if ($verbose) {
			echo 'Pre Event v' . VERSION . PHP_EOL;
			echo 'Querying...';
		}
		$now = date('Y-m-d H:i:s');
		$tomorrow = date('Y-m-d', strtotime('+1 day'));
		$bonusQuestions = $this->db->fetchAll("SELECT bq.id, bq.type, bq.score, bq.question, bq.winner_id, oh.id AS ohid, oh.start, oh.finish, p.first_name, p.last_name, p.email FROM bonus_questions bq LEFT JOIN order_hunts oh ON (oh.id = bq.order_hunt_id) LEFT JOIN players p ON (p.id = bq.winner_id) WHERE oh.start <= '{$tomorrow} 00:00:00' AND oh.finish > '{$now}' AND oh.flags & 4 = 0 ORDER BY bq.id ASC", Phalcon\Db::FETCH_ASSOC);
		if ($verbose) {
			echo 'done' . PHP_EOL;
			echo 'Processing data...';
		}
		$orderHunts = $orderHuntsData = [];
		foreach ($bonusQuestions as $bq) {
			if (!isset($orderHunts[$bq['ohid']])) {
				$orderHunts[$bq['ohid']] = [];
				$orderHuntsData[$bq['ohid']] = [strtotime($bq['start']), strtotime($bq['finish'])];
			}
			$orderHunts[$bq['ohid']][] = is_null($bq['winner_id']) ?
								[(int)$bq['id'], (int)$bq['type'], (int)$bq['score'], $bq['question']] : false;
		}
		if ($verbose)
			echo 'done' . PHP_EOL;
		if (!empty($orderHunts)) {
			unset($bonusQuestions);
			$time = time();
			foreach ($orderHunts as $ohId => $bq) {
				if ($verbose)
					echo 'Processing OrderHunt #' . $ohId . '...';
				if ($cache && $this->redis->exists(SB_PREFIX . 'bonusq:set:' . $ohId)) {
					if ($verbose)
						echo 'cached' . PHP_EOL;
					continue;
				}
				$huntTime = min(3600 /* max 1 hour */, $orderHuntsData[$ohId][1] - $orderHuntsData[$ohId][0]);
				if (!($huntTime > 0)) {
					try {
						$this->logger->critical('Preevent error: failed to calculate intervals for ' . $ohId);
					} catch(Exception $e) { }
					continue;
				}
				$interval = ceil(($huntTime - 1200 /*20min offset*/) / (count($bq) + 1));
				if (!($interval > 1)) {
					try {
						$this->logger->critical('Preevent error2: failed to calculate intervals for ' . $ohId);
					} catch(Exception $e) { }
					continue;
				}
				if (($fbr = $this->firebase->set(FB_PREFIX . 'bonusq/' . $ohId, $bq, [], 3)) != json_encode($bq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
					try {
						$this->logger->critical('Firebase error: failed to set bonusq ' . $fbr);
					} catch(Exception $e) { }
				} else {
					$interval = [$huntTime, $interval];
					if (($fbr = $this->firebase->set(FB_PREFIX . 'hqbonusinterval/' . $ohId, $interval, [], 3)) != json_encode($interval, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
						try {
							$this->logger->critical('Firebase error: failed to set bonus intervals ' . $fbr);
						} catch(Exception $e) { }
					} else{
						$this->redis->set(SB_PREFIX . 'bonusq:set:' . $ohId, 'tes', (int)max(8e4, $orderHuntsData[$ohId][1] - $time));
						if ($verbose)
							echo 'done' . PHP_EOL;
						continue;
					}
				}
				if ($verbose)
					echo 'failed' . PHP_EOL;
			}
		}
	}
}
