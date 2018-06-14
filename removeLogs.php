#!/usr/bin/php
<?php

$dir = __DIR__ . '/logs/';
$dir = __DIR__ . '/clilogs/';

$files = glob($dir . '*.txt');

if (!file_exists($backupDir = $dir . 'backup/'))
	mkdir($backupDir);

$lookFor = 'Querying...done

Execution done';

$lookFor2 = 'Querying...done
Processing data...done

Execution done';

$removed = 0;
foreach ($files as $file) {
	$log = file_get_contents($file);
	if (strpos($log, $lookFor) !== false || strpos($log, $lookFor2) !== false) {
		//unlink($file);
		rename($file, str_replace($dir, $backupDir, $file));
		$removed++;
	}
}

echo 'Removed ' . $removed . '/' . count($files) . PHP_EOL;