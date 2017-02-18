<?php

$deny = [
	'85.248.59.106',
];
if (in_array($_SERVER['REMOTE_ADDR'], $deny)) {
	exit;
}

$isMaintenance = FALSE;
$ips = [
	'127.0.0.1',
	'37.221.251.254', // SnS
	'37.221.251.252', // SnS
];
if ($isMaintenance && !in_array($_SERVER['REMOTE_ADDR'], $ips)) {
	require '.maintenance.php';
}

$container = require __DIR__ . '/../app/bootstrap.php';

$container->getService('application')->run();
