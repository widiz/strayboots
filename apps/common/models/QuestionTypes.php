<?php

use \Phalcon\Mvc\Model\Relation;

class QuestionTypes extends \Phalcon\Mvc\Model
{

	/**
	 *
	 * @var integer
	 */
	public $id;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var integer
	 */
	public $type;

	/**
	 *
	 * @var integer
	 */
	public $score;

	/**
	 *
	 * @var integer
	 */
	public $custom;

	/**
	 *
	 * @var integer
	 */
	public $limitAnswers;

	const Text = 0;
	const Photo = 1;
	const Completion = 2;
	const Other = 3;
	const Timer = 4;
	const Choose = 5;
	const OpenText = 6;
	const OpenCheckbox = 7;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Questions', 'type_id', [
			'alias' => 'Questions',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This type has questions'
			]
		]);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'question_types';
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('name', new Phalcon\Validation\Validator\StringLength([
			'max' => 100,
			'min' => 2,
			'messageMaximum' => 'Name is too long',
			'messageMinimum' => 'Name is too short'
		]));
		$validator->add('name', new Phalcon\Validation\Validator\Uniqueness([
			'message'   => 'Question type name already exists'
		]));
		$validator->add('type', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1, 2, 3, 4, 5, 6, 7],
			'message' => 'Invalid type'
		]));
		$validator->add('score', new Phalcon\Validation\Validator\Regex([
			'pattern'	=> '/^\d+$/',
			'message'	=> 'Score is invalid'
		]));
		$validator->add('custom', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Custom is invalid'
		]));
		$validator->add('limitAnswers', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Limit Answers is invalid'
		]));

		return $this->validate($validator);
	}

}
