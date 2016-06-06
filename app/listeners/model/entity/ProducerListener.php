<?php

namespace App\Listeners\Model\Entity;

use App\Components\Producer\Form\ModelSelector;
use App\Model\Entity\Producer;
use App\Model\Facade\ProducerFacade;
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
		$this->clearModelSelectorCache();
	}

	public function postUpdate($params)
	{
		$producer = $this->getProducerFromParams($params);
		if ($producer) {
			if ($this->hasChangeName($params)) {
				$this->clearProducerCache($producer);
			}
		}
		$this->clearModelSelectorCache();
	}

	public function postRemove()
	{
		$this->clearModelSelectorCache();
	}

	// </editor-fold>

	private function clearModelSelectorCache()
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ModelSelector::CACHE_ID,
			],
		]);
	}

	private function clearProducerCache(Producer $producer)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ProducerFacade::TAG_PRODUCER . $producer->id,
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
