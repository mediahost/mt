<?php

namespace App\Listeners\Model\Entity;

use App\Extensions\TodoQueue;
use App\Model\Entity\Category;
use App\Model\Entity\CategoryTranslation;
use App\Model\Facade\CategoryFacade;
use App\Model\Repository\CategoryRepository;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class CategoryListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var TodoQueue @inject */
	public $todoQueue;

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	public function getSubscribedEvents()
	{
		return array(
			Events::postPersist,
			Events::postUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function postPersist($params)
	{
		$category = $this->getCategoryFromParams($params, FALSE);
		if ($category) {
			if ($this->hasChange($params)) {
				$this->clearCache($category, TRUE);
			}
		}
	}

	public function postUpdate($params)
	{
		$category = $this->getCategoryFromParams($params);
		if ($category) {
			if ($this->hasChange($params)) {
				$this->clearCache($category);
				$this->generateUrls($category);
			}
		}
	}

	public function postRemove($params)
	{
		$category = $this->getCategoryFromParams($params, FALSE);
		if ($category) {
			$this->clearCache($category, TRUE);
		}
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

	private function clearCache(Category $category, $withParent = FALSE)
	{
		$tags = [CategoryFacade::TAG_CATEGORY . $category->id];
		if ($withParent) {
			$tags[] = CategoryFacade::TAG_CATEGORY . ($category->parent ? $category->parent->id : NULL);
		}

		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => $tags,
		]);

		/** @var CategoryRepository $categoryRepo */
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categoryRepo->clearResultCache(CategoryRepository::ROOT_CATEGORIES_CACHE_ID);
		$categoryRepo->clearResultCache(CategoryRepository::CATEGORY_CACHE_ID . $category->id);

		$this->todoQueue->todo(TodoQueue::REFRESH_CATEGORY_CACHE, TodoQueue::DO_IT_MORE_LATER);
	}

	private function generateUrls(Category $category)
	{
		$this->categoryFacade->idToUrl($category->id, NULL, NULL, $category);
		$this->categoryFacade->urlToId($category->getUrl(), NULL, NULL, $category);
	}

	/** @return Category|NULL */
	private function getCategoryFromParams($params, $allowTranslation = TRUE)
	{
		if ($params instanceof CategoryTranslation) {
			return $params->getTranslatable();
		} elseif ($allowTranslation && $params instanceof Category) {
			return $params;
		}
		return NULL;
	}

}
