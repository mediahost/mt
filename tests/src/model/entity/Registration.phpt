<?php

namespace Test\Model\Entity;

use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use DateTime;
use Kdyby\Doctrine\MemberAccessException;
use Nette\Security\Passwords;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Registration entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class RegistrationTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$mail = 'anakin@skywalker.com';
		$role = new Role('guest');
		$password = 'iAmDarthWaderL0053R5';
		$hash = 'HashL0053R5';
		$facebookId = 'FacebookIfThisIsIntCanBeLOngerThan32bit';
		$facebookAccessToken = 'facebookAccessTokenCanBeToooooooooLongOrLonger';
		$twitterId = 'TwitterIfThisIsIntCanBeLOngerThan32bit';
		$twitterAccessToken = 'twitterAccessTokenCanBeLongAsFacebookAccessToken';
		$verificationToken = 'verificationToken';

		$entity = new Registration();
		Assert::null($entity->id);
		Assert::exception(function () use ($entity) {
			$entity->id = 123;
		}, MemberAccessException::class);

		$entity->mail = $mail;
		Assert::same($mail, $entity->mail);

		$entity->hash = $hash;
		Assert::same($hash, $entity->hash);

		$entity->password = $password;
		Assert::true(Passwords::verify($password, $entity->hash));

		$entity->role = $role;
		Assert::type(Role::getClassName(), $entity->role);
		Assert::same($role->name, $entity->role->name);

		$entity->facebookId = $facebookId;
		Assert::same($facebookId, $entity->facebookId);

		$entity->facebookAccessToken = $facebookAccessToken;
		Assert::same($facebookAccessToken, $entity->facebookAccessToken);

		$entity->twitterId = $twitterId;
		Assert::same($twitterId, $entity->twitterId);

		$entity->twitterAccessToken = $twitterAccessToken;
		Assert::same($twitterAccessToken, $entity->twitterAccessToken);

		$tomorrow = new DateTime('now + 1 day');
		$entity->setVerification($verificationToken, $tomorrow);
		Assert::same($verificationToken, $entity->verificationToken);
		Assert::equal($tomorrow, $entity->verificationExpiration);
	}

}

$test = new RegistrationTest($container);
$test->run();
