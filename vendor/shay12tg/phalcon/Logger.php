<?php

namespace Shay12tg\Phalcon;

class Logger extends \Phalcon\Logger\Adapter\File {

	private $email;

	public function __construct($filename, $email = false, $env = 'production')
	{
		parent::__construct($filename);
		$this->setFormatter(new \Phalcon\Logger\Formatter\Line('[' . $env . '][%date%][%type%] %message%'));
		$this->email = $email;
	}

	public function critical($message, array $context = NULL)
	{
		parent::critical($message, $context);
		$this->contact($message);
	}

	public function error($message, array $context = NULL)
	{
		parent::error($message, $context);
		$this->contact($message);
	}

	private function contact($message = '')
	{
		if (!$this->email)
			return;

		if (!is_string($message))
			$message = json_encode($message);
		$message .= PHP_EOL . PHP_EOL . microtime(1);

		$di = \Phalcon\Di::getDefault();
		$config = $di->get('config');

		$di->get('mailer')->sendMessage($config->mailgun->domain, [
			'from'		=> $config->mailgun->from, 
			'to'		=> $this->email,
			'subject'	=> "Critical Error",
			'text'		=> $message
		]);
	}

}