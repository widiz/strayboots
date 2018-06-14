<?php

use Phalcon\Validation\Validator\Email,
	Phalcon\Validation\Validator\Uniqueness,
	Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Mvc\Model\Relation,
	Phalcon\Db\RawValue,
	Phalcon\Di;

class Suppliers extends \Phalcon\Mvc\Model
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
	public $email;

	/**
	 *
	 * @var string
	 */
	public $company;

	/**
	 *
	 * @var string
	 */
	public $password;

	/**
	 *
	 * @var string
	 */
	public $first_name;

	/**
	 *
	 * @var string
	 */
	public $last_name;

	/**
	 *
	 * @var string
	 */
	public $phone;

	/**
	 *
	 * @var string
	 */
	public $notes;

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


	public function initialize()
	{
		$this->skipAttributes(['created']);
		$this->hasMany('id', 'SupplierProducts', 'supplier_id', [
			'alias' => 'SupplierProducts',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
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

		$validator->add('email', new Email([
			'required'	=> true,
			'message'	=> 'Email is invalid'
		]));
		$validator->add('email', new Uniqueness([
			'message'	=> 'Email already exists'
		]));
		$validator->add('company', new StringLength([
			'max' => 150,
			'min' => 1,
			'messageMaximum' => 'Company is too long',
			'messageMinimum' => 'Company is too short'
		]));
		$validator->add('first_name', new StringLength([
			'max' => 20,
			'min' => 2,
			'messageMaximum' => 'First name is too long',
			'messageMinimum' => 'First name is too short'
		]));
		$validator->add('last_name', new StringLength([
			'max' => 20,
			'min' => 1,
			'messageMaximum' => 'Last name is too long',
			'messageMinimum' => 'Last name is too short'
		]));
		$validator->add('password', new StringLength([
			'max' => 32,
			'min' => 6,
			'messageMaximum' => 'Password is too long',
			'messageMinimum' => 'Password is too short'
		]));
		$validator->add('active', new InclusionIn([
			'domain' => [0, 1],
			'message' => 'Active is invalid'
		]));
		if (!is_null($this->phone)) {
			$validator->add('phone', new StringLength([
				'max' => 20,
				'min' => 1,
				'messageMaximum' => 'Phone is too long',
				'messageMinimum' => 'Phone is too short'
			]));
		}

		return $this->validate($validator);
	}

	public function beforeSave()
	{
		if (!isset($this->phone) || empty($this->phone))
			$this->phone = new RawValue('default');
		if (!isset($this->notes) || empty($this->notes))
			$this->notes = new RawValue('default');
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'suppliers';
	}

	public function checkLogin($password, $remember = false) {
		if ($this->active && $password == $this->password) {
			Suppliers::setSupplierLogin($this, $remember);
			return true;
		}
		return false;
	}

	public static function setSupplierLogin(Suppliers $supplier, $remember = false) {
		$di = Di::getDefault();
		$di->get('session')->set('supplierID', (int)$supplier->id);
		if ($remember) {
			$verifier = md5($supplier->id . '.strayboots%suppliers$salt#$%^34564' . $supplier->email);
			$lifetime = 86400 * 7; // 7 days cookie
			$di->get('redis')->set(SB_PREFIX . 'sverifier:' . $verifier, (int)$supplier->id, $lifetime);
			$lifetime += time();
			$secure = false;//$di->get('request')->isSecureRequest();
			$di->get('cookies')->set('sid', $supplier->id, $lifetime, '/', $secure)
								->set('sverifier', $verifier, $lifetime, '/', $secure);
		}
	}

	public static function logout() {
		$di = Di::getDefault();
		$secure = false;//$di->get('request')->isSecureRequest();
		$di->get('cookies')->set('sid', '', 0, '/', $secure)
							->set('sverifier', '', 0, '/', $secure);
		$di->get('session')->remove('supplierID');
	}

}
