<?php

namespace App\Extensions\Foto\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class FotoExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'folder' => '%wwwDir%/foto',
		'originalFolderName' => 'original',
		'defaultImage' => 'default.png',
		'defaultFormat' => 'png',
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('foto'))
				->setClass('App\Extensions\Foto')
				->addSetup('setFolders', [$config['folder'], $config['originalFolderName']])
				->addSetup('setDefaultImage', [$config['defaultImage'], $config['defaultFormat']])
				->setInject(TRUE);
	}

	/** @param Configurator $configurator */
	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('foto', new FotoExtension());
		};
	}

}
