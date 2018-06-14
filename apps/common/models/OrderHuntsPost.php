<?php

class OrderHuntsPost extends \Phalcon\Mvc\Model
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
	public $identifier;

	/**
	 *
	 * @var string
	 */
	public $created;

	const PostEventEmail = 0;
	const DaniellePostEventEmail = 1;
	const NikkiPostEventEmail = 2;
	const ShaunaPostEventEmail = 3;
	const NewPostEventEmail = 4;
	const NewPostEventEmailPlayers = 5;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('order_hunt_id', 'OrderHunts', 'id', [
			'alias' => 'OrderHunt',
			'foreignKey' => [
				'message' => 'Order hunt doesn\'t exists'
			]
		]);
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

		$validator->add(['order_hunt_id', 'identifier'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Order hunt with this identifier already exists'
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
		return 'order_hunts_post';
	}

}
