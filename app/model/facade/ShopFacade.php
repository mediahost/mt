<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\Request;
use Nette\Object;

class ShopFacade extends Object
{

	const PAIR_KEY_ALIAS = 'shop';

	/** @var EntityManager @inject */
	public $em;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var Request @inject */
	public $httpRequest;

	/** @return ShopVariant */
	public function getShopVariant()
	{
		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());

		$shopVariant = NULL;
		$shopVariantId = $this->settings->pageConfig->shop->defaultVariant;
		$domain = $this->httpRequest->getUrl()->getHost();
		if (preg_match('/^(?:www\.)?((\w+)\.(\w{2}))$/', $domain, $matches)) {
			switch ($matches[2]) {
				case 'mobilnetelefony':
					break;
				case 'mobilgen':
					switch ($matches[3]) {
						case 'cz':
							$shopVariantId = 4;
							break;
						case 'pl':
							$shopVariantId = 5;
							break;
						case 'sk':
							break;
					}
					break;
			}
		}

		if (!$shopVariant) {
			$shopVariant = $shopVariantRepo->find($shopVariantId);
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
