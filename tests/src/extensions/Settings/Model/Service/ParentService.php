<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use Nette\DI\Container;
use Test\DbTestCase;

/**
 * Parent for all Settings services
 */
abstract class BaseService extends DbTestCase
{
	
	/** @var DefaultSettingsStorage */
	protected $defaultSettings;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings = new DefaultSettingsStorage();
	}

}
