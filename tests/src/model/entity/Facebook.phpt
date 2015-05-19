<?php

namespace Test\Model\Entity;

use App\Model\Entity\Facebook;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Facebook entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class FacebookTest extends BaseTestCase
{

	const ID = '123456789';
	const ACCESS_TOKEN = 'veryLongAndCompicatedToken';

	public function testSetAndGet()
	{
		$id = '123456789';
		$accessToken = 'veryLongAndCompicatedToken12345678900987654321';
		$mail = 'mail@server.com';
		$name = 'Firstname Surname';
		$birthday = '31.2.1999';
		$gender = 'male';
		$hometown = 'Some hometown format';
		$link = 'facebbok.com/some.nick';
		$location = 'czech republic';
		$locale = 'cs';
		$username = 'some.nick';

		$entity = new Facebook($id);
		$entity->accessToken = $accessToken;
		$entity->mail = $mail;
		$entity->name = $name;
		$entity->birthday = $birthday;
		$entity->gender = $gender;
		$entity->hometown = $hometown;
		$entity->link = $link;
		$entity->location = $location;
		$entity->locale = $locale;
		$entity->username = $username;

		Assert::same($id, $entity->id);
		Assert::same($accessToken, $entity->accessToken);
		Assert::same($mail, $entity->mail);
		Assert::same($name, $entity->name);
		Assert::same($birthday, $entity->birthday);
		Assert::same($gender, $entity->gender);
		Assert::same($hometown, $entity->hometown);
		Assert::same($link, $entity->link);
		Assert::same($location, $entity->location);
		Assert::same($locale, $entity->locale);
		Assert::same($username, $entity->username);

		Assert::exception(function () use ($entity, $id) {
			$entity->id = $id;
		}, MemberAccessException::class);
	}

}

$test = new FacebookTest($container);
$test->run();
