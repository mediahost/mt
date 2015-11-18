<?php

namespace App\Extensions\Products;

use App\Components\Product\Form\IPrintStockFactory;
use App\Extensions\Products\Components\Paginator;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Helpers;
use App\Model\Entity\Category;
use App\Model\Entity\Parameter;
use App\Model\Entity\Price;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\ProductFacade;
use App\Model\Facade\StockFacade;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Exception;
use h4kuna\Exchange\Exchange;
use InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;
use Nette\Localization\ITranslator;
use Nette\Templating\FileTemplate;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

class ProductList extends Control
{

	const ORDER_ASC = 'ASC';
	const ORDER_DESC = 'DESC';
	const DEFAULT_PRICE_LEVEL = 'defaultPrice';

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

	/** @var int @persistent */
	public $page = 1;

	/** @var int @persistent */
	public $perPage;

	/** @var string @persistent */
	public $sort;

	/** @var bool @persistent */
	public $showOnlyAvailable = FALSE;

	/** @var int @persistent */
	public $minPrice;

	/** @var int @persistent */
	public $maxPrice;

	/** @var array @persistent */
	public $limitPrices = array();

	/** @var int @persistent */
	public $priceLevel;

	/** @var array @persistent */
	public $filter = array();

	/** @var array @persistent */
	public $sorting = array();

	/** @var array event on render */
	public $onRender = [];

	/** @var array event for modifying data */
	public $onFetchData = [];

	/** @var array event for modifying each item */
	public $onEachItem = [];

	/** @var bool show filter as expanded */
	public $expandFilter = FALSE;

	// <editor-fold defaultstate="collapsed" desc="protected variables">

	/** @var array */
	protected $perPageListMultiples = [1, 2, 3, 6];

	/** @var array */
	protected $perPageList = [9, 18, 27, 56];

	/** @var int */
	protected $itemsPerRow = 3;

	/** @var int */
	protected $rowsPerPage = 3;

	/** @var QueryBuilder */
	protected $qb;

	/** @var int */
	protected $limitMinPrice = 0;

	/** @var int */
	protected $limitMaxPrice;

	/** @var string */
	protected $priceLevelName = self::DEFAULT_PRICE_LEVEL;

	/** @var int total count of items */
	protected $count;

	/** @var mixed */
	protected $data;

	/** @var Paginator */
	protected $paginator;

	/** @var string */
	protected $currency;

	/** @var bool */
	protected $ajax;

	// </editor-fold>

	/* 	 ADD FILTERS *************************************************************************************** */

	// <editor-fold defaultstate="collapsed" desc="add filters">

	public function addFilterCategory(Category $category)
	{
		$this->setFilter([
			'category' => implode(',', array_keys($category->childrenArray)),
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName(), $category);
		}
		return $this;
	}

	public function addFilterProducer(Producer $producer)
	{
		$this->setFilter([
			'producer' => $producer->id,
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName(), NULL, $producer);
		}
		return $this;
	}

	public function addFilterLine(ProducerLine $line)
	{
		$this->setFilter([
			'line' => $line->id,
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName(), NULL, $line);
		}
		return $this;
	}

	public function addFilterModel(ProducerModel $model)
	{
		$this->setFilter([
			'model' => $model->id,
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName(), NULL, $model);
		}
		return $this;
	}

	public function addFilterAccessoriesFor(ProducerModel $model)
	{
		$this->setFilter([
			'accessoriesFor' => $model->id,
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName(), NULL, $model);
		}
		return $this;
	}

	public function addFilterFulltext($text)
	{
		$this->setFilter([
			'fulltext' => (string) $text,
		]);
		if (!count($this->limitPrices)) {
			$this->limitPrices = $this->stockFacade->getLimitPrices($this->getPriceLevelName());
		}
		return $this;
	}

	public function addFilterUpdatedFrom($time)
	{
		$this->setFilter([
			'updatedFrom' => (string) $time,
		]);
		return $this;
	}

	public function addFilterParameter($code, $value)
	{
		$this->filter['parameter'][$code] = $value;
		return $this;
	}

	public function resetFilterParameter()
	{
		$this->filter['parameter'] = [];
		return $this;
	}

	public function resetFilter()
	{
		$this->filter = [];
		return $this;
	}

	// </editor-fold>

	/* 	 SETTERS ******************************************************************************************* */

	// <editor-fold defaultstate="collapsed" desc="public setters">

	public function setQb(QueryBuilder $model)
	{
		$this->qb = $model;

		return $this;
	}

	public function setPriceLevel($level)
	{
		$this->priceLevel = $level;
		return $this;
	}

	/**
	 * Add filtering.
	 * @param array $filter
	 * @return ProductList
	 */
	public function setFilter(array $filter)
	{
		$this->filter = array_merge($this->filter, $filter);
		return $this;
	}

	/**
	 * Set sorting.
	 * @param array $sort
	 * @return ProductList
	 * @throws InvalidArgumentException
	 */
	public function setSorting($sort)
	{
		foreach ($sort as $column => $dir) {
			$this->checkSortDirection($column, $dir);
			$this->sorting[$column] = $dir;
		}

		return $this;
	}

	public function addSorting($column, $dir = NULL, $asFirst = TRUE)
	{
		if ($dir === NULL && preg_match('/^(\w+)_(\w+)$/', $column, $matches)) {
			$column = $matches[1];
			$dir = $matches[2];
		}
		if (!$column) {
			return $this;
		}
		$this->checkSortDirection($column, $dir);

		if ($asFirst) {
			$this->sorting = [$column => $dir] + $this->sorting;
		} else {
			$this->sorting[$column] = $dir;
		}

		return $this;
	}

	public function setItemsPerPage($itemsPerRow, $rowsPerPage = 1)
	{
		$itemsPerRowInt = (int) $itemsPerRow;
		$rowsPerPageInt = (int) $rowsPerPage;
		$this->itemsPerRow = $itemsPerRowInt ? $itemsPerRowInt : 1;
		$this->rowsPerPage = $rowsPerPageInt ? $rowsPerPageInt : 1;
		$itemsPerPage = $this->getDefaultPerPage();

		$this->resetPerPageList($itemsPerPage);

		return $this;
	}

	public function setPerPageList(array $perPageList)
	{
		$this->perPageList = $perPageList;

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

	public function setPaginator(Paginator $paginator)
	{
		$this->paginator = $paginator;

		return $this;
	}

	public function setPage($page)
	{
		$this->page = $page;

		return $this;
	}

	public function setAjax($value = TRUE)
	{
		$this->ajax = $value;

		return $this;
	}

	public function setTemplateFile($file)
	{
		$this->getTemplate()->setFile($file);

		return $this;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="protected setters">

	protected function checkSortDirection($column, &$dir)
	{
		$replace = array('asc' => self::ORDER_ASC, 'desc' => self::ORDER_DESC);
		$dir = strtr(strtolower($dir), $replace);
		if (!in_array($dir, $replace)) {
			throw new InvalidArgumentException("Dir '$dir' for column '$column' is not allowed.");
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

	// </editor-fold>

	/* 	 GETTERS ******************************************************************************************* */

	// <editor-fold defaultstate="collapsed" desc="public getters">

	/**
	 * Returns items per page.
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage === NULL ? $this->getDefaultPerPage() : $this->perPage;
	}

	/**
	 * Returns list of possible items per page.
	 * @return array
	 */
	public function getPerPageList()
	{
		return $this->perPageList;
	}

	/**
	 * Returns translator.
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->translator;
	}

	/**
	 * @return Paginator
	 * @internal
	 */
	public function getPaginator()
	{
		if ($this->paginator === NULL) {
			$this->paginator = new Paginator();
			$this->paginator->setItemsPerPage($this->getPerPage());
		}

		return $this->paginator;
	}

	/**
	 * @return QueryBuilder
	 * @internal
	 */
	public function getQb()
	{
		return $this->qb;
	}

	/**
	 * Returns total count of data.
	 * @return int
	 */
	public function getCount()
	{
		if ($this->count === NULL) {
			$paginator = new DoctrinePaginator($this->qb->getQuery());
			$this->count = $paginator->count();
		}

		return $this->count;
	}

	/**
	 * Returns fetched data.
	 * @param bool $applyPaging
	 * @param bool $useCache
	 * @param bool $fetch
	 * @throws Exception
	 * @return array
	 */
	public function getData($applyPaging = TRUE, $useCache = TRUE, $fetch = TRUE, $prepare = TRUE)
	{
		if ($this->qb === NULL) {
			throw new Exception('Model cannot be empty, please use method $productList->setQb().');
		}

		$data = $this->data;
		if ($data === NULL || $useCache === FALSE) {

			$this->applyFiltering();
			$this->applySorting();

			if ($applyPaging) {
				$this->applyPaging();
			}

			if ($fetch === FALSE) {
				return $this->qb;
			}

			$data = $this->fetchData();

			if ($useCache === TRUE) {
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

	/**
	 * @return array
	 * @internal
	 */
	public function fetchData()
	{
		$data = array();

		// DoctrinePaginator is better if the query uses ManyToMany associations
		if ($this->qb->getMaxResults() !== NULL || $this->qb->getFirstResult() !== NULL) {
			$result = new DoctrinePaginator($this->qb->getQuery());
		} else {
			$result = $this->qb->getQuery()->getResult();
		}

		foreach ($result as $item) {
			// Return only entity itself
			$data[] = is_array($item) ? $item[0] : $item;
		}

		return $data;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="protected getters">

	/**
	 * Returns default per page.
	 * @return int
	 */
	protected function getDefaultPerPage()
	{
		return $this->itemsPerRow * $this->rowsPerPage;
	}

	protected function getDefaultSortingMethod()
	{
		$sorting = $this->sorting;
		list($name, $order) = each($sorting);
		$code = Helpers::concatStrings('_', Strings::webalize($name), Strings::webalize($order));

		return $code;
	}

	/** @return array */
	protected function getItemsForCountSelect()
	{
		return array_combine($this->perPageList, $this->perPageList);
	}

	/** @return array */
	protected function getSortingMethods()
	{
		return [
			'price_asc' => 'Price (Low > High)',
			'price_desc' => 'Price (High > Low)',
			'name_asc' => 'Name (A - Z)',
			'name_desc' => 'Name (Z - A)',
		];
	}

	protected function getLimitPrices()
	{
		if (!count($this->limitPrices)) {
			$qb = clone $this->qb;
			$qb->select("MIN(s.{$this->getPriceLevelName()}) AS minimum, MAX(s.{$this->getPriceLevelName()}) AS maximum");
			$result = $qb->getQuery()->getOneOrNullResult();
			$this->limitPrices = [$result['minimum'], $result['maximum']];
		}
		return $this->limitPrices;
	}

	protected function getLimitPriceMin()
	{
		if ($this->limitMinPrice === NULL) {
			list($this->limitMinPrice, $maxPrice) = $this->getLimitPrices();
		}
		return $this->limitMinPrice;
	}

	protected function getLimitPriceMax()
	{
		if ($this->limitMaxPrice === NULL) {
			list($minPrice, $this->limitMaxPrice) = $this->getLimitPrices();
		}
		return $this->limitMaxPrice;
	}

	protected function getPriceLevelName()
	{
		if (!$this->priceLevelName && $this->priceLevel) {
			$allowedProperties = Stock::getPriceProperties();
			if (array_key_exists($this->priceLevel, $allowedProperties)) {
				$this->priceLevelName = $allowedProperties[$this->priceLevel];
			} else {
				$this->priceLevelName = self::DEFAULT_PRICE_LEVEL;
			}
		}
		return $this->priceLevelName;
	}

	protected function getCurrencySymbol()
	{
		if ($this->currency) {
			return $this->exchange[$this->currency]->getFormat()->getSymbol();
		}
		return NULL;
	}

	// </editor-fold>

	/* 	 SORTING & FILTERING & PAGING ********************************************************************** */

	// <editor-fold defaultstate="collapsed" desc="filter, sort, paging">

	private function appendTranslation()
	{
		$dql = $this->qb->getDQLParts();
		foreach ($this->qb->getRootAliases() as $rootAlias) {
			foreach ($dql['join'][$rootAlias] as $join) {
				if ($join->getAlias() === 't') {
					return $this;
				}
			}
		}
		$this->qb->innerJoin('p.translations', 't');

		return $this;
	}

	protected function applyFiltering()
	{
		$this->filterNotDeleted();
		$this->filterOnlyActive();
		$this->filterByInStore($this->showOnlyAvailable);
		foreach ($this->filter as $key => $value) {
			switch ($key) {
				case 'category':
					$this->filterByCategory($value);
					break;
				case 'producer':
					$this->filterByProducer($value);
					break;
				case 'line':
					$this->filterByLine($value);
					break;
				case 'model':
					$this->filterByModel($value);
					break;
				case 'accessoriesFor':
					$this->filterByAccessoriesFor($value);
					break;
				case 'fulltext':
					$this->filterByFulltext($value);
					break;
				case 'updatedFrom':
					$this->filterByUpdatedFrom($value);
					break;
				case 'parameter':
					$this->filterByParameters($value);
					break;
			}
		}

		// get limit prices before edit price part of query
		$this->getLimitPrices();

		if ($this->maxPrice) {
			$this->filterByPrice([$this->minPrice, $this->maxPrice]);
		}
	}

	protected function filterNotDeleted()
	{
		$this->qb
				->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
				->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
				->setParameter('now', new DateTime());

		return $this;
	}

	protected function filterOnlyActive()
	{
		$this->qb
				->andWhere('s.active = :active')
				->andWhere('p.active = :active')
				->setParameter('active', TRUE);

		return $this;
	}

	protected function filterByUpdatedFrom($time)
	{
		$dateTime = $time instanceof DateTime ? $time : DateTime::from($time);
		$this->qb
				->andWhere('s.updatedAt >= :time OR p.updatedAt >= :time')
				->setParameter('time', $dateTime);

		return $this;
	}

	protected function filterByCategory($category)
	{
		$category = is_string($category) ? explode(',', $category) : $category;
		$this->qb->innerJoin('p.categories', 'categories');
		if (is_array($category)) {
			$this->qb
					->andWhere('categories IN (:categories)')
					->setParameter('categories', $category);
		} else {
			$this->qb
					->andWhere('categories = :category')
					->setParameter('category', $category);
		}

		return $this;
	}

	protected function filterByProducer($producer)
	{
		if (is_array($producer)) {
			$this->qb
					->andWhere('p.producer IN (:producers)')
					->setParameter('producers', $producer);
		} else {
			$this->qb
					->andWhere('p.producer = :producer')
					->setParameter('producer', $producer);
		}

		return $this;
	}

	protected function filterByLine($line)
	{
		if (is_array($line)) {
			$this->qb
					->andWhere('p.producerLine IN (:lines)')
					->setParameter('lines', $line);
		} else {
			$this->qb
					->andWhere('p.producerLine = :line')
					->setParameter('line', $line);
		}

		return $this;
	}

	protected function filterByModel($model)
	{
		if (is_array($model)) {
			$this->qb
					->andWhere('p.producerModel IN (:models)')
					->setParameter('models', $model);
		} else {
			$this->qb
					->andWhere('p.producerModel = :model')
					->setParameter('model', $model);
		}

		return $this;
	}

	protected function filterByAccessoriesFor($model)
	{
		$this->qb->innerJoin('p.accessoriesFor', 'accessoriesFor');
		if (is_array($model)) {
			$this->qb
					->andWhere('accessoriesFor IN (:models)')
					->setParameter('models', $model);
		} else {
			$this->qb
					->andWhere('accessoriesFor = :model')
					->setParameter('model', $model);
		}

		return $this;
	}

	protected function filterByFulltext($text)
	{
		$words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
		$conditions = new Andx();
		foreach ($words as $key => $word) {
			$keyword = 'word' . $key;
			$conditions->add('t.name LIKE :' . $keyword);
			$this->qb->setParameter($keyword, "%$word%");
		}
		$this->appendTranslation();
		$this->qb->andWhere($conditions);

		return $this;
	}

	protected function filterByPrice(array $prices)
	{
		// prices are inserted with vat
		list($lowPriceValue, $highPriceValue) = $prices;

		// recount price to price without vat
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vatPricesLow = [];
		$vatPricesHigh = [];
		foreach ($vatRepo->findAll() as $vat) {
			if ($lowPriceValue > 0) {
				$vatPricesLow[$vat->id] = new Price($vat, $lowPriceValue, FALSE);
			}
			if ($highPriceValue > 0) {
				$vatPricesHigh[$vat->id] = new Price($vat, $highPriceValue, FALSE);
			}
		}

		$vatParameterAdd = FALSE;
		if (count($vatPricesLow)) {
			$condition = NULL;
			foreach ($vatPricesLow as $vatId => $price) {
				$conditionAdd = "s.vat = :vat{$vatId} AND s.{$this->getPriceLevelName()} >= :lowPrice{$vatId}";
				$condition = Helpers::concatStrings(') OR (', $condition, $conditionAdd);
				$this->qb->setParameter("lowPrice{$vatId}", $price->withoutVat);
				$this->qb->setParameter("vat{$vatId}", $vatId);
				$vatParameterAdd = TRUE;
			}
			$this->qb->andWhere('(' . $condition . ')');
		}
		if (count($vatPricesHigh)) {
			$condition = NULL;
			foreach ($vatPricesHigh as $vatId => $price) {
				$conditionAdd = "s.vat = :vat{$vatId} AND s.{$this->getPriceLevelName()} <= :highPrice{$vatId}";
				$condition = Helpers::concatStrings(') OR (', $condition, $conditionAdd);
				$this->qb->setParameter("highPrice{$vatId}", $price->withoutVat);
				if (!$vatParameterAdd) {
					$this->qb->setParameter("vat{$vatId}", $vatId);
				}
			}
			$this->qb->andWhere('(' . $condition . ')');
		}

		return $this;
	}

	protected function filterByInStore($isInStore)
	{
		if ($isInStore) {
			$this->qb->andWhere("s.inStore >= :inStore")
					->setParameter('inStore', 1);
		}

		return $this;
	}

	protected function filterByParameters(array $parameters)
	{
		foreach ($parameters as $code => $value) {
			$paramKey = 'param' . $code;
			if (Parameter::checkCodeHasType($code, Parameter::STRING)) {
				$operator = 'LIKE';
			} else {
				$operator = '=';
			}
			$this->qb->andWhere("p.parameter{$code} {$operator} :{$paramKey}")
					->setParameter($paramKey, $value);
		}
		return $this;
	}

	protected function applySorting()
	{
		try {
			$this->addSorting($this->sort);
		} catch (InvalidArgumentException $exc) {
			throw new ProductListException('This sorting method isn\'t supported.');
		}

		$orderBy = new OrderBy();
		foreach ($this->sorting as $key => $value) {
			switch ($key) {
				case 'name':
					$this->appendTranslation();
					$this->qb
							->andWhere('t.locale = :locale OR t.locale = :defaultLocale')
							->setParameter('locale', $this->translator->getDefaultLocale())
							->setParameter('defaultLocale', $this->translator->getDefaultLocale())
							->orderBy('t.name', $value);
					$orderBy->add('t.name', $value);
					break;
				case 'price':
					$orderBy->add('s.' . $this->getPriceLevelName(), $value);
					break;
			}
		}
		if ($orderBy->count()) {
			$this->qb->orderBy($orderBy);
		}
	}

	protected function applyPaging()
	{
		$paginator = $this->getPaginator()
				->setItemCount($this->getCount())
				->setPage($this->page);

		$offset = $paginator->getOffset();
		$limit = $paginator->getLength();
		$this->qb
				->setFirstResult($offset)
				->setMaxResults($limit);
	}

	// </editor-fold>

	/* 	 SIGNALS ******************************************************************************************* */

	// <editor-fold defaultstate="collapsed" desc="signals">

	/**
	 * @param int $page
	 * @internal
	 */
	public function handlePage($page)
	{
		$this->page = $page;
		$this->reload();
	}

	/**
	 * Refresh wrapper.
	 * @return void
	 * @internal
	 */
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
		$template->registerHelper('translate', callback($this->getTranslator(), 'translate'));

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
		$this->template->expandFilter = $this->expandFilter;
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

	private function templateRender()
	{
		$data = $this->getData();

		if ($this->onRender) {
			$this->onRender($this);
		}

		$this->template->stocks = $data;
		$this->template->priceLevel = $this->priceLevel;
		$this->template->paginator = $this->paginator;
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

	protected function createComponentSortingForm($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = ['sendOnChange', 'loadingNoOverlay', !$this->ajax ? : 'ajax'];

		$form->addSelect('sort', 'Sort by', $this->getSortingMethods())
				->setDefaultValue($this->getDefaultSortingMethod())
				->getControlPrototype()->class('input-sm');

		$form->addSelect('perPage', 'Show', $this->getItemsForCountSelect())
				->getControlPrototype()->class('input-sm');
		$defaultPerPage = array_search($this->perPage, $this->perPageList);
		if ($defaultPerPage !== FALSE) {
			$form['perPage']->setDefaultValue($this->perPage);
		}

		$form->onSuccess[] = $this->processSortingForm;
	}

	public function processSortingForm(Form $param, ArrayHash $values)
	{
		$this->sort = $values->sort;
		$key = array_search($values->perPage, $this->perPageList);
		if ($key !== FALSE) {
			$this->perPage = $key ? $values->perPage : NULL;
		}
		$this->reload();
	}

	protected function createComponentFilterForm($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = ['sendOnChange', 'loadingNoOverlay', !$this->ajax ? : 'ajax'];

		$form->addCheckbox('onlyAvailable', 'Only Available')
				->setDefaultValue($this->showOnlyAvailable);

		$limitMinPriceRaw = $this->getLimitPriceMin();
		$limitMaxPriceRaw = $this->getLimitPriceMax();
		$limitMinPrice = floor($this->exchange->change($limitMinPriceRaw));
		$limitMaxPrice = ceil($this->exchange->change($limitMaxPriceRaw));

		$fromValue = $this->minPrice ? floor($this->exchange->change($this->minPrice)) : NULL;
		$toValue = $this->maxPrice ? ceil($this->exchange->change($this->maxPrice)) : NULL;

		$form->addText('price', 'Range:')
				->setAttribute('data-min', $limitMinPrice)
				->setAttribute('data-max', $limitMaxPrice)
				->setAttribute('data-from', $fromValue)
				->setAttribute('data-to', $toValue)
				->setAttribute('data-type', 'double')
				->setAttribute('data-step', '1')
				->setAttribute('data-hasgrid', 'false')
				->setAttribute('data-postfix', ' ' . $this->getCurrencySymbol());

		$paramRepo = $this->em->getRepository(Parameter::getClassName());
		$allParams = $paramRepo->findAll();
		$defaultValues = [];
		foreach ($allParams as $parameter) {
			$parameter->setCurrentLocale($this->translator->getLocale());
			switch ($parameter->type) {
				case Parameter::BOOLEAN:
					$form->addCheckbox($parameter->code, $parameter->name);
					break;
				case Parameter::STRING:
					$items = [NULL => ''];
					$items += $this->productFacade->getParameterValues($parameter);
					$form->addSelect2($parameter->code, $parameter->name, $items);
					break;
			}
			if (isset($this->filter['parameter'][$parameter->code])) {
				$defaultValues[$parameter->code] = $this->filter['parameter'][$parameter->code];
			}
		}
		$form->setDefaults($defaultValues);

		$form->onSuccess[] = $this->processFilterForm;
	}

	public function processFilterForm(Form $form, ArrayHash $values)
	{
		$this->showOnlyAvailable = $values->onlyAvailable;

		$glue = preg_quote(';');
		if (preg_match('/^(\d+)' . $glue . '(\d+)$/', $values->price, $matches)) {
			$minPriceRaw = $matches[1];
			$maxPriceRaw = $matches[2];
			$this->minPrice = $this->exchange->change($minPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$this->maxPrice = $this->exchange->change($maxPriceRaw, $this->exchange->getWeb(), $this->exchange->getDefault());
			$form['price']
					->setAttribute('data-from', $minPriceRaw)
					->setAttribute('data-to', $maxPriceRaw);
		}

		$this->resetFilterParameter();
		foreach (Product::getParameterProperties() as $parameterProperty) {
			if (isset($values->{$parameterProperty->code}) && $values->{$parameterProperty->code}) {
				$this->addFilterParameter($parameterProperty->code, $values->{$parameterProperty->code});
			}
		}

		$this->expandFilter = TRUE;
		$this->reload();
	}

	// </editor-fold>
}

interface IProductListFactory
{

	/** @return ProductList */
	function create();
}
