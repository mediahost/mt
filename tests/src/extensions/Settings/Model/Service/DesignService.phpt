<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\DesignService;
use App\Model\Entity\PageDesignSettings;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use Kdyby\Doctrine\EntityManager;
use Nette\DI\Container;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Design service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class DesignServiceTest extends BaseService
{

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var DesignService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setDesign([
			'colors' => ['default' => 'Default', 'blue' => 'Blue'],
			'color' => 'default',
			'layoutBoxed' => TRUE,
			'containerBgSolid' => FALSE,
			'headerFixed' => TRUE,
			'footerFixed' => FALSE,
			'sidebarClosed' => TRUE,
			'sidebarFixed' => FALSE,
			'sidebarReversed' => TRUE,
			'sidebarMenuHover' => FALSE,
		]);
		$this->service = new DesignService();
		$this->service->defaultStorage = $this->defaultSettings;
		$this->service->em = $this->em;
	}

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->dropSchema();
	}

	// <editor-fold desc="tests">

	public function testService()
	{
		Assert::count(2, $this->service->colors);
		Assert::same('Default', $this->service->colors->default);
		Assert::same('Blue', $this->service->colors->blue);
		Assert::true($this->service->isAllowedColor('default'));
		Assert::true($this->service->isAllowedColor('blue'));
		Assert::false($this->service->isAllowedColor('red'));
		Assert::type(PageDesignSettings::getClassName(), $this->service->settings);
		Assert::same('default', $this->service->settings->color);
		Assert::true($this->service->settings->layoutBoxed);
		Assert::false($this->service->settings->containerBgSolid);
		Assert::true($this->service->settings->headerFixed);
		Assert::false($this->service->settings->footerFixed);
		Assert::true($this->service->settings->sidebarClosed);
		Assert::false($this->service->settings->sidebarFixed);
		Assert::true($this->service->settings->sidebarReversed);
		Assert::false($this->service->settings->sidebarMenuHover);

		$this->userFacade->create('user@mail.com', 'user', new Role(Role::USER));
		$userDao = $this->em->getDao(User::getClassName());
		$user = $userDao->find(1);
		$this->service->defaultStorage->user = $user;
		$this->service->defaultStorage->loggedIn = TRUE;
		Assert::type(User::getClassName(), $this->service->user);

		$this->service->color = 'blue';
		Assert::same('blue', $userDao->find(1)->pageDesignSettings->color);
		Assert::same('blue', $this->service->settings->color);

		$this->service->sidebarClosed = FALSE;
		Assert::false($userDao->find(1)->pageDesignSettings->sidebarClosed);
		Assert::false($this->service->settings->sidebarClosed);

		$design = $this->service->user->pageDesignSettings;
		$design->layoutBoxed = FALSE;
		$design->containerBgSolid = TRUE;
		$design->headerFixed = FALSE;
		$design->footerFixed = TRUE;
		$this->service->saveUser();
		Assert::false($userDao->find(1)->pageDesignSettings->sidebarClosed);
		Assert::false($this->service->settings->sidebarClosed);
		Assert::false($userDao->find(1)->pageDesignSettings->layoutBoxed);
		Assert::false($this->service->settings->layoutBoxed);
		Assert::true($userDao->find(1)->pageDesignSettings->containerBgSolid);
		Assert::true($this->service->settings->containerBgSolid);
		Assert::false($userDao->find(1)->pageDesignSettings->headerFixed);
		Assert::false($this->service->settings->headerFixed);
		Assert::true($userDao->find(1)->pageDesignSettings->footerFixed);
		Assert::true($this->service->settings->footerFixed);
	}

	// </editor-fold>
}

$test = new DesignServiceTest($container);
$test->run();
