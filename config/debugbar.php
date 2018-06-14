<?php

return array(
	'enabled' => isset($_COOKIE['_sb_']),

	'storage' => array(
		'enabled' => true,
		'driver' => 'file', // file, mongodb
		'path' => __DIR__ . '/../debug', // For file driver
	),

    'white_lists'=>array(
        //        '127.0.0.1'
    ),


    'allow_routes'=>array(

    ),

    'deny_routes'=>array(

    ),

	'include_vendors' => true,

	'capture_ajax' => true,

	/*
	 |--------------------------------------------------------------------------
	 | DataCollectors
	 |--------------------------------------------------------------------------
	 |
	 | Enable/disable DataCollectors
	 |
	 */

	'collectors' => array(
		'memory'          => true,  // Memory usage
		'exceptions'      => true,  // Exception displayer
		'default_request' => false, // Regular or special Symfony request logger
		'phalcon_request' => true,  // Only one can be enabled..
		'session'         => false,  // Display session data in a separate tab
		'config'          => false, // Display the config service content
		'route'           => false, // Display the current route infomations.
		'log'             => false, // Display messages of the log service sent.
		'db'              => true, // Display the sql statments infomations. Just for Phalcon ORM. 'db' and 'doctrine', you only can choose one!
		'doctrine'        => false, // Display the sql statments infomations. Just for Doctrine ORM.'db' and 'doctrine', you only can choose one!
		'view'            => false, // Display the rendered views infomations.
		'cache'           => true, // Display the cache operation infomations.
		'mail'            => false,
	),

	/*
	 |--------------------------------------------------------------------------
	 | Extra options
	 |--------------------------------------------------------------------------
	 |
	 | Configure some DataCollectors
	 |
	 */

	'options' => array(
		'exceptions'=>array(
			'chain'=>true,
		),
		'db' => array(
			'with_params'       => false,   // Render SQL with the parameters substituted
			'backtrace' => false,  // EXPERIMENTAL: Use a backtrace to find the origin of the query in your files.
			'explain'   => false,  // EXPLAIN select statement
			'error_mode'=> \PDO::ERRMODE_EXCEPTION, // \PDO::ERRMODE_SILENT , \PDO::ERRMODE_WARNING, \PDO::ERRMODE_EXCEPTION
			'show_conn'=>false, // IF show connection info
		),
		'mail' => array(
			'full_log' => false
		),
		'views' => array(
			'data' => false,    //Note: Can slow down the application, because the data can be quite large..
		),
		'config'=> array(
			'protect'=>array(
				'database.password', // 在debugbar中以******显示的敏感内容, 最多支持使用两次.号
			),
		),
		'log'=>array(
			'aggregate'=>false,  // Set to True will aggregate logs to MessagesCollector
			'formatter'=>'line', // line , syslog or a class implenment \Phalcon\Logger\FormatterInterface
		),
		'cache'=>array(
			'mode'=>1, // 0: only count and aggregate summary to MessagesCollector; 1: show detail on CacheCollector
		),
	),

	'inject' => true,

);
