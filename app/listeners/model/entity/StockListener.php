<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Stock;
use App\Model\Facade\StockFacade;
use App\Model\Facade\WatchDogFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class StockListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var WatchDogFacade @inject */
	public $watchDogFacade;

	public function getSubscribedEvents()
	{
		return array(
			Events::postFlush,
			Events::postUpdate,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function postFlush($params)
	{
		$this->checkWatchDog($params);
	}

	public function postUpdate($params)
	{
		$stock = $this->getStockFromParams($params);
		if ($stock) {
			$this->clearCache($stock);
		}
	}

	// </editor-fold>

	private function clearCache(Stock $stock)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [StockFacade::TAG_STOCK . $stock->id],
		]);
	}
	
	private function checkWatchDog(Stock $stock)
	{
		if ($this->hasChangeAnyPrice($stock) && $stock->active) {
			$this->watchDogFacade->onChangedPrice($stock);
		}
		if ($this->hasChangeInStoreFromEmpty($stock) && $stock->inStore && $stock->active) {
			$this->watchDogFacade->onStored($stock);
		}
	}
	
	private function hasChangeInStoreFromEmpty(Stock $stock)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($stock);
		if (is_array($changes) && array_key_exists('inStore', $changes)) {
			if ($changes['inStore'][0] <= 0 && $changes['inStore'][1] >= 1) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	private function hasChangeAnyPrice(Stock $stock)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($stock);
		if (is_array($changes)) {
			foreach ($changes as $key => $change) {
				if (preg_match('/^' . Stock::DEFAULT_PRICE_NAME . '/', $key) || preg_match('/^price(\d+)$/', $key)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/** @return Stock|NULL */
	private function getStockFromParams($params)
	{
		if ($params instanceof Stock) {
			return $params;
		}
		return NULL;
	}

}
