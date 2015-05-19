<?php

namespace Test\Model\Facade;

use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Test\DbTestCase;

abstract class BaseFacade extends DbTestCase
{

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var DefaultSettingsStorage @inject */
	public $defaultSettings;

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
