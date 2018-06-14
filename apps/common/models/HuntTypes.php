<?php

class HuntTypes extends \Phalcon\Mvc\Model
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
	public $name;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
        $this->hasMany('id', 'Hunts', 'type_id', [
        	'alias' => 'Hunts',
			'foreignKey' => [
				'action' => \Phalcon\Mvc\Model\Relation::ACTION_RESTRICT,
				'message' => 'This type has hunts'
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
			'max' => 100,
			'min' => 2,
			'messageMaximum' => 'Name is too long',
			'messageMinimum' => 'Name is too short'
		]));
		$validator->add('name', new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Type already exists'
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
		return 'hunt_types';
	}

}
