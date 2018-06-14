<?php

class RoutePoints extends \Phalcon\Mvc\Model
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
	public $route_id;

	/**
	 *
	 * @var integer
	 */
	public $hunt_point_id;

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
		$this->belongsTo('hunt_point_id', 'HuntPoints', 'id', [
			'alias' => 'HuntPoints',
			'foreignKey' => [
				'message' => 'Hunt point doesn\'t exists'
			]
		]);
		$this->belongsTo('route_id', 'Routes', 'id', [
			'alias' => 'Routes',
			'foreignKey' => [
				'message' => 'Route doesn\'t exists'
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

		$validator->add('idx', new Phalcon\Validation\Validator\Regex([
			'pattern'	=> '/^\d{1,3}$/',
			'message'	=> 'Index is invalid'
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
		return 'route_points';
	}

}
