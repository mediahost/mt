<?php

namespace App\Extensions\Installer\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use stdClass;

class InstallerExtension extends CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = [
		'lock' => TRUE,
		'doctrine' => FALSE,
		'adminer' => FALSE,
		'composer' => FALSE,
		'initUsers' => [],
		'appDir' => "%appDir%",
		'wwwDir' => "%wwwDir%",
		'tempDir' => "%tempDir%",
		'installDir' => "%tempDir%/install",
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('installer'))
				->setClass('App\Extensions\Installer')
				->addSetup('setPathes', [$config['appDir'], $config['wwwDir'], $config['tempDir'], $config['installDir']])
				->addSetup('setLock', $this->filterArgs($config['lock']))
				->addSetup('setInstallDoctrine', $this->filterArgs($config['doctrine']))
				->addSetup('setInstallAdminer', $this->filterArgs($config['adminer']))
				->addSetup('setInstallComposer', $this->filterArgs($config['composer']))
				->addSetup('setInitUsers', $this->filterArgs($config['initUsers']))
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('model'))
				->setClass('App\Extensions\Installer\Model\InstallerModel')
				->setInject(TRUE);
	}

	/**
	 * @param string|stdClass $statement
	 * @return Statement[]
	 */
	private function filterArgs($statement)
	{
		return [is_string($statement) ? new Statement($statement) : $statement];
	}

}
