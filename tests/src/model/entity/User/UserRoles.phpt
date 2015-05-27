<?php

namespace Test\Model\Entity;

use App\Model\Entity\Facebook;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use DateTime;
use Kdyby\Doctrine\MemberAccessException;
use Nette\Utils\Strings;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: User entity Roles
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserRolesTest extends UserTestBase
{

	const RECOVERY_TOKEN = "likeRecov3ryT0k3n";

	public function testSetAndGet()
	{
		Assert::type('array', $this->user->roles);

		$requiredRole = new Role('required');
		$this->user->requiredRole = $requiredRole;
		Assert::type(Role::getClassName(), $this->user->requiredRole);
		Assert::same($requiredRole->name, $this->user->requiredRole->name);
	}

	public function testVerifyPassword()
	{
		$this->user->password = self::PASSWORD;
		Assert::true($this->user->verifyPassword(self::PASSWORD));
		Assert::false($this->user->verifyPassword(self::PASSWORD . 'other'));
	}

	public function testAddRole()
	{
		$roleA = (new Role())->setName('Role A');
		$roleB = (new Role())->setName('Role B');
		$roleC = (new Role())->setName('Role C');

		Assert::count(0, $this->user->roles); // No roles

		$this->user->addRole($roleA); // Add first role
		Assert::count(1, $this->user->roles);
		Assert::same($roleA->name, $this->user->roles[0]);

		$this->user->addRole($roleA); // Add the same role
		Assert::count(1, $this->user->roles);
		Assert::same($roleA->name, $this->user->roles[0]);

		$this->user->addRole($roleB); // Add another role
		Assert::count(2, $this->user->roles);
		Assert::same($roleA->name, $this->user->roles[0]);
		Assert::same($roleB->name, $this->user->roles[1]);

		$this->user->clearRoles();
		$this->user->addRole($roleC);
		Assert::count(1, $this->user->roles);
		Assert::same('Role C', $this->user->roles[0]);

		$this->user->addRoles([$roleA, $roleC]); // Add array with duplicit roles
		Assert::count(2, $this->user->roles);
		Assert::same($roleC->name, $this->user->roles[0]);
		Assert::same($roleA->name, $this->user->roles[1]);

		$this->user->clearRoles();
		Assert::count(0, $this->user->roles);

	}

	public function testGetSavedRoles()
	{
		$this->updateSchema();

		$roleA = new Role(Role::GUEST);
		$roleB = new Role(Role::SIGNED);
		$roleC = new Role(Role::USER);
		$roleD = new Role(Role::DEALER);
		$roleE = new Role(Role::ADMIN);
		$roleF = new Role(Role::SUPERADMIN);
		$this->em->persist($roleA);
		$this->em->persist($roleB);
		$this->em->persist($roleC);
		$this->em->persist($roleD);
		$this->em->persist($roleE);
		$this->em->persist($roleF);
		$this->em->flush();

		$this->user->addRoles([$roleB, $roleC, $roleB, $roleA, $roleA, $roleC]);
		Assert::same([$roleB->id, $roleC->id, $roleA->id], $this->user->rolesKeys);

		$this->user->clearRoles();
		$this->user->addRoles([$roleB, $roleA, $roleC]);
		Assert::count(3, $this->user->roles);
		Assert::same([2 => Role::SIGNED, 1 => Role::GUEST, 3 => Role::USER], $this->user->roles);

		$this->user->clearRoles();
		$this->user->addRoles([$roleD, $roleE, $roleF]);
		Assert::type(Role::getClassName(), $this->user->maxRole);
		Assert::same($roleF, $this->user->maxRole);

		$this->dropSchema();
	}

	public function testRemoveRole()
	{
		$roleA = new Role('Role A');
		$roleB = new Role('Role B');

		$this->user->addRole($roleA);
		Assert::count(1, $this->user->roles);
		$this->user->addRole($roleB);
		Assert::count(2, $this->user->roles);
		$this->user->removeRole($roleA);
		Assert::count(1, $this->user->roles);
		$this->user->removeRole($roleB);
		Assert::count(0, $this->user->roles);
	}

	public function testSetRecovery()
	{
		$expiration = new DateTime('now + 3 hours');

		Assert::exception(function () {
			$this->user->recoveryToken = self::RECOVERY_TOKEN;
		}, MemberAccessException::class);
		Assert::exception(function () use ($expiration) {
			$this->user->recoveryExpiration = $expiration;
		}, MemberAccessException::class);

		$this->user->setRecovery(self::RECOVERY_TOKEN, $expiration);

		Assert::same(self::RECOVERY_TOKEN, $this->user->recoveryToken);
		Assert::equal($expiration, $this->user->recoveryExpiration);
	}

	public function testRemoveRecovery()
	{
		$token = Strings::random(32);
		$expiration = new DateTime();
		$this->user->setRecovery($token, $expiration);
		$this->user->removeRecovery();

		Assert::null($this->user->recoveryToken);
		Assert::null($this->user->recoveryExpiration);
	}

	public function testSocialConnection()
	{
		Assert::null($this->user->socialName);
		Assert::false($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_APP));
		Assert::false($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_TWITTER));
		Assert::false($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_FACEBOOK));
		Assert::same(0, $this->user->connectionCount);

		$tw = new Twitter('12345');
		$tw->name = 'TW social name';

		$this->user->twitter = $tw;
		Assert::same($tw->name, $this->user->socialName);
		Assert::null($this->user->socialBirthday);
		Assert::true($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_TWITTER));
		Assert::same(1, $this->user->connectionCount);

		$fb = new Facebook('12345');
		$fb->name = 'FB social name';
		$fb->birthday = '30.2.1920';

		$this->user->facebook = $fb;
		Assert::same($fb->name, $this->user->socialName);
		Assert::same($fb->birthday, $this->user->socialBirthday);
		Assert::true($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_FACEBOOK));
		Assert::same(2, $this->user->connectionCount);

		$this->user->setPassword(self::PASSWORD);
		Assert::true($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_APP));
		Assert::same(3, $this->user->connectionCount);

		Assert::false($this->user->hasSocialConnection('unknown'));
	}

}

$test = new UserRolesTest($container);
$test->run();
