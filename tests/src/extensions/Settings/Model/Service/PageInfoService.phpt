<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\PageInfoService;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: PageInfo service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class PageInfoServiceTest extends BaseService
{

	/** @var PageInfoService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setPageInfo([
			'projectName' => 'my project',
		]);
		$this->service = new PageInfoService();
		$this->service->defaultStorage = $this->defaultSettings;
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::same('my project', $this->service->projectName);
		Assert::null($this->service->author);
		Assert::null($this->service->description);
	}

	// </editor-fold>
}

$test = new PageInfoServiceTest($container);
$test->run();
