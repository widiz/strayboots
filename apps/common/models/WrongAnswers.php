<?php

class WrongAnswers extends \Phalcon\Mvc\Model
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
	public $question_id;

	/**
	 *
	 * @var integer
	 */
	public $player_id;

	/**
	 *
	 * @var integer
	 */
	public $hint;

	/**
	 *
	 * @var string
	 */
	public $answer;

	/**
	 *
	 * @var string
	 */
	public $created;

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
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
		$this->belongsTo('question_id', 'Questions', 'id', [
			'alias' => 'Question',
			'foreignKey' => [
				'message' => 'Question doesn\'t exists'
			]
		]);
		$this->skipAttributes(['created']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('hint', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Hint is invalid'
		]));

		$validator->add('answer', new Phalcon\Validation\Validator\PresenceOf([
			'message' => 'Answer is required'
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
		return 'wrong_answers';
	}

}
