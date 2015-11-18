<?php

namespace App\Listeners\Model\Entity;

use App\ApiModule\Presenters\ExportProductsPresenter;
use App\Model\Entity\Product;
use App\Model\Entity\ProductTranslation;
use App\Model\Entity\Stock;
use App\Model\Facade\StockFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Object;

class ProductListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var StockFacade @inject */
	public $stockFacade;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist($params)
	{
		$this->clearCache($params);
	}

	public function preUpdate($params)
	{
		$this->clearCache($params);
	}

	public function postRemove($params)
	{
		$this->clearCache($params);
	}

	// </editor-fold>

	private function clearCache($params)
	{
		$tags = [
			StockFacade::TAG_ALL_PRODUCTS,
			ExportProductsPresenter::TAG_STOCK,
		];

		$id = NULL;
		if ($params instanceof Stock) {
			$id = $params->id;
		} else if ($params instanceof Product) {
			$id = $params->stock->id;
		} else if ($params instanceof ProductTranslation) {
			$id = $params->translatable->stock->id;
		}
		
		if ($id) {
			$tags[] = ExportProductsPresenter::TAG_STOCK . '/' . $id;
		}

		$this->stockFacade->getCache()->clean([
			Cache::TAGS => $tags,
		]);
	}

}
