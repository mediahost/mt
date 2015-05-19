<?php

namespace Test\Extensions\Settings\Model\Storage;

use App\Extensions\Settings\Model\Storage\GuestSettingsStorage;
use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\PageDesignSettings;
use Nette\DI\Container;
use Nette\Http\Session;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Guest storage testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class GuestSettingsStorageTest extends DbTestCase
{

	/** @var Session @inject */
	public $session;

	public function __construct(Container $container)
	{
		parent::__construct($container);
	}

	// <editor-fold desc="tests">

	public function testSetAndGet()
	{
		$storage = new GuestSettingsStorage;
		$storage->injectSession($this->session);
		Assert::true($storage->empty);

		$pageSettings = new PageConfigSettings;
		$pageSettings->language = 'ru';
		$storage->pageSettings = $pageSettings;
		Assert::type(PageConfigSettings::getClassName(), $storage->pageSettings);
		Assert::same($pageSettings->language, $storage->pageSettings->language);

		$designSettings = new PageDesignSettings;
		$designSettings->color = 'blue';
		$storage->designSettings = $designSettings;
		Assert::type(PageDesignSettings::getClassName(), $storage->designSettings);
		Assert::same($designSettings->color, $storage->designSettings->color);

		Assert::false($storage->empty);
		$storage->wipe();
		Assert::true($storage->empty);
	}

	// </editor-fold>
}

$test = new GuestSettingsStorageTest($container);
$test->run();
