<?php

namespace Test\Extensions\Settings\Model\Service;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use Kdyby\Doctrine\EntityManager;
use Nette\DI\Container;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Language service testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class LanguageServiceTest extends BaseService
{

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var LanguageService */
	private $service;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings->setLanguages([
			'default' => 'en',
			'allowed' => ['en' => 'English', 'de' => 'German', 'cs' => 'Czech'],
			'recognize' => ['en' => 'en', 'cs' => 'cs'],
		]);
		$this->service = new LanguageService();
		$this->service->defaultStorage = $this->defaultSettings;
		$this->service->em = $this->em;
		$this->service->httpRequest = new Request(new UrlScript(), NULL, NULL, NULL, NULL, ['Accept-Language' => 'cs']);
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
		Assert::same('en', $this->service->defaultLanguage);

		Assert::type('Nette\Utils\ArrayHash', $this->service->allowedLanguages);
		Assert::count(3, $this->service->allowedLanguages);
		Assert::same('English', $this->service->allowedLanguages->en);
		Assert::same('Czech', $this->service->allowedLanguages->cs);

		Assert::true($this->service->isAllowed('en'));
		Assert::true($this->service->isAllowed('cs'));
		Assert::true($this->service->isAllowed('de'));
		Assert::false($this->service->isAllowed('ru'));

		Assert::same('cs', $this->service->detectedLanguage);

		Assert::same('en', $this->service->language);
		Assert::null($this->service->userLanguage);

		$this->userFacade->create('user@mail.com', 'user', new Role(Role::USER));
		$userDao = $this->em->getDao(User::getClassName());
		$user = $userDao->find(1);
		$this->service->defaultStorage->user = $user;
		$this->service->defaultStorage->loggedIn = TRUE;
		Assert::type(User::getClassName(), $this->service->user);

		$this->service->userLanguage = 'de';
		Assert::same('de', $userDao->find(1)->pageConfigSettings->language);
		Assert::same('de', $this->service->language);
		Assert::same('de', $this->service->userLanguage);

		$this->service->userLanguage = 'cs';
		Assert::same('cs', $userDao->find(1)->pageConfigSettings->language);
		Assert::same('cs', $this->service->language);

		$this->service->defaultStorage->loggedIn = FALSE;
		Assert::same('en', $this->service->language);
		Assert::null($this->service->userLanguage);
	}

	// </editor-fold>
}

$test = new LanguageServiceTest($container);
$test->run();
