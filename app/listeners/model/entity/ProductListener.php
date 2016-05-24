<?php

namespace App\Listeners\Model\Entity;

use App\Extensions\TodoQueue;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class ProductListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var TodoQueue @inject */
	public $todoQueue;

	public function getSubscribedEvents()
	{
		return array(
			Events::postUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function postUpdate($params)
	{
		if ($this->hasChangeName($params)) {
			$this->clearCache();
		}
	}

	public function postRemove($params)
	{
		$this->clearCache();
	}

	// </editor-fold>

	private function hasChangeName($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('name', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function clearCache()
	{
		$this->todoQueue->todo(TodoQueue::REFRESH_STOCK_CACHE);
	}

}
