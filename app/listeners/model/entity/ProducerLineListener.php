<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\ProducerLine;
use App\Model\Facade\ProducerFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class ProducerLineListener extends Object implements Subscriber
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
		$line = $this->getProducerLineFromParams($params);
		if ($line) {
			if ($this->hasChangeName($params)) {
				$this->clearProducerLineCache($line);
			}
		}
	}

	// </editor-fold>

	private function clearProducerLineCache(ProducerLine $line)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [
				ProducerFacade::TAG_LINE . $line->id,
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

	private function getProducerLineFromParams($params)
	{
		if ($params instanceof ProducerLine) {
			return $params;
		}
		return NULL;
	}

}
