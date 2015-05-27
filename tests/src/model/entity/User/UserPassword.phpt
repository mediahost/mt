<?php

namespace Test\Model\Entity;

use DateTime;
use Nette\Utils\Random;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: User entity Passwords
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserPasswordTest extends UserTestBase
{

	public function testPassword()
	{
		$this->user->password = self::PASSWORD;
		$this->saveUser();
		Assert::true($this->user->verifyPassword(self::PASSWORD));

		$this->user->clearPassword();
		$this->saveUser();
		Assert::false($this->user->verifyPassword(self::PASSWORD));
	}

	public function testRecovery()
	{
		$token = Random::generate(32);
		$expiration = new DateTime('now + 3 hours');

		$this->user->setRecovery($token, $expiration);
		$this->saveUser();
		Assert::same($this->user->recoveryToken, $token);
		Assert::equal($this->user->recoveryExpiration, $expiration);

		$this->user->removeRecovery();
		$this->saveUser();
		Assert::null($this->user->recoveryToken);
		Assert::null($this->user->recoveryExpiration);
	}

}

$test = new UserPasswordTest($container);
$test->run();
