<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\Traits\UserFacadeCreates;
use App\Model\Facade\Traits\UserFacadeDelete;
use App\Model\Facade\Traits\UserFacadeFinders;
use App\Model\Facade\Traits\UserFacadeGetters;
use App\Model\Facade\Traits\UserFacadeRecovery;
use App\Model\Facade\Traits\UserFacadeSetters;
use App\Model\Repository\RegistrationRepository;
use App\Model\Repository\UserRepository;
use Kdyby\Doctrine\EntityDao;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class UserFacade extends Object
{

	use UserFacadeCreates;
	use UserFacadeDelete;
	use UserFacadeFinders;
	use UserFacadeGetters;
	use UserFacadeSetters;
	use UserFacadeRecovery;

	/** @var EntityManager @inject */
	public $em;

	/** @var UserRepository */
	private $userRepo;

	/** @var RegistrationRepository */
	private $registrationRepo;

	/** @var EntityDao */
	private $roleDao;
	
	/** @var SettingsStorage */
	private $settings;

	public function __construct(EntityManager $em, SettingsStorage $settings)
	{
		$this->em = $em;
		$this->userRepo = $this->em->getRepository(User::getClassName());
		$this->registrationRepo = $this->em->getRepository(Registration::getClassName());
		$this->roleDao = $this->em->getDao(Role::getClassName());
		$this->settings = $settings;
	}

	public function isUnique($mail)
	{
		return $this->findByMail($mail) === NULL;
	}

}
