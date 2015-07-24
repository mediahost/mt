<?php

namespace Test\Model\Facade;

use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\PageDesignSettings;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade Setters
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeSettersTest extends UserFacade
{

	public function testAddRole()
	{
		$role = $this->roleFacade->findByName(Role::DEALER);

		$user = $this->userRepo->find(self::ID_NEW);

		Assert::count(1, $user->roles);
		$this->userFacade->addRole($user, $role);
		Assert::count(2, $user->roles);

		$user->removeRole($role);
		$this->userFacade->addRole($user, [Role::DEALER, Role::ADMIN]);
		Assert::count(3, $user->roles);
	}

	public function testAppendSettings()
	{
		$newConfigSettings = new PageConfigSettings();
		$this->userFacade->appendSettings(self::ID_NEW, $newConfigSettings);

		$user1 = $this->userRepo->find(self::ID_NEW);
		/* @var $user1 User */
		Assert::null($user1->pageConfigSettings->language);

		$rewriteConfigSettings = new PageConfigSettings();
		$rewriteConfigSettings->language = 'de';
		$this->userFacade->appendSettings(self::ID_NEW, $rewriteConfigSettings);

		$user2 = $this->userRepo->find(self::ID_NEW);
		/* @var $user2 User */
		Assert::same('de', $user2->pageConfigSettings->language);
	}

}

$test = new UserFacadeSettersTest($container);
$test->run();
