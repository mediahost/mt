<?php

namespace Test\Model\Storage\SignUpStorage;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Storage\SignUpStorage;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: SignUpStorage
 *
 * @testCase
 * @phpVersion 5.4
 */
class SignUpStorageTest extends DbTestCase
{

	/** @var \Nette\Http\Session @inject */
	public $session;

	// <editor-fold desc="tests">

	public function testStorage()
	{
		$storage = new SignUpStorage($this->session);
		$storage->wipe();

		Assert::false($storage->isVerified());
		Assert::type(User::getClassName(), $storage->getUser());
		Assert::null($storage->getUser()->mail);
		Assert::null($storage->getRole());

		$user = new User;
		$user->mail = 'user@mail.com';
		$storage->setUser($user);
		Assert::same($user->mail, $storage->getUser()->mail);

		$storage->setRole(Role::USER);
		Assert::same(Role::USER, $storage->getRole());
		Assert::same(Role::USER, $storage->getRole(TRUE));

		$storage->setRole(Role::ADMIN);
		Assert::same(Role::ADMIN, $storage->getRole());
		Assert::same(Role::USER, $storage->getRole(TRUE));

		$storage->setVerification(TRUE);
		Assert::true($storage->isVerified());
		$storage->setVerification(FALSE);
		Assert::false($storage->isVerified());
	}

	// </editor-fold>
}

$test = new SignUpStorageTest($container);
$test->run();
