<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\PasswordService;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Password service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class PasswordServiceTest extends BaseService
{

	/** @var PasswordService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setPasswords([
			'length' => 8,
		]);
		$this->service = new PasswordService();
		$this->service->defaultStorage = $this->defaultSettings;
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::same(8, $this->service->length);
	}

	// </editor-fold>
}

$test = new PasswordServiceTest($container);
$test->run();
