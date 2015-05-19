<?php

namespace Test\Model\Entity;

use App\Model\Entity\Role;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Role entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class RoleTest extends BaseTestCase
{

	const CMP_BIGGER = 1;
	const CMP_EQUAL = 0;
	const CMP_LOWER = -1;
	const R_NAME = 'signed';

	public function testSetAndGet()
	{
		$role = new Role(self::R_NAME);
		Assert::same(self::R_NAME, $role->name);
		Assert::same(self::R_NAME, (string) $role);
	}

	public function testCompare()
	{
		Assert::same(self::CMP_EQUAL, Role::compareRoles(new Role(Role::SUPERADMIN), Role::SUPERADMIN));
		Assert::same(self::CMP_LOWER, Role::compareRoles(new Role(Role::ADMIN), new Role(Role::SUPERADMIN)));
		Assert::same(self::CMP_BIGGER, Role::compareRoles(new Role(Role::SUPERADMIN), new Role(Role::ADMIN)));
		Assert::same(self::CMP_BIGGER, Role::compareRoles(new Role(Role::ADMIN), new Role(Role::DEALER)));
		Assert::same(self::CMP_BIGGER, Role::compareRoles(new Role(Role::DEALER), new Role(Role::USER)));
		Assert::same(self::CMP_BIGGER, Role::compareRoles(new Role(Role::USER), new Role(Role::SIGNED)));
		Assert::same(self::CMP_BIGGER, Role::compareRoles(new Role(Role::SIGNED), new Role(Role::GUEST)));
	}

	public function testMaxRole()
	{
		$roles1 = [
				Role::ADMIN,
				new Role(Role::DEALER),
				new Role(Role::SIGNED),
		];
		$maxRole1 = new Role(Role::ADMIN);
		Assert::same((string) $maxRole1, (string) Role::getMaxRole($roles1));

		$roles2 = [
				new Role(Role::SIGNED),
				new Role(Role::DEALER),
				Role::USER,
		];
		$maxRole2 = new Role(Role::DEALER);
		Assert::same((string) $maxRole2, (string) Role::getMaxRole($roles2));
	}

}

$test = new RoleTest($container);
$test->run();
