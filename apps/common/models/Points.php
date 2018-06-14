<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\Numericality,
	Phalcon\Mvc\Model\Relation;

class Points extends \Phalcon\Mvc\Model
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
	public $city_id;

	/**
	 *
	 * @var integer
	 */
	public $type_id;

	/**
	 *
	 * @var string
	 */
	public $internal_name;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var string
	 */
	public $subtitle;

	/**
	 *
	 * @var string
	 */
	public $latitude;

	/**
	 *
	 * @var string
	 */
	public $longitude;

	/**
	 *
	 * @var string
	 */
	public $address;

	/**
	 *
	 * @var string
	 */
	public $phone;

	/**
	 *
	 * @var string
	 */
	public $hours;

	/**
	 *
	 * @var string
	 */
	public $notes;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'HuntPoints', 'point_id', [
			'alias' => 'HuntPoints',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This point has hunt points'
			]
		]);
		$this->hasMany('id', 'Questions', 'point_id', [
			'alias' => 'Questions',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This point has questions'
			]
		]);
		$this->belongsTo('type_id', 'PointTypes', 'id', [
			'alias' => 'PointType',
			'foreignKey' => [
				'message' => 'Point type doesn\'t exists'
			]
		]);
		$this->belongsTo('city_id', 'Cities', 'id', [
			'alias' => 'City',
			'foreignKey' => [
				'message' => 'City doesn\'t exists'
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

		$validator->add('name', new StringLength([
			'max' => 100,
			'min' => 2,
			'messageMaximum' => 'Name is too long',
			'messageMinimum' => 'Name is too short'
		]));
		$validator->add('internal_name', new StringLength([
			'max' => 100,
			'min' => 0,
			'allowEmpty' => true,
			'messageMaximum' => 'Internal name is too long',
			'messageMinimum' => 'Internal name is too short'
		]));
		/*$validator->add('internal_name', new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Internal name already in use'
		]));*/
		$validator->add('subtitle', new StringLength([
			'max' => 100,
			'min' => 0,
			'allowEmpty' => true,
			'messageMaximum' => 'Subtitle is too long',
			'messageMinimum' => 'Subtitle is too short'
		]));
		$validator->add('latitude', new Numericality([
			'message'	=> 'Latitude is invalid'
		]));
		$validator->add('longitude', new Numericality([
			'message'	=> 'Longitude is invalid'
		]));
		if (!is_null($this->address)) {
			$validator->add('address', new StringLength([
				'max' => 100,
				'min' => 1,
				'messageMaximum' => 'Address is too long',
				'messageMinimum' => 'Address is too short'
			]));
		}
		if (!is_null($this->phone)) {
			$validator->add('phone', new StringLength([
				'max' => 20,
				'min' => 1,
				'messageMaximum' => 'Phone is too long',
				'messageMinimum' => 'Phone is too short'
			]));
		}
		if (!is_null($this->hours)) {
			$validator->add('hours', new Phalcon\Validation\Validator\Regex([
				'pattern'	=> '/^([01]?[0-9]|2[0-3]):[0-5][0-9]\-([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
				'message'	=> 'Open hours are invalid'
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
		return 'points';
	}

}
