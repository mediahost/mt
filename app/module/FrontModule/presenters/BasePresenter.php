<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Components\Auth\ISignInFactory;
use App\Components\Auth\ISignUpFactory;
use App\Components\Auth\SignIn;
use App\Components\Auth\SignUp;
use App\Components\Newsletter\Form\ISubscribeFactory;
use App\Components\Newsletter\Form\Subscribe;
use App\Components\Product\Form\IPrintStockFactory;
use App\Extensions\Products\IProductListFactory;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Helpers;
use App\Model\Entity\Category;
use App\Model\Entity\Page;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Entity\Voucher;
use App\Model\Facade\CategoryFacade;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Latte\Engine;
use Nette\Application\UI\Multiplier;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Caching\Cache;
use Nette\Utils\ArrayHash;

abstract class BasePresenter extends BaseBasePresenter
{

	const PAGE_INFO_TITLE = 'title';
	const PAGE_INFO_KEYWORDS = 'keywords';
	const PAGE_INFO_DESCRIPTION = 'description';

	/** @var ISubscribeFactory @inject */
	public $iSubscribeControlFactory;

	/** @var IProductListFactory @inject */
	public $iProductListFactory;

	/** @var ISignInFactory @inject */
	public $iSignInFactory;

	/** @var ISignUpFactory @inject */
	public $iSignUpFactory;

	/** @var IPrintStockFactory @inject */
	public $iStockPrint;

	/** @var CategoryRepository */
	protected $categoryRepo;

	/** @var ProductRepository */
	protected $productRepo;

	/** @var StockRepository */
	protected $stockRepo;

	/** @var string */
	protected $currentBacklink;

	/** @var bool */
	protected $showSlider = FALSE;

	/** @var bool */
	protected $showBrands = FALSE;

	/** @var bool */
	protected $showSteps = TRUE;

	/** @var string */
	protected $searched;

	/** @var string */
	protected $categoriesBlock;

	/** @var bool */
	protected $stockComponentLabels = TRUE;

	/** @var bool */
	protected $stockComponentSecondImage = TRUE;

	/** @var string */
	protected $stockComponentClasses = [
		'product',
	];

	protected function startup()
	{
		parent::startup();
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->currentBacklink = $this->storeRequest();
		$this->createCategoryTemplate();
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->backlink = $this->currentBacklink;
		$this->template->showSlider = $this->showSlider;
		$this->template->showBrands = $this->showBrands;
		$this->template->showSteps = $this->showSteps;
		$this->template->priceLevel = $this->priceLevel;

		$this->template->basket = $this->basketFacade;
		$this->template->loginError = $this['signInModal']->hasErrors();

		$this->template->pageKeywords = $this->settings->pageInfo->keywords;
		$this->template->pageDescription = $this->settings->pageInfo->description;

		$this->template->categoryBlock = $this->categoriesBlock;

		$this->loadTemplateMenu();
		$this->loadTemplateProducers();
		$this->loadTemplateApplets();
	}

	public function createCategoryTemplate()
	{
		$key = 'categories_' . $this->locale;
		$cache = new Cache($this->cacheStorage, 'CategoriesBlock');

		$this->categoriesBlock = $cache->load($key);
		if (!$this->categoriesBlock) {
			$categoryLatte = new Engine();
			UIMacros::install($categoryLatte->getCompiler());

			$categoriesSettings = $this->settings->modules->categories;
			$categoriesTemplate = new Template($categoryLatte);
			$categoriesTemplate->setFile(realpath(__DIR__ . '/../templates/categories.latte'));
			$categoriesTemplate->control = $categoriesTemplate->_control = $this;
			$categoriesTemplate->locale = $this->locale;
			$categoriesTemplate->categoryRepo = $this->categoryRepo;
			$categoriesTemplate->rootCategoriesIds = $this->categoryRepo->findRootIds();
			$categoriesTemplate->maxCategoryDeep = $categoriesSettings->enabled ? $categoriesSettings->maxDeep : 3;
			$this->categoriesBlock = (string)$categoriesTemplate;

			$cache->save($key, $this->categoriesBlock, [
				Cache::TAGS => [
					CategoryFacade::TAG_CATEGORY,
				]
			]);
		}
	}

	public function changePageInfo($type, $content)
	{
		if ($content) {
			switch ($type) {
				case self::PAGE_INFO_TITLE:
					$this->template->pageTitle = $content;
					break;
				case self::PAGE_INFO_DESCRIPTION:
					$this->template->pageDescription = Helpers::concatStrings(' | ', $content, $this->template->pageDescription);
					break;
				case self::PAGE_INFO_KEYWORDS:
					$this->template->pageKeywords = Helpers::concatStrings(', ', $content, $this->template->pageKeywords);
					break;
			}
		}
	}

	public function handleSignOut()
	{
		$this->user->logout();
		$this->flashMessage($this->translator->translate('flash.signOutSuccess'), 'success');
		$this->redirect('this');
	}

	public function handleRemoveFromCart($stockId)
	{
		if ($stockId) {
			$stockRepo = $this->em->getRepository(Stock::getClassName());
			$stock = $stockRepo->find($stockId);
			if ($stock) {
				$this->basketFacade->remove($stock);
			}
		}
		if ($this->ajax) {
			$this->redrawControl();
			if (isset($this['products'])) {
				$this['products']->redrawControl();
			}
		} else {
			$this->redirect('this');
		}
	}

	public function handleRemoveVoucherFromCart($voucherId)
	{
		if ($voucherId) {
			$voucherRepo = $this->em->getRepository(Voucher::getClassName());
			$voucher = $voucherRepo->find($voucherId);
			if ($voucher) {
				$this->basketFacade->removeVoucher($voucher);
			}
		}
		if ($this->ajax) {
			$this->redrawControl();
			if (isset($this['products'])) {
				$this['products']->redrawControl();
			}
		} else {
			$this->redirect('this');
		}
	}

	protected function loadTemplateMenu()
	{
		$cache = new Cache($this->cacheStorage, 'FrontModule.loadTemplateMenu');
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
		$pageRepo = $this->em->getRepository(Page::getClassName());
		$settings = $this->settings;

		$menuPages = ArrayHash::from([
			'page1' => $this->link('Homepage:'),
			'page2' => $settings->modules->buyout->enabled ? $pageRepo->find($settings->modules->buyout->pageId) : NULL,
			'page3' => $settings->modules->service->enabled ? $pageRepo->find($settings->modules->service->pageId) : NULL,
			'page4' => $pageRepo->find($settings->pageConfig->pageIds->bonusPageId),
			'page5' => $settings->modules->dealer->enabled ? $pageRepo->find($settings->modules->dealer->pageId) : NULL,
			'page6' => $pageRepo->find($settings->pageConfig->pageIds->contactPageId),
		]);
		$footerPages = ArrayHash::from([
			'page1' => $pageRepo->find($settings->pageConfig->pageIds->orderByPhonePageId),
			'page2' => $pageRepo->find($settings->pageConfig->pageIds->termPageId),
			'page3' => $pageRepo->find($settings->pageConfig->pageIds->complaintPageId),
			'page4' => $pageRepo->find($settings->pageConfig->pageIds->contactPageId),
		]);
		$mostSearchedStocks = [
			$stockRepo->find(4291),
			$stockRepo->find(132626),
			$stockRepo->find(131213),
			$stockRepo->find(131680),
			$stockRepo->find(132622),
			$stockRepo->find(132249),
			$stockRepo->find(131219),
		];
		$mostSearchedModels = [
			$modelRepo->find(1),
			$modelRepo->find(2),
			$modelRepo->find(3),
			$modelRepo->find(4),
			$modelRepo->find(5),
			$modelRepo->find(6),
			$modelRepo->find(7),
			$modelRepo->find(8),
		];

		$this->template->menuPages = $menuPages;
		$this->template->footerPages = $footerPages;
		$this->template->mostSearchedStocks = $mostSearchedStocks;
		$this->template->mostSearchedModels = $mostSearchedModels;
	}

	protected function loadTemplateSigns()
	{
		$signs = $this->settings->modules->signs;
		if ($signs->enabled) {
			$signRepo = $this->em->getRepository(Sign::getClassName());

			$new = $signRepo->find($signs->values->new);
			$new->setCurrentLocale($this->locale);

			$sale = $signRepo->find($signs->values->sale);
			$sale->setCurrentLocale($this->locale);

			$top = $signRepo->find($signs->values->top);
			$top->setCurrentLocale($this->locale);

			$special = $signRepo->find($signs->values->special);
			$special->setCurrentLocale($this->locale);

			$this->template->newSign = $new;
			$this->template->saleSign = $sale;
			$this->template->topSign = $top;
			$this->template->specialSign = $special;
		}
	}

	protected function loadTemplateProducers()
	{
		$this->template->producers = $this->producerFacade->getProducers(TRUE, TRUE);
	}

	protected function loadTemplateApplets()
	{
		if ($this->settings->modules->googleAnalytics->enabled) {
			$this->template->googleAnalyticsCode = $this->settings->modules->googleAnalytics->code;
		}
		if ($this->settings->modules->googleSiteVerification->enabled) {
			$this->template->googleSiteVerification = $this->settings->modules->googleSiteVerification->code;
		}
		if ($this->settings->modules->smartSupp->enabled) {
			$this->template->smartSuppKey = $this->settings->modules->smartSupp->key;
		}
		if ($this->settings->modules->smartLook->enabled) {
			$this->template->smartLookKey = $this->settings->modules->smartLook->key;
		}
		if ($this->settings->modules->facebookApplet->enabled) {
			$this->template->facebookAppletId = $this->settings->modules->facebookApplet->id;
		}
	}

	// <editor-fold desc="forms">

	public function createComponentStock()
	{
		return new Multiplier(function ($itemId) {
			$control = $this->iStockPrint->create();
			$control->setShopVariant($this->shopVariant)
				->setStockById($itemId)
				->setPriceLevel($this->priceLevel)
				->setMainClasses($this->stockComponentClasses)
				->setShowLabels($this->stockComponentLabels)
				->setShowSecondImage($this->stockComponentSecondImage);
			return $control;
		});
	}

	public function createComponentProducts()
	{
		$list = $this->iProductListFactory->create();
		$list->setTranslator($this->translator)
			->setExchange($this->exchange, $this->exchange->getWeb())
			->setItemsPerPage($this->settings->pageConfig->itemsPerRow, $this->settings->pageConfig->rowsPerPage)
			->setAjax()
			->setShopVariant($this->shopVariant)
			->setLevel($this->priceLevel)
			->setSorting(ProductList::SORT_BY_PRICE_DESC);

		return $list;
	}

	public function createComponentSearch($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);

		$form->addText('search')
			->setDefaultValue($this->searched)
			->setAttribute('placeholder', 'Search by Keyword')
			->getControlPrototype()->class = 'form-control search-input';

		$form->addSubmit('send', 'Search')
			->getControlPrototype()->class = 'btn btn-primary';

		$form->onSuccess[] = $this->searchSucceeded;
	}

	public function searchSucceeded(Form $form, $values)
	{
		$this->redirect('Category:search', $values->search);
	}

	/** @return Subscribe */
	public function createComponentSubscribe()
	{
		return $this->iSubscribeControlFactory->create();
	}

	/** @return SignIn */
	protected function createComponentSignInModal()
	{
		$control = $this->iSignInFactory->create();
		$control->setBacklink($this->currentBacklink);
		return $control;
	}

	/** @return SignUp */
	protected function createComponentSignUpModal()
	{
		$control = $this->iSignUpFactory->create();
		$control->setBacklink($this->currentBacklink);
		return $control;
	}

	// </editor-fold>
}
