<?php

namespace App\Extensions\GoogleTranslate\DI;

use Nette\DI\CompilerExtension;

class GoogleTranslateExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'apiKey' => NULL,
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('googleTranslate'))
				->setClass('App\Extensions\GoogleTranslate')
				->addSetup('setAuth', [$config['apiKey']])
				->setInject(TRUE);
	}

}
