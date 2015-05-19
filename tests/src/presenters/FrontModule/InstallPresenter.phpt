<?php

namespace Test\Presenters\FrontModule;

use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Test\Presenters\BasePresenter;
use Tester\Assert;
use Tester\DomQuery;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: InstallPresenter
 *
 * @testCase
 * @phpVersion 5.4
 */
class InstallPresenterTest extends BasePresenter
{

	/** @var string */
	private $installDir;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->installDir = $container->getParameters()['tempDir'] . 'install/';
	}

	protected function setUp()
	{
		parent::setUp();
		$this->setOwnDb();

		$this->openPresenter('Front:Install');
		FileSystem::createDir($this->installDir);
	}

	protected function tearDown()
	{
		FileSystem::delete($this->installDir);
		parent::tearDown();
	}

	public function testRenderDefault()
	{
		$response = $this->runPresenterActionGet('default', ['printHtml' => TRUE]);
		$html = (string) $response->getSource();
		$dom = DomQuery::fromHtml($html);
		Assert::true($dom->has('html'));
		Assert::true($dom->has('body'));

		$expectedBodyValue = "DB_Doctrine INSTALLED\n "
				. "DB_Roles INSTALLED\n "
				. "DB_Users INSTALLED\n";
		Assert::same($expectedBodyValue, (string) $dom->find('body')[0]->p);
	}

}

$test = new InstallPresenterTest($container);
$test->run();
