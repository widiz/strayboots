<?php

use Phalcon\Validation\Validator\PresenceOf;

class BonusQuestions extends \Phalcon\Mvc\Model
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
	public $type;

	/**
	 *
	 * @var integer
	 */
	public $order_hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $winner_id;

	/**
	 *
	 * @var string
	 */
	public $question;

	/**
	 *
	 * @var string
	 */
	public $answers;

	/**
	 *
	 * @var integer
	 */
	public $score;

	/**
	 *
	 * @var string
	 */
	public $answer_time;

	/**
	 *
	 * @var string
	 */
	public $answer;

	const TypeTeam = 0;
	const TypePrivate = 1;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('winner_id', 'Players', 'id', [
			'alias' => 'Winner',
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
		$this->skipAttributesOnCreate(['winner_id', 'answer_time', 'answer']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('type', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Type is invalid'
		]));
		$validator->add('question', new PresenceOf([
			'message'	=> 'Question is required'
		]));
		$validator->add('answers', new PresenceOf([
			'message'	=> 'Answers are required'
		]));
		if ($this->type == BonusQuestions::TypeTeam) {
			$validator->add('score', new Phalcon\Validation\Validator\Regex([
				'pattern'	=> '/^\d+$/',
				'message'	=> 'Score is invalid'
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
		return 'bonus_questions';
	}

}
