<?php

namespace App\Extensions\Products;

use App\Extensions\Products\Components\Paginator;
use Grido\DataSources\IDataSource;
use Grido\DataSources\Model;
use InvalidArgumentException;
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

	/** @var array @persistent */
	public $sort = array();

	/** @var array @persistent */
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

	/** @var IDataSource */
	protected $model;

	/** @var int total count of items */
	protected $count;

	/** @var mixed */
	protected $data;

	/** @var Paginator */
	protected $paginator;

	/** @var ITranslator */
	protected $translator;

	/**
	 * Sets a model that implements the interface Grido\DataSources\IDataSource or data-source object.
	 * @param mixed $model
	 * @param bool $forceWrapper
	 * @throws InvalidArgumentException
	 * @return ProductList
	 */
	public function setModel($model, $forceWrapper = FALSE)
	{
		$this->model = $model instanceof IDataSource && $forceWrapper === FALSE ? $model : new Model($model);

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
	 * @throws \InvalidArgumentException
	 */
	public function setSort(array $sort)
	{
		static $replace = array('asc' => self::ORDER_ASC, 'desc' => self::ORDER_DESC);

		foreach ($sort as $column => $dir) {
			$dir = strtr(strtolower($dir), $replace);
			if (!in_array($dir, $replace)) {
				throw new \InvalidArgumentException("Dir '$dir' for column '$column' is not allowed.");
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
			$this->count = $this->model->getCount();
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
	 * @throws \Exception
	 * @return array
	 */
	public function getData($applyPaging = TRUE, $useCache = TRUE, $fetch = TRUE)
	{
		if ($this->model === NULL) {
			throw new \Exception('Model cannot be empty, please use method $grid->setModel().');
		}

		$data = $this->data;
		if ($data === NULL || $useCache === FALSE) {
			$this->applyFiltering();
			$this->applySorting();

			if ($applyPaging) {
				$this->applyPaging();
			}

			if ($fetch === FALSE) {
				return $this->model;
			}

			$data = $this->model->getData();

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
	 * @return IDataSource
	 * @internal
	 */
	public function getModel()
	{
		return $this->model;
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

	/**
	 * @internal
	 * @throws \Exception
	 */
	public function render()
	{
		$data = $this->getData();

		if ($this->onRender) {
			$this->onRender($this);
		}

		$this->template->products = $data;
		$this->template->paginator = $this->paginator;
		$this->template->itemsPerRow = $this->itemsPerRow;

		$this->template->render();
	}

	public function renderList()
	{
		$this->template->setFile(__DIR__ . '/templates/productList.latte');
		$this->render();
	}

	public function renderFilter()
	{
		$this->template->setFile(__DIR__ . '/templates/filter.latte');
		$this->render();
	}

	public function renderPaginator()
	{
		$this->template->setFile(__DIR__ . '/templates/paginator.latte');
		$this->render();
	}

	public function renderPerPage()
	{
		$this->template->setFile(__DIR__ . '/templates/perPage.latte');
		$this->render();
	}

	public function renderSorting()
	{
		$this->template->setFile(__DIR__ . '/templates/sorting.latte');
		$this->render();
	}

	protected function applyFiltering()
	{
		
	}

	protected function applySorting()
	{
		
	}

	protected function applyPaging()
	{
		$paginator = $this->getPaginator()
				->setItemCount($this->getCount())
				->setPage($this->page);

		$this->model->limit($paginator->getOffset(), $paginator->getLength());
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

}
