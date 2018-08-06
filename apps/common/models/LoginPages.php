<?php

use Phalcon\Validation\Validator\StringLength,
	Phalcon\Mvc\Model\Message;

class LoginPages extends \Phalcon\Mvc\Model
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
	 * @var string
	 */
	public $slug;

	/**
	 *
	 * @var string
	 */
	public $title;

	/**
	 *
	 * @var string
	 */
	public $welcome_title;

	/**
	 *
	 * @var string
	 */
	public $email_login;

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$success = true;

		$validator->add('slug', new StringLength([
			'min' => 3,
			'max' => 200,
			'messageMaximum' => 'Slug is too long',
			'messageMinimum' => 'Slug is too short'
		]));
		$validator->add('title', new StringLength([
			'min' => 0,
			'max' => 200,
			'messageMaximum' => 'Title is too long'
		]));
		$validator->add('welcome_title', new StringLength([
			'min' => 0,
			'max' => 200,
			'messageMaximum' => 'Welcome title is too long'
		]));
		$validator->add('slug', new \Phalcon\Validation\Validator\Uniqueness([
			'message' => 'Slug is already in use'
		]));

		if (!is_null($this->email_login)) {
			$validator->add('email_login', new \Phalcon\Validation\Validator\Email([
				'message' => 'Email is invalid'
			]));
		}

		if (substr($this->slug, 0, 1) === '/' || substr($this->slug, -1) === '/') {
			$this->appendMessage(new Message(
				'Slug cannot begin or end with slash',
				'finish',
				'error'
			));
			$success = false;
		}

		return $this->validate($validator) && $success;
	}

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
	}

	/**
	 * Returns table name mapped in the model.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return 'login_pages';
	}

}
