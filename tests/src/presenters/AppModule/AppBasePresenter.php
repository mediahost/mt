<?php

namespace Test\Presenters\AppModule;

use Test\Presenters\BasePresenter as ParentBasePresenter;

abstract class AppBasePresenter extends ParentBasePresenter
{

	protected function setUp()
	{
		parent::setUp();
		$this->initSystem();
	}

	protected function tearDown()
	{
		$this->logout();
		parent::tearDown();
	}

}
