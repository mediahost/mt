<?php

namespace App\AppModule\Presenters;

use App\Components\Product\Grid\IStocksGridFactory;
use App\Components\Product\Grid\StocksGrid;
use App\Components\Product\Form\IStockAddFactory;
use App\Components\Product\Form\IStockBasicFactory;
use App\Components\Product\Form\IStockCategoryFactory;
use App\Components\Product\Form\IStockImageFactory;
use App\Components\Product\Form\IStockPriceFactory;
use App\Components\Product\Form\IStockQuantityFactory;
use App\Components\Product\Form\IStockSeoFactory;
use App\Components\Product\Form\StockAdd;
use App\Components\Product\Form\StockBasic;
use App\Components\Product\Form\StockCategory;
use App\Components\Product\Form\StockImage;
use App\Components\Product\Form\StockPrice;
use App\Components\Product\Form\StockQuantity;
use App\Components\Product\Form\StockSeo;
use App\Model\Entity\Stock;
use App\TaggedString;
use Kdyby\Doctrine\EntityRepository;

class ProductsPresenter extends BasePresenter
{

	/** @var Stock */
	private $stockEntity;

	/** @var EntityRepository */
	private $stockRepo;

	/** @var IStockAddFactory @inject */
	public $iStockAddFactory;

	/** @var IStockBasicFactory @inject */
	public $iStockBasicFactory;

	/** @var IStockPriceFactory @inject */
	public $iStockPriceFactory;

	/** @var IStockQuantityFactory @inject */
	public $iStockQuantityFactory;

	/** @var IStockCategoryFactory @inject */
	public $iStockCategoryFactory;

	/** @var IStockSeoFactory @inject */
	public $iStockSeoFactory;

	/** @var IStockImageFactory @inject */
	public $iStockImageFactory;

	/** @var IStocksGridFactory @inject */
	public $iStocksGridFactory;

	protected function startup()
	{
		parent::startup();
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->stockEntity = new Stock();
		$this['stockAddForm']->setStock($this->stockEntity);
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->stockEntity = $this->stockRepo->find($id);
		if (!$this->stockEntity) {
			$this->flashMessage('This product wasn\'t found.', 'error');
			$this->redirect('default');
		} else {
			$this['stockBasicForm']->setStock($this->stockEntity);
			$this['stockPriceForm']->setStock($this->stockEntity);
			$this['stockQuantityForm']->setStock($this->stockEntity);
			$this['stockCategoryForm']->setStock($this->stockEntity);
			$this['stockSeoForm']->setStock($this->stockEntity);
			$this['stockImageForm']->setStock($this->stockEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->stock = $this->stockEntity;
	}

	// <editor-fold desc="forms">

	/** @return StockAdd */
	public function createComponentStockAddForm()
	{
		$control = $this->iStockAddFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockBasic */
	public function createComponentStockBasicForm()
	{
		$control = $this->iStockBasicFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockPrice */
	public function createComponentStockPriceForm()
	{
		$control = $this->iStockPriceFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockQuantity */
	public function createComponentStockQuantityForm()
	{
		$control = $this->iStockQuantityFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockCategory */
	public function createComponentStockCategoryForm()
	{
		$control = $this->iStockCategoryFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSeo */
	public function createComponentStockSeoForm()
	{
		$control = $this->iStockSeoFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockImage */
	public function createComponentStockImageForm()
	{
		$control = $this->iStockImageFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	public function afterStockSave(Stock $stock)
	{
		$message = new TaggedString('Product \'%s\' was successfully saved.', (string) $stock);
		$this->flashMessage($message, 'success');
		$this->redirect('edit', $stock->id);
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return StocksGrid */
	public function createComponentStocksGrid()
	{
		$control = $this->iStocksGridFactory->create();
		$control->setLang($this->lang);
		return $control;
	}

	// </editor-fold>
}
