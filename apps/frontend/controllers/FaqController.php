<?php

namespace Play\Frontend\Controllers;

class FaqController extends ControllerBase
{

	public function indexAction()
	{
		//if ($this->requirePlayer())
		//	return true;
		if ($this->orderHunt instanceof \OrderHunts) {
			$teamsStatus = $this->orderHunt->getTeamsStatus();
			foreach ($teamsStatus as $team) {
				if ($team['id'] == $this->team->id) {
					$this->view->teamStatus = $team;
					break;
				}
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

			$this->view->firebase = [
				'config' => $this->config->firebase,
				'appLoc' => [
					'orderHunt' => (int)$this->orderHunt->id,
					'timeLeft' => strtotime($this->orderHunt->finish) - time()
				]
			];

			if ($this->orderHunt->isB2CEnabled())
				$this->view->pick('faq/b2c');
			
			if ($this->orderHunt->order_id == 2651 || $this->orderHunt->order_id == 2674)
				$this->view->pick('faq/asurion');
		}

		$this->assets->collection('style')->addCss('/css/app/faq.css');
	}

}
