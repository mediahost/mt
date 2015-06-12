<?php

namespace App\Model\Facade;

use App\Model\Entity\Stock;
use App\Model\Repository\StockRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class StockFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var StockRepository */
	private $stockRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
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
		return $this->getDemoProducts();
	}

	public function getNews()
	{
		return $this->getDemoProducts();
	}

	public function getLastVisited()
	{
		return $this->getDemoProducts();
	}

}
