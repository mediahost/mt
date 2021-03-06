<?php

namespace Test\Presenters\FrontModule;

use Test\Presenters\BasePresenter;
use Tester\Assert;
use Tester\DomQuery;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: HomepagePresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class HomepagePresenterTest extends BasePresenter
{

	protected function setUp()
	{
		$this->initSystem();
		parent::setUp();
		$this->openPresenter('Front:Homepage');
	}

	public function testRenderDefault()
	{
		$response = $this->runPresenterAction('default');

		$html = (string) $response->getSource();
		$dom = DomQuery::fromHtml($html);
		Assert::true($dom->has('html'));
		Assert::true($dom->has('head'));
		Assert::true($dom->has('title'));
		Assert::true($dom->has('body'));
	}

}

$test = new HomepagePresenterTest($container);
$test->run();
