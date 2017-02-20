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

	/** @var Request @inject */
	public $httpRequest;

	/** @var string */
	private $websiteName;

	/** @var string */
	private $domainName;

	private function loadWebInfo()
	{
		if (!$this->websiteName || !$this->domainName) {
			$domain = $this->httpRequest->getUrl()->getHost();
			if (preg_match('/^(?:www\.)?((\w+)\.(\w{2}))$/', $domain, $matches)) {
				$this->websiteName = $matches[2];
				$this->domainName = $matches[3];
			}
		}
	}

	/** @return ShopVariant */
	public function getShopVariant()
	{
		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());

		$shopVariant = NULL;
		$this->loadWebInfo();
		switch ($this->websiteName) {
			default:
			case 'mobilnetelefony':
				$shopVariantId = 1;
				break;
			case 'mobilgen':
				switch ($this->domainName) {
					default:
						$shopVariantId = 3;
						break;
					case 'cz':
						$shopVariantId = 4;
						break;
					case 'pl':
						$shopVariantId = 5;
						break;
				}
				break;
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

	public function getWebsiteName()
	{
		$this->loadWebInfo();
		return $this->websiteName;
	}

	public function getDomainName()
	{
		$this->loadWebInfo();
		return $this->domainName;
	}

}
