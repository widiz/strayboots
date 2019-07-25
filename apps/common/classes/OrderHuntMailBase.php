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
		$lang = $oh->Hunt->multilang && isset($config->altLang->{$oh->Hunt->multilang - 1}) ? $config->altLang->{$oh->Hunt->multilang - 1} : 'en';
		$this->isHeb = $lang === 'he';
		$this->translate = new \Phalcon\Translate\Adapter\NativeArray([
			'content' => require __DIR__ . '/translations/' . $lang . '.php'
		]);
	}

	public function getHtml()
	{
		return $this->html;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function send(callable $sendMail, $email = null, $attachments = [])
	{
		if (!is_callable($sendMail))
			throw new Exception('SendMail isn\'t callable', 792);
		$mail1 = call_user_func($sendMail, is_null($email) ? $this->client->email : $email, $this->title, $this->text, $this->html, $attachments, !is_null($email) ? [] : [
			// 'cc' => '077cca161d@invite.trustpilot.com',
			'bcc' => implode(',', $this->cc)
		]);
		// $mail2 = is_null($email) ? call_user_func($sendMail, 'ariel@safronov.co.il,karen@strayboots.com,ido@strayboots.com,support@strayboots.com', $this->title, $this->text, $this->html) : true;
		$mail2 = is_null($email) ? call_user_func($sendMail, 'ariel@safronov.co.il,cs@strayboots.com', $this->title, $this->text, $this->html) : true;
		return $mail1 && $mail2;
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