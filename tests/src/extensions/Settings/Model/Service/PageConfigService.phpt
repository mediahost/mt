<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\PageConfigService;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: PageConfig service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class PageConfigServiceTest extends BaseService
{

	/** @var PageConfigService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setPageConfig([
			'itemsPerPage' => 20,
			'itemsPerRow' => 3,
		]);
		$this->service = new PageConfigService();
		$this->service->defaultStorage = $this->defaultSettings;
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::same(20, $this->service->itemsPerPage);
		Assert::same(3, $this->service->itemsPerRow);
		Assert::null($this->service->rowsPerPage);
	}

	// </editor-fold>
}

$test = new PageConfigServiceTest($container);
$test->run();
