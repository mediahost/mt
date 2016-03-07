<?php

namespace App\Extensions\HomeCredit\DI;

use Nette\DI\CompilerExtension;

class HomeCreditExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = [
		'shopId' => NULL,
		'key' => NULL,
		'iShopUrl' => 'https://i-shop.train.homecredit.sk/ishop/entry.do',
		'iCalcUrl' => 'https://i-calc.train.homecredit.sk/icalc/entry.do',
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('homecredit'))
				->setClass('App\Extensions\HomeCredit')
				->addSetup('setShop', [$config['shopId'], $config['key'], $config['iShopUrl'], $config['iCalcUrl']])
				->setInject(TRUE);
	}

}
