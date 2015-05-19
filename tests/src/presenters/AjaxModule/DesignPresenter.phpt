<?php

namespace Test\Presenters\FrontModule;

use Test\Presenters\BasePresenter;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: DesignPresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class DesignPresenterTest extends BasePresenter
{

	protected function setUp()
	{
		parent::setUp();
		$this->initSystem();
		$this->openPresenter('Ajax:Design');
	}

	protected function tearDown()
	{
		$this->logout();
		parent::tearDown();
	}

	public function testRenderSetColor()
	{
		$this->loginAdmin();
		$color = 'default';
		$response = $this->runPresenterActionGet('setColor', ['color' => $color]);

		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::same($color, $arrayResponse->success->color);
	}

	public function testRenderSetColorFail()
	{
		$this->loginAdmin();
		$response = $this->runPresenterActionGet('setColor', ['color' => 'blue']);

		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::same('This color isn\'t supported.', $arrayResponse->error);
	}

	public function testRenderSetColorUnlogged()
	{
		$color = 'default';
		$response = $this->runPresenterActionGet('setColor', ['color' => $color]);

		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::same('You aren\'logged in.', $arrayResponse->error);
	}

	public function testRenderSetSidebarClosed()
	{
		$this->loginAdmin();

		$response = $this->runPresenterActionGet('setSidebarClosed', ['value' => TRUE]);
		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::true($arrayResponse->success->sidebarClosed);
	}

	public function testRenderSetSidebarClosedUnlogged()
	{
		$response = $this->runPresenterActionGet('setSidebarClosed', ['value' => TRUE]);
		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::same('You aren\'logged in.', $arrayResponse->error);
	}

	public function testRenderSetLayout()
	{
		$this->loginAdmin();

		$response = $this->runPresenterActionGet('setLayout', [
			'layoutOption' => 'boxed',
			'sidebarOption' => 'fixed',
			'headerOption' => 'fixed',
			'footerOption' => 'fixed',
			'sidebarPosOption' => 'right',
			'sidebarStyleOption' => 'light',
			'sidebarMenuOption' => 'hover',
		]);
		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::count(7, (array) $arrayResponse->success);
		Assert::true($arrayResponse->success->layoutBoxed);
		Assert::true($arrayResponse->success->sidebarFixed);
		Assert::true($arrayResponse->success->headerFixed);
		Assert::true($arrayResponse->success->footerFixed);
		Assert::true($arrayResponse->success->sidebarReversed);
		Assert::true($arrayResponse->success->sidebarMenuLight);
		Assert::true($arrayResponse->success->sidebarMenuHover);
	}

	public function testRenderSetLayoutUnlogged()
	{
		$response = $this->runPresenterActionGet('setLayout', []);
		$json = (string) $response->getSource();
		$arrayResponse = json_decode($json);
		Assert::count(1, (array) $arrayResponse);
		Assert::same('You aren\'logged in.', $arrayResponse->error);
	}

}

$test = new DesignPresenterTest($container);
$test->run();
