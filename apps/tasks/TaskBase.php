<?php

class TaskBase extends \Phalcon\Cli\Task
{
	public $dateFormat = 'm/d/Y';
	public $timeFormat = 'm/d/Y h:i A \E\S\T';

	public function afterExecuteRoute(\Phalcon\Cli\Dispatcher $dispatcher) {
		$this->redis->close();
		$runtime = microtime(1) - __runtime__;
		$memory =  max(memory_get_peak_usage(true), 0);
		$pow = min(floor(log($memory) / log(1024)), 3); 
		$memory = round($memory / (1 << (10 * $pow)), 2) . '' . ['B', 'KB', 'MB', 'GB'][$pow]; 
		echo PHP_EOL . "Execution done: {$memory} @ {$runtime}s" . PHP_EOL;
	}

	public function sendMail($to, $subject, $text = '', $html = '', $attachments = [], $options = [], $attachmentsOpts = []) {
		return $this->mailer->sendMessage($this->config->mailgun->domain, [
			'from'		=> $this->config->mailgun->from, 
			'to'		=> $to,
			'subject'	=> $subject,
			'text'		=> $text,
			'html'		=> $html
		] + $options, [
			'attachment' => $attachments
		] + $attachmentsOpts);
	}

	/**
	 * pngquant
	 * @param string $path
	 *
	 * @return bool
	 */
	public function pngquant($path)
	{
		$path = escapeshellarg($path);
		return @shell_exec("/usr/local/bin/pngquant --force --skip-if-larger --quality 40-100 --speed 1 --output {$path} {$path} 2>&1");
	}

	/**
	 * jpegtran
	 * @param string $path
	 *
	 * @return bool
	 */
	public function jpegtran($path)
	{
		$path = escapeshellarg($path);
		return @shell_exec("/usr/bin/jpegtran -optimize -progressive -copy all -outfile {$path} {$path} 2>&1");
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
