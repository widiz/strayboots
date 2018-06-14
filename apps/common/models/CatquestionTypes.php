<?php

class CatquestionTypes extends \Phalcon\Mvc\Model
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
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Catquestions', 'type_id', [
			'alias' => 'Catquestions',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => "This type has questions"
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

		$validator->add('name', new Phalcon\Validation\Validator\StringLength([
			'max' => 30,
			'min' => 2,
			'messageMaximum' => 'Name is too long',
			'messageMinimum' => 'Name is too short'
		]));
		$validator->add('name', new Phalcon\Validation\Validator\Uniqueness([
			'message'   => "Type name already exists"
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
		return 'catquestion_types';
	}

}
