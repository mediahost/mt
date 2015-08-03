<?php

namespace App\Listeners;

use App\Model\Facade\UserFacade;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Nette\Security\IUserStorage;
use Nette\Security\User;

class LoggedListener extends Object implements Subscriber
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var IUserStorage @inject */
	public $userStorage;
	
	public function getSubscribedEvents()
	{
		return array(
			'Nette\Security\User::onLoggedIn' => 'userLoggedIn',
			'Nette\Security\User::onLoggedOut' => 'userLoggedOut',
		);
	}

	/**
	 * @param User $identity
	 */
	public function userLoggedIn(User $user)
	{
		$this->userStorage->fromGuestToUser();
	}

	public function userLoggedOut(User $user)
	{
		
	}

}
