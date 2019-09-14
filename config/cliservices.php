<?php


require __DIR__ . '/../vendor/autoload.php';

/**
 * Setup dependency injection
 */
$di = new Phalcon\Di\FactoryDefault\Cli();

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function() use ($config) {
	return new Phalcon\Db\Adapter\Pdo\Mysql($config->database->toArray());
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function() use ($config){
	return new Phalcon\Mvc\Model\Metadata\Memcache([
		'prefix' => SB_PREFIX
	]);
});

/**
 * The URL component is used to generate all kinds of URLs in the application
 */
$di->setShared('url', function() use ($config) {
	$url = new Phalcon\Mvc\Url();
	$url->setBaseUri($config->application->baseUri);
	return $url;
});


$di->setShared('escaper', function() use ($config) {
	return new Phalcon\Escaper();
});

$di->set('security', function(){
    $security = new Phalcon\Security();
    return $security;
});

$di->setShared('redis', function() use ($config){
	$redis = new \Redis;
	//$redis->pconnect('/var/tmp/redis.sock');
	$redis->pconnect('127.0.0.1', 6379, 10);
	if (!$redis->IsConnected()) {
		usleep(10000);
		$redis->pconnect('127.0.0.1', 6379, 10);
	}
	$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
	return $redis;
});

$di->setShared('firebase', function() use ($config){
	return new \Firebase\FirebaseLib($config->firebase->databaseURL, $config->firebaseServerKey);
});

$di->setShared('logger', function() use ($config){
	return new Shay12tg\Phalcon\Logger(__DIR__ . '/../logs/log.txt', 'shay12tg@gmail.com', $config->prefix);
});

$di->setShared('crypt', function(){
	$crypt = new \Phalcon\Crypt();
	$crypt->setCipher('aes-256-cbc');
	$crypt->setKey('S9mg_uU2*JF.S4f9');
	return $crypt;
});

$di->setShared('mailer', function() use ($config){
	return new \Mailgun\Mailgun($config->mailgun->key, new \Http\Adapter\Guzzle6\Client());
});

$di->setShared('config', $config);

function logToFile($e){
	global $argv;
	ini_set('memory_limit', '384M');
	ignore_user_abort(true);
	set_time_limit(30);
	file_put_contents(__DIR__ . '/../clilogs/' .  microtime(true) . mt_rand(0, 1e4) . '.txt', print_r([
		'args'		=> $argv
	] + $e, true));
}


set_error_handler(function($errno, $errstr, $errfile = '', $errline = ''){
	logToFile([
		'errno'		=> $errno,
		'errstr'	=> $errstr,
		'errfile'	=> $errfile,
		'errline'	=> $errline
	]);
});
set_exception_handler(function($e){
	logToFile([
		'eh'		=> true,
		'code'		=> $e->getCode(),
		'message'	=> $e->getMessage(),
		'stack'		=> [ $e->getFile(), $e->getLine(), $e->getTrace() ]
	]);
});
register_shutdown_function(function(){
	if (http_response_code() === 500 && !is_null($e = error_get_last())) {
		logToFile([
			'error'	=> $e
		]);
	}
});

/*Phalcon\Mvc\Model::setup([
	'notNullValidations' => false
]);*/
