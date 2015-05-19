<?php

namespace Test\Presenters\FotoModule;

use Test\Presenters\BasePresenter;
use Tester\Assert;
use Tracy\Debugger;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: FotoPresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class FotoPresenterTest extends BasePresenter
{

	public function testFoto()
	{
		$this->openPresenter('Foto:Foto');
		$response = $this->runPresenterActionGet('default', ['size' => '200-200', 'name' => 'person/default.png']);
		Assert::same(NULL, $response);
	}

}

$test = new FotoPresenterTest($container);
$test->run();
