<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Rate;
use App\Model\Repository\RateRepository;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Object;

class RateListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var RateRepository */
	private $rateRepo;

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
		return $this->getRepository()->clearResultCache(RateRepository::ALL_RATES_CACHE_ID);
	}

	/** @return RateRepository */
	private function getRepository()
	{
		if (!$this->rateRepo) {
			$this->rateRepo = $this->em->getRepository(Rate::getClassName());
		}
		return $this->rateRepo;
	}

}
