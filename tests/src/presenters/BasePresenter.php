<?php

namespace Test\Presenters;

use App\Extensions\Installer;
use Nette\DI\Container;
use Test\PresenterTestCase;

abstract class BasePresenter extends PresenterTestCase
{

	use LoginTrait;

	/** @var Installer @inject */
	public $installer;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->initIdentity();
	}

	protected function initSystem()
	{
		$this->dropSchema();
		$this->setOwnDb();
		$this->installer->install();
	}

	protected function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

}
