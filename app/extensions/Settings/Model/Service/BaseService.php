<?php

namespace App\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Entity\User;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 * @property-read User $user
 */
abstract class BaseService extends Object
{

	/** @var DefaultSettingsStorage @inject */
	public $defaultStorage;

	/** @var EntityManager @inject */
	public $em;

	/** @return User|NULL */
	public function getUser()
	{
		if ($this->defaultStorage->loggedIn) {
			return $this->defaultStorage->user;
		}
		return NULL;
	}

	public function saveUser()
	{
		if ($this->user instanceof User && $this->user->id) {
			if ($this->user->pageConfigSettings) {
				$this->em->persist($this->user->pageConfigSettings);
			}
			if ($this->user->pageDesignSettings) {
				$this->em->persist($this->user->pageDesignSettings);
			}
			$this->em->persist($this->user);
			$this->em->flush();
			return $this->user;
		} else {
			throw new BaseServiceException('User for saving must already exists');
		}
	}

}

class BaseServiceException extends Exception
{
	
}
