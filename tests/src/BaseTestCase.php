<?php

namespace Test;

use Nette\DI\Container;
use Tester\TestCase;

abstract class BaseTestCase extends TestCase
{

	/** @var Container */
	private $container;

	function __construct(Container $container = NULL)
	{
		$this->container = $container;
		$this->container->callInjects($this);
	}

	/** @return Container */
	protected function getContainer()
	{
		return $this->container;
	}

}
