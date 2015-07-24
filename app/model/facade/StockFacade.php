<?php

namespace App\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
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

	/** @var SettingsStorage @inject */
	public $settings;

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
				->andWhere('p.active = :active')
				->AndWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
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
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->sale : NULL;
		return $this->getSignedProducts($id);
	}

	public function getNews()
	{
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->new : NULL;
		return $this->getSignedProducts($id);
	}

	public function getTops()
	{
		$signs = $this->settings->modules->signs;
		$id = $signs->enabled ? $signs->values->top : NULL;
		return $this->getSignedProducts($id);
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
						->where('p.active = :active')
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
