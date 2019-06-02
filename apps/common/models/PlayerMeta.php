<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Mvc\Model\Relation,
	Phalcon\Di;

class PlayerMeta extends \Phalcon\Mvc\Model
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
		$this->belongsTo('player_id', 'Players', 'id', [
			'alias' => 'Player',
			'foreignKey' => [
				'message' => 'Player doesn\'t exists'
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

		$validator->add('meta_key', new StringLength([
			'max' => 250,
			'min' => 1,
			'messageMaximum' => 'Meta key is too long',
			'messageMinimum' => 'Meta key is too short'
		]));
		$validator->add('meta_value', new StringLength([
			'max' => 250,
			'min' => 0,
			'messageMaximum' => 'Meta value is too long',
			'messageMinimum' => 'Meta value is too short'
		]));
		$validator->add(['player_id', 'meta_key'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'This meta key already exists'
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
		return 'player_meta';
	}

}
