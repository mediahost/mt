<?php

namespace Test\Components\Example;

use App\Components\Example\Form\ExampleForm;
use App\Components\Example\Form\IExampleFormFactory;
use Test\Components\BaseControl;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Example form
 *
 * @testCase
 * @phpVersion 5.4
 */
class ExampleFormTest extends BaseControl
{

	/** @var IExampleFormFactory @inject */
	public $factory;

	/** @var ExampleForm */
	private $component;

	protected function setUp()
	{
		parent::setUp();
		$this->component = $this->factory->create();
	}

	public function testComponent()
	{
		\Tracy\Debugger::barDump($this->component);
		Assert::same(TRUE, TRUE);
	}

}

$test = new ExampleFormTest($container);
$test->run();
