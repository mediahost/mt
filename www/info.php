<?php

$ips = [
	'127.0.0.1',
	'37.221.251.252',
	'213.81.220.67',
];

if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
	phpinfo();
}
