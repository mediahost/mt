<?php

namespace App\AppModule\Presenters;

use App\Components\Grids\Product\IStocksGridFactory;
use App\Components\Grids\Product\StocksGrid;
use App\Components\Product\IStockAddControlFactory;
use App\Components\Product\IStockBasicControlFactory;
use App\Components\Product\IStockCategoryControlFactory;
use App\Components\Product\IStockImageControlFactory;
use App\Components\Product\IStockPriceControlFactory;
use App\Components\Product\IStockQuantityControlFactory;
use App\Components\Product\IStockSeoControlFactory;
use App\Components\Product\StockAddControl;
use App\Components\Product\StockBasicControl;
use App\Components\Product\StockCategoryControl;
use App\Components\Product\StockImageControl;
use App\Components\Product\StockPriceControl;
use App\Components\Product\StockQuantityControl;
use App\Components\Product\StockSeoControl;
use App\Model\Entity\Stock;
use App\TaggedString;
use Kdyby\Doctrine\EntityRepository;

class ProductsPresenter extends BasePresenter
{

	/** @var Stock */
	private $stockEntity;

	/** @var EntityRepository */
	private $stockRepo;

	/** @var IStockAddControlFactory @inject */
	public $iStockAddControlFactory;

	/** @var IStockBasicControlFactory @inject */
	public $iStockBasicControlFactory;

	/** @var IStockPriceControlFactory @inject */
	public $iStockPriceControlFactory;

	/** @var IStockQuantityControlFactory @inject */
	public $iStockQuantityControlFactory;

	/** @var IStockCategoryControlFactory @inject */
	public $iStockCategoryControlFactory;

	/** @var IStockSeoControlFactory @inject */
	public $iStockSeoControlFactory;

	/** @var IStockImageControlFactory @inject */
	public $iStockImageControlFactory;

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

	/** @return StockAddControl */
	public function createComponentStockAddForm()
	{
		$control = $this->iStockAddControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockBasicControl */
	public function createComponentStockBasicForm()
	{
		$control = $this->iStockBasicControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockPriceControl */
	public function createComponentStockPriceForm()
	{
		$control = $this->iStockPriceControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockQuantityControl */
	public function createComponentStockQuantityForm()
	{
		$control = $this->iStockQuantityControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockCategoryControl */
	public function createComponentStockCategoryForm()
	{
		$control = $this->iStockCategoryControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSeoControl */
	public function createComponentStockSeoForm()
	{
		$control = $this->iStockSeoControlFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockImageControl */
	public function createComponentStockImageForm()
	{
		$control = $this->iStockImageControlFactory->create();
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
