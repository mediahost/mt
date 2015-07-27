<?php

namespace App\Listeners;

use App\Model\Facade\UserFacade;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Nette\Security;

class LoggedListener extends Object implements Subscriber
{

	/** @var UserFacade @inject */
	public $userFacade;

	public function getSubscribedEvents()
	{
		return array(
			'Nette\Security\User::onLoggedIn' => 'userLoggedIn',
			'Nette\Security\User::onLoggedOut' => 'userLoggedOut',
		);
	}

	/**
	 * @param \Nette\Security\User $identity
	 */
	public function userLoggedIn(Security\User $identity)
	{
		/** @todo Metoda import nad entitou User dostane jako argument předchozího uživatele
		 * (tedy guesta) a z něj překopíruje nějaká data. Jaké řeší ono metoda import().
		 */
	}

	public function userLoggedOut(Security\User $user)
	{
		
	}

}
