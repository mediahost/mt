<?php

namespace App\Extensions\Foto\DI;

use App\Model\Entity\Image;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class FotoExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'folder' => '%wwwDir%/foto',
		'originalFolderName' => 'original',
		'defaultImage' => NULL, // rewriten in loadConfiguration()
		'defaultFormat' => 'png',
	];

	public function loadConfiguration()
	{
		$this->defaults['defaultImage'] = Image::DEFAULT_IMAGE;
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
