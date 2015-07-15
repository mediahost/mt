<?php

namespace App\AppModule\Presenters;

use App\Components\Product\Form\CsvStockImport;
use App\Components\Product\Form\ICsvStockImportFactory;
use App\Components\Product\Form\IStockAddFactory;
use App\Components\Product\Form\IStockBasicFactory;
use App\Components\Product\Form\IStockCategoryFactory;
use App\Components\Product\Form\IStockImageFactory;
use App\Components\Product\Form\IStockParameterFactory;
use App\Components\Product\Form\IStockPriceFactory;
use App\Components\Product\Form\IStockQuantityFactory;
use App\Components\Product\Form\IStockSeoFactory;
use App\Components\Product\Form\IStockSignFactory;
use App\Components\Product\Form\IStockSimilarFactory;
use App\Components\Product\Form\StockAdd;
use App\Components\Product\Form\StockBasic;
use App\Components\Product\Form\StockCategory;
use App\Components\Product\Form\StockImage;
use App\Components\Product\Form\StockParameter;
use App\Components\Product\Form\StockPrice;
use App\Components\Product\Form\StockQuantity;
use App\Components\Product\Form\StockSeo;
use App\Components\Product\Form\StockSign;
use App\Components\Product\Form\StockSimilar;
use App\Components\Product\Grid\IStocksGridFactory;
use App\Components\Product\Grid\StocksGrid;
use App\Model\Entity\Image;
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

	/** @var IStockParameterFactory @inject */
	public $iStockParameterFactory;

	/** @var IStockSignFactory @inject */
	public $iStockSignFactory;

	/** @var IStockSimilarFactory @inject */
	public $iStockSimilarFactory;

	/** @var ICsvStockImportFactory @inject */
	public $iCsvStockImportFactory;

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
			$this->flashMessage('This product wasn\'t found.', 'warning');
			$this->redirect('default');
		} else {
			$this->stockEntity->product->setCurrentLocale($this->locale);
			$this['stockBasicForm']->setStock($this->stockEntity);
			$this['stockPriceForm']->setStock($this->stockEntity);
			$this['stockQuantityForm']->setStock($this->stockEntity);
			$this['stockCategoryForm']->setStock($this->stockEntity);
			$this['stockSeoForm']->setStock($this->stockEntity);
			$this['stockImageForm']->setStock($this->stockEntity);
			$this['stockParameterForm']->setStock($this->stockEntity);
			$this['stockSignForm']->setStock($this->stockEntity);
			$this['stockSimilarForm']->setStock($this->stockEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->stock = $this->stockEntity;
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('deleteImage')
	 */
	public function handleDeleteImage($imageId)
	{
		$imageRepo = $this->em->getRepository(Image::getClassName());
		$image = $imageRepo->find($imageId);
		if ($image && $this->stockEntity->product->hasOtherImage($image)) {
			$imageRepo->delete($image);
			$this->flashMessage('Image was successfully deleted', 'success');
		} else {
			$this->flashMessage('This image wasn\'t found for this product.', 'warning');
		}
		$this->redirect('this');
	}

	// <editor-fold desc="forms">

	/** @return StockAdd */
	public function createComponentStockAddForm()
	{
		$control = $this->iStockAddFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockBasic */
	public function createComponentStockBasicForm()
	{
		$control = $this->iStockBasicFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockPrice */
	public function createComponentStockPriceForm()
	{
		$control = $this->iStockPriceFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockQuantity */
	public function createComponentStockQuantityForm()
	{
		$control = $this->iStockQuantityFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockCategory */
	public function createComponentStockCategoryForm()
	{
		$control = $this->iStockCategoryFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSeo */
	public function createComponentStockSeoForm()
	{
		$control = $this->iStockSeoFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockImage */
	public function createComponentStockImageForm()
	{
		$control = $this->iStockImageFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockParameter */
	public function createComponentStockParameterForm()
	{
		$control = $this->iStockParameterFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSign */
	public function createComponentStockSignForm()
	{
		$control = $this->iStockSignFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSimilar */
	public function createComponentStockSimilarForm()
	{
		$control = $this->iStockSimilarFactory->create();
		$control->setLang($this->locale);
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	public function afterStockSave(Stock $stock)
	{
		$message = new TaggedString('Product \'%s\' was successfully saved.', (string) $stock);
		$this->flashMessage($message, 'success');
		$this->redirect('edit', $stock->id);
	}

	/** @return CsvStockImport */
	public function createComponentCsvImportForm()
	{
		$control = $this->iCsvStockImportFactory->create();
		$control->setLang($this->locale);
		$control->onSuccess = function (array $importedStocks) {
			$count = count($importedStocks);
			if ($count) {
				$message = new TaggedString('%s product was successfully updated.', $count);
				$message->setForm($count);
				$type = 'success';
			} else {
				$message = 'No product was updated';
				$type = 'warning';
			}
			$this->flashMessage($message, $type);
		};
		$control->onFail = function ($message) {
			$this->flashMessage($message, 'danger');
		};
		$control->onDone = function () {
			$this->redirect('this');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return StocksGrid */
	public function createComponentStocksGrid()
	{
		$control = $this->iStocksGridFactory->create();
		$control->setLang($this->locale);
		return $control;
	}

	// </editor-fold>
}
