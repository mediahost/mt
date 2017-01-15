<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\ShopVariant;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ShopFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var SettingsStorage @inject */
	public $settings;

	public function getShopVariant($locale)
	{
		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());

		$shopVariant = $shopVariantRepo->findOneBy([
			'locale' => $locale,
			'shop' => $this->settings->pageConfig->shop->id,
		]);

		if (!$shopVariant) {
			$shopVariant = $shopVariantRepo->find($this->settings->pageConfig->shop->defaultVariant);
		}

		return $shopVariant;
	}

}
