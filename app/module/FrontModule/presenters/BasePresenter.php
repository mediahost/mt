<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Model\Entity\Category;
use App\Model\Repository\CategoryRepository;

abstract class BasePresenter extends BaseBasePresenter
{

	/** @var CategoryRepository */
	protected $categoryRepo;

	/** @var array */
	protected $categories;

	/** @var Category */
	protected $activeCategory;

	protected function startup()
	{
		parent::startup();
		if ($this->isInstallPresenter()) {
			return;
		}
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->categories = $this->categoryRepo->findBy(['parent' => NULL]);
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->categories = $this->categories;
		$this->template->activeCategory = $this->activeCategory;
		
		$categoriesSettings = $this->moduleService->getModuleSettings('categories');
		$this->template->expandOnlyActiveCategories = $categoriesSettings ? $categoriesSettings->expandOnlyActiveCategories : TRUE;
		$this->template->maxCategoryDeep = $categoriesSettings ? $categoriesSettings->maxDeep : 3;
	}

}
