<?php

use Phalcon\Mvc\Model\Relation;

class HuntPoints extends \Phalcon\Mvc\Model
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
	public $point_id;

	/**
	 *
	 * @var integer
	 */
	public $question_id;

	/**
	 *
	 * @var integer
	 */
	public $is_start;

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
		$this->hasMany('id', 'RoutePoints', 'hunt_point_id', [
			'alias' => 'RoutePoints',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE,
				'message' => 'This hunt point has route points'
			]
		]);
		$this->hasMany('id', 'CatquestionsOrders', 'hunt_point_id', [
			'alias' => 'CatquestionsOrders',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->belongsTo('question_id', 'Questions', 'id', [
			'alias' => 'Question',
			'foreignKey' => [
				'message' => 'Question doesn\'t exists'
			]
		]);
		$this->belongsTo('hunt_id', 'Hunts', 'id', [
			'alias' => 'Hunt',
			'foreignKey' => [
				'message' => 'Hunt doesn\'t exists'
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

		$validator->add('idx', new Phalcon\Validation\Validator\Regex([
			'pattern'	=> '/^\d{1,3}$/',
			'message'	=> 'Index is invalid'
		]));
		$validator->add('is_start', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1],
			'message' => 'IsStart is invalid'
		]));
		if (!is_null($this->point_id)) {
			$validator->add(['hunt_id', 'point_id'], new Phalcon\Validation\Validator\Uniqueness([
				'message'	=> 'Same point is being used more than once in one hunt'
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
		return 'hunt_points';
	}


}
