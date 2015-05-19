<?php

namespace Test\Presenters\AppModule;

use Nette\Application\Responses\RedirectResponse;
use Tester\Assert;
use Tester\DomQuery;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: ExamplesPresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class ExamplesPresenterTest extends AppBasePresenter
{

	protected function setUp()
	{
		parent::setUp();
		$this->openPresenter('App:Examples');
	}

	public function testUnlogged()
	{
		$response = $this->runPresenterActionGet('form');
		Assert::type(RedirectResponse::class, $response);
	}

	public function testForbidden()
	{
		$this->loginAdmin();
		Assert::exception(function() {
			$this->runPresenterActionGet('form');
		}, 'Nette\Application\ForbiddenRequestException');
	}

	public function testDefault()
	{
		$this->loginSuperadmin();
		$response = $this->runPresenterActionGet('form');

		$html = (string) $response->getSource();
		$dom = @DomQuery::fromHtml($html);

		Assert::true($dom->has('html'));
	}

}

$test = new ExamplesPresenterTest($container);
$test->run();
