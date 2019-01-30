<?php

use Phalcon\Validation\Validator\StringLength;

class EventEmails extends \Phalcon\Mvc\Model
{

	/**
	 *
	 * @var integer
	 */
	public $email_id;

	/**
	 *
	 * @var string
	 */
	public $title;

	/**
	 *
	 * @var string
	 */
	public $html;

	/**
	 *
	 * @var string
	 */
	public $text;

	const DaniellePostEventEmailPlayers = 0;
	const DaniellePostEventEmail = 1;
	const NikkiPostEventEmail = 2;
	const ShaunaPostEventEmail = 3;
	const NewPostEventEmail = 4;
	const NewPostEventEmailPlayers = 5;

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('title', new StringLength([
			'min' => 3,
			'max' => 200,
			'messageMaximum' => 'Title is too long',
			'messageMinimum' => 'Title is too short'
		]));
		$validator->add('text', new StringLength([
			'min' => 20,
			'max' => 200000,
			'messageMaximum' => 'Text is too long',
			'messageMinimum' => 'Text is too short'
		]));
		$validator->add('html', new StringLength([
			'min' => 20,
			'max' => 200000,
			'messageMaximum' => 'HTML is too long',
			'messageMinimum' => 'HTML is too short'
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
		return 'event_emails';
	}

}
