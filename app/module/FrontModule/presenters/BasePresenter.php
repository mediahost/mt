<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Components\Producer\Form\IModelSelectorFactory;
use App\Components\Producer\Form\ModelSelector;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Model\Entity\Category;
use App\Model\Entity\Page;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Nette\Utils\ArrayHash;

abstract class BasePresenter extends BaseBasePresenter
{

	/** @var IModelSelectorFactory @inject */
	public $iModelSelectorFactory;

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

	/** @var string */
	protected $searched;

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
		if ($this->isInstallPresenter()) {
			return;
		}
		$this->template->categories = $this->categories;
		$this->template->activeCategory = $this->activeCategory;
		$this->template->showSlider = $this->showSlider;
		$this->template->showBrands = $this->showBrands;
		$this->template->showSteps = $this->showSteps;
		$this->template->priceLevel = $this->priceLevel;

		$this->template->newStocks = $this->stockFacade->getNews();
		$this->template->saleStocks = $this->stockFacade->getSales();
		$this->template->topStocks = $this->stockFacade->getTops();
		$this->template->bestsellerStocks = $this->stockFacade->getBestSellers();
		$this->template->visitedStocks = $this->stockFacade->getLastVisited();

		$this->loadTemplateMenu();
		$this->loadTemplateCategoriesSettings();
		$this->loadTemplateSigns();
		$this->loadTemplateProducers();
	}

	protected function loadTemplateMenu()
	{
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$this->template->menuPages = ArrayHash::from([
					'page1' => $pageRepo->find(3),
					'page2' => $pageRepo->find(2),
					'page3' => $pageRepo->find(1),
		]);
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
			$newSign->setCurrentLocale($this->locale);

			$saleSign = $signRepo->find($signSettings->sale);
			$saleSign->setCurrentLocale($this->locale);

			$topSign = $signRepo->find($signSettings->top);
			$topSign->setCurrentLocale($this->locale);

			$this->template->newSign = $newSign;
			$this->template->saleSign = $saleSign;
			$this->template->topSign = $topSign;
		}
	}

	protected function loadTemplateProducers()
	{
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$producers = $producerRepo->findAll();
		$this->template->producers = $producers;
	}

	// <editor-fold desc="forms">

	public function createComponentProducts()
	{
		$list = new ProductList();
		$list->setTranslator($this->translator);
		$list->setExchange($this->exchange, $this->currency);
		$list->setItemsPerPage($this->pageConfigService->rowsPerPage, $this->pageConfigService->itemsPerRow);

		$list->setAjax();
		$list->setPriceLevel($this->priceLevel);

		$list->sorting = [
			'price' => ProductList::ORDER_DESC,
			'name' => ProductList::ORDER_ASC,
		];

		$list->qb = $this->stockRepo->createQueryBuilder('s')
				->innerJoin('s.product', 'p');

		return $list;
	}

	public function createComponentSearch($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);

		$form->addText('search')
						->setDefaultValue($this->searched)
						->setAttribute('placeholder', 'Search by Keyword')
						->getControlPrototype()->class = 'form-control typeahead';

		$form->addSubmit('send', 'Search')
						->getControlPrototype()->class = 'btn btn-primary';

		$form->onSuccess[] = $this->searchSucceeded;
	}

	public function searchSucceeded(Form $form, $values)
	{
		$this->redirect('Category:search', $values->search);
	}

	/** @return ModelSelector */
	public function createComponentModelSelector()
	{
		$control = $this->iModelSelectorFactory->create();
		$control->setAjax(FALSE);
		$control->onAfterSelect = function ($producer, $line, $model) {
			if ($model instanceof ProducerModel) {
				$this->redirect('Category:accessories', $model->id);
			} else {
				$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Model')]);
				$this->flashMessage($message, 'warning');
				$this->redirect('this');
			}
		};
		return $control;
	}

	// </editor-fold>
}
