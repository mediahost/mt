<?php

namespace Test\Examples;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use Nette\DI\Container;
use Test\DbTestCase;

/**
 * Parent for example use
 */
abstract class BaseUse extends DbTestCase
{

	/** @var DefaultSettingsStorage */
	protected $defaultSettings;

	/** @var LanguageService */
	private $languageService;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->defaultSettings = new DefaultSettingsStorage;
	}

	protected function getLanguageService()
	{
		if (!$this->languageService) {
			$this->defaultSettings->setLanguages([
				'default' => 'en',
				'allowed' => ['en' => 'English', 'fr' => 'French', 'cs' => 'Czech'],
			]);
			$this->languageService = new LanguageService;
			$this->languageService->defaultStorage = $this->defaultSettings;
			$this->languageService->em = $this->em;
		}
		return $this->languageService;
	}

}
