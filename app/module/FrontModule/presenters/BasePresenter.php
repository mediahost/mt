<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Extensions\Products\ProductList;
use App\Model\Entity\Category;
use App\Model\Entity\Product;
use App\Model\Entity\Sign;
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
	protected $showBrands = FALSE;

	/** @var bool */
	protected $showSteps = TRUE;

	/** @var int */
	protected $priceLevel = NULL;

	protected function startup()
	{
		parent::startup();
		if ($this->isInstallPresenter()) {
			return;
		}
		$this->loadPriceLevel();
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
		$this->template->showBrands = $this->showBrands;
		$this->template->showSteps = $this->showSteps;
		
		$this->template->topStocks = $this->stockFacade->getTops();
		$this->template->bestsellerStocks = $this->stockFacade->getBestSellers();
		$this->template->newStocks = $this->stockFacade->getNews();
		$this->template->saleStocks = $this->stockFacade->getSales();
		$this->template->visitedStocks = $this->stockFacade->getLastVisited();

		$this->loadTemplateCategoriesSettings();
		$this->loadTemplateSigns();
	}

	protected function loadPriceLevel()
	{
		if ($this->user->loggedIn) {
			$identity = $this->user->identity;
			if ($identity->group) {
				$this->priceLevel = $identity->group->level;
			}
		}
	}

	protected function loadTemplateCategoriesSettings()
	{
		$categoriesSettings = $this->moduleService->getModuleSettings('categories');
		$this->template->expandOnlyActiveCategories = $categoriesSettings ? $categoriesSettings->expandOnlyActiveCategories : FALSE;
		$this->template->maxCategoryDeep = $categoriesSettings ? $categoriesSettings->maxDeep : 3;
		$this->template->showProductsCount = $categoriesSettings ? $categoriesSettings->showProductsCount : FALSE;
	}

	protected function loadTemplateSigns()
	{
		$signSettings = $this->moduleService->getModuleSettings('signs');
		if ($signSettings) {
			$signRepo = $this->em->getRepository(Sign::getClassName());
			$newSign = $signRepo->find($signSettings->new);
			$newSign->setCurrentLocale($this->lang);
			$saleSign = $signRepo->find($signSettings->sale);
			$saleSign->setCurrentLocale($this->lang);

			$this->template->newSign = $newSign;
			$this->template->saleSign = $saleSign;
		}
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
