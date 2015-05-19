<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\ExpirationService;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Expiration service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class ExpirationServiceTest extends BaseService
{

	/** @var ExpirationService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setExpiration([
			'recovery' => '30 minutes',
			'verification' => '1 hour',
			'registration' => '1 hour',
			'remember' => '14 days',
			'notRemember' => '30 minutes',
		]);
		$this->service = new ExpirationService();
		$this->service->defaultStorage = $this->defaultSettings;
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::same('30 minutes', $this->service->recovery);
		Assert::same('1 hour', $this->service->verification);
		Assert::same('1 hour', $this->service->registration);
		Assert::same('14 days', $this->service->remember);
		Assert::same('30 minutes', $this->service->notRemember);
	}

	// </editor-fold>
}

$test = new ExpirationServiceTest($container);
$test->run();
