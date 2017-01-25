<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\ShopVariant;
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

//		$shopVariant = $shopVariantRepo->find(5);

		return $shopVariant;
	}

}
