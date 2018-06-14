<?php

use Phalcon\Validation\Validator\Regex,
	Phalcon\Validation\Validator\StringLength,
	Phalcon\Mvc\Model\Message,
	Phalcon\Mvc\Model\Relation;

class OrderHunts extends \Phalcon\Mvc\Model
{

	/**
	 *
	 * @var integer
	 */
	public $id;

	/**
	 *
	 * @var integer
	 */
	public $order_id;

	/**
	 *
	 * @var integer
	 */
	public $hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $max_players;

	/**
	 *
	 * @var integer
	 */
	public $max_teams;

	/**
	 *
	 * @var string
	 */
	public $start;

	/**
	 *
	 * @var string
	 */
	public $finish;

	/**
	 *
	 * @var string
	 */
	public $expire;

	/**
	 *
	 * @var string
	 */
	public $start_msg;

	/**
	 *
	 * @var string
	 */
	public $end_msg;

	/**
	 *
	 * @var string
	 */
	public $timeout_msg;

	/**
	 *
	 * @var string
	 */
	public $pdf_start;

	/**
	 *
	 * @var string
	 */
	public $pdf_finish;

	/**
	 *
	 * @var string
	 */
	public $redirect;

	/**
	 *
	 * @var string
	 */
	public $video;

	/**
	 *
	 * @var integer
	 */
	public $flags;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Teams', 'order_hunt_id', [
			'alias' => 'Teams',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'OrderHuntsPost', 'order_hunt_id', [
			'alias' => 'OrderHuntPost',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'BonusQuestions', 'order_hunt_id', [
			'alias' => 'BonusQuestions',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'CustomQuestions', 'order_hunt_id', [
			'alias' => 'CustomQuestions',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'CustomAnswers', 'order_hunt_id', [
			'alias' => 'CustomAnswers',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT
			]
		]);
		$this->hasMany('id', 'CatquestionsOrders', 'order_hunt_id', [
			'alias' => 'CatquestionsOrders',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'LoginPages', 'order_hunt_id', [
			'alias' => 'LoginPages',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->belongsTo('order_id', 'Orders', 'id', [
			'alias' => 'Order',
			'foreignKey' => [
				'message' => 'Order doesn\'t exists'
			]
		]);
		$this->belongsTo('hunt_id', 'Hunts', 'id', [
			'alias' => 'Hunt',
			'foreignKey' => [
				'message' => 'Hunt doesn\'t exists'
			]
		]);
		$this->skipAttributesOnCreate([
			'start_msg',
			'end_msg',
			'video',
			'timeout_msg'
		]);
	}

	public function countPlayers()
	{
		$count = 0;
		foreach ($this->Teams as $team)
			$count += $team->countPlayers();
		return $count;
	}

	public function beforeValidation()
	{
		if (is_null($this->flags))
			$this->flags = 0;
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();
		$timestampRegex = '/^\d{4}(\-\d{2}){2} \d{2}(:\d{2}){1,2}$/';
		$success = true;

		$validator->add('max_players', new Regex([
			'pattern'	=> '/^\d{1,5}$/',
			'message'	=> 'Max players is invalid'
		]));
		$validator->add('max_teams', new Regex([
			'pattern'	=> '/^\d{1,5}$/',
			'message'	=> 'Max teams is invalid'
		]));
		$validator->add('flags', new Regex([
			'pattern'	=> '/^\d{1,3}$/',
			'message'	=> 'Flags are invalid'
		]));
		$validator->add('start', new Regex([
			'pattern'	=> $timestampRegex,
			'message'	=> 'Start is invalid'
		]));
		$validator->add('finish', new Regex([
			'pattern'	=> $timestampRegex,
			'message'	=> 'Finish is invalid'
		]));
		/*$validator->add(['order_id', 'hunt_id'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'This hunt already exists in this order'
		]));*/
		if (!is_null($this->expire)) {
			$validator->add('expire', new Regex([
				'pattern'	=> $timestampRegex,
				'message'	=> 'Expire is invalid'
			]));
		}
		if ($this->start && $this->finish) {
			$start = strtotime($this->start);
			$finish = strtotime($this->finish);
			if ($start > $finish) {
				$this->appendMessage(new Message(
					'Finish can\'t be before start',
					'finish',
					'error'
				));
				$success = false;
			}
			if (!is_null($this->expire)) {
				$expire = strtotime($this->expire);
				if ($finish > $expire) {
					$this->appendMessage(new Message(
						'Expire can\'t be before finish',
						'expire',
						'error'
					));
					$success = false;
				}
			}
		}
		if (!is_null($this->pdf_start)) {
			$validator->add('pdf_start', new StringLength([
				'min' => 1,
				'max' => 400,
				'messageMaximum' => 'PDF start is too long',
				'messageMinimum' => 'PDF start is too short'
			]));
		}
		if (!is_null($this->pdf_finish)) {
			$validator->add('pdf_finish', new StringLength([
				'min' => 1,
				'max' => 400,
				'messageMaximum' => 'PDF finish is too long',
				'messageMinimum' => 'PDF finish is too short'
			]));
		}
		if (!is_null($this->redirect)) {
			$validator->add('redirect', new StringLength([
				'min' => 1,
				'max' => 200,
				'messageMaximum' => 'Redirect is too long',
				'messageMinimum' => 'Redirect is too short'
			]));
			$validator->add('redirect', new \Phalcon\Validation\Validator\Url([
				'message' => 'Redirect is not a valid URL'
			]));
		}
		if ($this->max_players < 1) {
			$this->appendMessage(new Message(
				'Max players must be 1 or above',
				'max_players',
				'error'
			));
			$success = false;
		}

		if ($this->max_teams < 1) {
			$this->appendMessage(new Message(
				'Max teams must be 1 or above',
				'max_teams',
				'error'
			));
			$success = false;
		}

		if ($this->max_players < $this->max_teams) {
			$this->appendMessage(new Message(
				'Max players cannot be lower than max teams',
				'max_players',
				'error'
			));
			$success = false;
		}

		$hunt = $this->Hunt;
		if ($hunt && !$hunt->approved) {
			$this->appendMessage(new Message(
				'Hunt is not approved',
				'hunt_id',
				'error'
			));
			$success = false;
		}

		return $this->validate($validator) && $success;
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'order_hunts';
	}

	public function getTeams($all = false)
	{
		$criteria = [
			'order_hunt_id=' . (int)$this->id,
			'order' => 'id ASC'
		];
		if (!($this->isMultiHunt() || $all))
			$criteria['limit'] = $this->max_teams;
		return Teams::find($criteria);
	}

	public function getFiles($bdir = false)
	{
		$di = $this->getDI();
		$config = $di->get('config');
		$baseDir = $config->application->frontUploadsDir->path . $this->id;
		$files = [];
		$baseDirLen = strlen($baseDir) + 1;
		if (file_exists($baseDir)) {
			$directory = new RecursiveDirectoryIterator($baseDir);
			$iterator = new RecursiveIteratorIterator($directory);
			$regex = new RegexIterator($iterator, '/^.+\/(\d+|chat)\/[0-9a-z_]+\.(jpg|png|gif)$/i', RecursiveRegexIterator::GET_MATCH);
			foreach ($regex as $file)
				$files[] = [$bdir ? $file[0] : substr($file[0], $baseDirLen), $file[1], @filemtime($file[0])];
			usort($files, function($a, $b){
				return $a[2] > $b[2] ? 1 : -1;
			});
		}
		return $bdir ? ['baseDir' => $baseDir, 'baseDirLen' => $baseDirLen, 'files' => $files] : $files;
	}

	public function resetTeams()
	{
		$order = $this->Order;
		$teams = $this->getTeams();
		$routes = array_flip(array_map(function($r){
			return $r['id'];
		}, $this->Hunt->getRoutes()));
		if (empty($routes))
			throw new Exception('No routes', 1);
		foreach ($routes as $r => $c)
			$routes[$r] = 0;
		foreach ($teams as $t) {
			if (isset($routes[$t->route_id]) && !is_null($t->activation))
				$routes[$t->route_id]++;
		}

		$regular = !$this->isCustomLogin();
		foreach ($teams as $t) {
			if (isset($routes[$t->route_id]) && !is_null($t->activation))
				continue;
			if ($regular) {
				$values = array_values($routes);
				$keys = array_keys($routes);
				array_multisort($values, SORT_NATURAL, $keys, SORT_NATURAL);
				$routes = array_combine($keys, $values);
				reset($routes);
			}
			$t->route_id = key($routes);
			if ($t->save())
				$routes[$t->route_id]++;
			else
				throw new Exception(var_export($t->getMessages(), true), 1);
		}

		return true;
	}

	public function addTeams($count = 1)
	{
		$order = $this->Order;
		$teams = $this->getTeams()->toArray();
		$routes = array_flip(array_map(function($r){
			return $r['id'];
		}, $this->Hunt->getRoutes()));
		if (empty($routes))
			throw new Exception('No routes', 1);
		foreach ($routes as $r => $c)
			$routes[$r] = 0;
		foreach ($teams as $t) {
			if (isset($routes[$t['route_id']]))
				$routes[$t['route_id']]++;
		}

		$ret = [];
		$codes = $order->generateActivationCodes($count * 2);
		$numfails = 0;
		$regular = !$this->isCustomLogin();
		while ($count > 0) {
			if ($regular) {
				$values = array_values($routes);
				$keys = array_keys($routes);
				array_multisort($values, SORT_NATURAL, $keys, SORT_NATURAL);
				$routes = array_combine($keys, $values);
				reset($routes);
			}
			$r = key($routes);
			$team = new Teams();
			$team->order_hunt_id = $this->id;
			$team->route_id = $r;
			if (count($codes) < 2)
				$codes = $order->generateActivationCodes(max(2, $count * 2));
			$team->activation_player = array_pop($codes);
			$team->activation_leader = array_pop($codes);
			if ($team->save()) {
				$ret[] = $team;
				$routes[$r]++;
				$count--;
			} else if (++$numfails > 3) {
				throw new Exception(var_export($team->getMessages(), true), 1);
			}
		}
		return $ret;
	}

	public function getTeamsStatus($multihunt = true, $onlyactive = true, $afterFunfact = true)
	{
		if ($afterFunfact && $this->isOver())
			$afterFunfact = false;

		$di = $this->getDI();
		$db = $di->get('db');
		/*$cache = $di->get('redis');
		$key = SB_PREFIX . 'ts:' . $this->id;
		if (($c = $cache->get($key)) !== false)
			return $c;*/

		$multihunt = $multihunt && $this->isMultiHunt();
		$ohids = [$this->id => 0];

		if ($multihunt) {
			/*$t = $db->fetchAll(
				'SELECT oh.id, oh.max_teams, GROUP_CONCAT(t.id SEPARATOR \',\') as `teams` FROM teams t ' .
				'LEFT JOIN order_hunts oh ON (oh.id = t.order_hunt_id) ' .
				'WHERE oh.order_id = ' . (int)$this->order_id . ' AND oh.flags & 4 = 0 AND oh.flags & 8 = 8 ' .
				'GROUP BY oh.id',
			Phalcon\Db::FETCH_ASSOC);
			$teams = [];
			foreach ($t as $oht)
				$teams = array_merge($teams, array_slice(explode(',', $oht['teams']), 0, $oht['max_teams']));
			if (empty($teams))
				return [];
			$tids = implode(',', $teams);
			$teams = Teams::find('id IN (' . $tids . ')')->toArray();*/
			$oh = array_flip(array_map('array_pop', $db->fetchAll('SELECT id FROM order_hunts WHERE order_id = ' . (int)$this->order_id . ' AND flags & 4 = 0 AND flags & 8 = 8', Phalcon\Db::FETCH_ASSOC)));
			$ohids += $oh;
			$teams = Teams::find('order_hunt_id IN (' . implode(',', array_keys($ohids)) . ')')->toArray();
		} else {
			$teams = $this->getTeams()->toArray();
		}

		$tids = [];
		if ($onlyactive) {
			foreach ($teams as $t => $team) {
				if (is_null($team['activation'])) {
					unset($teams[$t]);
				} else {
					$tids[] = $team['id'];
					$ohids[$team['order_hunt_id']] = 0;
					$teams[$t]['num'] = $t + 1;
					if (is_null($team['name']))
						$teams[$t]['name'] = 'Team ' . $teams[$t]['num'];
				}
			}
			$teams = array_values($teams);
		} else {
			foreach ($teams as $t => $team) {
				$tids[] = $team['id'];
				$ohids[$team['order_hunt_id']] = 0;
				$teams[$t]['num'] = $t + 1;
				if (is_null($team['name']))
					$teams[$t]['name'] = 'Team ' . $teams[$t]['num'];
			}
		}

		if (empty($tids))
			return [];

		$tids = implode(',', $tids);
		$ohids = implode(',', array_keys($ohids));

		usort($teams, function($a, $b) {
			if ($a['activation'] == $b['activation'])
				return $a['id'] < $b['id'] ? 0 : 1;
			return $a['activation'] < $b['activation'] ? 1 : 0;
		});

		$scores = $db->fetchAll(
			'SELECT a.team_id,' .
			'SUM(IF(a.action=' . Answers::Skipped . ',0,FLOOR(IF(q.score IS NULL,qt.score,q.score) / IF(a.action=' . Answers::AnsweredWithHint . ',2,1)))) as `score`, ' .
			'TIME_TO_SEC(TIMEDIFF(MAX(a.created), t.first_activation)) as hunttime, ' .
			//'TIME_TO_SEC(TIMEDIFF(IF(MAX(a.created)=min(a.created),now(),MAX(a.created)), min(a.created))) as hunttime, ' .
			'COUNT(a.id) as `count` FROM answers a' .
			' LEFT JOIN teams t ON (t.id = a.team_id)' .
			' LEFT JOIN questions q ON (q.id = a.question_id)' .
			' LEFT JOIN question_types qt ON (qt.id = q.type_id) ' .
			'WHERE a.team_id IN (' . $tids . ') ' . ($afterFunfact ? 'AND (a.funfact_viewed IS NOT NULL OR q.funfact=\'\' OR qt.type=' . QuestionTypes::Other . ') ' : '') .
				'AND t.activation IS NOT NULL' .// AND a.action != ' . Answers::Skipped .
			' GROUP BY a.team_id' .//, a.hunt_id
			' ORDER BY `score` DESC, ' .
			'hunttime ASC, t.id ASC',//, `count` DESC, t.activation ASC
			//'TIME_TO_SEC(TIMEDIFF(IF(ISNULL(t.activation),IF(MAX(a.created)=MIN(a.created),now(),MIN(a.created)),t.activation), MAX(a.created))) ASC',
		Phalcon\Db::FETCH_ASSOC);

		$resort = false;

		$huntQuery = $multihunt ? ' IN (' . $ohids . ')' : ('=' . (int)$this->id);

		$bq = $db->fetchAll(
			'SELECT p.team_id, SUM(bq.score) as score FROM bonus_questions bq FORCE INDEX (orderhuntbonus) ' .
			'LEFT JOIN players p ON (p.id = bq.winner_id) ' .
			'WHERE bq.order_hunt_id' . $huntQuery . ' AND bq.type=0 AND bq.winner_id IS NOT NULL ' .
			'GROUP BY p.team_id',
		Phalcon\Db::FETCH_ASSOC);
		if (!empty($bq)) {
			$resort = true;
			foreach ($bq as $b) {
				foreach ($scores as $p => $score) {
					if ($score['team_id'] == $b['team_id']) {
						$scores[$p]['score'] += $b['score'];
						continue 2;
					}
				}
				$b['count'] = 0;
				$scores[] = $b;
			}
		}

		$reduceTeamHuntScore = [];
		$events = $db->fetchAll(
			'SELECT team_id, SUM(score) as score FROM custom_events ' .
			'WHERE order_hunt_id' . $huntQuery . ' AND team_id IS NOT NULL ' .
			'GROUP BY team_id',
		Phalcon\Db::FETCH_ASSOC);
		if (!empty($events)) {
			$resort = true;
			foreach ($events as $e) {
				$e['score'] = (int)$e['score'];
				foreach ($scores as $p => $score) {
					if ($score['team_id'] == $e['team_id']) {
						if (isset($reduceTeamHuntScore[$e['team_id']]))
							$reduceTeamHuntScore[$e['team_id']] += $e['score'];
						else
							$reduceTeamHuntScore[$e['team_id']] = $e['score'];
						$scores[$p]['score'] += $e['score'];
						continue 2;
					}
				}
				$e['count'] = 0;
				$reduceTeamHuntScore[$e['team_id']] = $e['score'];
				$scores[] = $e;
			}
		}

		$cq = $db->fetchAll(
			'SELECT ca.team_id, SUM(IF(ca.action=' . Answers::Skipped . ',0,FLOOR(cq.score / IF(ca.action=' . Answers::AnsweredWithHint . ',2,1)))) as score, COUNT(ca.id) as `count` FROM custom_answers ca FORCE INDEX (orderaction) ' .
			'LEFT JOIN custom_questions cq ON (ca.custom_question_id = cq.id) WHERE cq.order_hunt_id' .
			$huntQuery . ($afterFunfact ? ' AND (ca.funfact_viewed IS NOT NULL OR cq.funfact=\'\')' : '') . ' GROUP BY ca.team_id',
		Phalcon\Db::FETCH_ASSOC);
		if (!empty($cq)) {
			$resort = true;
			foreach ($cq as $c) {
				foreach ($scores as $p => $score) {
					if ($score['team_id'] == $c['team_id']) {
						$scores[$p]['score'] += $c['score'];
						$scores[$p]['count'] += $c['count'];
						continue 2;
					}
				}
				$scores[] = $c;
			}
		}
		if ($resort) {
			usort($scores, function($a, $b) {
				$c = $b['score'] <=> $a['score'];
				if ($c === 0)
					return ($a['hunttime'] ?? PHP_INT_MAX) <=> ($b['hunttime'] ?? PHP_INT_MAX);
				return $c;
			});
		}
		/*if (isset($_COOKIE['_sb_'])) {
			var_dump($scores);
			die;
		}*/

		$ts = [];
		$p = 0;
		foreach ($scores as $p => $score) {
			foreach ($teams as $t => $team) {
				if ($score['team_id'] == $team['id']) {
					$team['huntscore'] = $team['score'] = (int)$score['score'];
					$team['count'] = (int)$score['count'];
					if (isset($reduceTeamHuntScore[$team['id']]))
						$team['huntscore'] -= $reduceTeamHuntScore[$team['id']];
					$team['position'] = $p + 1;
					if (isset($team['hunttime']))
						unset($team['hunttime']);
					$ts[] = $team;
					$p++;
					unset($teams[$t]);
					break;
				}
			}
		}
		foreach ($teams as $team) {
			$team['huntscore'] = $team['count'] = $team['score'] = 0;
			$team['position'] = ++$p;
			if ($multihunt)
				$team['activation'] = $team['first_activation'];
			$ts[] = $team;
		}
		//$cache->set($key, $ts, 3600);
		return $ts;
	}

	public function isOver()
	{
		/*if ($this->isDurationFinish())
			return strtotime($this->start /* should be the team activation time *//*) + $this->Hunt->getDurationMinutes() * 60 <= time();*/
		return $this->finish ? strtotime($this->finish) <= time() : false;
	}

	public function isStarted()
	{
		return $this->start ? strtotime($this->start) <= time() : false;
	}

	public function isCustomLogin()
	{
		return (bool)($this->flags & 1);
	}

	public function isDurationFinish()
	{
		return (bool)($this->flags & 2);
	}

	public function isCanceled()
	{
		return (bool)($this->flags & 4);
	}

	public function isMultiHunt()
	{
		return (bool)($this->flags & 8);
	}

	public function isSurveyDisabled()
	{
		return (bool)($this->flags & 16);
	}

	public function setCustomLogin($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 1;
	}

	public function setDurationFinish($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 2;
	}

	public function setCanceled($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 4;
	}

	public function setMultiHunt($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 8;
	}

	public function setSurveyDisabled($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 16;
	}

	/**
	 * Resets the order hunt
	 *
	 * @return string
	 */
	public function resetOrderHunt()
	{
		$ok = true;
		foreach ($this->Teams as $team) {
			if (!$team->resetTeam())
				$ok = false;
		}

		$redis = Phalcon\Di::getDefault()->get('redis');

		$this->removeCacheWildCard($redis, SB_PREFIX . 'breakfb:' . $this->id . ':*');
		$this->removeCacheWildCard($redis, SB_PREFIX . 'breakp:' . $this->id . ':*');
		$redis->delete(SB_PREFIX . 'bqanswer:' . $this->id);

		return $ok;
	}

	private function removeCacheWildCard(&$redis, $wildcard)
	{
		while ($key = $redis->keys($wildcard))
			$redis->delete($key);
	}

}
