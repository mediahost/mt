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
use App\Model\Entity\Stock;
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
			4 => 'defaultPrice',
		];
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
		/* @var $stock Stock */
		$stock = $stockRepo->find($row->id);
		if ($stock) {
			$groups = $groupRepo->findAll();
			$stock->product->name = $row->name;
			$stock->setPurchasePrice($row->purchasePrice);
			foreach ($groups as $group) {
				$priceName = 'price' . $group->level;
				$priceValue = $row->$priceName;
				if (preg_match('/^(\d+)\%$/', $priceValue, $matches)) {
					$value = StockPrice::PERCENT_IS_PRICE ? $matches[1] : 100 - $matches[1];
					$discount = new Discount($value, Discount::PERCENTAGE);
				} else {
					$discount = new Discount($priceValue, Discount::FIXED_PRICE);
				}
				$stock->addDiscount($discount, $group);
			}
			$stock->setDefaltPrice($row->defaultPrice);
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
