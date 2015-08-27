<?php

namespace App\Extensions\Settings\DI;

use Nette\DI\CompilerExtension;

class SettingsExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'modules' => [
			'cron' => [ // access to cron scripts
				'enabled' => FALSE,
				'allowedIps' => ['127.0.0.1'],
			],
			'order' => [
				'enabled' => TRUE,
				'states' => [
					'order in system' => 'ordered',
					'in proceedings' => 'ordered',
					'sent shippers' => 'expeded',
					'ready to take' => 'expeded',
					'ok - recieved' => 'done',
					'ok - taken' => 'done',
					'canceled' => 'storno',
				],
				'types' => [
					'ordered' => FALSE,
					'expeded' => TRUE,
					'done' => TRUE,
					'storno' => FALSE,
				],
			],
			'categories' => [ // product categories
				'enabled' => FALSE,
				'expandOnlyActiveCategories' => TRUE, // TRUE -> expand only active category | FALSE -> expand all categories
				'maxDeep' => 3, // count of levels to show subcategories
				'showOnlyNonEmpty' => TRUE, // TRUE -> fetch only categories with some products // not implemented yet
				'showProductsCount' => FALSE, // TRUE -> show count of product after category name
			],
			'signs' => [ // fixed IDs for signs (příznaky)
				'enabled' => FALSE,
				'values' => [
					'new' => 1,
					'sale' => 2,
					'top' => 3,
				],
			],
			'pohoda' => [
				'enabled' => FALSE,
				'ico' => '',
				'language' => '',
				'defaultStorage' => '',
				'typePrice' => '',
				'allowedReadStorageCart' => FALSE,
				'allowedReadOrders' => FALSE,
				'allowedCreateStore' => FALSE,
				'allowedCreateShortStock' => FALSE,
				'removeParsedXmlOlderThan' => '1 month',
				'newCodeLenght' => 8,
				'newCodeCharlist' => 'a-Z0-9',
				'vatRates' => [
					'high' => 20,
					'low' => 15,
					'none' => 0,
				],
			],
			'newsletter' => [
				'enabled' => FALSE,
				'email' => 'noreply@example.sk',
			],
			'service' => [
				'enabled' => FALSE,
				'pageId' => 1, // ID of page in pages to show as basic info
			],
			'buyout' => [
				'enabled' => FALSE,
				'pageId' => 1, // ID of page in pages to show as basic info
				'email' => 'buyout@example.sk',
			],
		],
		'pageInfo' => [
			'projectName' => 'projectName',
			'author' => 'author',
			'description' => 'description',
		],
		'pageConfig' => [
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
		'passwords' => [
			'minLength' => 8,
		],
		'design' => [
			'color' => 'default',
			'layoutBoxed' => FALSE,
			'containerBgSolid' => FALSE,
			'headerFixed' => FALSE,
			'footerFixed' => FALSE,
			'sidebarClosed' => FALSE,
			'sidebarFixed' => FALSE,
			'sidebarReversed' => FALSE,
			'sidebarMenuHover' => FALSE,
			'sidebarMenuLight' => FALSE,
		],
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('settings'))
				->setClass('App\Extensions\Settings\SettingsStorage')
				->addSetup('setPageInfo', [$config['pageInfo']])
				->addSetup('setPageConfig', [$config['pageConfig']])
				->addSetup('setExpiration', [$config['expiration']])
				->addSetup('setPasswords', [$config['passwords']])
				->addSetup('setDesign', [$config['design']])
				->addSetup('setModules', [$config['modules']])
				->setInject(TRUE);
	}

}
