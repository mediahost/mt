<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Stock;
use App\Model\Facade\WatchDogFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class StockListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var WatchDogFacade @inject */
	public $watchDogFacade;

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
		$this->checkWatchDog($params);
	}

	public function preUpdate($params)
	{
		$this->checkWatchDog($params);
	}

	public function postRemove($params)
	{
		
	}

	// </editor-fold>
	
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
				if (preg_match('/^defaultPrice$/', $key) || preg_match('/^price(\d+)$/', $key)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

}
