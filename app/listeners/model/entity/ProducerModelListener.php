<?php

namespace App\Listeners\Model\Entity;

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
			Events::prePersist,
			Events::preUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist()
	{
		$this->clearCache();
	}

	public function preUpdate()
	{
		$this->clearCache();
	}

	public function postRemove()
	{
		$this->clearCache();
	}

	// </editor-fold>

	private function clearCache()
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				'model',
				'models',
			],
		]);
	}

}
