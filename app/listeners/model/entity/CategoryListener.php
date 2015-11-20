<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Category;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\StockFacade;
use App\Model\Repository\CategoryRepository;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Object;

class CategoryListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

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
		if ($this->hasChange($params)) {
			$this->clearCache();
		}
	}

	public function preUpdate($params)
	{
		if ($this->hasChange($params)) {
			$this->clearCache();
		}
	}

	public function postRemove($params)
	{
		$this->clearCache();
	}

	// </editor-fold>

	private function hasChange($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('name', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function clearCache()
	{
		$this->categoryFacade->getCache()->clean([
			Cache::TAGS => [CategoryFacade::TAG_ALL_CATEGORIES],
		]);
		$this->stockFacade->getCache()->clean([
			Cache::TAGS => [StockFacade::TAG_ALL_PRODUCTS],
		]);
		$this->getRepository()->clearResultCache(CategoryRepository::ALL_CATEGORIES_CACHE_ID);
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
