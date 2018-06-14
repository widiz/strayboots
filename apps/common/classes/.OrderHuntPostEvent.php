<?php

use \Phalcon\Db;

class OrderHuntPostEvent extends OrderHuntMailBase {

	public function __construct(OrderHunts $orderHunt, bool $toPlayers = true)
	{
		parent::__construct($orderHunt);

		$translate = $this->translate;

		$order = $orderHunt->Order;
		$hunt = $orderHunt->Hunt;
		$this->client = $order->Client;
		$this->title = $translate->_('Scavenger Hunt Scores and Photos - %order% / %hunt%', [
			'order' => $order->name,
			'hunt' => $hunt->name
		]);
		$teamStatus = $orderHunt->getTeamsStatus();
		/*usort($teamStatus, function($a, $b){
			return $a['num'] > $b['num'] ? 1 : -1;
		});*/

		$di = Phalcon\Di::getDefault();
		$db = $di->get('db');

		$ohids = [$orderHunt->id => 0];
		$teamNames = $tids = [];
		foreach ($teamStatus as $t => $team) {
			$ohids[$team['order_hunt_id']] = 0;
			$tids[] = $team['id'];
			$teamNames[$team['id']] = $team['name'];
		}
		$multihunt = count($ohids) > 1 || $orderHunt->isMultiHunt();
		if ($multihunt) {
			$oh = array_flip(array_map('array_pop', $db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Db::FETCH_ASSOC)));
			$ohids += $oh;
		}
		$ohids = implode(',', array_keys($ohids));

		if (!empty($tids)) {
			$teamPos = $teamAns = $teamHints = [];
			$tids = implode(',', $tids);
			$group = $db->fetchAll('SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') AND action != ' . Answers::Skipped . ' GROUP BY team_id) t GROUP BY team_id', Db::FETCH_ASSOC);
			foreach ($group as $g)
				$teamPos[$g['team_id']] = (int)$g['rowcount'];
			$answers = Answers::find([
				'team_id IN (' . $tids . ')',
				'columns' => 'team_id, MAX(created) AS maxi',
				'group' => 'team_id'
			]);
			foreach ($answers as $a)
				$teamAns[$a->team_id] = $a->maxi;
			$group = $db->fetchAll('SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') AND action = ' . Answers::AnsweredWithHint . ' GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') AND action = ' . Answers::AnsweredWithHint . ' GROUP BY team_id) t GROUP BY team_id', Db::FETCH_ASSOC);
			foreach ($group as $g)
				$teamHints[$g['team_id']] = (int)$g['rowcount'];
			foreach ($teamStatus as $t => $team) {
				$x = isset($teamAns[$team['id']]) ? $teamAns[$team['id']] : false;
				$teamStatus[$t]['totaltime'] = $x ? (new DateTime($teamStatus[$t]['activation']))->diff(new DateTime($x))->format('%H:%I:%S') : '';
				$teamStatus[$t]['hints'] = isset($teamHints[$team['id']]) ? $teamHints[$team['id']] : 0;
				$teamStatus[$t]['question'] = isset($teamPos[$team['id']]) ? $teamPos[$team['id']] : 0;
			}
			if ($toPlayers) {
				$players = Players::find([
					'team_id IN (' . $tids . ')',
					'columns' => 'email'
				]);
				foreach ($players as $p)
					$this->cc[] = $p['email'];
			}
		}

		if ($multihunt) {
			$max = (int)$db->fetchColumn(
				'SELECT SUM(c) FROM (SELECT COUNT(1) as c FROM custom_questions  WHERE order_hunt_id IN (' . $ohids . ') UNION ALL SELECT COUNT(1) as c FROM hunt_points hp LEFT JOIN order_hunts oh ON (oh.hunt_id = hp.hunt_id) WHERE oh.order_id = ' . (int)$orderHunt->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8) t'
			);
		} else {
			$max = HuntPoints::count('hunt_id=' . $orderHunt->hunt_id) + $orderHunt->countCustomQuestions();
		}

		$bonusQuestions = $db->fetchAll('SELECT bq.*, p.team_id, p.email, p.first_name, p.last_name FROM bonus_questions bq LEFT JOIN players p ON (p.id = bq.winner_id) WHERE bq.order_hunt_id' . ($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND bq.winner_id IS NOT NULL', Db::FETCH_ASSOC);

		$bonusQuestionsTXT = implode("\n", array_map(function($bq) use ($teamNames){
			return '* ' . $bq['email'] .
					(is_null($bq['first_name']) ? '' :  (' ' . htmlspecialchars(trim($bq['first_name'] . ' ' . $bq['last_name'])))) . 
					' from team ' . $teamNames[$bq['team_id']] . ($bq['type'] == BonusQuestions::TypeTeam ?
						' won ' . $bq['score'] . ' points for the question "' : ' answered the question "') .
					$bq['question'] . '"';
		}, $bonusQuestions));
		if (!empty($bonusQuestionsTXT))
			$bonusQuestionsTXT = "\n\n" . $bonusQuestionsTXT;

		$customQuestions = $db->fetchAll(
			'SELECT cq.*, ca.team_id, ca.created FROM custom_answers ca FORCE INDEX (orderaction) ' .
			'LEFT JOIN custom_questions cq ON (ca.custom_question_id = cq.id) WHERE cq.order_hunt_id' .
			($multihunt ? ' IN (' . $ohids . ')' : ('=' . $ohids)) . ' AND ca.action !=' . Answers::Skipped,
		Db::FETCH_ASSOC);

		$customQuestionsTXT = implode("\n", array_map(function($cq) use ($teamNames){
			return '* Team ' . $teamNames[$cq['team_id']] . ' won ' . $cq['score'] . ' points for the question "' .
					mb_strimwidth($cq['question'], 0, 110, '...') . '"';
		}, $customQuestions));
		if (!empty($customQuestionsTXT))
			$customQuestionsTXT = "\n\n" . $customQuestionsTXT;

		$teamStatusTXT = implode("\n", array_map(function($team) use ($max){
			return $team['name'] . ' - ' . $team['score'] . ' - ' . $team['question'] . '/' . $max . ' (Hints used - ' . $team['hints'] . ') - ' . $team['totaltime'];
		}, $teamStatus));

		$redis = $di->get('redis');
		$config = $di->get('config');
		
		if (!$eurl = $redis->get(SB_PREFIX . 'elink:' . $orderHunt->id)) {
			$endlink = $config->fullUri . '/clients/order_hunts/end/?h=' . rawurlencode($di->get('crypt')->encryptBase64($orderHunt->id));
			if ($eurl = $this->bitly($endlink, $config->bitly))
				$redis->set(SB_PREFIX . 'elink:' . $orderHunt->id, $eurl, max(strtotime($orderHunt->expire) - time(), 0) + 604800);
			else
				$eurl = $endlink;
		}

		$this->text = <<<EOF
Hi {$this->client->first_name}!
Thanks so much for doing a scavenger hunt with us and congrats to the 
winners! Let us know that you caught this email so we know you're set!

Here are the results and the team photos:

{$teamStatusTXT}
{$bonusQuestionsTXT}
{$customQuestionsTXT}


<b>TEAM PHOTOS</b> 
You can find your pictures at this link {$eurl}

Lastly, we would love to hear what the group thought about the experience.
You can just forward this email to them to share the scores and photos, and 
if they can spare 2 minutes, here's a very quick survey for them:

https://www.surveymonkey.com/r/posthunt

We hope all of your teams enjoyed and return to play another hunt with us soon!

Thank You!

~ The Strayboots Squad ~
(877) 787-2929
events@strayboots.com

EOF;
		$this->html = <<<EOF
Hi {$this->client->first_name}!<br>
Thanks so much for doing a scavenger hunt with us and congrats to the <br>
winners! Let us know that you caught this email so we know you're set!<br>
<br>
EOF;
		if (!empty($teamStatus)) {
			$this->html .= <<<EOF
Here are the results and the team photos:<br>
<br>
<table border="0" cellpadding="0" cellspacing="0" width="99%" style="margin:0;padding:0;border:0;border-collapse:collapse">
	<thead>
		<tr style="background-color:#e7e7e7;">
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Position</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Team</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Score</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Correct Answers (+Hints)</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Total Time</b></th>
		</tr>
	</thead>
	<tbody>
EOF;
			foreach ($teamStatus as $team) {
				$this->html .= '<tr>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $team['position'] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $team['name'] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $team['score'] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $team['question'] . '/' . $max . ' (Hints used - ' . $team['hints'] . ')</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $team['totaltime'] . '</td>' .
						'</tr>';
			}
			$this->html .= "</tbody></table><br>";
		}
		if (!empty($bonusQuestions)) {
			$this->html .= <<<EOF
<br>Bonus questions winners:<br>
<br>
<table border="0" cellpadding="0" cellspacing="0" width="99%" style="margin:0;padding:0;border:0;border-collapse:collapse">
	<thead>
		<tr style="background-color:#e7e7e7;">
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Question</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Team</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Player</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Score</b></th>
		</tr>
	</thead>
	<tbody>
EOF;
			foreach ($bonusQuestions as $bq) {
				$this->html .= '<tr>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . mb_strimwidth($bq['question'], 0, 110, '...') . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $teamNames[$bq['team_id']] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $bq['email'] . (is_null($bq['first_name']) ? '' :  (' ' . htmlspecialchars(trim($bq['first_name'] . ' ' . $bq['last_name'])))) . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . ($bq['type'] == BonusQuestions::TypeTeam ? $bq['score'] : '') . '</td>' .
						'</tr>';
			}
		}
		if (!empty($customQuestions)) {
			$this->html .= <<<EOF
<br>Custom questions winners:<br>
<br>
<table border="0" cellpadding="0" cellspacing="0" width="99%" style="margin:0;padding:0;border:0;border-collapse:collapse">
	<thead>
		<tr style="background-color:#e7e7e7;">
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Question</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Team</b></th>
			<th style="border:1px solid #AAA;padding:5px 8px"><b>Score</b></th>
		</tr>
	</thead>
	<tbody>
EOF;
			foreach ($customQuestions as $bq) {
				$this->html .= '<tr>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $bq['question'] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $teamNames[$bq['team_id']] . '</td>' .
							'<td style="border:1px solid #AAA;padding:5px 8px">' . $bq['score'] . '</td>' .
						'</tr>';
			}
			$this->html .= "</tbody></table><br>";
		}
		$this->html .= <<<EOF
<br>
<b>TEAM PHOTOS</b> <br>
You can find your pictures at this link <a href="{$eurl}">{$eurl}</a><br>
<br>
Lastly, we would love to hear what the group thought about the experience.<br>
You can just forward this email to them to share the scores and photos, and <br>
if they can spare 2 minutes, here's a very quick survey for them:<br>
<br>
<a href="https://www.surveymonkey.com/r/posthunt">https://www.surveymonkey.com/r/posthunt</a><br>
<br>
We hope all of your teams enjoyed and return to play another hunt with us soon!<br>
<br>
Thank You!<br>

<br>
~ The Strayboots Squad ~<br>
(877) 787-2929<br>
<a href="mailto:events@strayboots.com">events@strayboots.com</a><br>

EOF;
	}
}
