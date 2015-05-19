<?php

namespace Test\Model\Facade;

use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeTest extends UserFacade
{

	public function testIsUnique()
	{
		Assert::false($this->userFacade->isUnique(self::MAIL));
		Assert::true($this->userFacade->isUnique('not@unique.com'));
	}
}

$test = new UserFacadeTest($container);
$test->run();
