<?php
define('SB_PRODUCTION', true);
define('SB_PREFIX', 'sbin:');
define('FB_PREFIX', 'india/');
return new \Phalcon\Config([
	'prefix' => 'india',
	'altLang' => ['he', 'pt', 'ar'],
	'defLang' => 0,
	'fullUri' => 'https://in.strayboots.com',
	'database' => [
		'adapter'	=> 'Mysql',
		'host'		=> 'mysql.go.strayboots.com',
		'username'	=> 'india',
		'password'	=> '26YGF2CRkueFuBFL',
		'dbname'	=> 'india',
		'timezone'	=> 'America/New_York',
		'charset'	=> 'utf8mb4'
	],
	'application' => [
		'debug' => isset($_COOKIE['_sb_']),
		//'controllersDir'	=> APP_PATH . '/apps/admin/controllers/',
		//'viewsDir'		=> APP_PATH . '/apps/admin/views/',
		'modelsDir'			=> APP_PATH . '/apps/common/models/',
		'tasksDir'			=> APP_PATH . '/apps/tasks/',
		'publicDir'			=> APP_PATH . '/public/',
		'tmpDir'			=> APP_PATH . '/apps/common/tmp/in/',
		'frontUploadsDir'	=> [
			'path'	=> APP_PATH . '/apps/frontend/uploads/in/',
			'uri'	=> '/uploads/in/'
		],
		'clientsUploadsDir'	=> [
			'path'	=> APP_PATH . '/apps/clients/uploads/in/',
			'uri'	=> '/cu/in/'
		],
		'suppliersUploadsDir'	=> [
			'path'	=> APP_PATH . '/apps/suppliers/uploads/in/',
			'uri'	=> '/su/in/'
		],
		'baseUri'			=> '/'
	],
	'hunt' => [
		'surveyAfterQuestion'	=> true
	],
	'bitly' => [
		'login'		=> 'newsb',
		'APIKey'	=> 'R_5b7d84518b8148f99b3c929484329156'
	],
	'firebaseServerKey' => 'nNJwjhGhRgg83fD6tAhyrGD70Mqcv9bVLNYEOgxV',
	'firebase' => [
		'apiKey'		=> "AIzaSyCPnP5V2TLAzJCAm8WBusHL-YfkBR-Av0s",
		'authDomain'	=> "project-309384948789582914.firebaseapp.com",
		'databaseURL'	=> "https://project-309384948789582914.firebaseio.com",
		'storageBucket'	=> "project-309384948789582914.appspot.com"
	],
	'googleapis'	=> [
		'maps'	=> 'AIzaSyBrR_FkVuGSjxFEbcLIVc_OIZg6VDhnpzM'
	],
	'paypal'	=> [
		'account' => 'billing@strayboots.com',
		'sandbox' => [
			// go-sb@strayboots.com 123123Aa
			'ep' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			'clientId' => 'ARUuBwndszj32RyJzNCqGlHltYjQgRr5d6fAX17PfqgVJ77bi0pi1tCIiXLRj3ucYODYORv4IeN8OMnd',
			'clientSecret' => 'EM4rI_wuUj9nf0mbMkRI7saFaVRdXt7x3U0nB-En5Ats1LaTgonNxJExoP_JgYkGNLNbheLM3pNyykUv'
		],
		'live' => [
			'ep' => 'https://www.paypal.com/cgi-bin/webscr',
			'clientId' => 'AYkhQZv0_phonFrQm2m0Aa2CNiBIwfkFf2x3dbMgmpDEWZ27Xlv7Zuw85RxUvWZCXjlMnfI45WLj1q5h',
			'clientSecret' => 'EOhCgJc7yMYkvh3GLgFE6e_PZdd7CpCoYzdp65Cim_gOUgYTXadLG4WCL4qBMNFxhzUr3ZrCRQry778y'
		]
	],
	'mailgun' => [
		'key'		=> 'key-4sxqu4ogalmb8aw8an095728gighral4',
		'domain'	=> 'mailer.strayboots.com',
		'from'		=> 'Strayboots <events@strayboots.com>'
	]
]);