<?php

namespace App\Listeners\Model\Facade;

use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class PohodaListener extends Object implements Subscriber
{

	public function getSubscribedEvents()
	{
		return [
			'App\Model\Facade\PohodaFacade::onRecieveXml' => 'recieveXml',
		];
	}

	public function recieveXml()
	{
//		Debugger::log('recieve XML', 'pohoda');
	}

}
