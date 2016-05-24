<?php

namespace App\Extensions\FilesManager\DI;

use Nette\DI\CompilerExtension;

class TodoQueueExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('todoQueue'))
				->setClass('App\Extensions\TodoQueue')
				->setInject(TRUE);
	}

}
