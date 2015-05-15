<?php

namespace App\Listeners;

use App\Extensions\Installer;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class InstallerListener extends Object implements Subscriber
{

	public function getSubscribedEvents()
	{
		return [
			'App\Extensions\Installer::onSuccessInstall' => 'successInstall',
			'App\Extensions\Installer::onLockedInstall' => 'lockedInstall',
		];
	}

	public function successInstall(Installer $installer, $type)
	{
		if (Debugger::isEnabled()) {
			Debugger::log($type . ' was installed', 'install');
		}
	}

	public function lockedInstall(Installer $installer, $type)
	{
		if (Debugger::isEnabled()) {
			Debugger::log($type . ' wasn\'t installed - LOCKED', 'install');
		}
	}

}
