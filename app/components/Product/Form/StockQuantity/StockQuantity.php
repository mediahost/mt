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

		$form = new Form();
		$form->setTranslator($this->translator)
				->setRenderer(new MetronicHorizontalFormRenderer());
		$form->getElementPrototype()->class('ajax');

		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$defaultUnit = $unitRepo->find(1);
		$defaultUnit->setCurrentLocale($this->translator->getLocale());
		$units = $this->unitFacade->getUnitsList($this->translator->getLocale());

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

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->stock);
		} else {
			$this->redrawControl();
		}
	}

	private function load(Form &$form, ArrayHash $values)
	{
		if ($values->quantity < $this->stock->lock) {
			$form['quantity']->addError($this->translator->translate('There are %count% products locked', $this->stock->lock));
			$form['quantity']->setValue($this->stock->lock);
		} else {
			$this->stock->quantity = $values->quantity > 1 ? $values->quantity : 0;

			$unitRepo = $this->em->getRepository(Unit::getClassName());
			$unit = $unitRepo->find($values->unit);
			$this->stock->product->unit = $unit;
		}

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
