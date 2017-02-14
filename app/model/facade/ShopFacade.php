<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ShopFacade extends Object
{

	const PAIR_KEY_ALIAS = 'shop';

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

	public function getPairs($aliasId = FALSE)
	{
		$shopRepo = $this->em->getRepository(Shop::getClassName());
		$shopList = [];
		foreach ($shopRepo->findAll() as $shop) {
			$shopList[$aliasId ? (self::PAIR_KEY_ALIAS . $shop->priceLetter) : $shop->id] = (string)$shop;
		}
		return $shopList;
	}

	public function getDefaultPriceName()
	{
		return Stock::DEFAULT_PRICE_NAME . $this->getShopVariant()->priceCode;
	}

}
