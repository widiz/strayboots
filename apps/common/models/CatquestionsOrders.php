<?php

class CatquestionsOrders extends \Phalcon\Mvc\Model
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
	public $order_hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $hunt_point_id;

	/**
	 *
	 * @var integer
	 */
	public $catquestion_id;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunts',
			'foreignKey' => [
				'message' => "Order hunt doesn'nt exists"
			]
		]);
		$this->belongsTo('catquestion_id', 'Catquestions', 'id', [
			'alias' => 'Catquestions',
			'foreignKey' => [
				'message' => "Question doesn'nt exists"
			]
		]);
		$this->belongsTo('hunt_point_id', 'HuntPoints', 'id', [
			'alias' => 'HuntPoints',
			'foreignKey' => [
				'message' => "Hunt point doesn'nt exists"
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
		return 'catquestions_orders';
	}

}
