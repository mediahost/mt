<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Producer;
use App\Model\Facade\ProducerFacade;
use App\Model\Repository\ProducerRepository;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class ProducerListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::postUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist()
	{
		$this->clearProducersCache();
	}

	public function postUpdate($params)
	{
		$producer = $this->getProducerFromParams($params);
		if ($producer) {
			if ($this->hasChangeName($params)) {
				$this->clearProducerCache($producer);
			}
		}
		$this->clearProducersCache();
	}

	public function postRemove()
	{
		$this->clearProducersCache();
	}

	// </editor-fold>

	private function clearProducerCache(Producer $producer)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ProducerFacade::TAG_PRODUCER . $producer->id,
			],
		]);
	}
	
	private function clearProducersCache()
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ProducerRepository::ALL_PRODUCERS_CACHE_ID,
			],
		]);
	}

	private function hasChangeName($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('name', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function getProducerFromParams($params)
	{
		if ($params instanceof Producer) {
			return $params;
		}
		return NULL;
	}

}
