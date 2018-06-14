<?php

class QuestionTags extends \Phalcon\Mvc\Model
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
	public $question_id;

	/**
	 *
	 * @var integer
	 */
	public $tag_id;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('question_id', 'Questions', 'id', [
			'alias' => 'Question',
			'foreignKey' => [
				'message' => 'Question doesn\'t exists'
			]
		]);
		$this->belongsTo('tag_id', 'Tags', 'id', [
			'alias' => 'Tag',
			'foreignKey' => [
				'message' => 'Tag doesn\'t exists'
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

		$validator->add(['question_id', 'tag_id'], new Phalcon\Validation\Validator\Uniqueness([
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
		return 'question_tags';
	}

}
