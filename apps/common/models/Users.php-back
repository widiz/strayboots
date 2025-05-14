<?php

use Phalcon\Validation\Validator\Email,
	Phalcon\Validation\Validator\Uniqueness,
	Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Db\RawValue,
	Phalcon\Di;

class Users extends \Phalcon\Mvc\Model
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
		$this->skipAttributesOnCreate(['active']);
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
		$validator->add('first_name', new PresenceOf([
			'message' => 'First name is required'
		]));
		$validator->add('last_name', new PresenceOf([
			'message' => 'Last name is required'
		]));
		$validator->add('password', new PresenceOf([
			'message' => 'Password is required'
		]));
		if (isset($this->active) && !is_null($this->active)) {
			$validator->add('active', new InclusionIn([
				'domain' => [0, 1],
				'message' => 'Active is invalid'
			]));
		}

		return $this->validate($validator);
	}

	public function beforeCreate()
	{
		if (!isset($this->phone) || empty($this->phone))
			$this->phone = new RawValue('default');
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'users';
	}

	public function checkLogin($password, $remember = false) {
		if ($this->active && $this->getDI()->get('security')->checkHash($password, $this->password)) {
			Users::setUserLogin($this, $remember);
			return true;
		}
		return false;
	}

	public static function setUserLogin(Users &$user, $remember = false) {
		$di = Di::getDefault();
		$di->get('session')->set('userID', (int)$user->id);
		if ($remember) {
			$verifier = md5($user->id . '.strayboots%users$salt#$%^34564' . $user->email);
			$lifetime = 86400 * 7; // 7 days cookie
			$di->get('redis')->set(SB_PREFIX . 'uverifier:' . $verifier, (int)$user->id, $lifetime);
			$lifetime += time();
			$secure = false;//$di->get('request')->isSecureRequest();
			$di->get('cookies')->set('uid', $user->id, $lifetime, '/', $secure)
								->set('uverifier', $verifier, $lifetime, '/', $secure);
		}
	}

	public static function logout() {
		$di = Di::getDefault();
		$secure = false;//$di->get('request')->isSecureRequest();
		$di->get('cookies')->set('uid', '', 0, '/', $secure)
							->set('uverifier', '', 0, '/', $secure);
		$di->get('session')->remove('userID');
	}

}
