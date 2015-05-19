<?php

namespace Test\Model\Entity;

use App\Model\Entity\Twitter;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Twitter entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class TwitterTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$id = '123456789';
		$accessToken = 'veryLongAndCompicatedToken12345678900987654321';
		$name = 'Firstname Surname';
		$screenName = 'FirstSur';
		$url = 'twitter.com/FirstSur';
		$location = 'Some location format';
		$description = 'some long description';
		$statusesCount = '123';
		$lang = 'cs';

		$entity = new Twitter($id);
		$entity->accessToken = $accessToken;
		$entity->name = $name;
		$entity->screenName = $screenName;
		$entity->url = $url;
		$entity->location = $location;
		$entity->description = $description;
		$entity->statusesCount = $statusesCount;
		$entity->lang = $lang;

		Assert::same($id, $entity->id);
		Assert::same($accessToken, $entity->accessToken);
		Assert::same($name, $entity->name);
		Assert::same($screenName, $entity->screenName);
		Assert::same($url, $entity->url);
		Assert::same($location, $entity->location);
		Assert::same($description, $entity->description);
		Assert::same($statusesCount, $entity->statusesCount);
		Assert::same($lang, $entity->lang);

		Assert::exception(function () use ($entity, $id) {
			$entity->id = $id;
		}, MemberAccessException::class);
	}

}

$test = new TwitterTest($container);
$test->run();
