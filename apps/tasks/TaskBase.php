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
		
		if (!is_array($to))
			$to = explode(',', $to);
		if (isset($options['bcc'])) {
			$to = array_unique(array_merge($to, is_array($options['bcc']) ? $options['bcc'] : explode(',', $options['bcc'])));
			unset($options['bcc']);
		}
		ignore_user_abort(true);
		set_time_limit(120);
		$success = true;

		$to = array_filter($to, function($e){
			return filter_var($e, FILTER_VALIDATE_EMAIL);
		});
		$emailsTo = array_map(function($e){
			return '\'' . $e .  '\'';
		}, $to);

		$unsubscribingList = empty($emailsTo) ? [] : array_map('array_pop', $this->db->fetchAll('SELECT email FROM unsubscribing_list WHERE email IN (' . implode(',', $emailsTo) . ')'));

		foreach ($to as $e) {
			if (in_array($e, $unsubscribingList))
				continue;
			try {
				$unsubscribeText = '<hr><p>You can unsubscribe from the mailing list by clicking on the <a href="' . $this->config->fullUri . '/index/unsubscribe?m=' . rawurlencode($this->crypt->encryptBase64($e)) . '">link</a>.</p>';
				$html = str_replace('%unsubscribe%', $unsubscribeText, $html);

				$success = $this->mailer->sendMessage($this->config->mailgun->domain, [
					'from'		=> $this->config->mailgun->from, 
					'to'		=> $e,
					'subject'	=> $subject,
					'text'		=> $text,
					'html'		=> $html
				] + $options, [
					'attachment' => $attachments
				] + $attachmentsOpts) && $success;
			} catch(\Exception $e) {
				$success =  false;
			}
		}
		return /*$success*/ true;
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
