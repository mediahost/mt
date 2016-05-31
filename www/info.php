<?php

$ips = [
	'127.0.0.1',
	'37.221.251.254', // SnS
];

if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
	phpinfo();
}
