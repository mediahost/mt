<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Category;
use App\Model\Repository\CategoryRepository;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Object;

class CategoryListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var CategoryRepository */
	private $categoryRepo;

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
		return $this->getRepository()->clearResultCache(CategoryRepository::ALL_CATEGORIES_CACHE_ID);
	}

	/** @return CategoryRepository */
	private function getRepository()
	{
		if (!$this->categoryRepo) {
			$this->categoryRepo = $this->em->getRepository(Category::getClassName());
		}
		return $this->categoryRepo;
	}

}
