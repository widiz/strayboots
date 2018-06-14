<?php

use Phalcon\Validation\Validator\Regex;

class CustomQuestions extends QuestionsBase
{

	/**
	 *
	 * @var integer
	 */
	public $order_hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $idx;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'CustomAnswers', 'custom_question_id', [
			'alias' => 'CustomAnswers',
			'foreignKey' => [
				'action' => Phalcon\Mvc\Model\Relation::ACTION_RESTRICT,
				'message' => 'This question has answers'
			]
		]);
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
		$this->belongsTo('type_id', 'QuestionTypes', 'id', [
			'alias' => 'QuestionType',
			'foreignKey' => [
				'message' => 'Question type doesn\'t exists'
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

		$validator->add('score', new Regex([
			'pattern'	=> '/^\d+$/',
			'message'	=> 'Score is invalid'
		]));
		$validator->add('idx', new Regex([
			'pattern'	=> '/^\d+$/',
			'message'	=> 'Idx is invalid'
		]));
		$validator->add(['order_hunt_id', 'idx'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'A custom question already exists in this question idx'
		]));
		if ($oh = $this->orderHunt) {
			$validator->add('idx', new Phalcon\Validation\Validator\Between([
				'minimum' => 0,
				'maximum' => $oh->Hunt->countHuntPoints() /*- 1*/,
				'message' => 'Idx is out of range'
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
		return 'custom_questions';
	}

}
