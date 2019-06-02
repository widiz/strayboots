<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Mvc\Model\Relation,
	Phalcon\Di;

class Players extends \Phalcon\Mvc\Model
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
	public $team_id;

	/**
	 *
	 * @var string
	 */
	public $email;

	/**
	 *
	 * @var string
	 */
	public $first_name;

	/**
	 *
	 * @var string
	 */
	public $last_name;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasOne('id', 'Teams', 'leader', [
			'alias' => 'Leads',
			'foreignKey' => [
				'action' => Relation::NO_ACTION,
				'allowNulls' => true
			]
		]);
		$this->hasOne('id', 'SocialPlayers', 'player_id', [
			'alias' => 'SocialPlayer',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasOne('id', 'BonusQuestions', 'winner_id', [
			'alias' => 'BonusQuestionsWon',
			'foreignKey' => [
				'action' => Relation::NO_ACTION
			]
		]);
		$this->belongsTo('team_id', 'Teams', 'id', [
			'alias' => 'Team',
			'foreignKey' => [
				'message' => 'Team doesn\'t exists'
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
		
		if (!is_null($this->email)) {
			$validator->add('email', new Phalcon\Validation\Validator\Email([
				'required'	=> true
			]));
		}
		if (!is_null($this->first_name)) {
			$validator->add('first_name', new StringLength([
				'max' => 20,
				'min' => 1,
				'messageMaximum' => 'First name is too long',
				'messageMinimum' => 'First name is too short'
			]));
		}
		if (!is_null($this->last_name)) {
			$validator->add('last_name', new StringLength([
				'max' => 20,
				'min' => 1,
				'messageMaximum' => 'Last name is too long',
				'messageMinimum' => 'Last name is too short'
			]));
		}

		return $this->validate($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'players';
	}

	public function isLeader()
	{
		$team = $this->Team;
		return $team && !is_null($team->leader) ? $team->leader == $this->id : false;
	}

	public static function setPlayerLogin(Players $player, $remember = false) {
		$di = Di::getDefault();
		$di->get('session')->set('playerID', (int)$player->id);
		if ($remember) {
			$verifier = md5($player->id . '.strayboots*players&salt#.%^04565' . $player->email);
			$lifetime = 172800; // 2 days cookie
			$di->get('redis')->set(SB_PREFIX . 'pverifier:' . $verifier, (int)$player->id, $lifetime);
			$lifetime += time();
			$secure = false;//$di->get('request')->isSecureRequest();
			$di->get('cookies')->set('pid', $player->id, $lifetime, '/', $secure)
								->set('pverifier', $verifier, $lifetime, '/', $secure);
		}
	}

	public static function logout() {
		$di = Di::getDefault();
		$secure = false;//$di->get('request')->isSecureRequest();
		$di->get('cookies')->set('pid', '', 0, '/', $secure)
							->set('pverifier', '', 0, '/', $secure);
		$di->get('session')->remove('playerID');
	}

	public function setMeta($key, $value)
	{
		$di = Phalcon\DI::getDefault();
		return $di->get('db')->query('INSERT INTO player_meta (player_id, meta_key, meta_value) VALUES (:player, :key, :value) ON DUPLICATE KEY UPDATE meta_value=:value', [
			'player'	=> $this->id,
			'key'		=> $key,
			'value'		=> $value
		]);
	}

	public function getMeta($key, $default = null)
	{
		$di = Phalcon\DI::getDefault();
		$value = $di->get('db')->fetchColumn('SELECT value FROM player_meta WHERE player_id=:player AND meta_key=:key', [
			'player'	=> $this->id,
			'key'		=> $key
		]);
		return $value === false ? $default : $value;
	}

}
