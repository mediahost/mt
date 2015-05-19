<?php

namespace Test\Presenters\FrontModule;

use Nette\Application\Responses\RedirectResponse;
use Test\Presenters\BasePresenter;
use Tester\Assert;
use Tester\DomQuery;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: ProfilePresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class MyAccountPresenterTest extends BasePresenter
{

	protected function setUp()
	{
		$this->initSystem();
		parent::setUp();
		$this->openPresenter('Front:MyAccount');
	}

	protected function tearDown()
	{
		$this->logout();
		parent::tearDown();
	}

	public function testUnlogged()
	{
		$response = $this->runPresenterActionGet('default');
		Assert::type(RedirectResponse::class, $response);
	}

	public function testDefault()
	{
		$this->loginUser();
		$response = $this->runPresenterActionGet('default');

		$html = (string) $response->getSource();
		$dom = DomQuery::fromHtml($html);

		Assert::true($dom->has('html'));
	}

}

$test = new MyAccountPresenterTest($container);
$test->run();
