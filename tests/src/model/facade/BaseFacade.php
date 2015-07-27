<?php

namespace Test\Model\Facade;

use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Test\DbTestCase;

abstract class BaseFacade extends DbTestCase
{

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

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
