<?php

namespace App\Model\Facade;

use App\Model\Entity\Category;
use App\Model\Repository\CategoryRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;

class CategoryFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var CategoryRepository */
	private $categoryRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}

	public function getCategoriesList($lang = NULL)
	{
		if ($lang === NULL) {
			$lang = $this->translator->getDefaultLocale();
		}
		$categories = [];
		foreach ($this->categoryRepo->findAll() as $category) {
			/* @var $category Category */
			$category->setCurrentLocale($lang);
			$categories[$category->id] = $category->treeName;
		}
		@uasort($categories, 'strcoll');
		return $categories;
	}

}
