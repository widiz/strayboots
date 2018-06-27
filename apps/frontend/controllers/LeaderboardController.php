<?php

namespace Play\Frontend\Controllers;

class LeaderboardController extends ControllerBase
{

	public function indexAction()
	{
		if ($this->requirePlayer())
			return true;
		if ($this->orderHunt->id == 1850) //loyalty
			return $this->response->redirect('/play');
		// Set current status
		$teamsStatus = $this->orderHunt->getTeamsStatus();
		$tids = [];
		foreach ($teamsStatus as $team) {
			$tids[] = $team['id'];
			if ($team['id'] == $this->team->id)
				$this->view->teamStatus = $team;
		}

		$questions = $this->db->fetchAll(<<<EOF
SELECT q.id, q.point_id, q.type_id, q.name, q.question, q.qattachment, q.hint, 
	q.funfact, q.response_correct, q.answers, q.attachment, q.timeout, 0 AS `customq`, 
	IF(q.score IS NULL,qt.score,q.score) as `cscore`, rp.idx, a.created, 
	qt.type as `question_type`, qt.limitAnswers, a.action as `answer_action`, a.funfact_viewed, a.id as aid 
FROM route_points rp 
	LEFT JOIN hunt_points hp ON (rp.hunt_point_id = hp.id) 
	LEFT JOIN questions q ON (hp.question_id = q.id) 
	LEFT JOIN question_types qt ON (q.type_id = qt.id) 
	LEFT JOIN answers a ON (a.team_id = {$this->team->id} AND a.hunt_id = {$this->orderHunt->hunt_id} AND a.question_id = q.id) 
WHERE rp.route_id = {$this->team->route_id} 
UNION ALL SELECT cq.id, NULL as `point_id`, cq.type_id, cq.name, cq.question, cq.qattachment, cq.hint, cq.funfact, 
		cq.response_correct, cq.answers, cq.attachment, cq.timeout, 1 AS `customq`, cq.score as `cscore`, cq.idx, ca.created, 
		qt.type as `question_type`, qt.limitAnswers, ca.action as `answer_action`, ca.funfact_viewed, ca.id as aid 
FROM custom_questions cq 
	LEFT JOIN question_types qt ON (cq.type_id = qt.id) 
	LEFT JOIN custom_answers ca ON (ca.team_id = {$this->team->id} AND ca.custom_question_id = cq.id) 
WHERE cq.order_hunt_id = {$this->orderHunt->id} 
ORDER BY idx ASC, type_id DESC
EOF
		, \Phalcon\Db::FETCH_ASSOC);
		foreach ($questions as $i => $q) {
			if (is_null($q['answer_action'])) {
				$q['currentPos'] = $i + 1;
				$q['numQuestions'] = count($questions);
				$this->view->question = $q;
				break;
			}
		}
		
		/*if (empty($tids)) {
			$group = [];
		} else {
			$tids = implode(',', $tids);
			$group = $this->db->fetchAll("SELECT team_id, SUM(c) as rowcount FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (" . $tids . ") GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (" . $tids . ") GROUP BY team_id) t GROUP BY team_id");
		}

		$teamPos = [];
		foreach ($group as $g)
			$teamPos[$g['team_id']] = (int)$g['rowcount'];*/
		if ($this->orderHunt->isMultiHunt()) {
			$max = (int)$this->db->fetchColumn(
				'SELECT SUM(c) FROM (SELECT COUNT(1) as c FROM custom_questions  WHERE order_hunt_id IN (SELECT id FROM order_hunts WHERE order_id = ' . (int)$this->orderHunt->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8) UNION ALL SELECT COUNT(1) as c FROM hunt_points hp LEFT JOIN order_hunts oh ON (oh.hunt_id = hp.hunt_id) WHERE oh.order_id = ' . (int)$this->orderHunt->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8) t'
			);
		} else {
			$max = \HuntPoints::count('hunt_id=' . $this->orderHunt->hunt_id) + $this->orderHunt->countCustomQuestions();
		}
		$maxAnswers = (int)$this->db->fetchColumn('SELECT MAX(ss.`s`) FROM (SELECT team_id, SUM(t.c) as `s` FROM (SELECT team_id, COUNT(1) as c FROM answers WHERE team_id IN (' . $tids . ') GROUP BY team_id UNION ALL SELECT team_id, COUNT(1) as c FROM custom_answers WHERE team_id IN (' . $tids . ') GROUP BY team_id) t GROUP BY team_id) ss');
		$max = max($max, $maxAnswers);
		
		foreach ($teamsStatus as $t => $team)
			$teamsStatus[$t]['question'] = min($team['count'] + 1, $max);
		$this->view->max = $max;
		$this->view->leaderboard = $teamsStatus;
		$this->view->firebase = [
			'config' => $this->config->firebase,
			'appLoc' => [
				'orderHunt' => (int)$this->orderHunt->id,
				'timeLeft' => strtotime($this->orderHunt->finish) - time()
			]
		];
	}

}
