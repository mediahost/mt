<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ShopFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var Exchange @inject */
	public $exchange;

	/** @return ShopVariant */
	public function getShopVariant()
	{
		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());

		$shopVariant = $shopVariantRepo->findOneBy([
			'currency' => $this->exchange->getWeb()->getCode(),
			'shop' => $this->settings->pageConfig->shop->id,
		]);

		if (!$shopVariant) {
			$shopVariant = $shopVariantRepo->find($this->settings->pageConfig->shop->defaultVariant);
		}

		return $shopVariant;
	}

	public function getDefaultPriceName()
	{
		return Stock::DEFAULT_PRICE_NAME . $this->getShopVariant()->priceCode;
	}

}
