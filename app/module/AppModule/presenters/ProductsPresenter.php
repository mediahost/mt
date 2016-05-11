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
use App\Model\Entity\Category;
use App\Model\Entity\Image;
use App\Model\Entity\Stock;
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
	 * @privilege('export')
	 */
	public function actionExport(array $ids)
	{
		$this['stocksGrid']->setIds($ids);
		$this['stocksGrid']->getExport()->handleExport();
	}

	private function getStocksIds(Category $category, array &$ids)
	{
		foreach ($category->products as $product) {
			$ids[] = $product->stock->id;
		}
		foreach ($category->children as $child) {
			$this->getStocksIds($child, $ids);
		}
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('exportCategory')
	 */
	public function actionExportCategory(array $categoryIds)
	{
		$ids = [];
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		foreach ($categoryIds as $categoryId) {
			/** @var Category $category */
			$category = $categoryRepo->find($categoryId);
			if ($category) {
				$this->getStocksIds($category, $ids);
			}
		}
		$this['stocksGrid']->setIds($ids);
		$this['stocksGrid']->getExport()->handleExport();
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
		if (!$this->stockEntity || $this->stockEntity->isDeleted()) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Product')]);
			$this->flashMessage($message, 'warning');
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

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('editPrices')
	 */
	public function actionEditPrices($id)
	{
		$this->stockEntity = $this->stockRepo->find($id);
		if (!$this->stockEntity || $this->stockEntity->isDeleted()) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Product')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this->stockEntity->product->setCurrentLocale($this->locale);
			$this['stockPriceForm']->setStock($this->stockEntity);
			$this['stockPriceForm']->onAfterSave = function (Stock $stock) {
				$message = $this->translator->translate('successfullySaved', NULL, [
					'type' => $this->translator->translate('Product'), 'name' => (string) $stock
				]);
				$this->flashMessage($message, 'success');

				if (!$this->isAjax()) {
					$this->redirect('default');
				}
			};
			$this->template->stock = $this->stockEntity;
		}
	}

	public function renderEdit()
	{
		$this->template->stock = $this->stockEntity;
	}

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$this->stockEntity = $this->stockRepo->find($id);
		if (!$this->stockEntity) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Product')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->stockRepo->delete($this->stockEntity);
				$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Product')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDelete', NULL, ['name' => $this->translator->translate('Product')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
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
			$message = $this->translator->translate('successfullyDeleted', NULL, ['name' => $this->translator->translate('Image')]);
			$this->flashMessage($message, 'success');
		} else {
			$message = $this->translator->translate('This image wasn\'t found for this product.');
			$this->flashMessage($message, 'warning');
		}
		$this->redirect('this');
	}

	// <editor-fold desc="forms">

	/** @return StockAdd */
	public function createComponentStockAddForm()
	{
		$control = $this->iStockAddFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockBasic */
	public function createComponentStockBasicForm()
	{
		$control = $this->iStockBasicFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockPrice */
	public function createComponentStockPriceForm()
	{
		$control = $this->iStockPriceFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockQuantity */
	public function createComponentStockQuantityForm()
	{
		$control = $this->iStockQuantityFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockCategory */
	public function createComponentStockCategoryForm()
	{
		$control = $this->iStockCategoryFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSeo */
	public function createComponentStockSeoForm()
	{
		$control = $this->iStockSeoFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockImage */
	public function createComponentStockImageForm()
	{
		$control = $this->iStockImageFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockParameter */
	public function createComponentStockParameterForm()
	{
		$control = $this->iStockParameterFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSign */
	public function createComponentStockSignForm()
	{
		$control = $this->iStockSignFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	/** @return StockSimilar */
	public function createComponentStockSimilarForm()
	{
		$control = $this->iStockSimilarFactory->create();
		$control->onAfterSave = $this->afterStockSave;
		return $control;
	}

	public function afterStockSave(Stock $stock)
	{
		$message = $this->translator->translate('successfullySaved', NULL, [
			'type' => $this->translator->translate('Product'), 'name' => (string) $stock
		]);
		$this->flashMessage($message, 'success');

		if ($this->isAjax()) {
			$this->redrawControl();
		} else {
			$this->redirect('edit', $stock->id);
		}
	}

	/** @return CsvStockImport */
	public function createComponentCsvImportForm()
	{
		$control = $this->iCsvStockImportFactory->create();
		$control->onSuccess = function (array $importedStocks) {
			$count = count($importedStocks);
			if ($count) {
				$message = $this->translator->translate('%count% product was successfully updated.', $count, ['count' => $count]);
				$type = 'success';
			} else {
				$message = $this->translator->translate('No product was updated');
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
		return $control;
	}

	// </editor-fold>
}
