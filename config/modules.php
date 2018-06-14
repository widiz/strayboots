<?php

/**
 * Register application modules
 */
$application->registerModules([
	'frontend' => [
		'className' => 'Play\Frontend\Module',
		'path' => __DIR__ . '/../apps/frontend/Module.php'
	],
	'admin' => [
		'className' => 'Play\Admin\Module',
		'path' => __DIR__ . '/../apps/admin/Module.php'
	],
	'clients' => [
		'className' => 'Play\Clients\Module',
		'path' => __DIR__ . '/../apps/clients/Module.php'
	],
	'suppliers' => [
		'className' => 'Play\Suppliers\Module',
		'path' => __DIR__ . '/../apps/suppliers/Module.php'
	]
]);
