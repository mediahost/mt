<?php

namespace App\Model\Facade;

use App\Model\Entity\Category;
use App\Model\Repository\CategoryRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Object;

class CategoryFacade extends Object
{

	const ORDER_DIR_UP = 'up';
	const ORDER_DIR_DOWN = 'down';
	const TAG_CATEGORY = 'category_';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var CategoryRepository */
	private $categoryRepo;

	/** @var array */
	private $ids = [];

	/** @var array */
	private $urls = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}

	public function getCategoriesList($locale = NULL)
	{
		if ($locale === NULL) {
			$locale = $this->translator->getDefaultLocale();
		}
		$categories = [];
		foreach ($this->categoryRepo->findAll() as $category) {
			/* @var $category Category */
			$category->setCurrentLocale($locale);
			$categories[$category->id] = $category->treeName;
		}
		@uasort($categories, 'strcoll');
		return $categories;
	}

	public function reorder(Category $category, $new, $old)
	{
		$dir = $new > $old ? self::ORDER_DIR_UP : self::ORDER_DIR_DOWN;

		$categories = $this->categoryRepo->findByParent($category->parent, ['priority' => 'ASC']);
		foreach ($categories as $i => $categoryItem) {
			if ($categoryItem->id === $category->id) {
				$categoryItem->priority = $new;
			} else if (in_array($i, range($new, $old))) {
				$categoryItem->priority = $dir == self::ORDER_DIR_UP ? $i - 1 : $i + 1;
			} else {
				$categoryItem->priority = $i;
			}
			$this->categoryRepo->save($categoryItem);
		}
	}

}
