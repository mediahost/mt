<?php

namespace Test\Model\Facade;

use App\Model\Entity\User;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade Recovery
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeRecoveryTest extends UserFacade
{

	public function testRecoveryToken()
	{
		// Expired token
		/* @var $user1 User */
		$user1 = $this->userRepo->find(self::ID_NEW);
		$user1->setRecovery(self::EXPIRED_TOKEN, 'now - 1 day');
		$this->userRepo->save($user1);

		$this->userRepo->clear();
		Assert::null($this->userFacade->findByRecoveryToken(self::EXPIRED_TOKEN));

		/* @var $user2 User */
		$user2 = $this->userRepo->find(self::ID_NEW);
		Assert::null($user2->recoveryExpiration);
		Assert::null($user2->recoveryToken);

		// Valid token
		$user2->setRecovery(self::VALID_TOKEN, 'now + 1 day');
		$this->userRepo->save($user2);
		$this->userRepo->clear();

		/* @var $user3 User */
		$user3 = $this->userFacade->findByRecoveryToken(self::VALID_TOKEN);
		Assert::type(User::getClassName(), $user3);
		Assert::same(self::VALID_TOKEN, $user3->recoveryToken);
	}

	public function testSetRecovery()
	{
		/* @var $user1 User */
		$user1 = $this->userRepo->find(self::ID_NEW);
		$this->userFacade->setRecovery($user1);
		$this->userRepo->save($user1);
		$this->userRepo->clear();

		/* @var $user2 User */
		$user2 = $this->userRepo->find(self::ID_NEW);
		Assert::same($user1->recoveryToken, $user2->recoveryToken);
		Assert::equal($user1->recoveryExpiration, $user2->recoveryExpiration);
	}

}

$test = new UserFacadeRecoveryTest($container);
$test->run();
