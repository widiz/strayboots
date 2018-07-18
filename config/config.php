<?php
$configFn = 'production';
switch (defined('SBENV') ? SBENV : $_SERVER['SERVER_NAME']) {
	case 'go.strayboots.com': break;
	case 'staging.strayboots.com':
	case 'staging':
		$configFn = 'staging';
		break;
	case 'in.strayboots.com':
	case 'india':
		$configFn = 'india';
		break;
	case 'eu.strayboots.com':
	case 'eu':
	case 'europe':
	case 'portugal':
		$configFn = 'europe';
		break;
	default:
}
if (!defined('SBENV'))
	define('SBENV', $configFn);
return require __DIR__ . '/config.' . $configFn . '.php';