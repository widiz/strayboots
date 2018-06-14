<?php

$router = $di->get("router");

foreach ($application->getModules() as $key => $module) {
	$namespace = str_replace('Module', 'Controllers', $module['className']);
	$mod = $key == 'frontend' ? '' : ($key . '/');
	$router->add('/'.$mod.':params', [
		'namespace'	=> $namespace,
		'module'	=> $key,
		'controller'=> 'index',
		'action'	=> 'index',
		'params'	=> 1
	])->setName($key);
	$router->add('/'.$mod.':controller/:params', [
		'namespace'	=> $namespace,
		'module'	=> $key,
		'controller'=> 1,
		'action'	=> 'index',
		'params'	=> 2
	]);
	$router->add('/'.$mod.':controller/:action/:params', [
		'namespace'	=> $namespace,
		'module'	=> $key,
		'controller'=> 1,
		'action'	=> 2,
		'params'	=> 3
	]);
	$router->add('/'.$mod.':controller/:int', [
		'namespace'	=> $namespace,
		'module'	=> $key,
		'controller'=> 1,
		'action'	=> 'index',
		'params'	=> 2
	]);
}
/*
$router->notFound([
	'namespace'	=> "Play\Frontend\Controllers",
	'module'	=> 'frontend',
	'controller'=> 'error',
	'action'	=> 'e404'
]);*/
//$di->set("router", $router);
