<?php

$ips = [
	'127.0.0.1',
	'37.221.251.254', // SnS
];
$deny = [
	'85.248.59.106',
];

// Uncomment this line if you must temporarily take down your site for maintenance.
//if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
//	require '.maintenance.php';
//}
if (in_array($_SERVER['REMOTE_ADDR'], $deny)) {
	exit;
}

$container = require __DIR__ . '/../app/bootstrap.php';

$container->getService('application')->run();
