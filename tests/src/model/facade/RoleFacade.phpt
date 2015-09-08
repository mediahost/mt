<?php

namespace Test\Model\Facade;

use App\Model\Entity\Role;
use Kdyby\Doctrine\EntityDao;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: RoleFacade
 *
 * @testCase
 * @phpVersion 5.4
 */
class RoleFacadeTest extends BaseFacade
{

	/** @var EntityDao */
	private $roleDao;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->roleDao = $this->em->getDao(Role::getClassName());
	}

	public function testCreate()
	{
		$role = $this->roleFacade->create(Role::GUEST);
		Assert::type(Role::getClassName(), $role);
		Assert::same(Role::GUEST, $role->name);
		Assert::null($this->roleFacade->create(Role::GUEST));
	}

	public function testFinds()
	{
		$this->createAllRoles();

		$roles = [Role::USER, Role::DEALER, Role::ADMIN];
		$lowers = [1 => Role::GUEST, Role::SIGNED, Role::USER, Role::DEALER];
		Assert::same($lowers, $this->roleFacade->findLowerRoles($roles));
		$lowers[] = Role::ADMIN;
		Assert::same($lowers, $this->roleFacade->findLowerRoles($roles, TRUE));
	}

	public function testIsUnique()
	{
		$this->roleFacade->create(Role::USER);
		$this->roleDao->clear();
		Assert::false($this->roleFacade->isUnique(Role::USER));
		Assert::true($this->roleFacade->isUnique(Role::GUEST));
	}

	private function createAllRoles()
	{
		$this->roleFacade->create(Role::GUEST);
		$this->roleFacade->create(Role::SIGNED);
		$this->roleFacade->create(Role::USER);
		$this->roleFacade->create(Role::DEALER);
		$this->roleFacade->create(Role::ADMIN);
		$this->roleFacade->create(Role::SUPERADMIN);
		$this->roleDao->clear();
	}

}

$test = new RoleFacadeTest($container);
$test->run();
