<?php

use Phalcon\Mvc\Model\Relation,
	Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\Uniqueness;

class Teams extends \Phalcon\Mvc\Model
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
	public $order_hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $route_id;

	/**
	 *
	 * @var string
	 */
	public $activation_player;

	/**
	 *
	 * @var string
	 */
	public $activation_leader;

	/**
	 *
	 * @var integer
	 */
	public $leader;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var string
	 */
	public $activation;

	/**
	 *
	 * @var string
	 */
	public $first_activation;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Answers', 'team_id', [
			'alias' => 'Answers',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This team has answers'
			]
		]);
		$this->hasMany('id', 'CustomAnswers', 'team_id', [
			'alias' => 'CustomAnswers',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This team has custom answers'
			]
		]);
		$this->hasMany('id', 'Players', 'team_id', [
			'alias' => 'Players',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This team has players'
			]
		]);
		$this->belongsTo('route_id', 'Routes', 'id', [
			'alias' => 'Route',
			'foreignKey' => [
				'message' => 'Route doesn\'t exists'
			]
		]);
		$this->belongsTo('leader', 'Players', 'id', [
			'alias' => 'Leader',
			'foreignKey' => [
				'allowNulls' => true,
				'message' => 'Leader doesn\'t exists'
			]
		]);
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
		$this->skipAttributesOnCreate(['leader', 'name', 'activation', 'first_activation']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$oh = $this->OrderHunt;
		$route = $this->Route;
		$ok = true;
		if ($oh && $route && $route->hunt_id != $oh->hunt_id) {
			$ok = false;
			$this->appendMessage(new Phalcon\Mvc\Model\Message(
				'Route doesn\'t much order hunt',
				'route_id',
				'error'
			));
		}

		$validator->add('activation_player', new StringLength([
			'max' => 32,
			'min' => 1,
			'messageMaximum' => 'Player activation code is too long',
			'messageMinimum' => 'Player activation code is too short'
		]));
		$validator->add('activation_leader', new StringLength([
			'max' => 32,
			'min' => 1,
			'messageMaximum' => 'Leader activation code is too long',
			'messageMinimum' => 'Leader activation code is too short'
		]));
		$validator->add('activation_player', new Uniqueness([
			'message'	=> 'Player activation code already in use'
		]));
		$validator->add('activation_leader', new Uniqueness([
			'message'	=> 'Leader activation code already in use'
		]));
		if (!is_null($this->name)) {
			$validator->add('name', new StringLength([
				'max' => 30,
				'min' => 2,
				'messageMaximum' => 'Name is too long',
				'messageMinimum' => 'Name is too short'
			]));
		}

		return $ok && $this->validate($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'teams';
	}

	/**
	 * Resets the team
	 *
	 * @return string
	 */
	public function resetTeam($removePlayers = false)
	{
		$this->name = $this->activation = $this->first_activation = $this->leader = null;
		if ($this->save()) {
			$this->Answers->delete();
			$this->CustomAnswers->delete();
			if ($removePlayers)
				$this->Players->delete();

			$redis = Phalcon\Di::getDefault()->get('redis');

			$this->removeCacheWildCard($redis, SB_PREFIX . 'qtimeout:' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'answerslimit:' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'bqchooseskip:' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'ohloc:' . $this->order_hunt_id . ':' . $this->id);
			$this->removeCacheWildCard($redis, SB_PREFIX . 'hint:' . $this->order_hunt_id . ':' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'qtimer:' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'bqchooseskip:' . $this->id . ':*');
			$this->removeCacheWildCard($redis, SB_PREFIX . 'survey:' . $this->order_hunt_id . ':*');

			return true;
		}
		return false;
	}

	private function removeCacheWildCard(&$redis, $wildcard)
	{
		while ($key = $redis->keys($wildcard))
			$redis->delete($key);
	}

}
