<?php

namespace App\Extensions\Products;

use App\Components\Product\Form\IPrintStockFactory;
use App\Extensions\Products\Components\DataHolder;
use App\Extensions\Products\Components\IProducerFilterFactory;
use App\Extensions\Products\Components\ISortingFormFactory;
use App\Extensions\Products\Components\Paginator;
use App\Extensions\Products\Components\SortingForm;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Category;
use App\Model\Entity\Parameter;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\ProductFacade;
use App\Model\Facade\StockFacade;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;
use Nette\Localization\ITranslator;
use Nette\Templating\FileTemplate;
use Nette\Utils\ArrayHash;

class ProductList extends Control
{

	const SORT_BY_PRICE_ASC = 1;
	const SORT_BY_PRICE_DESC = 2;
	const SORT_BY_NAME_ASC = 3;
	const SORT_BY_NAME_DESC = 4;
	const DEFAULT_PRICE_LEVEL = 'defaultPrice';

	// <editor-fold defaultstate="collapsed" desc="injects">

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var ProductFacade @inject */
	public $productFacade;

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	/** @var IPrintStockFactory @inject */
	public $iStockPrint;

	/** @var ISortingFormFactory @inject */
	public $iSortingFormFactory;

	/** @var IProducerFilterFactory @inject */
	public $iProducerFilterFactory;

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="persistent">

	/** @var int @persistent */
	public $page = 1;

	/** @var int @persistent */
	public $sorting = self::SORT_BY_PRICE_ASC;

	/** @var bool @persistent show only in store */
	public $stored = FALSE;

	/** @var int @persistent */
	public $perPage = 15;

	/** @var int @persistent */
	public $minPrice;

	/** @var int @persistent */
	public $maxPrice;

	/** @var int @persistent */
	public $producer;

	/** @var int @persistent */
	public $line;

	/** @var int @persistent */
	public $model;

	/** @var array @persistent */
	public $param;

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="events">

	/** @var array event on render */
	public $onRender = [];

	/** @var array event for modifying data */
	public $onFetchData = [];

	/** @var array event for modifying each item */
	public $onEachItem = [];

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="variables">

	/** @var DataHolder */
	protected $holder;

	/** @var mixed */
	protected $data;

	/** @var int total count of items */
	protected $count;

	/** @var Paginator */
	protected $paginator;

	/** @var array */
	protected $perPageListMultiples = [1, 2, 3, 6];

	/** @var array */
	protected $perPageList = [9, 18, 27, 56];

	/** @var int */
	protected $itemsPerRow = 3;

	/** @var int */
	protected $rowsPerPage = 3;

	/** @var bool */
	protected $producerAllowNone = TRUE;

	/** @var int */
	protected $priceLevel;

	/** @var int */
	protected $limitPriceMin;

	/** @var int */
	protected $limitPriceMax;

	/** @var string */
	protected $currency;

	/** @var bool */
	protected $ajax;

	// </editor-fold>

	private function getHolder()
	{
		if (!$this->holder) {
			$this->holder = new DataHolder($this->em);
		}
		return $this->holder;
	}

	/* 	 ADD FILTERS *************************************************************************************** */

	// <editor-fold defaultstate="collapsed" desc="add filters">

	public function addFilterCategory(Category $category)
	{
		$this->getHolder()->filterCategory($category);
		$this->setLimitPrices();
		return $this;
	}

	public function addFilterProducers($producer)
	{
		$this->getHolder()->filterProducer($producer);
		$this->setLimitPrices();
	}

	public function addFilterFulltext($text)
	{
		$this->getHolder()->filterFulltext($text);
		$this->setLimitPrices();
		return $this;
	}

	public function addFilterUpdatedFrom($time)
	{
		$this->getHolder()->filterUpdatedFrom($time);
		return $this;
	}

	public function addFilterCreatedFrom($time)
	{
		$this->getHolder()->filterCreatedFrom($time);
		return $this;
	}

	// </editor-fold>

	protected function applyPaging()
	{
		$paginator = $this->getPaginator()
			->setItemCount($this->getCount())
			->setPage($this->page);

		$offset = $paginator->getOffset();
		$limit = $paginator->getLength();
		$this->getHolder()->setPaging($limit, $offset);
		return $this;
	}

	protected function applySorting()
	{
		switch ($this->sorting) {
			case self::SORT_BY_PRICE_ASC:
			case self::SORT_BY_PRICE_DESC:
				$dir = $this->sorting === self::SORT_BY_PRICE_ASC ? 'ASC' : 'DESC';
				$this->getHolder()->setSorting(DataHolder::ORDER_BY_PRICE, $dir);
				break;
			case self::SORT_BY_NAME_ASC:
			case self::SORT_BY_NAME_DESC:
				$dir = $this->sorting === self::SORT_BY_NAME_ASC ? 'ASC' : 'DESC';
				$this->getHolder()->setSorting(DataHolder::ORDER_BY_NAME, $dir);
				break;
		}
		return $this;
	}

	protected function applyFiltering()
	{
		$this->getHolder()->filterInStore($this->stored);

		$accessoriesFilter = [];
		if ($this->producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$producer = $producerRepo->find($this->producer);
			if ($producer) {
				$accessoriesFilter[] = $producer;
			}
		}
		if ($this->line) {
			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$line = $lineRepo->find($this->line);
			if ($line) {
				$accessoriesFilter[] = $line;
			}
		}
		if ($this->model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$model = $modelRepo->find($this->model);
			if ($model) {
				$accessoriesFilter[] = $model;
			}
		}
		if (count($accessoriesFilter)) {
			$this->getHolder()->filterAccessoriesFor($accessoriesFilter);
		}
		if ($this->maxPrice) {
			$this->getHolder()->filterPrice($this->minPrice > 0 ? $this->minPrice : 0, $this->maxPrice);
		}
		if ($this->param) {
			$params = unserialize($this->param);
			if (is_array($params)) {
				foreach ($params as $code => $value) {
					$this->getHolder()->filterParameter($code, $value);
				}
			}
		} else {
			$this->getHolder()->filterResetParameters();
		}
		return $this;
	}

	/* 	 SETTERS ******************************************************************************************* */

	// <editor-fold defaultstate="collapsed" desc="public setters">

	public function setLevel($level)
	{
		$this->priceLevel = $level;
		$this->getHolder()->setPriceLevel($level);
		return $this;
	}

	public function setSorting($sort)
	{
		switch ($sort) {
			case self::SORT_BY_PRICE_ASC:
			case self::SORT_BY_PRICE_DESC:
			case self::SORT_BY_NAME_ASC:
			case self::SORT_BY_NAME_DESC:
				$this->sorting = $sort;
				break;
		}

		return $this;
	}

	public function setProducer(Producer $producer = NULL, $allowNone = FALSE)
	{
		$this->producer = $producer ? $producer->id : NULL;
		$this->producerAllowNone = $allowNone;
		return $this;
	}

	public function setLine(ProducerLine $line = NULL)
	{
		$this->line = $line ? $line->id : NULL;
		return $this;
	}

	public function setModel(ProducerModel $model = NULL)
	{
		$this->model = $model ? $model->id : NULL;
		return $this;
	}

	public function setFilterPrice($from, $to)
	{
		$this->minPrice = $from;
		$this->maxPrice = $to;
		return $this;
	}

	public function setItemsPerPage($itemsPerRow, $rowsPerPage = 1)
	{
		$itemsPerRowInt = (int)$itemsPerRow;
		$rowsPerPageInt = (int)$rowsPerPage;
		$this->itemsPerRow = $itemsPerRowInt ? $itemsPerRowInt : 1;
		$this->rowsPerPage = $rowsPerPageInt ? $rowsPerPageInt : 1;
		$itemsPerPage = $this->getDefaultPerPage();

		$this->resetPerPageList($itemsPerPage);

		return $this;
	}

	public function setTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
		return $this;
	}

	public function setExchange(Exchange $exchange, $currency)
	{
		$this->exchange = $exchange;
		$this->currency = $currency;
		return $this;
	}

	public function setAjax($value = TRUE)
	{
		$this->ajax = $value;
		return $this;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="protected setters">

	protected function setLimitPrices()
	{
		if (!$this->limitPriceMin || !$this->limitPriceMax) {
			list($this->limitPriceMin, $this->limitPriceMax) = $this->getHolder()->getLimitPrices();
		}
		return $this;
	}

	protected function resetPerPageList($firstItem)
	{
		$this->perPageList = $this->perPageListMultiples;
		foreach ($this->perPageList as $key => $value) {
			$this->perPageList[$key] = $firstItem * $value;
		}

		return $this;
	}

	protected function setFilterParams(array $params)
	{
		$serialized = @serialize($params);
		$this->param = count($params) && $serialized ? $serialized : NULL;
		return $this;
	}

	// </editor-fold>

	/* 	 GETTERS ******************************************************************************************* */

	// <editor-fold defaultstate="collapsed" desc="public getters">

	public function getPerPage()
	{
		return $this->perPage === NULL ? $this->getDefaultPerPage() : $this->perPage;
	}

	public function getPerPageList()
	{
		return $this->perPageList;
	}

	public function getPaginator()
	{
		if ($this->paginator === NULL) {
			$this->paginator = new Paginator();
			$this->paginator->setItemsPerPage($this->getPerPage());
		}

		return $this->paginator;
	}

	public function getCount($refresh = FALSE)
	{
		if ($this->count === NULL || $refresh) {
			$this->count = $this->getHolder()->getCount();
		}
		return $this->count;
	}

	public function getData($applyPaging = TRUE, $useCache = TRUE, $prepare = TRUE)
	{
		$data = $this->data;
		if ($data === NULL || $useCache === FALSE) {

			$this->applyFiltering();
			$this->applySorting();

			if ($applyPaging) {
				$this->applyPaging();
			}

			$data = $this->getHolder()->getStocks();

			if ($useCache) {
				$this->data = $data;
			}

			if ($applyPaging && $data && !in_array($this->page, range(1, $this->getPaginator()->pageCount))) {
				$this->page = 1;
			}

			$this->onFetchData($this, $data);

			if ($prepare) {
				foreach ($data as $item) {
					$item->product->setCurrentLocale($this->translator->getLocale());
					$this->onEachItem($this, $item);
				}
			}
		}

		return $data;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="protected getters">

	protected function getDefaultPerPage()
	{
		return $this->itemsPerRow * $this->rowsPerPage;
	}

	protected function getCurrencySymbol()
	{
		if ($this->currency) {
			return $this->exchange[$this->currency]->getFormat()->getSymbol();
		}
		return NULL;
	}

	protected function getLimitPriceMin()
	{
		$this->setLimitPrices();
		return $this->limitPriceMin;
	}

	protected function getLimitPriceMax()
	{
		$this->setLimitPrices();
		return $this->limitPriceMax;
	}

	protected function getFilterParams()
	{
		$unserialized = @unserialize($this->param);
		return $unserialized ? $unserialized : [];
	}

	// </editor-fold>

	/* 	 SIGNALS ******************************************************************************************* */

	// <editor-fold desc="signals">

	public function handlePage($page)
	{
		$this->page = $page;
		$this->reload();
	}

	public function reload()
	{
		if ($this->presenter->isAjax()) {
			$this->redrawControl();
			$this->presenter->redrawControl();
		} else {
			$this->redirect('this');
		}
	}

	// </editor-fold>

	/* 	 TEMPLATES ***************************************************************************************** */

	// <editor-fold defaultstate="collapsed" desc="templates">

	/**
	 * @return FileTemplate
	 * @internal
	 */
	public function createTemplate()
	{
		$template = parent::createTemplate();
		$template->setFile(__DIR__ . '/templates/productList.latte');
		$template->registerHelper('translate', callback($this->translator, 'translate'));

		return $template;
	}

	public function render()
	{
		if ($this->presenter->isAjax()) {
			if ($this->isControlInvalid('productList')) {
				$this->renderList();
			}
			if ($this->isControlInvalid('productFilter')) {
				$this->renderFilter();
			}
			if ($this->isControlInvalid('productPaginator')) {
				$this->renderPaginator();
			}
			if ($this->isControlInvalid('productSorting')) {
				$this->renderSorting();
			}
			if ($this->isControlInvalid('productAccessories')) {
				$this->renderAccessories();
			}
		} else {
			$this->renderList();
		}
	}

	public function renderList()
	{
		$this->template->basket = $this->basketFacade;
		$this->template->setFile(__DIR__ . '/templates/productList.latte');
		$this->templateRender();
	}

	public function renderFilter()
	{
		$this->template->setFile(__DIR__ . '/templates/filter.latte');
		$this->templateRender();
	}

	public function renderPaginator()
	{
		$this->template->setFile(__DIR__ . '/templates/paginator.latte');
		$this->templateRender();
	}

	public function renderSorting()
	{
		$this->template->setFile(__DIR__ . '/templates/sorting.latte');
		$this->templateRender();
	}

	public function renderAccessories()
	{
		$this->template->setFile(__DIR__ . '/templates/accessories.latte');
		$this->templateRender();
	}

	private function templateRender()
	{
		$data = $this->getData();

		if ($this->onRender) {
			$this->onRender($this);
		}

		$this->template->stocks = $data;
		$this->template->paginator = $this->getPaginator();
		$this->template->itemsPerRow = $this->itemsPerRow;
		$this->template->lang = $this->translator->getLocale();
		$this->template->ajax = $this->ajax;
		$this->template->render();
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="forms">

	protected function createComponentStock()
	{
		return new Multiplier(function ($itemId) {
			$control = $this->iStockPrint->create();
			$control->setStockById($itemId);
			$control->setPriceLevel($this->priceLevel);
			return $control;
		});
	}

	/** @return SortingForm */
	protected function createComponentSortingForm()
	{
		$control = $this->iSortingFormFactory->create();
		$control->setAjax();
		$control->setSorting($this->sorting);
		$control->setPerPage($this->perPage, $this->perPageList);

		$control->onAfterSend = function ($sorting, $perPage) {
			$this->setSorting($sorting);
			$this->perPage = $perPage;
			$this->reload();
		};
		return $control;
	}

	protected function createComponentAccessoriesFilterForm()
	{
		$findedProductIds = $this->getHolder()->getProductsIds(TRUE);
		$control = $this->iProducerFilterFactory->create();
		$control->setAjax();
		$control->setProducer($this->producer, $this->producerAllowNone);
		$control->setLine($this->line);
		$control->setModel($this->model);
		$control->setProductIds($findedProductIds);
		$control->onAfterSend = function ($producer, $line, $model) {
			$this->setProducer($producer, $this->producerAllowNone);
			$this->setLine($line);
			$this->setModel($model);
			$this->reload();
		};
		return $control;
	}

	protected function createComponentFilterForm($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [
			'sendOnChange',
			!$this->ajax ?: 'ajax'
		];

		$form->addCheckbox('onlyAvailable', 'Only Available')
			->setDefaultValue($this->stored);

		$limitMinPriceRaw = $this->getLimitPriceMin();
		$limitMaxPriceRaw = $this->getLimitPriceMax();
		$limitMinPrice = floor($this->exchange->change($limitMinPriceRaw));
		$limitMaxPrice = ceil($this->exchange->change($limitMaxPriceRaw));

		$fromValue = $this->minPrice ? floor($this->exchange->change($this->minPrice)) : NULL;
		$toValue = $this->maxPrice ? ceil($this->exchange->change($this->maxPrice)) : NULL;

		if ($limitMaxPrice > 0) {
			$form->addText('price', 'Range')
				->setAttribute('data-min', $limitMinPrice)
				->setAttribute('data-max', $limitMaxPrice)
				->setAttribute('data-from', $fromValue)
				->setAttribute('data-to', $toValue)
				->setAttribute('data-type', 'double')
				->setAttribute('data-step', '1')
				->setAttribute('data-hasgrid', 'false')
				->setAttribute('data-postfix', ' ' . $this->getCurrencySymbol());
		}

		$filteredParams = $this->getFilterParams();
		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		$allParams = $paramRepo->findAll();
		$defaultValues = [];
		$findedProductIds = $this->getHolder()->getProductsIds(TRUE);
		foreach ($allParams as $parameter) {
			$parameter->setCurrentLocale($this->translator->getLocale());
			switch ($parameter->type) {
				case Parameter::BOOLEAN:
					$moreItems = $this->productFacade->getParameterValues($parameter, $findedProductIds, TRUE);
					if ($moreItems) {
						$form->addCheckbox($parameter->code, $parameter->name);
					}
					break;
				case Parameter::STRING:
					$items = [NULL => '--- Not selected ---'];
					$moreItems = $this->productFacade->getParameterValues($parameter, $findedProductIds);
					if (count($moreItems)) {
						$form->addSelect2($parameter->code, $parameter->name, $items + $moreItems);
					}
					break;
			}
			if (isset($filteredParams[$parameter->code])) {
				$defaultValues[$parameter->code] = $filteredParams[$parameter->code];
			}
		}
		$form->setDefaults($defaultValues);

		$form->onSuccess[] = $this->processFilterForm;
	}

	public function processFilterForm(Form $form, ArrayHash $values)
	{
		$this->stored = $values->onlyAvailable;

		$glue = preg_quote(';');
		if (preg_match('/^(\d+)' . $glue . '(\d+)$/', $values->price, $matches)) {
			$minPriceRaw = $matches[1];
			$maxPriceRaw = $matches[2];

			$minPrice = $this->exchange->change($minPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$maxPrice = $this->exchange->change($maxPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$this->setFilterPrice($minPrice, $maxPrice);
			$form['price']
				->setAttribute('data-from', $minPriceRaw)
				->setAttribute('data-to', $maxPriceRaw);
		}

		$params = [];
		foreach (Product::getParameterProperties() as $parameterProperty) {
			if (isset($values->{$parameterProperty->code}) && $values->{$parameterProperty->code}) {
				$params[$parameterProperty->code] = $values->{$parameterProperty->code};
			}
		}
		$this->setFilterParams($params);

		$this->reload();
	}

	// </editor-fold>
}

interface IProductListFactory
{

	/** @return ProductList */
	function create();
}
