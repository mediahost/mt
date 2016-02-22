<?php

namespace App\Extensions\UserStorage;

use App\Model\Entity\Role;
use App\Model\Entity\Stock;
use App\Model\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;
use SplStack;

/**
 * @property SplStack $visits
 */
class GuestStorage extends Object implements IUserStorage
{

	/** @var EntityManager */
	private $em;

	/** @var SessionSection */
	private $section;

	/** @var EntityRepository */
	private $roleRepo;

	public function __construct(Session $session, EntityManager $em)
	{
		$this->section = $session->getSection(get_class($this));
		$this->em = $em;
		$this->roleRepo = $this->em->getRepository(Role::getClassName());
	}

	public function getIdentity()
	{
		if (!($this->section->identity instanceof User)) {
			$this->setDefault();
		}

		return $this->section->identity;
	}

	public function getVisits()
	{
		if (!($this->section->identity instanceof User)) {
			$this->setDefault();
		}

		return $this->section->visits;
	}

	public function setVisits($array)
	{
		$this->section->visits = $array;
	}
	
	public function addVisit(Stock $stock)
	{
		$this->section->visits = [$stock->id => new DateTime()] + $this->section->visits;
		return $this;
	}
	
	public function deleteVisit(Stock $stock)
	{
		unset($this->section->visits[$stock->id]);
		return $this;
	}
	
	public function setBasketId($id)
	{
		$this->section->basketId = $id;
	}
	
	public function getBasketId()
	{
		return $this->section->basketId;
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
		$role = $this->roleRepo->findOneByName(Role::GUEST);
		$user->addRole($role);
		$this->section->identity = $user;
		$this->section->visits = [];
		$this->section->basketId = NULL;
		return $this;
	}

}
