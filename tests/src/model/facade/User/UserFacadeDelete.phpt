<?php

namespace Test\Model\Facade;

use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: UserFacade Delete
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeDeleteTest extends UserFacade
{

	public function testDelete()
	{
		Assert::count(6, $this->roleDao->findAll());
		Assert::count(3, $this->userRepo->findAll());
		Assert::count(1, $this->facebookDao->findAll());
		Assert::count(1, $this->twitterDao->findAll());
		Assert::count(1, $this->pageConfigSettingsDao->findAll());
		Assert::count(1, $this->pageDesignSettingsDao->findAll());

		$this->userFacade->deleteById(self::ID_NEW);
		$this->userRepo->clear();

		Assert::count(6, $this->roleDao->findAll());
		Assert::count(2, $this->userRepo->findAll());
		Assert::count(0, $this->facebookDao->findAll());
		Assert::count(0, $this->twitterDao->findAll());
		Assert::count(0, $this->pageConfigSettingsDao->findAll());
		Assert::count(0, $this->pageDesignSettingsDao->findAll());
	}

}

$test = new UserFacadeDeleteTest($container);
$test->run();
