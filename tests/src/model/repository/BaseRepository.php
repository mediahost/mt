<?php

namespace Test\Model\Facade;

use Kdyby\Doctrine\EntityManager;
use Test\DbTestCase;

abstract class BaseRepository extends DbTestCase
{

	/** @var EntityManager @inject */
	public $em;

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
	}

	protected function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

}
