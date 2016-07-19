<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\ProducerModel;
use App\Model\Facade\ProducerFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class ProducerModelListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	public function getSubscribedEvents()
	{
		return array(
			Events::postUpdate,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function postUpdate($params)
	{
		$model = $this->getProducerModelFromParams($params);
		if ($model) {
			if ($this->hasChangeName($params)) {
				$this->clearProducerModelCache($model);
			}
		}
	}

	// </editor-fold>

	private function clearProducerModelCache(ProducerModel $model)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ProducerFacade::TAG_MODEL . $model->id,
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

	private function getProducerModelFromParams($params)
	{
		if ($params instanceof ProducerModel) {
			return $params;
		}
		return NULL;
	}

}
