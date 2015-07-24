<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\ModuleService;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Repository\SignRepository;
use App\Model\Repository\StockRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\DateTime;

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

	private function getSignedProducts($signId)
	{
		if (!$signId) {
			return [];
		}
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
				->andWhere('s.active = :active')
				->andWhere('p.active = :active')
				->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
				->setParameter('active', TRUE)
				->setParameter('sign', $newSign)
				->setParameter('now', new DateTime());
		return $qb->orderBy('s.id', $sorting[rand(0, 1)])
						->setMaxResults(10)
						->getQuery()
						->getResult();
	}

	public function getSales()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		$saleSignId = $signSettings ? $signSettings->sale : NULL;
		return $this->getSignedProducts($saleSignId);
	}

	public function getNews()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		$newSignId = $signSettings ? $signSettings->new : NULL;
		return $this->getSignedProducts($newSignId);
	}

	public function getTops()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		$topSignId = $signSettings ? $signSettings->top : NULL;
		return $this->getSignedProducts($topSignId);
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
						->where('s.active = :active')
						->andWhere('p.active = :active')
						->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
						->setParameter('active', TRUE)
						->setParameter('now', new DateTime())
						->orderBy('s.id', $sorting[rand(0, 1)])
						->setMaxResults(10)
						->getQuery()
						->getResult();
	}

	public function getBestSellers()
	{
		return $this->getDemoProducts();
	}

	public function getLastVisited()
	{
		return $this->getDemoProducts();
	}

}
