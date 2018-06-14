<?php
/**
 * Services are globally registered in this file
 *
 * @var \Phalcon\Config $config
 */

use Phalcon\Dispatcher;

require __DIR__ . '/../vendor/autoload.php';

/**
 * The FactoryDefault Dependency Injector automatically registers the right services to provide a full stack framework
 */
$di = new Phalcon\Di\FactoryDefault();

/**
 * Registering a router
 */
$di->setShared('router', function(){
	$router = new Phalcon\Mvc\Router();

	$router->setDefaultModule('frontend');
	$router->setDefaultNamespace('Play\Frontend\Controllers');

	return $router;
});

/**
 * The URL component is used to generate all kinds of URLs in the application
 */
$di->setShared('url', function() use ($config) {
	$url = new Phalcon\Mvc\Url();
	$url->setBaseUri($config->application->baseUri);
	return $url;
});

$di->setShared('assets', function() use ($config) {
	$assets = new Phalcon\Assets\Manager([
		'sourceBasePath' => $config->application->publicDir
	]);
	return $assets;
});

$di->setShared('escaper', function() use ($config) {
	return new Phalcon\Escaper();
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function() use ($config) {
	return new Phalcon\Db\Adapter\Pdo\Mysql($config->database->toArray());
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function(){
	return new Phalcon\Mvc\Model\Metadata\Memcache([
		'prefix' => SB_PREFIX
		//'metaDataDir' => $config->application->metaDataDir
	]);
});

/**
 * Starts the session the first time some component requests the session service
 */
$di->setShared('session', function(){
	//ini_set('session.cookie_domain', '.strayboots.com');
	/*$session = new Phalcon\Session\Adapter\Redis([
		'uniqueId' => 'playfront',
		'host' => '127.0.0.1',
		'port' => 6379,
		'persistent' => false,
		'lifetime' => 86400,
		'prefix' => 'pf_'
	]);*/
	$session = new Phalcon\Session\Adapter\Files();
	$session->start();
	return $session;
});

/**
 * Register the session flash service with the Twitter Bootstrap classes
 */
$di->set('flash', function(){
	return new Phalcon\Flash\Session(/*[
		'error'   => 'alert alert-danger',
		'success' => 'alert alert-success',
		'notice'  => 'alert alert-info',
		'warning' => 'alert alert-warning'
	]*/);
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

$di->setShared('logger', function(){
	return new Shay12tg\Phalcon\Logger(__DIR__ . '/../logs/log.txt', "shay12tg@gmail.com");
});

$di->setShared('crypt', function(){
	$crypt = new \Phalcon\Crypt();
	$crypt->setCipher('aes-256-cbc');
	$crypt->setKey('S9mg_uU2*JF.S4f9');
	return $crypt;
});

$di->setShared('cookies', function(){
	$cookies = new Phalcon\Http\Response\Cookies();
	$cookies->useEncryption(true);
	return $cookies;
});

$di->setShared('mailer', function() use ($config){
	return new \Mailgun\Mailgun($config->mailgun->key, new \Http\Adapter\Guzzle6\Client());
});

$di->setShared('config', $config);

/**
* Set the default namespace for dispatcher
*/

function logToFile($e, $h = false){
	ini_set('memory_limit', '1G');
	ignore_user_abort(true);
	set_time_limit(30);
	$fnx = (isset($_COOKIE['_sb_']) ? 'dev-' : '') . microtime(true) . mt_rand(0, 1e4);
	file_put_contents(__DIR__ . '/../logs/' . $fnx . '.txt', print_r([
		'_GET'		=> $_GET,
		'_POST'		=> $_POST,
		'URI'		=> $_SERVER['REQUEST_URI']
	] + $e, true));
	if ($h)
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
}

$di->set('dispatcher', function() use ($config){
	$eventsManager = new Phalcon\Events\Manager();
	$eventsManager->attach("dispatch:beforeExecuteRoute", function($event, $dispatcher) {
		$c = $dispatcher->getControllerName();
		$a = $dispatcher->getActionName();
		$_c = strtolower($c);
		$_a = strtolower($a);
		if ($c != $_c || $a != $_a) {
			$dispatcher->forward([
				//'namespace'	=> $dispatcher->getModuleName(),
				//'module'	=> $dispatcher->getNamespaceName(),
				'controller'=> $_c,
				'action'	=> $_a
			]);
			return false;
		}
	});
	$eventsManager->attach("dispatch:beforeException", function($event, $dispatcher, $exception) use ($config) {
		$code = $exception->getCode();
		if (in_array($code, [Dispatcher::EXCEPTION_HANDLER_NOT_FOUND, Dispatcher::EXCEPTION_ACTION_NOT_FOUND])) {
			$dispatcher->forward([
				'controller'	=> 'error',
				'action'		=> 'e404'
			]);
		} else if ($exception instanceof \Exception && $config->application->debug) {
			ini_set('memory_limit', '1G');
			if (!SB_PRODUCTION && $code == 23000) {
				var_dump([
					'code'		=> $code,
					'message'	=> $exception->getMessage(),
					'stack'		=> [ $exception->getFile(), $exception->getLine() ]
				]);die;
			}
			var_dump([
				'code'		=> $code,
				'message'	=> $exception->getMessage(),
				'stack'		=> [ $exception->getFile(), $exception->getLine(), $exception->getTraceAsString() ]
			]);die;
		} else {
			ini_set('memory_limit', '512M');
			logToFile([
				'code'		=> $code,
				'message'	=> $exception->getMessage(),
				'stack'		=> [ $exception->getFile(), $exception->getLine(), $exception->getTraceAsString() ]
			]);
			$dispatcher->forward([
				'controller'	=> 'error',
				'action'		=> 'e500'
			]);
		}
		return false;
	});
	$dispatcher = new Phalcon\Mvc\Dispatcher();
	$dispatcher->setDefaultNamespace('Play\Frontend\Controllers');
	$dispatcher->setEventsManager($eventsManager);
	return $dispatcher;
}, true);
/*
$di->setShared('paypal', function() use ($config){

	$mode = $config->application->debug ? 'sandbox' : 'live';

	$config = [
		'mode' => $mode,
		'log.LogEnabled' => true,
		'log.FileName' => __DIR__ . '/../payPal.log',
		'log.LogLevel' => $config->application->debug ? 'DEBUG' : 'INFO', 
		'cache.enabled' => true,
		// 'http.CURLOPT_CONNECTTIMEOUT' => 30
		// 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
		//'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
	];

	$apiContext = new \PayPal\Service\PayPalAPIInterfaceServiceService($config);
*/
	/*$apiContext = new PayPal\Rest\ApiContext(new PayPal\Auth\OAuthTokenCredential(
		$config->paypal->{$mode}->clientId,
		$config->paypal->{$mode}->clientSecret
	));

	$apiContext->setConfig($config);

	// Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
	// To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
	// $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');*/
/*
	return $apiContext;
});*/

set_error_handler(function($errno, $errstr, $errfile = '', $errline = ''){
	ini_set('memory_limit', '512M');
	logToFile([
		'errno'		=> $errno,
		'errstr'	=> $errstr,
		'errfile'	=> $errfile,
		'errline'	=> $errline
	]/*, true*/);
});
set_exception_handler(function($e){
	ini_set('memory_limit', '512M');
	logToFile([
		'eh'		=> true,
		'code'		=> $e->getCode(),
		'message'	=> $e->getMessage(),
		'stack'		=> [ $e->getFile(), $e->getLine(), $e->getTraceAsString() ]
	], true);
});
register_shutdown_function(function(){
	if (http_response_code() === 500 && !is_null($e = error_get_last())) {
		ini_set('memory_limit', '512M');
		logToFile([
			'error'	=> $e
		], true);
	}
});

/*Phalcon\Mvc\Model::setup([
	'notNullValidations' => false,
	//'castOnHydrate' => true
]);*/
