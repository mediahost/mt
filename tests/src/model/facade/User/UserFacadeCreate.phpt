<?php

namespace Test\Model\Facade;

use App\Model\Entity\Facebook;
use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade Create
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeCreateTest extends UserFacade
{

	public function testCreate()
	{
		$mail = 'second.user@domain.com';
		$password = 'password654321';
		$role = $this->em->getRepository(Role::getClassName())->findOneByName(Role::USER);

		Assert::count(3, $this->userRepo->findAll());

		Assert::null($this->userFacade->create(self::MAIL, self::PASSWORD, $role)); // Create user with existing e-mail

		$user = $this->userFacade->create($mail, $password, $role);
		Assert::type(User::getClassName(), $user);
		Assert::same($user->mail, $mail);
		Assert::true($user->verifyPassword($password));

		Assert::true(in_array(Role::USER, $user->getRoles()));

		Assert::same(self::ID_NEW + 1, $user->id);

		Assert::count(4, $this->userRepo->findAll());
	}

	public function testCreateRegistration()
	{
		$mail = 'second.user@domain.com';
		$password = 'password654321';
		$role = $this->em->getRepository(Role::getClassName())->findOneByName(Role::USER);

		$user = new User($mail);
		$user->password = $password;
		$user->facebook = new Facebook('fb22');
		$user->twitter = new Twitter('tw22');
		$user->requiredRole = $role;

		Assert::count(0, $this->registrationRepo->findAll());
		$this->userFacade->createRegistration($user);
		Assert::count(1, $this->registrationRepo->findAll());

		/* @var $registration Registration */
		$registration = $this->registrationRepo->find(1);
		Assert::same($mail, $registration->mail);
		Assert::same($role->id, $registration->role->id);
		Assert::same($user->facebook->id, $registration->facebookId);
		Assert::same($user->twitter->id, $registration->twitterId);

		// clear previous with same mail
		$this->userFacade->createRegistration($user);
		$this->registrationRepo->clear();
		Assert::count(1, $this->registrationRepo->findAll());

		$user->mail = 'another.user@domain.com';
		$this->userFacade->createRegistration($user);
		$this->registrationRepo->clear();
		Assert::count(2, $this->registrationRepo->findAll());
	}

	public function testCreateUserFromRegistration()
	{
		$userRole = $this->em->getRepository(Role::getClassName())->findOneByName(Role::USER);
		$password = 'password';
		$user = new User('new@user.com');
		$user->setPassword($password)
				->setFacebook(new Facebook('facebookID'))
				->setTwitter(new Twitter('twitterID'))
				->setRequiredRole($userRole);
		$registration = $this->userFacade->createRegistration($user);
		$this->registrationRepo->clear();
		Assert::count(1, $this->registrationRepo->findAll());
		Assert::count(3, $this->userRepo->findAll());

		$initRole = $userRole;
		$findedRegistration = $this->registrationRepo->find($registration->id);
		$this->userFacade->createFromRegistration($findedRegistration, $initRole);
		$this->userRepo->clear();
		Assert::count(4, $this->userRepo->findAll());

		$newUser = $this->userFacade->findByMail($user->mail);
		Assert::type(User::getClassName(), $newUser);
		Assert::same($user->mail, $newUser->mail);
		Assert::true($newUser->verifyPassword($password));
		Assert::same($initRole->id, $newUser->getMaxRole()->id);
		Assert::same($user->requiredRole->id, $newUser->requiredRole->id);
		Assert::same($user->facebook->id, $newUser->facebook->id);
		Assert::same($user->twitter->id, $newUser->twitter->id);
	}

}

$test = new UserFacadeCreateTest($container);
$test->run();
