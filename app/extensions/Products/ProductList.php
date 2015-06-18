<?php

namespace App\Extensions\Products;

use App\Extensions\Products\Components\Paginator;
use App\Model\Entity\Category;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Exception;
use InvalidArgumentException;
use Kdyby\Doctrine\QueryBuilder;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Nette\Templating\FileTemplate;

class ProductList extends Control
{

	const ORDER_ASC = 'ASC';
	const ORDER_DESC = 'DESC';

	/** @var int @persistent */
	public $page = 1;

	/** @var int @persistent */
	public $perPage;

	/** @var array */
	public $sort = array();

	/** @var array */
	public $filter = array();

	/** @var array event on render */
	public $onRender;

	/** @var array event for modifying data */
	public $onFetchData;

	/** @var array */
	protected $perPageList = array(9, 18, 27, 36, 45);

	/** @var int */
	protected $itemsPerRow = 3;

	/** @var int */
	protected $rowsPerPage = 3;

	/** @var QueryBuilder */
	protected $qb;

	/** @var int total count of items */
	protected $count;

	/** @var mixed */
	protected $data;

	/** @var Paginator */
	protected $paginator;

	/** @var ITranslator */
	protected $translator;

	/** @var string */
	protected $lang = 'en';

	/** @var string */
	protected $defaultLang = 'en';

	/**
	 * Sets a QueryBuilder.
	 * @param QueryBuilder $model
	 * @return ProductList
	 */
	public function setQb(QueryBuilder $model)
	{
		$this->qb = $model;

		return $this;
	}

	/**
	 * Sets the number of items per page.
	 * @param int $itemsPerRow
	 * @param int $rowsPerPage
	 * @return ProductList
	 */
	public function setItemsPerPage($itemsPerRow, $rowsPerPage)
	{
		$itemsPerRowInt = (int) $itemsPerRow;
		$rowsPerPageInt = (int) $rowsPerPage;
		$this->itemsPerRow = $itemsPerRowInt;
		$this->rowsPerPage = $rowsPerPageInt;
		$itemsPerPage = $this->getDefaultPerPage();

		if (!in_array($itemsPerPage, $this->perPageList)) {
			$this->perPageList[] = $itemsPerPage;
			sort($this->perPageList);
		}

		return $this;
	}

	/**
	 * Sets filtering.
	 * @param array $filter
	 * @return ProductList
	 */
	public function setFilter(array $filter)
	{
		$this->filter = array_merge($this->filter, $filter);
		return $this;
	}

	/**
	 * Sets sorting.
	 * @param array $sort
	 * @return ProductList
	 * @throws InvalidArgumentException
	 */
	public function setSort(array $sort)
	{
		static $replace = array('asc' => self::ORDER_ASC, 'desc' => self::ORDER_DESC);

		foreach ($sort as $column => $dir) {
			$dir = strtr(strtolower($dir), $replace);
			if (!in_array($dir, $replace)) {
				throw new InvalidArgumentException("Dir '$dir' for column '$column' is not allowed.");
			}

			$this->sort[$column] = $dir;
		}

		return $this;
	}

	/**
	 * Sets items to per-page select.
	 * @param array $perPageList
	 * @return ProductList
	 */
	public function setPerPageList(array $perPageList)
	{
		$this->perPageList = $perPageList;

		return $this;
	}

	/**
	 * Sets translator.
	 * @param ITranslator $translator
	 * @return ProductList
	 */
	public function setTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
		return $this;
	}

	/**
	 * Sets language.
	 * @param string $language
	 * @return ProductList
	 */
	public function setLang($language, $defaultLang = 'en')
	{
		$this->lang = $language;
		$this->defaultLang = $defaultLang;
		return $this;
	}

	/**
	 * Sets custom paginator.
	 * @param Paginator $paginator
	 * @return ProductList
	 */
	public function setPaginator(Paginator $paginator)
	{
		$this->paginator = $paginator;
		return $this;
	}

	/**
	 * Sets file name of custom template.
	 * @param string $file
	 * @return ProductList
	 */
	public function setTemplateFile($file)
	{
		$this->getTemplate()->setFile($file);
		return $this;
	}

	/*	 * ******************************************************************************************* */

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
	 * Returns default per page.
	 * @return int
	 */
	public function getDefaultPerPage()
	{
		return $this->itemsPerRow * $this->rowsPerPage;
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
	 * Returns items per page.
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage === NULL ? $this->getDefaultPerPage() : $this->perPage;
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
	 * Returns translator.
	 * @return ITranslator
	 */
	public function getTranslator()
	{
		return $this->translator;
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
	 * @return array
	 * @internal
	 */
	public function fetchData()
	{
		$data = array();

		// DoctrinePaginator is better if the query uses ManyToMany associations
		$result = $this->qb->getMaxResults() !== NULL || $this->qb->getFirstResult() !== NULL ? new DoctrinePaginator($this->qb->getQuery()) : $this->qb->getQuery()->getResult();

		foreach ($result as $item) {
			// Return only entity itself
			$data[] = is_array($item) ? $item[0] : $item;
		}

		return $data;
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

	/*	 * ******************************************************************************************* */

	protected function applyFiltering()
	{
		$this->filterOnlyActive();
		foreach ($this->filter as $key => $value) {
			switch ($key) {
				case 'category':
					$this->filterByCategory($value);
					break;
			}
		}
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

	protected function applySorting()
	{
		foreach ($this->sort as $key => $value) {
			switch ($key) {
				case 'name':
					$this->qb
							->innerJoin('p.translations', 't')
							->andWhere('t.locale = :lang OR t.locale = :defaultLang')
							->setParameter('lang', $this->lang)
							->setParameter('defaultLang', $this->defaultLang)
							->orderBy('t.name', $value);
					break;
			}
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

	/*	 * ******************************************************************************************* */

	/**
	 * @param int $page
	 * @internal
	 */
	public function handlePage($page)
	{
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
			$this->presenter->payload->grido = TRUE;
			$this->redrawControl();
		} else {
			$this->redirect('this');
		}
	}

	/*	 * ******************************************************************************************* */

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
		$this->renderList();
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
		$this->template->paginator = $this->paginator;
		$this->template->itemsPerRow = $this->itemsPerRow;
		$this->template->lang = $this->lang;
		$this->template->render();
	}

}
