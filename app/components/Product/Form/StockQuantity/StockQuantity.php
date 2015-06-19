<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Entity\Unit;
use App\Model\Facade\UnitFacade;
use Nette\Utils\ArrayHash;

class StockQuantity extends StockBase
{
	// <editor-fold desc="variables">

	/** @var UnitFacade @inject */
	public $unitFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer());

		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$defaultUnit = $unitRepo->find(1);
		$defaultUnit->setCurrentLocale($this->lang);
		$units = $this->unitFacade->getUnitsList($this->lang);

		$form->addTouchSpin('quantity', 'Quantity')
				->setMax(1000)
				->setPostfix($defaultUnit)
				->setSize(MetronicTextInputBase::SIZE_M)
				->setDefaultValue(0);
		$form->addSelect2('unit', 'Units', $units)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->stock);
	}

	private function load(ArrayHash $values)
	{
		$this->stock->quantity = $values->quantity > 1 ? $values->quantity : 0;
		
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$unit = $unitRepo->find($values->unit);
		$this->stock->product->unit = $unit;

		return $this;
	}

	private function save()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stockRepo->save($this->stock);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'quantity' => $this->stock->quantity,
			'units' => $this->stock->product->unit->id,
		];
		return $values;
	}

}

interface IStockQuantityFactory
{

	/** @return StockQuantity */
	function create();
}
