<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Mvc\Model\Relation;

class Hunts extends \Phalcon\Mvc\Model
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
	public $city_id;

	/**
	 *
	 * @var integer
	 */
	public $type_id;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var string
	 */
	public $slug;

	/**
	 *
	 * @var string
	 */
	public $time;

	/**
	 *
	 * @var integer
	 */
	public $approved;

	/**
	 *
	 * @var integer
	 */
	public $last_edit;

	/**
	 *
	 * @var string
	 */
	public $breakpoints;

	/**
	 *
	 * @var integer
	 */
	public $multilang;

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
		$this->hasMany('id', 'Answers', 'hunt_id', [
			'alias' => 'Answers',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This hunt has answer submittions'
			]
		]);
		$this->hasMany('id', 'HuntPoints', 'hunt_id', [
			'alias' => 'HuntPoints',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE,
				//'message' => 'This hunt has hunt points'
			]
		]);
		$this->hasMany('id', 'Routes', 'hunt_id', [
			'alias' => 'Routes',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE,
				'message' => 'This hunt has routes'
			]
		]);
		$this->hasMany('id', 'OrderHunts', 'hunt_id', [
			'alias' => 'OrderHunts',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This hunt has orders'
			]
		]);
		$this->belongsTo('type_id', 'HuntTypes', 'id', [
			'alias' => 'HuntType',
			'foreignKey' => [
				'message' => 'Hunt type doesn\'t exists'
			]
		]);
		$this->belongsTo('city_id', 'Cities', 'id', [
			'alias' => 'City',
			'foreignKey' => [
				'message' => 'City doesn\'t exists'
			]
		]);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('name', new StringLength([
			'max' => 100,
			'min' => 2,
			'messageMaximum' => 'Name is too long',
			'messageMinimum' => 'Name is too short'
		]));
		$validator->add('slug', new StringLength([
			'max' => 120,
			'min' => 2,
			'messageMaximum' => 'Slug is too long',
			'messageMinimum' => 'Slug is too short'
		]));
		$validator->add('slug', new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Slug is already in use'
		]));
		$validator->add('time', new Regex([
			'pattern'	=> '/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
			'message'	=> 'Hunt duration is invalid'
		]));
		if (!is_null($this->breakpoints)) {
			$validator->add('breakpoints', new Regex([
				'pattern'	=> '/^(\d+,)*\d+$/',
				'message'	=> 'Breakpoints format is invalid'
			]));
			$validator->add('breakpoints', new StringLength([
				'max' => 250,
				'min' => 1,
				'messageMaximum' => 'Breakpoints format is invalid',
				'messageMinimum' => 'Breakpoints limit reached'
			]));
		}
		$validator->add('approved', new InclusionIn([
			'domain' => [0, 1],
			'message' => 'Approved is invalid'
		]));
		$validator->add('multilang', new InclusionIn([
			'domain' => [0, 1, 2, 3],
			'message' => 'Language is invalid'
		]));
		$validator->add('flags', new Regex([
			'pattern'	=> '/^\d{1,3}$/',
			'message'	=> 'Flags are invalid'
		]));

		return $this->validate($validator);
	}

	public function beforeValidation()
	{
		if (is_null($this->flags))
			$this->flags = 0;
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'hunts';
	}

	public function beforeSave()
	{
		$this->last_edit = date('Y-m-d H:i:s');
		if (!is_null($this->breakpoints)) {
			$breakpoints = array_unique(array_filter(array_map(function($s){
				return is_numeric($s) ? (int)$s : false;
			}, explode(',', $this->breakpoints))));
			sort($breakpoints);
			$this->breakpoints = implode(',', $breakpoints);
		}
	}

	public function getPoints(){
		return HuntPoints::find([
			'hunt_id=' . (int)$this->id,
			'order' => 'idx ASC, id ASC'
		])->toArray();
	}

	public function getRoutes($all = false){
		$routes = Routes::find('hunt_id=' . (int)$this->id . ($all ? ' AND deleted=0' : ' AND active=1'))->toArray();
		$rids = [];
		foreach ($routes as $route)
			$rids[] = $route['id'];
		$routePoints = empty($rids) ? [] : RoutePoints::find([
			'route_id IN (' . implode(',', $rids) . ')',
			'order' => 'idx ASC, id ASC'
		])->toArray();
		unset($rids);
		$huntPoints = $this->getPoints();
		$tmpRoutePoints = [];
		$maxIdx = 0;
		$sortFunc = function($hp1, $hp2) use (&$tmpRoutePoints, &$maxIdx) {
			$c1 = isset($tmpRoutePoints[$hp1['id']]) ? $tmpRoutePoints[$hp1['id']] : ($maxIdx + $hp1['idx']);
			$c2 = isset($tmpRoutePoints[$hp2['id']]) ? $tmpRoutePoints[$hp2['id']] : ($maxIdx + $hp2['idx']);
			return $c1 == $c2 ? 0 : $c1 < $c2 ? -1 : 1;
		};
		foreach ($routes as $r => $route) {
			$tmpRoutePoints = [];
			$routes[$r]['active'] = (bool)$route['active'];
			$routes[$r]['points'] = $huntPoints;
			foreach ($routePoints as $k => $rp) {
				if ($route['id'] == $rp['route_id']) {
					$maxIdx = $tmpRoutePoints[$rp['hunt_point_id']] = $rp['idx'];
					unset($routePoints[$k]);
				}
			}
			usort($routes[$r]['points'], $sortFunc);
			foreach ($routes[$r]['points'] as $p => $rp)
				unset($routes[$r]['points'][$p]['idx']);
		}
		return $routes;
	}

	public function getDurationMinutes()
	{
		$d = explode(':', $this->time);
		if (count($d) === 2)
			return $d[0] * 60 + $d[1];
		return 360;
	}

	public function checkBreakpoints(OrderHunts $orderHunt, $playing = false)
	{
		if (is_null($this->breakpoints))
			return false;

		$breakpoints = explode(',', $this->breakpoints);

		if (!empty($breakpoints)) {
			$di = Phalcon\Di::getDefault();
			$redis = $di->get('redis');
			$firebase = $di->get('firebase');

			$teamsStatus = $orderHunt->getTeamsStatus(true, true, true);
			$totalTeams = count($teamsStatus);
			$time = time();

			foreach ($breakpoints as $b => $bq) {
				$teamsJson = $teams = [];
				foreach ($teamsStatus as $i => $ts) {
					if ($ts['count'] < $bq) {
						$teamsJson[] = [
							$teams[] = (int)$ts['id'],
							$ts['count']
						];
					}
				}

				//$redis->delete(SB_PREFIX . 'breakp:' . $orderHunt->id . ':' . $bq);
				//$redis->delete(SB_PREFIX . 'breakfb:' . $orderHunt->id);
				$cache = $redis->get(SB_PREFIX . 'breakp:' . $orderHunt->id . ':' . $bq);
				$hasCache = $cache !== false;

				if (count($teams) == $totalTeams || ($hasCache && $time - $cache > -3))
					continue;

				if (empty($teams) || $hasCache)
					$teamsJson = [0];

				$teamsJsonStr = json_encode($teamsJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				if ($redis->get(SB_PREFIX . 'breakfb:' . $orderHunt->id) != $teamsJsonStr) {
					if (($fbr = str_replace('"', '', $firebase->set(FB_PREFIX . 'breakfb/' . $orderHunt->id, $teamsJson, [], 5))) != $teamsJsonStr) {
						try {
							$di->get('logger')->critical('Firebase break error: (ohid ' . $orderHunt->id . ' ' . $fbr . ' should be ' . $teamsJsonStr);
						} catch(Exception $e) { }
					} else {
						$redis->set(SB_PREFIX . 'breakfb:' . $orderHunt->id, $teamsJsonStr, 1800);
					}
				}

				if (empty($teams) || $hasCache) {
					if (!$hasCache)
						$redis->set(SB_PREFIX . 'breakp:' . $orderHunt->id . ':' . $bq, $time + 30, 1209600);
					if ($playing)
						return [$bq];
					continue;
				}

				return [$bq, $teams];
			}
		}

		return false;
	}

	public function isStrategyHunt()
	{
		return (bool)($this->flags & 1);
	}

	public function setStrategyHunt($value)
	{
		$this->flags ^= (($value ? -1 : 0) ^ $this->flags) & 1;
	}
}
