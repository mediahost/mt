<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode([
	'94.113.177.5', // Petr - Brno
	'37.221.251.252', // Petr - Svetla n.S.
	'147.229.204.31', // Kapco 1
	'213.81.220.67', // Kapco 2
	// TMPs
	'149.62.146.153', // Brno TMP1
	'94.113.216.110', // Brno TMP2
	'94.113.23.178', // Brno TMP3
	'89.102.3.159', // Brno TMP4
//	'27.109.132.1', // Kapco Filipiny
]);

$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
		->addDirectory(__DIR__)
		->addDirectory(__DIR__ . '/../vendor/others')
		->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
