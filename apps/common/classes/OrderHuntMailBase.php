<?php

/**
* OrderHuntMailBase
*/
class OrderHuntMailBase
{
	protected $client;
	protected $cc = [];
	protected $html;
	protected $text;
	protected $title;
	protected $translate;
	protected $isHeb;

	public function __construct(OrderHunts $oh)
	{
		global $config;
		$this->isHeb = false;
		$lang = $oh->Hunt->multilang && isset($config->altLang->{$oh->Hunt->multilang - 1}) ? $config->altLang->{$oh->Hunt->multilang - 1} : 'en';
		if ($lang === 'he')
			$this->isHeb = true;
		$this->translate = new \Phalcon\Translate\Adapter\NativeArray([
			'content' => require __DIR__ . '/translations/' . $lang . '.php'
		]);
	}

	public function send(callable $sendMail, $email = null)
	{
		if (!is_callable($sendMail))
			throw new Exception("SendMail isn't callable", 792);
		$mail = call_user_func($sendMail, is_null($email) ? $this->client->email : $email, $this->title, $this->text, $this->html, [], !is_null($email) ? [] : [
			'bcc' => 'ariel@safronov.co.il,karen@strayboots.com,madison@strayboots.com,support@strayboots.com,077cca161d@invite.trustpilot.com' .
			($email ? '' : (count($this->cc) ? ',' . implode(',', $this->cc) : ''))
		]);
		return is_object($mail) && isset($mail->http_response_code) && $mail->http_response_code == 200 ? true : $mail;
	}

	/**
	 * Convert a long url to a short url using Bitly API
	 * @param $url
	 * @return String a Bit.ly url | bool false
	 */
	protected function bitly($url, $config)
	{
		$get = 'https://api-ssl.bitly.com/v3/shorten?login=' . $config->login . '&apiKey=' . $config->APIKey . '&longUrl=' . urlencode($url);
		if (is_object($response = json_decode(file_get_contents($get))))
			return $response->status_code == 200 ? $response->data->url : $url;
		return $url;
	}
}