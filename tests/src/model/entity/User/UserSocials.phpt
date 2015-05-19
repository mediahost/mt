<?php

namespace Test\Model\Entity;

use App\Model\Entity\Facebook;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: User entity Socials
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserSocialsTest extends UserTestBase
{

	public function testSetAndClear()
	{
		$this->user->facebook = new Facebook('fbID123456');
		$this->user->twitter = new Twitter('twID123456');
		$this->saveUser();

		Assert::type(Facebook::getClassName(), $this->user->facebook);
		Assert::type(Twitter::getClassName(), $this->user->twitter);

		$this->user->clearFacebook();
		$this->saveUser();
		Assert::null($this->user->facebook);

		$this->user->clearTwitter();
		$this->saveUser();
		Assert::null($this->user->twitter);
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
		$this->saveUser();

		Assert::same($tw->name, $this->user->socialName);
		Assert::null($this->user->socialBirthday);
		Assert::true($this->user->hasSocialConnection(User::SOCIAL_CONNECTION_TWITTER));
		Assert::same(1, $this->user->connectionCount);

		$fb = new Facebook('12345');
		$fb->name = 'FB social name';
		$fb->birthday = '30.2.1920';
		$this->user->facebook = $fb;
		$this->saveUser();

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

$test = new UserSocialsTest($container);
$test->run();
