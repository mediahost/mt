<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Model\Entity\Category;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class CategoryFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var LanguageService @inject */
	public $languageService;

	/** @var EntityRepository */
	private $categoryRepo;

	public function __construct(EntityManager $em, LanguageService $languageService)
	{
		$this->em = $em;
		$this->languageService = $languageService;
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}

	public function getCategoriesList($lang = NULL)
	{
		if ($lang === NULL) {
			$lang = $this->languageService->defaultLanguage;
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
