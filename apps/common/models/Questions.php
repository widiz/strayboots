<?php

use Phalcon\Validation\Validator\Regex,
	Phalcon\Mvc\Model\Relation;

class Questions extends QuestionsBase
{

	/**
	 *
	 * @var integer
	 */
	public $point_id;

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
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Answers', 'question_id', [
			'alias' => 'Answers',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This question has answers'
			]
		]);
		$this->hasMany('id', 'HuntPoints', 'question_id', [
			'alias' => 'HuntPoints',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This question has hunt points'
			]
		]);
		$this->hasMany('id', 'WrongAnswers', 'question_id', [
			'alias' => 'WrongAnswers',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'QuestionTags', 'question_id', [
			'alias' => 'Tags',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->belongsTo('type_id', 'QuestionTypes', 'id', [
			'alias' => 'QuestionType',
			'foreignKey' => [
				'message' => 'Question type doesn\'t exists'
			]
		]);
		$this->belongsTo('point_id', 'Points', 'id', [
			'alias' => 'Point',
			'foreignKey' => [
				'allowNulls' => true,
				'message' => 'Point doesn\'t exists'
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

		/*$validator->add('hint', new PresenceOf([
			'message'	=> 'Hint is required'
		]));
		$validator->add('response_incorrect', new PresenceOf([
			'message'	=> 'Incorrect response is required'
		]));
		$validator->add('response_skip', new PresenceOf([
			'message'	=> 'Skip response is required'
		]));*/
		if (!is_null($this->score)) {
			$validator->add('score', new Regex([
				'pattern'	=> '/^\d+$/',
				'message'	=> 'Score is invalid'
			]));
		}

		return $this->baseValidation($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'questions';
	}

}
