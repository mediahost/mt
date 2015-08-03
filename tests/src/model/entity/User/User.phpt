<?php

namespace Test\Model\Entity;

use App\Model\Entity\Role;
use Kdyby\Doctrine\EmptyValueException;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: User entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserTest extends UserTestBase
{

	public function testSetAndGet()
	{
		$this->saveUser();
		Assert::same(self::MAIL, $this->user->mail);
		Assert::same(self::MAIL, (string) $this->user);
	}

	public function testSaveEmptyMail()
	{
		$this->user->mail = NULL;
		Assert::exception(function () {
			$this->saveUser(TRUE);
		}, EmptyValueException::class);
	}

	public function testToArray()
	{
		$roleA = new Role('Role A');
		$roleB = new Role('Role B');
		$this->em->persist($roleA);
		$this->em->persist($roleB);
		$this->em->flush();

		$this->user->mail = self::MAIL;
		$this->user->addRoles([$roleB, $roleA]);

		$this->saveUser();
		$array = $this->user->toArray();

		Assert::same($this->user->id, $array['id']);
		Assert::same($this->user->mail, $array['mail']);

		Assert::type('array', $array['role']);
		Assert::type(Role::getClassName(), $array['role'][0]);
		Assert::same('Role A', $array['role'][0]->name);
		Assert::type(Role::getClassName(), $array['role'][1]);
		Assert::same('Role B', $array['role'][1]->name);
	}

	public function testIsNew()
	{
		Assert::true($this->user->isNew());
		$this->saveUser();
		Assert::false($this->user->isNew());
		$findedUser = $this->userRepo->find($this->user->id);
		Assert::false($findedUser->isNew());
	}

}

$test = new UserTest($container);
$test->run();
