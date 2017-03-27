<?php

$purge = [
	'temp/deployment',
];

$before = [];

$after = [];
if (!isset($allowInstall) || $allowInstall) {
	$after[] = $domain . '/install?printHtml=0';
}

if (!isset($allowDeleteCache) || $allowDeleteCache) {
	$purge[] = 'temp/cache';
	$purge[] = 'temp/install';
}
if (!isset($allowDeleteTmp) || $allowDeleteTmp) {
	$purge[] = 'tmp/';
}

return [
	'my site' => [
		'remote' => 'ftp://' . $username . ':' . $password . '@' . $server,
		'passivemode' => TRUE,
		'local' => '..',
		'test' => FALSE,
		'ignore' => '
			.git*
			!.gitignore
			!.gitattributes
			!.gitkeep
			.composer*
			composer.lock
			project.pp[jx]
			/.idea
			/nbproject
			/deployment
			/doc
			/files
			/backup
			log/*
			!log/.htaccess
			temp/*
			!temp/.htaccess
			tests/
			bin/
			www/webtemp/*
			!www/webtemp/.htaccess
			www/foto/*
			!www/foto/original/default.png
			www/adminer/database-log.sql
			*.local*.neon
			*.server*.neon
			/app/config/settings.local*
			/app/config/deployment.*
			/vendor/dg/ftp-deployment
			/vendor/nette/tester
			/vendor/kdyby/tester-extras
			/vendor/mockery/mockery
			/vendor/bin
		',
		'allowdelete' => TRUE,
		'before' => $before,
		'after' => $after,
		'purge' => $purge,
		'preprocess' => FALSE,
	],
	'tempdir' => __DIR__ . '/temp',
	'colors' => TRUE,
];
