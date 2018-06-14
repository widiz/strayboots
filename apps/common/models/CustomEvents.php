<?php

class CustomEvents extends \Phalcon\Mvc\Model
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
	public $team_id;

	/**
	 *
	 * @var string
	 */
	public $title;

	/**
	 *
	 * @var integer
	 */
	public $score;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('team_id', 'Teams', 'id', [
			'alias' => 'Team',
			'foreignKey' => [
				'allowNulls' => true,
				'message' => 'Player doesn\'t exists'
			]
		]);
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
		//$this->skipAttributesOnCreate(['team_id']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('title', new Phalcon\Validation\Validator\StringLength([
			'max' => 100,
			'min' => 1,
			'messageMaximum' => 'Title is too long',
			'messageMinimum' => 'Title is too short'
		]));
		$validator->add('score', new Phalcon\Validation\Validator\Regex([
			'pattern'	=> '/^\d+$/',
			'message'	=> 'Score is invalid'
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
		return 'custom_events';
	}

}
