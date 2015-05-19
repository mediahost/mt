<?php

namespace Test\Extensions\Settings\Model\Storage;

use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Extensions\Settings\Model\Storage\GuestSettingsStorage;
use App\Model\Entity\User;
use Nette\DI\Container;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Default storage testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class DefaultSettingsStorageTest extends DbTestCase
{

	public function __construct(Container $container)
	{
		parent::__construct($container);
	}

	// <editor-fold desc="tests">

	public function testSetAndGet()
	{
		$storage = new DefaultSettingsStorage;

		$storage->expiration = [
			'recovery' => '30 minutes',
			'verification' => '1 hour',
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->expiration);
		Assert::same('30 minutes', $storage->expiration->recovery);
		Assert::same('1 hour', $storage->expiration->verification);

		$storage->languages = [
			'default' => 'en',
			'allowed' => ['en' => 'English', 'cs' => 'Czech'],
			'recognize' => ['en' => 'en', 'cs' => 'cs'],
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->languages);
		Assert::same('en', $storage->languages->default);
		Assert::same('English', $storage->languages->allowed->en);
		Assert::same('Czech', $storage->languages->allowed->cs);
		Assert::same('en', $storage->languages->recognize->en);
		Assert::same('cs', $storage->languages->recognize->cs);

		$storage->passwords = [
			'length' => 10,
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->passwords);
		Assert::same(10, $storage->passwords->length);

		$storage->design = [
			'color' => 'default',
			'colors' => ['default' => 'Default'],
			'layoutBoxed' => TRUE,
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->design);
		Assert::same('default', $storage->design->color);
		Assert::same('Default', $storage->design->colors->default);
		Assert::true($storage->design->layoutBoxed);

		$storage->pageConfig = [
			'itemsPerPage' => 20,
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->pageConfig);
		Assert::same(20, $storage->pageConfig->itemsPerPage);

		$storage->pageInfo = [
			'author' => 'me',
			'description' => 'desc',
		];
		Assert::type('Nette\Utils\ArrayHash', $storage->pageInfo);
		Assert::same('me', $storage->pageInfo->author);
		Assert::same('desc', $storage->pageInfo->description);

		$storage->setModules([
			'testModule' => TRUE,
				], [
			'testModule' => [
				'parameterForModule' => 123,
			]
		]);
		Assert::type('Nette\Utils\ArrayHash', $storage->modules);
		Assert::true($storage->modules->testModule);
		Assert::type('Nette\Utils\ArrayHash', $storage->moduleSettings);
		Assert::same(123, $storage->moduleSettings->testModule->parameterForModule);

		$user = new User;
		$user->mail = 'user@mail.com';
		$storage->user = $user;
		Assert::type(User::getClassName(), $storage->user);
		Assert::same($user->mail, $storage->user->mail);

		Assert::null($storage->loggedIn);
		$storage->loggedIn = TRUE;
		Assert::true($storage->loggedIn);

		$guest = new GuestSettingsStorage;
		$storage->guest = $guest;
		Assert::type(get_class($guest), $storage->guest);
	}

	// </editor-fold>
}

$test = new DefaultSettingsStorageTest($container);
$test->run();
