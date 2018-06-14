#!/usr/bin/php
<?php

define('__runtime__', microtime(1));
define('VERSION', '1.0.5');

/**
 * This makes our life easier when dealing with paths. 
 * Everything is relative to the application root now.
 */
define('APP_PATH', __DIR__);
chdir(APP_PATH);

/*
 * configure timeout and memory limit
 */
ini_set('memory_limit', '256M');
set_time_limit(60 * 60 * 24);

/*
 * Load config
 */

for ($i = $argc - 1; $i >= 0; $i--) {
	if (preg_match('/^\-\-host=(.+)$/', $argv[$i], $m)) {
		$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $m[1];
		unset($argv[$i]);
		$argv = array_values($argv);
		$argc--;
		break;
	}
}

/**
 * Process the console arguments
 */
$arguments = ['params' => []];
foreach ($argv as $k => $arg) {
	if (strpos($arg, '--env=') !== false)
		define('SBENV', substr($arg, 6));
	else if ($k === 0)
		continue;
	else if (!isset($arguments['task']))
		$arguments['task'] = $arg;
	else if (!isset($arguments['action']))
		$arguments['action'] = $arg;
	else
		$arguments['params'][] = $arg;
}

define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

$config = require APP_PATH . '/config/config.php';

/**
 * Init loader
 */
$loader = new \Phalcon\Loader();
$loader->registerDirs([
	$config->application->tasksDir,
	$config->application->modelsDir,
	__DIR__ . '/apps/common/classes'
])->registerNamespaces([
	'Play\Frontend\Controllers' => APP_PATH . '/apps/frontend/controllers'
])->register();

/**
 * Load services
 */

require APP_PATH . '/config/cliservices.php';

/**
 * Run application
 */
$application = new Phalcon\Cli\Console();
$di->setShared('console', $application);
$application->setDI($di);

try {
	$application->handle($arguments);
	echo PHP_EOL;
} catch (\Phalcon\Exception $e) {
	echo $e->getMessage() . PHP_EOL;
	exit(255);
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
	exit(255);
}