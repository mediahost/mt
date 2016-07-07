<?php

namespace App\Model\Facade;

use App\Model\Entity\Parameter;
use App\Model\Entity\Product;
use App\Model\Repository\ProductRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ProductFacade extends Object
{
	const TAG_PRODUCT = 'product_';

	/** @var EntityManager @inject */
	public $em;

	/** @var ProductRepository */
	private $productRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->productRepo = $this->em->getRepository(Product::getClassName());
	}

	public function getParameterValues(Parameter $parameter)
	{
		return $this->productRepo->getParameterValues($parameter->code);
	}

}
