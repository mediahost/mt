<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Extensions\Products\ProductList;
use App\Model\Entity\Category;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;

abstract class BasePresenter extends BaseBasePresenter
{

	/** @var CategoryRepository */
	protected $categoryRepo;

	/** @var ProductRepository */
	protected $productRepo;

	/** @var StockRepository */
	protected $stockRepo;

	/** @var array */
	protected $categories;

	/** @var Category */
	protected $activeCategory;

	/** @var bool */
	protected $showSlider = FALSE;

	/** @var bool */
	protected $showSidebanner = FALSE;

	/** @var bool */
	protected $showBrands = FALSE;

	/** @var bool */
	protected $showSteps = TRUE;

	protected function startup()
	{
		parent::startup();
		if ($this->isInstallPresenter()) {
			return;
		}
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->categories = $this->categoryRepo->findBy(['parent' => NULL]);
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->categories = $this->categories;
		$this->template->activeCategory = $this->activeCategory;
		$this->template->showSlider = $this->showSlider;
		$this->template->showSidebanner = $this->showSidebanner;
		$this->template->showBrands = $this->showBrands;
		$this->template->showSteps = $this->showSteps;

		$categoriesSettings = $this->moduleService->getModuleSettings('categories');
		$this->template->expandOnlyActiveCategories = $categoriesSettings ? $categoriesSettings->expandOnlyActiveCategories : FALSE;
		$this->template->maxCategoryDeep = $categoriesSettings ? $categoriesSettings->maxDeep : 3;
		$this->template->showProductsCount = $categoriesSettings ? $categoriesSettings->showProductsCount : FALSE;
	}

	public function createComponentProducts()
	{
		$list = new ProductList();
		$list->setTranslator($this->translator);
		$list->setItemsPerPage($this->pageConfigService->rowsPerPage, $this->pageConfigService->itemsPerRow);
		$list->setLang($this->lang, $this->languageService->defaultLanguage);

		$list->qb = $this->stockRepo->createQueryBuilder('s')
				->innerJoin('s.product', 'p');

		return $list;
	}

}
