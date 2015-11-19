<?php

namespace App\Listeners\Model\Entity;

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
		$this->clearCache();
	}

	public function preUpdate($params)
	{
		$this->clearCache();
	}

	public function postRemove($params)
	{
		$this->clearCache();
	}

	// </editor-fold>

	private function clearCache()
	{
		$this->stockFacade->getCache()->clean([
			Cache::TAGS => [StockFacade::TAG_ALL_PRODUCTS],
		]);
	}

}
