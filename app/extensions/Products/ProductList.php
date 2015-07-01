<?php

namespace App\Extensions\Products;

use App\Extensions\Products\Components\Paginator;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Helpers;
use App\Model\Entity\Category;
use App\Model\Entity\Stock;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Exception;
use h4kuna\Exchange\Exchange;
use InvalidArgumentException;
use Kdyby\Doctrine\QueryBuilder;
use Nette\Application\UI\Control;
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
	const DEFAULT_LANGUAGE = 'en';

	/** @var int @persistent */
	public $page = 1;

	/** @var int @persistent */
	public $perPage;

	/** @var string @persistent */
	public $sort;

	/** @var bool @persistent */
	public $showAvailable = TRUE;

	/** @var bool @persistent */
	public $showNotAvailable = TRUE;

	/** @var int @persistent */
	public $minPrice;

	/** @var int @persistent */
	public $maxPrice;

	/** @var array event on render */
	public $onRender;

	/** @var array event for modifying data */
	public $onFetchData;

	// <editor-fold defaultstate="collapsed" desc="protected variables">

	/** @var array */
	protected $filter = array();

	/** @var array */
	protected $sorting = array();

	/** @var string */
	protected $lang = self::DEFAULT_LANGUAGE;

	/** @var string */
	protected $defaultLang = self::DEFAULT_LANGUAGE;

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
	protected $priceLevel;

	/** @var string */
	protected $priceLevelName = self::DEFAULT_PRICE_LEVEL;

	/** @var int total count of items */
	protected $count;

	/** @var mixed */
	protected $data;

	/** @var Paginator */
	protected $paginator;

	/** @var ITranslator */
	protected $translator;

	/** @var Exchange */
	protected $exchange;

	/** @var string */
	protected $currencySymbol;

	/** @var bool */
	protected $ajax;

	/** @var array */
	protected $limitPrices = [];

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

		$allowedProperties = Stock::getPriceProperties();
		if (array_key_exists($level, $allowedProperties)) {
			$this->priceLevelName = $allowedProperties[$level];
		} else {
			$this->priceLevelName = self::DEFAULT_PRICE_LEVEL;
		}

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
		$this->checkSortDirection($column, $dir);

		if ($asFirst) {
			$this->sorting = [$column => $dir] + $this->sorting;
		} else {
			$this->sorting[$column] = $dir;
		}

		return $this;
	}

	public function setItemsPerPage($itemsPerRow, $rowsPerPage)
	{
		$itemsPerRowInt = (int) $itemsPerRow;
		$rowsPerPageInt = (int) $rowsPerPage;
		$this->itemsPerRow = $itemsPerRowInt;
		$this->rowsPerPage = $rowsPerPageInt;
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
		$this->currencySymbol = $this->exchange[$currency]->getFormat()->getSymbol();

		return $this;
	}

	public function setLang($language, $defaultLang = self::DEFAULT_LANGUAGE)
	{
		$this->lang = $language;
		$this->defaultLang = $defaultLang;

		return $this;
	}

	public function setPaginator(Paginator $paginator)
	{
		$this->paginator = $paginator;

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
	public function getData($applyPaging = TRUE, $useCache = TRUE, $fetch = TRUE)
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

			if ($this->onFetchData) {
				$this->onFetchData($this);
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
			$qb->select("MIN(s.{$this->priceLevelName}) AS minimum, MAX(s.{$this->priceLevelName}) AS maximum");
			$result = $qb->getQuery()->getOneOrNullResult();
			$this->limitPrices = [$result['minimum'], $result['maximum']];
		}
		return $this->limitPrices;
	}

	protected function getLimitPriceMin()
	{
		list($minPrice, $maxPrice) = $this->getLimitPrices();
		return $minPrice;
	}

	protected function getLimitPriceMax()
	{
		list($minPrice, $maxPrice) = $this->getLimitPrices();
		return $maxPrice;
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
		if ($this->showAvailable !== $this->showNotAvailable) {
			$this->filterByInStore($this->showAvailable);
		}
		foreach ($this->filter as $key => $value) {
			switch ($key) {
				case 'category':
					$this->filterByCategory($value);
					break;
				case 'fulltext':
					$this->filterByFulltext($value);
					break;
			}
		}

		// get limit prices before edit price part of query
		$this->getLimitPrices();

		if ($this->minPrice && $this->maxPrice) {
			$this->filterByPrice([$this->minPrice, $this->maxPrice]);
		}
	}

	protected function filterNotDeleted()
	{
		$this->qb
				->andWhere('p.deletedAt IS NULL OR p.deletedAt > :now')
				->setParameter('now', new DateTime());

		return $this;
	}

	protected function filterOnlyActive()
	{
		$this->qb
				->andWhere('p.active = :active')
				->setParameter('active', TRUE);

		return $this;
	}

	protected function filterByCategory($category)
	{
		$this->qb->innerJoin('p.categories', 'categories');
		if (is_array($category)) {
			$this->qb
					->andWhere('categories IN (:categories)')
					->setParameter('categories', $category);
		} else if ($category instanceof Category) {
			$this->qb
					->andWhere('categories = :category')
					->setParameter('category', $category);
		}

		return $this;
	}

	protected function filterByFulltext($text)
	{
		$words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
		$conditions = new Andx();
		foreach ($words as $key => $word) {
			$keyword = 'word' . $key;
			if (strlen($word) > 1) {
				$conditions->add('t.name LIKE :' . $keyword);
				$this->qb->setParameter($keyword, "%$word%");
			}
		}
		$this->appendTranslation();
		$this->qb->andWhere($conditions);

		return $this;
	}

	protected function filterByPrice(array $prices)
	{
		list($lowPrice, $highPrice) = $prices;

		if ($lowPrice >= 0) {
			$this->qb->andWhere("s.{$this->priceLevelName} >= :lowPrice")
					->setParameter('lowPrice', $lowPrice);
		}
		if ($highPrice >= 0) {
			$this->qb->andWhere("s.{$this->priceLevelName} <= :highPrice")
					->setParameter('highPrice', $highPrice);
		}

		return $this;
	}

	protected function filterByInStore($isInStore)
	{
		if ($isInStore) {
			$this->qb->andWhere("s.inStore >= :inStore")
					->setParameter('inStore', 1);
		} else {
			$this->qb->andWhere("s.inStore = :inStore")
					->setParameter('inStore', 0);
		}

		return $this;
	}

	protected function applySorting()
	{
		try {
			$this->addSorting($this->sort);
		} catch (InvalidArgumentException $exc) {
			$this->flashMessage('This sorting method isn\t supported.', 'warning');
		}

		$orderBy = new OrderBy();
		foreach ($this->sorting as $key => $value) {
			switch ($key) {
				case 'name':
					$this->appendTranslation();
					$this->qb
							->andWhere('t.locale = :lang OR t.locale = :defaultLang')
							->setParameter('lang', $this->lang)
							->setParameter('defaultLang', $this->defaultLang)
							->orderBy('t.name', $value);
					$orderBy->add('t.name', $value);
					break;
				case 'price':
					$orderBy->add('s.' . $this->priceLevelName, $value);
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
		$this->template->lang = $this->lang;
		$this->template->ajax = $this->ajax;
		$this->template->render();
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="forms">

	protected function createComponentSortingForm($name)
	{
		$form = new Form($this, $name);
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = 'sendOnChange ' . (!$this->ajax ? : 'ajax');

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
		$form->getElementPrototype()->class = 'sendOnChange ' . (!$this->ajax ? : 'ajax');

		$availabilities = [
			1 => 'Not Available',
			2 => 'In Stock',
		];
		$defaultAvailablity = [];
		if ($this->showNotAvailable) {
			$defaultAvailablity[] = 1;
		}
		if ($this->showAvailable) {
			$defaultAvailablity[] = 2;
		}
		$form->addCheckboxList('availability', NULL, $availabilities)
				->setDefaultValue($defaultAvailablity);

		$limitMinPrice = $this->getLimitPriceMin();
		$limitMaxPrice = $this->getLimitPriceMax();
		$form->addText('price', 'Range:')
				->setAttribute('data-value-min', $this->minPrice)
				->setAttribute('data-value-max', $this->maxPrice)
				->setAttribute('data-min', $limitMinPrice)
				->setAttribute('data-max', $limitMaxPrice)
				->setAttribute('data-glue', ' - ')
				->setAttribute('data-prefix', '')
				->setAttribute('data-suffix', $this->currencySymbol);

		$form->onSuccess[] = $this->processFilterForm;
	}

	public function processFilterForm(Form $param, ArrayHash $values)
	{
		$this->showAvailable = FALSE;
		$this->showNotAvailable = FALSE;
		foreach ($values->availability as $available) {
			switch ($available) {
				case 1:
					$this->showNotAvailable = TRUE;
					break;
				case 2:
					$this->showAvailable = TRUE;
					break;
			}
		}

		$prefix = preg_quote('');
		$suffix = preg_quote($this->currencySymbol);
		$glue = preg_quote(' - ');
		if (preg_match('/^' . $prefix . '(\d+)' . $suffix . $glue . $prefix . '(\d+)' . $suffix . '$/', $values->price, $matches)) {
			$this->minPrice = $matches[1];
			$this->maxPrice = $matches[2];
		}

		$this->reload();
	}

	// </editor-fold>
}
