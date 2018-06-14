<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\Numericality,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Mvc\Model\Relation;

class SupplierProducts extends \Phalcon\Mvc\Model
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
	public $supplier_id;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var string
	 */
	public $description;

	/**
	 *
	 * @var integer
	 */
	public $price;

	/**
	 *
	 * @var integer
	 */
	public $min_players;

	/**
	 *
	 * @var integer
	 */
	public $max_players;

	/**
	 *
	 * @var string
	 */
	public $hours;

	/**
	 *
	 * @var string
	 */
	public $address;

	/**
	 *
	 * @var integer
	 */
	public $latitude;

	/**
	 *
	 * @var integer
	 */
	public $longitude;

	/**
	 *
	 * @var array
	 */
	public $images;

	/**
	 *
	 * @var integer
	 */
	public $active;

	/**
	 *
	 * @var string
	 */
	public $created;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'SupplierProductCities', 'supplier_product_id', [
			'alias' => 'Cities',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		$this->belongsTo('supplier_id', 'Suppliers', 'id', [
			'alias' => 'Supplier',
			'foreignKey' => [
				'message' => 'Supplier doesn\'t exists'
			]
		]);
		$this->skipAttributes(['created']);
	}

	public function beforeSave()
	{
		if (!isset($this->description))
			$this->description = '';
		if (isset($this->images) && is_array($this->images) && !empty($this->images))
			$this->images = json_encode($this->images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		else
			$this->images = null;
	}

	public function afterFetch()
	{
		if (isset($this->images))
			$this->images = json_decode($this->images, true);
	}

	public function afterSave()
	{
		if (isset($this->images))
			$this->images = json_decode($this->images, true);
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
		$validator->add('latitude', new Numericality([
			'message'	=> 'Latitude is invalid'
		]));
		$validator->add('longitude', new Numericality([
			'message'	=> 'Longitude is invalid'
		]));
		$validator->add('price', new Numericality([
			'message'	=> 'Price is invalid'
		]));
		$validator->add('min_players', new Regex([
			'pattern'	=> '/^\d{1,5}$/',
			'message'	=> 'Min players is invalid'
		]));
		$validator->add('max_players', new Regex([
			'pattern'	=> '/^\d{1,5}$/',
			'message'	=> 'Max players is invalid'
		]));
		$validator->add('address', new StringLength([
			'max' => 100,
			'min' => 1,
			'messageMaximum' => 'Address is too long',
			'messageMinimum' => 'Address is too short'
		]));
		if (!is_null($this->hours)) {
			$validator->add('hours', new Regex([
				'pattern'	=> '/^([01]?[0-9]|2[0-3]):[0-5][0-9]\-([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
				'message'	=> 'Open hours are invalid'
			]));
		}
		if (!is_null($this->images)) {
			$validator->add('images', new PresenceOf([
				'message'	=> 'Images invalid format'
			]));
		}
		$validator->add('active', new InclusionIn([
			'domain' => [0, 1],
			'message' => 'Active is invalid'
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
		return 'supplier_products';
	}

}
