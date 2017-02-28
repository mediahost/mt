<?php

namespace App\Components\Product\Form;

use App\Components\BaseControl;
use App\Extensions\Csv\Exceptions\BeforeProcessException;
use App\Extensions\Csv\Exceptions\WhileProcessException;
use App\Extensions\Csv\IParserFactory;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Price;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Stock;
use App\Model\Facade\ShopFacade;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;

class CsvStockImport extends BaseControl
{

	/** @var IParserFactory @inject */
	public $iParserFactory;

	// <editor-fold desc="events">

	/** @var array */
	public $onSuccess = [];

	/** @var array */
	public $onFail = [];

	/** @var array */
	public $onDone = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addUpload('file', 'CSV file');

		$form->addSubmit('save', 'Update Products');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->processFile($values->file);
		$this->onDone();
	}

	private function getColumnsAliases()
	{
		$aliases = [
			0 => 'id',
			1 => 'pohodaCode',
			2 => 'name',
			3 => 'purchasePrice',
		];

		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
		$shopVariants = $shopVariantRepo->findAll();
		foreach ($shopVariants as $shopVariant) {
			/** @var ShopVariant $shopVariant */
			if ($shopVariant->active) {
				$aliases[] = Stock::DEFAULT_PRICE_NAME . $shopVariant->priceCode;
			}
		}

		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findAll();
		foreach ($groups as $group) {
			$aliases[] = 'price' . $group->level;
		}
		return $aliases;
	}

	public function parseRow(array $rowArray)
	{
		$row = ArrayHash::from($rowArray);
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
		/* @var $stock Stock */
		$stock = $stockRepo->find($row->id);
		if ($stock) {
			$stock->product->name = $row->name;
			$stock->setPurchasePrice($row->purchasePrice);

			$groups = $groupRepo->findAll();
			foreach ($groups as $group) {
				/* @var $group Group */
				$priceName = 'price' . $group->level;
				$priceValue = $row->$priceName;

				// temporary skip bonus value
				if ($group->isBonusType()) {
					continue;
				}

				if (preg_match('/^(\d+([,\.]\d+)*)\%$/', $priceValue, $matches)) {
					$number = Price::strToFloat($matches[1]);
					$value = StockPrice::PERCENT_IS_PRICE ? $number : 100 - $number;
					$discount = new Discount($value, Discount::PERCENTAGE);
				} else if (preg_match('/^(\d+([,\.]\d+)*)$/', $priceValue, $matches) && Price::strToFloat($priceValue) > 0) {
					$number = Price::strToFloat($priceValue);
					$discount = new Discount($number, Discount::FIXED_PRICE);
				} else if ($group->isBonusType()) {
					$discount = $group->getDiscount();
				}
				if (isset($discount)) {
					$stock->addDiscount($discount, $group);
				}
			}

			$shopVariants = $shopVariantRepo->findAll();
			foreach ($shopVariants as $shopVariant) {
				/* @var $shopVariant ShopVariant */
				$priceName = Stock::DEFAULT_PRICE_NAME . $shopVariant->priceCode;
				$priceValue = $row->$priceName;
				if ($priceValue) {
					$stock->setSynchronizePrice($shopVariant->shop->priceLetter, $shopVariant->priceNumber, FALSE);
					$stock->setDefaultPrice($priceValue, FALSE, $shopVariant->shop->priceLetter, $shopVariant->priceNumber, FALSE);
				} else {
					$stock->setSynchronizePrice($shopVariant->shop->priceLetter, $shopVariant->priceNumber);
				}
			}
			$stock->recalculatePrices();

			$stockRepo->save($stock);
			return $stock->id;
		}
		return FALSE;
	}

	private function processFile(FileUpload $file)
	{
		$csvParser = $this->iParserFactory->create();
		try {
			$csvParser
					->setCsv(';')
					->setFile($file)
					->setCallback($this->parseRow)
					->setRowChecker($this->getColumnsAliases());
			$executed = $csvParser->execute();
			$this->onSuccess($executed);
		} catch (BeforeProcessException $e) {
			$this->onFail($e->getMessage());
		} catch (WhileProcessException $e) {
			$this->onFail($e->getMessage());
			$this->onSuccess($e->getExecuted());
		}
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

}

interface ICsvStockImportFactory
{

	/** @return CsvStockImport */
	function create();
}
