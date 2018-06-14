<?php

class Answers extends \Phalcon\Mvc\Model
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
	public $hunt_id;

	/**
	 *
	 * @var integer
	 */
	public $team_id;

	/**
	 *
	 * @var integer
	 */
	public $question_id;

	/**
	 *
	 * @var integer
	 */
	public $action;

	/**
	 *
	 * @var string
	 */
	public $answer;

	/**
	 *
	 * @var string
	 */
	public $created;

	/**
	 *
	 * @var string
	 */
	public $funfact_viewed;

	const Answered = 0;
	const AnsweredWithHint = 1;
	const Skipped = 2;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->belongsTo('team_id', 'Teams', 'id', [
			'alias' => 'Team',
			'foreignKey' => [
				'message' => 'Team doesn\'t exists'
			]
		]);
		$this->belongsTo('hunt_id', 'Hunts', 'id', [
			'alias' => 'Hunt',
			'foreignKey' => [
				'message' => 'Hunt doesn\'t exists'
			]
		]);
		$this->belongsTo('question_id', 'Questions', 'id', [
			'alias' => 'Question',
			'foreignKey' => [
				'message' => 'Question doesn\'t exists'
			]
		]);
		$this->skipAttributesOnCreate(['created', 'funfact_viewed']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('action', new Phalcon\Validation\Validator\InclusionIn([
			'domain' => [0, 1, 2],
			'message' => 'Action is invalid'
		]));
		$validator->add(['team_id', 'hunt_id', 'question_id'], new Phalcon\Validation\Validator\Uniqueness([
			'message'	=> 'Answer already exists'
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
		return 'answers';
	}

	public static function filterAnswer($a)
	{
		return mb_strtolower(trim($a));
	}

	public static function checkAnswer($answers = '', $answer = '')
	{
		if (empty($answers) && $answers !== '0')
			return true;
		if (empty($answer) && $answer !== '0')
			return false;

		$answers = explode("\n", $answers);
		$answer = Answers::filterAnswer($answer);
		$answerWords =  explode(' ', $answer);

		foreach ($answers as $a) {
			$a = Answers::filterAnswer($a);
			if ($a === '<any>' || $a === $answer)
				return true;

			if (substr($a, 0, 2) === '**') {
				if (substr($a, 2) === $answer)
					return true;
			} else if (!empty(array_intersect($answerWords, explode(' ', $a)))) {
				return true;
			}
		}
		return false;
	}

}
