<?php

class Blocked extends \Phalcon\Mvc\Model
{

	/**
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('email', new Phalcon\Validation\Validator\Email([
			'required'	=> true,
			'message'	=> 'Email is invalid'
		]));

		$validator->add('email', new Phalcon\Validation\Validator\Uniqueness([
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
		return 'blocked';
	}

}
