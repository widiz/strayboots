<?php

class Catquestions extends \Phalcon\Mvc\Model
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
	public $type_id;

	/**
	 *
	 * @var string
	 */
	public $question;

	/**
	 *
	 * @var string
	 */
	public $hint;

	/**
	 *
	 * @var string
	 */
	public $response_correct;

	/**
	 *
	 * @var string
	 */
	public $response_incorrect;

	/**
	 *
	 * @var string
	 */
	public $response_skip;

	/**
	 *
	 * @var string
	 */
	public $answers;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'CatquestionsOrders', 'catquestion_id', [
			'alias' => 'CatquestionsOrders',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => "This question is used in an order"
			]
		]);
		$this->belongsTo('type_id', 'CatquestionTypes', 'id', [
			'alias' => 'CatquestionTypes',
			'foreignKey' => [
				'message' => "Question type doesn'nt exists"
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

		$validator->add('question', new PresenceOf([
			'message'	=> 'Question is required'
		]));
		$validator->add('answers', new PresenceOf([
			'message'	=> 'Answers are required'
		]));
		/*$validator->add('hint', new PresenceOf([
			'message'	=> 'Hint is required'
		]));
		$validator->add('response_correct', new PresenceOf([
			'message'	=> 'Correct response is required'
		]));
		$validator->add('response_incorrect', new PresenceOf([
			'message'	=> 'Incorrect response is required'
		]));
		$validator->add('response_skip', new PresenceOf([
			'message'	=> 'Skip response is required'
		]));*/

		return $this->validate($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'catquestions';
	}

}
