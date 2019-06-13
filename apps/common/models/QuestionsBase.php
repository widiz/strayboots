<?php

use Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Mvc\Model\Relation,
	Phalcon\Mvc\Model\Message;

class QuestionsBase extends \Phalcon\Mvc\Model
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
	public $type_id;

	/**
	 *
	 * @var integer
	 */
	public $name;

	/**
	 *
	 * @var integer
	 */
	public $score;

	/**
	 *
	 * @var string
	 */
	public $question;

	/**
	 *
	 * @var string
	 */
	public $qattachment;

	/**
	 *
	 * @var string
	 */
	public $hint;

	/**
	 *
	 * @var string
	 */
	public $funfact;

	/**
	 *
	 * @var string
	 */
	public $response_correct;

	/**
	 *
	 * @var string
	 */
	public $answers;

	/**
	 *
	 * @var string
	 */
	public $attachment;

	/**
	 *
	 * @var integer
	 */
	public $timeout;

	const ATTACHMENT_PHOTO = 1;
	const ATTACHMENT_VIMEO = 2;
	const ATTACHMENT_YOUTUBE = 3;


	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function baseValidation($validator)
	{
		$success = true;

		$validator->add('question', new PresenceOf([
			'message'	=> 'Question is required'
		]));
		$validator->add('funfact', new PresenceOf([
			'message'	=> 'Fun fact is required'
		]));
		if (!is_null($this->name)) {
			$validator->add('name', new Phalcon\Validation\Validator\StringLength([
				'max' => 100,
				'min' => 2,
				'messageMaximum' => 'Name is too long',
				'messageMinimum' => 'Name is too short'
			]));
		}
		if ($this->timeout === '00:00:00')
			$this->timeout = null;
		if (!is_null($this->timeout)) {
			if (preg_match('/^(\d{2}:){2}\d{2}$/', $this->timeout)) {
				if ($t = strtotime($this->timeout))
					$this->timeout = $t - strtotime('today');
				if (!($this->timeout > 0))
					$this->timeout = '';
			}
			$validator->add('timeout', new Regex([
				'pattern'	=> '/^\d+$/',
				'message'	=> 'Timeout is invalid'
			]));
		}
		if ($qtype = $this->QuestionType) {
			if ($qtype->type == QuestionTypes::Text) {
				$validator->add('answers', new PresenceOf([
					'message'	=> 'Answers are required'
				]));
			} else if ($qtype->type == QuestionTypes::Choose) {
				$numCorrect = 0;
				$options = array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $this->answers))), function($o) use (&$numCorrect){
					if (substr($o, 0, 1) == '*') {
						$o = ltrim($o, ' *');
						$numCorrect++;
					}
					return !empty($o) || $o === '0';
				});
				$c = count($options);
				if ($c > 1 && $c < 6) {
					if ($numCorrect > 0) {
						/*if ($numCorrect >= $c) {
							$this->appendMessage(new Message(
								'Too many correct answers',
								'answers',
								'error'
							));
							$success = false;
						} else {*/
							$this->answers = implode("\n", $options);
						//}
					} else {
						$this->appendMessage(new Message(
							'Choose at least one correct answer (* in the beginning of the line)',
							'answers',
							'error'
						));
						$success = false;
					}
				} else {
					$this->appendMessage(new Message(
						'Enter 2-5 options. one option per line',
						'answers',
						'error'
					));
					$success = false;
				}
			} else if ($qtype->type == QuestionTypes::Timer) {
				$validator->add('answers', new Regex([
					'pattern'	=> '/^\d{2}:\d{2}$/',
					'message'	=> 'Answer should be a timer (30:00 for example)'
				]));
			} else if ($qtype->type == QuestionTypes::Completion) {
				$o = isset($_POST['answers']) ? json_decode($_POST['answers'], true) : json_decode($this->answers, true);
				if (is_array($o) && isset($o['w']) && strlen($o['w']) && isset($o['l']) && is_array($o['l']) && count($o['l'])) {
					$this->answers = json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
				} else {
					$this->appendMessage(new Message(
						'Answer is missing. please enter a phrase with at least one hidden letter',
						'answers',
						'error'
					));
					$success = false;
				}
			}
		}

		if (!is_null($this->qattachment)) {
			if ($this->getQAttachment() === false) {
				$this->appendMessage(new Message(
					'Question attachment is invalid',
					'qattachment',
					'error'
				));
				$success = false;
			}
		}

		if (!is_null($this->attachment)) {
			if ($this->getAttachment() === false) {
				$this->appendMessage(new Message(
					'Fun fact attachment is invalid',
					'attachment',
					'error'
				));
				$success = false;
			}
		}

		return $this->validate($validator) && $success;
	}

	/**
	 * Returns attachment array
	 *
	 * @return array
	 */

	public function getAttachment()
	{
		return $this->processAttachment($this->attachment);
	}

	public function getQAttachment()
	{
		return $this->processAttachment($this->qattachment);
	}

	private function processAttachment($v)
	{
		if (!is_null($v)) {
			$attachment = json_decode($v, true);
			if (isset($attachment['type'])) {
				if ($attachment['type'] == QuestionsBase::ATTACHMENT_PHOTO && isset($attachment['photo']) && !empty($attachment['photo'])) {
					return [QuestionsBase::ATTACHMENT_PHOTO, $attachment['photo']];
				} else if (($attachment['type'] == QuestionsBase::ATTACHMENT_VIMEO || $attachment['type'] == QuestionsBase::ATTACHMENT_YOUTUBE) && isset($attachment['video']) && !empty($attachment['video'])) {
					return [$attachment['type'], $attachment['video']];
				}
			}
		}
		return false;
	}
}
