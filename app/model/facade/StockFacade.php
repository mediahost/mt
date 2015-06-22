<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\ModuleService;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Repository\SignRepository;
use App\Model\Repository\StockRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class StockFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var ModuleService @inject */
	public $moduleService;

	/** @var StockRepository */
	private $stockRepo;

	/** @var SignRepository */
	private $signRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		$this->signRepo = $this->em->getRepository(Sign::getClassName());
	}

	private function getDemoProducts()
	{
		$sorting = [
			0 => 'ASC',
			1 => 'DESC',
		];
		return $this->stockRepo
						->createQueryBuilder('s')
						->innerJoin('s.product', 'p')
						->orderBy('s.id', $sorting[rand(0, 1)])
						->setMaxResults(10)
						->getQuery()
						->getResult();
	}

	private function getSignedProducts($signId)
	{
		$sorting = [
			0 => 'ASC',
			1 => 'DESC',
		];
		$newSign = $this->signRepo->find($signId);
		if (!$newSign) {
			return [];
		}
		$qb = $this->stockRepo
				->createQueryBuilder('s')
				->innerJoin('s.product', 'p')
				->innerJoin('p.signs', 'signs')
				->where('signs = :sign')
				->setParameter('sign', $newSign);
		return $qb->orderBy('s.id', $sorting[rand(0, 1)])
						->setMaxResults(10)
						->getQuery()
						->getResult();
	}

	public function getBestSellers()
	{
		return $this->getDemoProducts();
	}

	public function getTops()
	{
		return $this->getDemoProducts();
	}

	public function getSales()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		return $this->getSignedProducts($signSettings->sale);
	}

	public function getNews()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		return $this->getSignedProducts($signSettings->new);
	}

	public function getLastVisited()
	{
		return $this->getDemoProducts();
	}

}
