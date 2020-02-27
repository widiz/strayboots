<?php

use Phalcon\Mvc\Model\Relation;

class Routes extends \Phalcon\Mvc\Model
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
	public $hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $active;

	/**
	 *
	 * @var integer
	 */
	public $deleted;

	/**
	 *
	 * @var text
	 */
	public $notes;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'RoutePoints', 'route_id', [
			'alias' => 'RoutePoints',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->hasMany('id', 'Teams', 'route_id', [
			'alias' => 'Teams',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This route has teams'
			]
		]);
		$this->belongsTo('hunt_id', 'Hunts', 'id', [
			'alias' => 'Hunts',
			'foreignKey' => [
				'message' => 'Hunt doesn\'t exists'
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

		$validator->add('active', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Active is invalid'
		]));

		$validator->add('deleted', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'Delete is invalid'
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
		return 'routes';
	}

}
