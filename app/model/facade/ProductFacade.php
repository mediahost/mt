<?php

namespace App\Model\Facade;

use App\Model\Entity\Parameter;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ProductFacade extends Object
{
	const TAG_PRODUCT = 'product_';

	/** @var EntityManager @inject */
	public $em;

	/** @var ProductRepository */
	private $productRepo;

	/** @var StockRepository */
	private $stockRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
	}

	public function getParameterValues(Parameter $parameter, array $criteria = [], $specificValue = NULL)
	{
		return $this->stockRepo->getParameterValues($parameter->code, $criteria, $specificValue);
	}

}
