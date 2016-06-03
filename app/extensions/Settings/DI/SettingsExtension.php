<?php

namespace App\Extensions\Settings\DI;

use App\Model\Entity\OrderStateType;
use App\Model\Entity\Vat;
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
			'vats' => [ // vat levels
				Vat::HIGH => 20,
				Vat::LOW => 15,
				Vat::NONE => 0,
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
					'ordered' => OrderStateType::LOCK_ORDER,
					'expeded' => OrderStateType::LOCK_DONE,
					'done' => OrderStateType::LOCK_DONE,
					'storno' => OrderStateType::LOCK_STORNO,
				],
			],
			'categories' => [ // product categories
				'enabled' => FALSE,
				'expandOnlyActiveCategories' => TRUE, // TRUE -> expand only active category | FALSE -> expand all categories
				'maxDeep' => 3, // count of levels to show subcategories
				'showOnlyNonEmpty' => TRUE, // TRUE -> fetch only categories with some products // not implemented yet
				'showProductsCount' => FALSE, // TRUE -> show count of product after category name
			],
			'parameters' => [
				'onlyForCategory' => 1,
			],
			'signs' => [ // fixed IDs for signs (příznaky)
				'enabled' => FALSE,
				'values' => [
					'new' => 1,
					'sale' => 2,
					'top' => 3,
				],
			],
			'bonus' => [ // fixed IDs for bonus groups
				'enabledFrom' => '2016-03-01 00:00:00',
				'values' => [
					'bsc' => 1, // basic
					'vip' => 2, // vip
					'plt' => 3, // platinum
					'gns' => 4, // genius
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
				'removeParsedXmlOlderThan' => '7 days',
				'ordersExportDaysBack' => '7 days',
				'newCodeLenght' => 8,
				'newCodeCharlist' => 'a-Z0-9',
				'vatRates' => [
					'high' => 20,
					'low' => 15,
					'none' => 0,
				],
			],
			'heureka' => [
				'enabled' => FALSE,
				'keyOvereno' => NULL, // ověřeno zákazníky
				'keyConversion' => NULL, // mereni konverzí
				'cpc' => NULL, // max. cena za proklik (max. 100)
				'deliveryStoreTime' => 0,
				'deliveryNotInStoreTime' => NULL,
				'onlyInStore' => TRUE,
				'hideDelivery' => FALSE,
				'denyCategoryId' => NULL,
				'locales' => [],
			],
			'zbozi' => [
				'enabled' => FALSE,
				'onlyInStore' => TRUE,
				'deliveryStoreTime' => 0,
				'deliveryNotInStoreTime' => NULL,
				'locales' => ['cs'],
			],
			'service' => [
				'enabled' => FALSE,
				'pageId' => 1, // ID of page in pages to show as basic info
				'email' => 'service@example.sk',
			],
			'dealer' => [
				'enabled' => FALSE,
				'pageId' => 1, // ID of page in pages to show as basic info
				'email' => 'dealer@example.sk',
			],
			'buyout' => [
				'enabled' => FALSE,
				'pageId' => 1, // ID of page in pages to show as basic info
				'email' => 'buyout@example.sk',
			],
			'newsletter' => [
				'enabled' => FALSE,
				'email' => 'newsletter@example.sk',
			],
			'googleAnalytics' => [
				'enabled' => FALSE,
				'code' => NULL,
			],
			'googleSiteVerification' => [
				'enabled' => FALSE,
				'code' => NULL,
			],
			'smartSupp' => [
				'enabled' => FALSE,
				'key' => NULL,
			],
			'smartLook' => [
				'enabled' => FALSE,
				'key' => NULL,
			],
			'facebookApplet' => [
				'enabled' => FALSE,
				'id' => NULL,
			],
		],
		'pageInfo' => [
			'projectName' => 'projectName',
			'author' => 'Mediahost.sk',
			'authorUrl' => 'http://www.mediahost.sk/',
			'keywords' => 'keywords',
			'description' => 'description',
			'termPageId' => 1, // TODO: move to pageConfig
			'complaintPageId' => 1, // TODO: move to pageConfig
			'contactPageId' => 1, // TODO: move to pageConfig
			'orderByPhonePageId' => 1, // TODO: move to pageConfig
		],
		'pageConfig' => [
			'itemsPerRow' => 3,
			'rowsPerPage' => 4,
		],
		'companyInfo' => [
			'address' => [
				'company' => 'Company name',
				'street' => 'Main street 2',
				'zip' => '123 45',
				'city' => 'City',
			],
			'contact' => [
				'phone' => '+420 123 456 789',
				'email' => 'contact@company.sk',
			],
			'bank' => [
				'sk' => 'SK1234567890123456789012',
				'cz' => 'CZ1234567890123456789012',
			],
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
		'mails' => [ // default value is NULL - doesnt send mail
			'automatFrom' => 'info@example.sk',
			'createOrder' => NULL,
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
				->addSetup('setCompanyInfo', [$config['companyInfo']])
				->addSetup('setMails', [$config['mails']])
				->addSetup('setExpiration', [$config['expiration']])
				->addSetup('setPasswords', [$config['passwords']])
				->addSetup('setDesign', [$config['design']])
				->addSetup('setModules', [$config['modules']])
				->setInject(TRUE);
	}

}
