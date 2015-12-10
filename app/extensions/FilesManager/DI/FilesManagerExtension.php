<?php

namespace App\Extensions\FilesManager\DI;

use Nette\DI\CompilerExtension;

class FilesManagerExtension extends CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = [
		'folder' => 'files',
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('filesManager'))
				->setClass('App\Extensions\FilesManager')
				->addSetup('setRootFolder', [$config['folder']])
				->setInject(TRUE);
	}

}
