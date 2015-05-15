<?php

namespace App\Extensions\ListenersGenericRegistration\DI;

use Nette\DI\CompilerExtension;

/**
 * Generická registrace listenerů
 * http://forum.nette.org/cs/16192-kdyby-events-ako-naviazat-event-na-factory
 */
class EventOnControlCreateCreateExtension extends CompilerExtension
{

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $definitions = $builder->getDefinitions();
		
		foreach ($definitions as $definition) {
			if ($definition->implement && method_exists($definition->implement, 'create')) {
				$definition->addSetup('?->createEvent(?)->dispatch($service);', [
					'@Kdyby\Events\EventManager',
					'Nette\Application\UI\Control::onCreate'
				]);
			}
		}
    }

}
