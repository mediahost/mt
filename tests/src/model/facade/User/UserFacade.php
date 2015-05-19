<?php

namespace Test\Model\Facade;

use App\Model\Entity\Company;
use App\Model\Entity\CompanyPermission;
use App\Model\Entity\Facebook;
use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\PageDesignSettings;
use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use App\Model\Repository\CompanyPermissionRepository;
use App\Model\Repository\RegistrationRepository;
use App\Model\Repository\UserRepository;
use Kdyby\Doctrine\EntityDao;
use Nette\DI\Container;

abstract class UserFacade extends BaseFacade
{

	const ID_NEW = 3;
	const MAIL = 'user.mail@domain.com';
	const PASSWORD = 'password123456';
	const EXPIRED_TOKEN = 'expiredToken';
	const VALID_TOKEN = 'validToken';
	const TWITTER_ID = 'tw123456789';
	const FACEBOOK_ID = 'fb123456789';

	/** @var UserRepository */
	protected $userRepo;

	/** @var EntityDao */
	protected $roleDao;

	/** @var RegistrationRepository */
	protected $registrationRepo;

	/** @var EntityDao */
	protected $facebookDao;

	/** @var EntityDao */
	protected $twitterDao;

	/** @var EntityDao */
	protected $pageConfigSettingsDao;

	/** @var EntityDao */
	protected $pageDesignSettingsDao;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->userRepo = $this->em->getDao(User::getClassName());
		$this->roleDao = $this->em->getDao(Role::getClassName());
		$this->registrationRepo = $this->em->getDao(Registration::getClassName());
		$this->facebookDao = $this->em->getDao(Facebook::getClassName());
		$this->twitterDao = $this->em->getDao(Twitter::getClassName());
		$this->pageConfigSettingsDao = $this->em->getDao(PageConfigSettings::getClassName());
		$this->pageDesignSettingsDao = $this->em->getDao(PageDesignSettings::getClassName());
	}

	protected function setUp()
	{
		parent::setUp();
		$this->importDbDataFromFile(__DIR__ . '/sql/users_after_install.sql');
	}

}
