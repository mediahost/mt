<?php

namespace Test\Model\Facade;

use App\Model\Entity\Role;
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
		$role = $this->em->getRepository(Role::getClassName())->findOneByName(Role::DEALER);

		$user = $this->userRepo->find(self::ID_NEW);

		Assert::count(1, $user->roles);
		$this->userFacade->addRole($user, $role);
		Assert::count(2, $user->roles);

		$user->removeRole($role);
		$this->userFacade->addRole($user, [Role::DEALER, Role::ADMIN]);
		Assert::count(3, $user->roles);
	}

}

$test = new UserFacadeSettersTest($container);
$test->run();
