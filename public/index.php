<?php
//$t = microtime(1);

$isDebug = isset($_COOKIE['_sb_']);

if ($isDebug) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	$debug = new \Phalcon\Debug();
	$debug->listen();
} else {
	error_reporting(0);
	ini_set('display_errors', 0);
}

define('STRAYBOOTS_BUILD', 1159);

define('APP_PATH', realpath('..'));
define('PUBLIC_PATH', __DIR__ . '/');

try {

	/**
	 * Read the configuration
	 */
	$config = include APP_PATH . '/config/config.php';

	/**
	 * Include services
	 */
	require APP_PATH . '/config/services.php';

	/**
	 * Handle the request
	 */
	$application = new Phalcon\Mvc\Application($di);

	/**
	 * Include modules
	 */
	require APP_PATH . '/config/modules.php';

	/**
	 * Include routes
	 */
	require APP_PATH . '/config/routes.php';

	if ($isDebug) {
		if (isset($_COOKIE['debugbar'])) {
			$di->setShared('app', $application);
			(new Snowair\Debugbar\ServiceProvider(APP_PATH . '/config/debugbar.php'))->start();
			if ($di->has('debugbar'))
				$di['debugbar']->attachCache('redis');
		}
		echo $application->handle()->getContent();
	} else {
		echo str_replace(["\t", "\n"], '', $application->handle()->getContent());
	}

} catch (\Exception $e) {
	if ($isDebug) {
		echo $e->getMessage() . '<br>';
		echo '<pre>' . $e->getTraceAsString() . '</pre>';
	} else {
		logToFile([
			'eh'		=> true,
			'code'		=> $e->getCode(),
			'message'	=> $e->getMessage(),
			'stack'		=> [ $e->getFile(), $e->getLine(), $e->getTrace() ]
		], true);
	}
}

//echo  "<!-- " . (microtime(1) - $t) . " -->";