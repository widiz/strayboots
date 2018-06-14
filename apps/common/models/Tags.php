<?php

class Tags extends \Phalcon\Mvc\Model
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
	public $tag;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'QuestionTags', 'tag_id', [
			'alias' => 'QuestionTags',
			'foreignKey' => [
				'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
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

		$validator->add('tag', new Phalcon\Validation\Validator\StringLength([
			'max' => 100,
			'min' => 2,
			'messageMaximum' => 'Tag is too long',
			'messageMinimum' => 'Tag is too short'
		]));
		$validator->add('tag', new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Tag already exists'
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
		return 'tags';
	}

}
