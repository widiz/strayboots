<?php

class SupplierProductCities extends \Phalcon\Mvc\Model
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
	public $supplier_product_id;

	/**
	 *
	 * @var integer
	 */
	public $city_id;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('supplier_product_id', 'SupplierProducts', 'id', [
			'alias' => 'Product',
			'foreignKey' => [
				'message' => 'Product doesn\'t exists'
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

		$validator->add(['supplier_product_id', 'city_id'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'City already exists'
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
		return 'supplier_product_cities';
	}

}
