<?php

namespace App\Model\Facade;

use App\Model\Entity\Searched;
use App\Model\Repository\SearchedRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class SearchedFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var SearchedRepository */
	private $searchedRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->searchedRepo = $this->em->getRepository(Searched::getClassName());
	}

	public function getMostSearched($limit)
	{
		return $this->searchedRepo->findMostBy([], $limit);
	}

	public function getMostSearchedWithProducts($limit)
	{
		return $this->searchedRepo->findMostBy([
			'product NOT' => NULL,
		], $limit);
	}

}
