<?php

namespace App\Extensions\ImportFromMT1\DI;

use Nette\DI\CompilerExtension;

class ImportFromMT1Extension extends CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = [
		'db' => 'shopbox.mobilnetelefony.sk',
		'mapping' => [
			'groups' => [],
		],
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('importFromMT1'))
				->setClass('App\Extensions\ImportFromMT1')
				->addSetup('setDbName', [$config['db']])
				->addSetup('setMapping', [$config['mapping']])
				->setInject(TRUE);
	}

}
