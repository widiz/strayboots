<?php

use Phalcon\Validation\Validator\Email,
	Phalcon\Validation\Validator\Uniqueness,
	Phalcon\Validation\Validator\PresenceOf;

class UnsubscribingList extends \Phalcon\Mvc\Model
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
	public $created;

	public function initialize()
	{
		$this->skipAttributes(['created']);
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

		return $this->validate($validator);
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'unsubscribing_list';
	}

}
