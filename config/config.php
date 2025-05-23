<?php
$configFn = 'production';
switch (defined('SBENV') ? SBENV : ($_SERVER['SERVER_NAME'] ?? '')) {
	case 'staging.strayboots.com':
	case 'staging':
		$configFn = 'staging';
		break;
	case 'in.strayboots.com':
	case 'india':
		$configFn = 'india';
		break;
	case 'eu.strayboots.com':
	case 'pt.strayboots.com':
	case 'portugal':
	case 'europe':
	case 'eu':
		$configFn = 'europe';
		break;
	default:
}
if (!defined('SBENV'))
	define('SBENV', $configFn);
return require __DIR__ . '/config.' . $configFn . '.php';