<?php

namespace App\Extensions\UserStorage;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

/**
 * @author Martin Šifra <me@martinsifra.cz>
 */
class GuestStorage implements IUserStorage
{

	/**
	 * @var SessionSection
	 */
	private $section;
	
	/**
	 * @var RoleFacade
	 */
	private $roles;

	public function __construct(Session $session, RoleFacade $roles)
	{
		$this->section = $session->getSection(get_class($this));
		$this->roles = $roles;
	}

	public function getIdentity()
	{
		if (!($this->section->identity instanceof User)) {
			$this->setDefault();
		}

		return $this->section->identity;
	}

	public function getLogoutReason()
	{
		return NULL;
	}

	public function isAuthenticated()
	{
		return FALSE;
	}

	public function setAuthenticated($state)
	{
		return $this;
	}

	public function setExpiration($time, $flags = 0)
	{
		return $this;
	}

	public function setIdentity(IIdentity $identity = NULL)
	{
		return $this;
	}

	public function setDefault()
	{
		$user = new User();
		$role = $this->roles->findByName(Role::GUEST);
		$user->addRole($role);
		$this->section->identity = $user;
		return $this;
	}

}
