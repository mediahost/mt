<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\ModuleService;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Module service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class ModuleServiceTest extends BaseService
{

	/** @var ModuleService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setModules([
			'myModule' => TRUE,
			'disabledModule' => FALSE,
		], [
			'myModule' => [
				'one' => 1,
				'two' => 2,
			],
		]);
		$this->service = new ModuleService();
		$this->service->defaultStorage = $this->defaultSettings;
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::true($this->service->isAllowedModule('myModule'));
		Assert::false($this->service->isAllowedModule('disabledModule'));
		Assert::false($this->service->isAllowedModule('unknownModule'));

		Assert::type('App\Model\Entity\Special\UniversalDataEntity', $this->service->getModuleSettings('myModule'));
		Assert::same(1, $this->service->getModuleSettings('myModule')->one);
		Assert::same(2, $this->service->getModuleSettings('myModule')->two);
		Assert::null($this->service->getModuleSettings('myModule')->three);
		Assert::null($this->service->getModuleSettings('disabledModule'));
		Assert::null($this->service->getModuleSettings('unknownModule'));
	}

	// </editor-fold>
}

$test = new ModuleServiceTest($container);
$test->run();
