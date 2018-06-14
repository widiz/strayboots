<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Di;

class SocialPlayers extends \Phalcon\Mvc\Model
{

	/**
	 *
	 * @var integer
	 */
	public $player_id;

	/**
	 *
	 * @var integer
	 */
	public $network;

	/**
	 *
	 * @var integer
	 */
	public $network_id;

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
	 *
	 * @var string
	 */
	public $thumbnail;

	/**
	 *
	 * @var string
	 */
	public $created;

	const Facebook = 1;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('player_id', 'Players', 'id', [
			'alias' => 'Player',
			'foreignKey' => [
				'message' => 'Player doesn\'t exists'
			]
		]);
		$this->SkipAttributesOnCreate(['created']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('network', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [SocialPlayers::Facebook],
			'message' => 'Network is invalid'
		]));
		$validator->add('network_id', new Phalcon\Validation\Validator\Regex([
			'pattern' => '/^\d+$/',
			'message' => 'Network ID is not a valid number'
		]));
		if (!is_null($this->thumbnail)) {
			$validator->add('thumbnail', new Phalcon\Validation\Validator\Url([
				'message' => 'Thumbnail is not a valid URL'
			]));
		}
		$validator->add('first_name', new StringLength([
			'allowEmpty' => true,
			'max' => 20,
			'messageMaximum' => 'First name is too long',
			'messageMinimum' => 'First name is too short'
		]));
		$validator->add('last_name', new StringLength([
			'allowEmpty' => true,
			'max' => 20,
			'messageMaximum' => 'Last name is too long',
			'messageMinimum' => 'Last name is too short'
		]));

		return $this->validate($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'social_players';
	}

}
