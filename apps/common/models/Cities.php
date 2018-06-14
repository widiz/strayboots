<?php

use \Phalcon\Mvc\Model\Relation;

class Cities extends \Phalcon\Mvc\Model
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
	public $country_id;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var integer
	 */
	public $status;

	/**
	 *
	 * @var integer
	 */
	public $timezone;

	const Active = 0;
	const _New = 1;
	const ComingSoon = 2;
	const ContactOnly = 3;
	const B2c = 4;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'Hunts', 'city_id', [
			'alias' => 'Hunts',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This city has hunts'
			]
		]);
		$this->hasMany('id', 'Points', 'city_id', [
			'alias' => 'Points',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This city has points'
			]
		]);
		$this->belongsTo('country_id', 'Countries', 'id', [
			'alias' => 'Country',
			'foreignKey' => [
				'message' => 'Country doesn\'t exists'
			]
		]);
		/*$this->skipAttributesOnCreate([
			'status'
		]);*/
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
		$validator->add(['country_id', 'name'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'City already exists'
		]));
		$validator->add('status', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1, 2, 3, 4],
			'message' => 'Status is invalid'
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
		return 'cities';
	}

}
