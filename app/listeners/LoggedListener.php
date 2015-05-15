<?php

namespace App\Listeners;

use App\Extensions\Settings\Model\Storage\GuestSettingsStorage;
use App\Model\Facade\UserFacade;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Nette\Security;

class LoggedListener extends Object implements Subscriber
{

	/** @var GuestSettingsStorage @inject */
	public $guestStorage;

	/** @var UserFacade @inject */
	public $userFacade;

	public function getSubscribedEvents()
	{
		return array(
			'Nette\Security\User::onLoggedIn' => 'userLoggedIn',
			'Nette\Security\User::onLoggedOut' => 'userLoggedOut',
		);
	}

	public function userLoggedIn(Security\User $identity)
	{
		if (!$this->guestStorage->empty) {
			$this->userFacade->appendSettings($identity->id, $this->guestStorage->pageSettings, $this->guestStorage->designSettings);
			$this->guestStorage->wipe();
		}
	}

	public function userLoggedOut(Security\User $user)
	{
		
	}

}
