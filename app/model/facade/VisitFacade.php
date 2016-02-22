<?php

namespace App\Model\Facade;

use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Entity\Visit;
use App\Model\Repository\VisitRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\Request;
use Nette\Object;

class VisitFacade extends Object
{
	
	const IGNORE_TIME = '1 minutes';
	const LAST_VISIT = '24 hours';

	/** @var EntityManager @inject */
	public $em;

	/** @var Request @inject */
	public $httpRequest;

	/** @var VisitRepository */
	private $visitRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->visitRepo = $this->em->getRepository(Visit::getClassName());
	}

	public function add(Stock $stock, User $user = NULL)
	{
		$visit = new Visit($stock, $this->httpRequest->remoteAddress);
		$visit->user = $user;
		
		$previousVisit = $this->visitRepo->findPreviousVisit($visit, $olderThan = self::IGNORE_TIME);
		if ($previousVisit) {
			$previousVisit->visitedAt = $visit->visitedAt;
			$visit = $previousVisit;
		}
		
		$this->visitRepo->save($visit);
		
		return $this;
	}
	
	public function getVisitsCount(Stock $stock, $inLastTime = self::LAST_VISIT, $excludeThis = TRUE)
	{
		$denyIp = $excludeThis ? $this->httpRequest->remoteAddress : NULL;
		return $this->visitRepo->countStockVisits($stock, $inLastTime, $denyIp);
	}
	
	public function getUserVisits(User $user, $limit = NULL)
	{
		return $this->visitRepo->findByUserGroupByStockOrderByVisitedDesc($user, $limit);
	}

}
