<?php

use Phalcon\Mvc\Model\Relation;

class Orders extends \Phalcon\Mvc\Model
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
	public $name;

	/**
	 *
	 * @var integer
	 */
	public $client_id;

	/**
	 *
	 * @var string
	 */
	public $customize;

	/**
	 *
	 * @var string
	 */
	public $created;

	/**
	 *
	 * @var string
	 */
	public $code_prefix;

	const codeLength = 5;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
		$this->hasMany('id', 'OrderHunts', 'order_id', [
			'alias' => 'OrderHunts',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT,
				'message' => 'This order has hunts'
			]
		]);
		$this->belongsTo('client_id', 'Clients', 'id', [
			'alias' => 'Client',
			'foreignKey' => [
				'message' => 'Client doesn\'t exists'
			]
		]);
		$this->skipAttributes(['created']);
		$this->skipAttributesOnCreate(['customize']);
	}

	/**
	 * Validations and business logic
	 *
	 * @return boolean
	 */
	public function validation()
	{
		$validator = new Phalcon\Validation();

		$validator->add('name', new Phalcon\Validation\Validator\PresenceOf([
			'message'	=> 'Name is required'
		]));
		$validator->add('code_prefix', new Phalcon\Validation\Validator\StringLength([
			'max' => 20,
			'min' => 1,
			'messageMaximum' => 'Activation code prefix is too long',
			'messageMinimum' => 'Activation code prefix is too short'
		]));
		$validator->add('code_prefix', new Phalcon\Validation\Validator\Regex([
			'pattern'	=> '/^(\d+|[a-z]+)$/i',
			'message'	=> 'Activation code prefix should be numbers OR letters only'
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
		return 'orders';
	}

	public function getCustomizeArray()
	{
		if (!is_null($this->customize) && is_array($customize = json_decode($this->customize, true)))
			return $customize;
		return [];
	}

	public function getCSS($paths, $customize = null, $removedImages = [])
	{
		if (is_null($customize))
			$customize = $this->getCustomizeArray();
		$uploadBase = $paths->path . 'order.' . $this->id . '.';
		$huntCss = '';
		if (file_exists($uploadBase . 'background.png') && !isset($removedImages['background']))
			$huntCss .= 'body{background-image:url(' . $paths->uri . 'order.' . $this->id . '.background.png) !important;background-position:50% !important}';
		if (isset($customize['header_color']))
			$huntCss .= '.navbar-default{background-color:' . $customize['header_color'] . '}';
		if (isset($customize['background_color']))
			$huntCss .= 'body{background-color:' . $customize['background_color'] . ' !important;background-image:none}';
		if (isset($customize['main_color']))#header-score>div>div,
			$huntCss .= '#faq,.navbar-default .navbar-nav>li>a.endtimer-link:hover,.navbar-default .navbar-nav>li>a,#navbar .visible-xs,#header-score>i,footer a,footer a:hover,#playground .question,.content-wrapper h1,.content-wrapper h3,.content-wrapper h4{color:' . $customize['main_color'] . '}';
		if (isset($customize['second_color']))
			$huntCss .= '#playground h2,#header-score>div>span,b.second_color,#bq-timer h2,#bq-timer .sec,#playground .hint-title,#playground .hint{color:' . $customize['second_color'] . '}';
		if (file_exists($uploadBase . 'header.png') && !isset($removedImages['header']))
			$huntCss .= '.navbar-default{background-image:url(' . $paths->uri . 'order.' . $this->id . '.header.png)}';
		if (isset($customize['custom_css']))
			$huntCss .= str_replace(["\t", "\n", '#body'], ['', '', 'body'], $customize['custom_css']);
		return $huntCss;
	}

	public function generateActivationCodes($count = 1)
	{ 
		//LPAD(CONV(RAND() * POW(36, ' . Orders::codeLength . '), 10, 36), ' . Orders::codeLength . ', 0)
		$count = (int)$count;
		$db = $this->getDI()->get('db');
		$codePrefix = $db->escapeString($this->code_prefix);
		$codes = [];
		while (($c = $count - count($codes)) > 0) {
			$codes = array_values(array_unique(array_merge($codes, array_filter(array_map(function($c){
				return $c['code'];
			}, $db->fetchAll('SELECT DISTINCT codes.code FROM (SELECT CONCAT(' . $codePrefix . ', \'-\', LPAD(FLOOR(RAND() * 1e' . Orders::codeLength . '), ' . Orders::codeLength . ', 0)) as `code` FROM questions LIMIT ' . $c . ') codes WHERE 1 NOT IN (SELECT 1 FROM teams WHERE activation_player=codes.code) AND 1 NOT IN (SELECT 1 FROM teams WHERE activation_leader=codes.code)', Phalcon\Db::FETCH_ASSOC))))));
		}
		return $codes;
	}

}
