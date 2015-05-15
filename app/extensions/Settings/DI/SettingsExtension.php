<?php

namespace App\Extensions\Settings\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class SettingsExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'modules' => [], // auto generated default FALSE
		'modulesSettings' => [], // auto generated default NULL
		'pageInfo' => [],
		'pageConfig' => [
			'itemsPerPage' => 20,
			'itemsPerRow' => 3,
			'rowsPerPage' => 4,
		],
		'expiration' => [
			'recovery' => '30 minutes',
			'verification' => '1 hour',
			'registration' => '1 hour',
			'remember' => '14 days',
			'notRemember' => '30 minutes',
		],
		'languages' => [
			'default' => 'en', // code
			'allowed' => ['en' => 'English', 'cs' => 'Czech'], // code => name
			'recognize' => ['en' => 'en', 'cs' => 'cs'], // toDetect => code - http://www.metamodpro.com/browser-language-codes
		],
		'passwords' => [
			'length' => 8,
		],
		'design' => [
			'colors' => ['default' => 'Default'], // code => name
			'color' => 'default',
			'layoutBoxed' => FALSE,
			'containerBgSolid' => FALSE,
			'headerFixed' => FALSE,
			'footerFixed' => FALSE,
			'sidebarClosed' => FALSE,
			'sidebarFixed' => FALSE,
			'sidebarReversed' => FALSE,
			'sidebarMenuHover' => FALSE,
		],
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('defaults'))
				->setClass('App\Extensions\Settings\Model\Storage\DefaultSettingsStorage')
				->addSetup('setModules', [$config['modules'], $config['modulesSettings']])
				->addSetup('setPageInfo', [$config['pageInfo']])
				->addSetup('setPageConfig', [$config['pageConfig']])
				->addSetup('setExpiration', [$config['expiration']])
				->addSetup('setLanguages', [$config['languages']])
				->addSetup('setPasswords', [$config['passwords']])
				->addSetup('setDesign', [$config['design']])
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('guest'))
				->setClass('App\Extensions\Settings\Model\Storage\GuestSettingsStorage')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('design'))
				->setClass('App\Extensions\Settings\Model\Service\DesignService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('language'))
				->setClass('App\Extensions\Settings\Model\Service\LanguageService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('password'))
				->setClass('App\Extensions\Settings\Model\Service\PasswordService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('expiration'))
				->setClass('App\Extensions\Settings\Model\Service\ExpirationService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('pageInfo'))
				->setClass('App\Extensions\Settings\Model\Service\PageInfoService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('pageConfig'))
				->setClass('App\Extensions\Settings\Model\Service\PageConfigService')
				->setInject(TRUE);

		$builder->addDefinition($this->prefix('module'))
				->setClass('App\Extensions\Settings\Model\Service\ModuleService')
				->setInject(TRUE);
	}

	/** @param Configurator $configurator */
	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('settings', new SettingsExtension());
		};
	}

}
