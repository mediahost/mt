<?php

namespace Test\Model\Entity;

use App\Model\Entity\OAuth;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: OAuth entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class OAuthTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$entity = new OAuth;

		Assert::null($entity->id);

		Assert::exception(function () use ($entity) {
			$entity->id = '123456789';
		}, MemberAccessException::class);
	}

}

$test = new OAuthTest($container);
$test->run();
