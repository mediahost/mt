<?php

namespace Test\Model\Facade;

use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade Find
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeFindTest extends UserFacade
{

	public function testFindByMail()
	{
		$user = $this->userFacade->findByMail(self::MAIL);
		Assert::type(User::getClassName(), $user);
		Assert::same(self::MAIL, $user->mail);
	}

	public function testFindByFbId()
	{
		$user = $this->userFacade->findByFacebookId(self::FACEBOOK_ID);
		Assert::type(User::getClassName(), $user);
		Assert::same(self::FACEBOOK_ID, $user->facebook->id);
	}

	public function testFindByTwId()
	{
		$user = $this->userFacade->findByTwitterId(self::TWITTER_ID);
		Assert::type(User::getClassName(), $user);
		Assert::same(self::TWITTER_ID, $user->twitter->id);
	}

	public function testVerificationToken()
	{
		Assert::count(0, $this->registrationRepo->findAll());

		$role = $this->roleFacade->findByName(Role::DEALER);

		$registration1 = (new Registration())
				->setMail('user1@mail.com')
				->setRole($role)
				->setVerification('verificationToken1', DateTime::from('now +1 hour'));
		$this->em->persist($registration1);
		$this->em->flush();
		$this->registrationRepo->clear();
		Assert::count(1, $this->registrationRepo->findAll());

		$findedRegistration1 = $this->userFacade->findByVerificationToken($registration1->verificationToken);
		Assert::type(Registration::getClassName(), $findedRegistration1);
		Assert::same($registration1->mail, $findedRegistration1->mail);

		$registration2 = (new Registration())
				->setMail('user2@mail.com')
				->setRole($role)
		->setVerification('verificationToken2', DateTime::from('now -1 hour'));
		$this->em->persist($registration2);
		$this->em->flush();
		$this->registrationRepo->clear();
		Assert::count(2, $this->registrationRepo->findAll());

		$findedRegistration2 = $this->userFacade->findByVerificationToken($registration2->verificationToken);
		Assert::null($findedRegistration2); // expired is deleted
		Assert::count(1, $this->registrationRepo->findAll());

		Assert::null($this->userFacade->findByVerificationToken('unknown token'));
	}

}

$test = new UserFacadeFindTest($container);
$test->run();
