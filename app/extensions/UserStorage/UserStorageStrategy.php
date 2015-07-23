<?php

namespace App\Extensions\UserStorage;

use Majkl578\NetteAddons\Doctrine2Identity\Http\UserStorage;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

/**
 * @author Martin Šifra <me@martinsifra.cz>
 */
class UserStorageStrategy extends Object implements IUserStorage
{

	/**
	 * @var UserStorage
	 */
	private $userStorage;

	/**
	 * @var GuestStorage
	 */
	private $guestStorage;

	public function getIdentity()
	{
		if ($this->userStorage->isAuthenticated()) {
			return $this->userStorage->getIdentity();
		} else {
			return $this->guestStorage->getIdentity();
		}
	}

	public function setIdentity(IIdentity $identity = NULL)
	{
		$this->userStorage->setIdentity($identity);
	}

	public function getLogoutReason()
	{
		return $this->userStorage->getLogoutReason();
	}

	public function isAuthenticated()
	{
		return $this->userStorage->isAuthenticated();
	}

	public function setAuthenticated($state)
	{
		$this->userStorage->setAuthenticated($state);
	}

	public function setExpiration($time, $flags = 0)
	{
		$this->userStorage->setExpiration($time, $flags);
	}

	public function setUser(IUserStorage $storage)
	{
		$this->userStorage = $storage;
		return $this;
	}

	public function setGuest(IUserStorage $storage)
	{
		$this->guestStorage = $storage;
		return $this;
	}

}
