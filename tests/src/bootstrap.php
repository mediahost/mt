<?php

$rootDir = __DIR__ . '/..';

require $rootDir . '/../vendor/autoload.php';

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

// Global from is %wwwDir% taken and is not set when php-cgi (run from cmd)
$_SERVER['SCRIPT_FILENAME'] = $rootDir . '/../www/index.php';

// Directory for lock files
define('LOCK_DIR', $rootDir . '/tmp');

// Temp directory
//\Kdyby\TesterExtras\Bootstrap::setup($rootDir);
define('TEMP_DIR', $rootDir . '/tmp');

$configurator = new Nette\Configurator;

$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(['appDir' => $rootDir . '/../app']); // řeší problém s nefunkčním $this->em->getMetadataFactory()->getAllMetadata()
$configurator->addParameters(['wwwDir' => $rootDir . '/../www']); // potřebné pro správné nastavení webloaderu

$configurator->addConfig($rootDir . '/../app/config/config.neon');
$configurator->addConfig($rootDir . '/../app/config/test/config.test.local.neon');

$configurator->createRobotLoader()
		->addDirectory($rootDir . '/../app')
		->addDirectory($rootDir . '/../tests')
		->addDirectory($rootDir . '/../vendor/others')
		->register();

if (!getenv(\Tester\Environment::RUNNER)) {
	$configurator->setDebugMode(TRUE);
	$configurator->enableDebugger($rootDir . '/../log/');
}

Drahak\Restful\DI\RestfulExtension::install($configurator);

return $configurator->createContainer();
