<?php

class CustomAnswers extends \Phalcon\Mvc\Model
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
	public $custom_question_id;

	/**
	 *
	 * @var integer
	 */
	public $team_id;

	/**
	 *
	 * @var string
	 */
	public $answer;

	/**
	 *
	 * @var string
	 */
	public $action;

	/**
	 *
	 * @var string
	 */
	public $created;

	/**
	 *
	 * @var string
	 */
	public $funfact_viewed;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('team_id', 'Teams', 'id', [
			'alias' => 'Team',
			'foreignKey' => [
				'message' => 'Team doesn\'t exists'
			]
		]);
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
		$this->belongsTo('custom_question_id', 'CustomQuestions', 'id', [
			'alias' => 'CustomQuestion',
			'foreignKey' => [
				'message' => 'Custom question doesn\'t exists'
			]
		]);
		$this->skipAttributesOnCreate(['created', 'funfact_viewed']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('action', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1, 2],
			'message' => 'Action is invalid'
		]));
		$validator->add(['team_id', 'custom_question_id'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Answer already exists'
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
		return 'custom_answers';
	}

}
