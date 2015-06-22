<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class StockPrice extends StockBase
{
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer());
		
		
		$form->addCheckSwitch('with_vat', 'Prices are with VAT', 'YES', 'NO')
				->setDefaultValue(FALSE);
		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setRequired();
		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addText('purchase', 'Purchase price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setOption('description', 'Vat included');
		$form->addText('old', 'Old price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setOption('description', 'Vat included');

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
		$this->stock->purchasePrice = $values->purchase > 0 ? $values->purchase : NULL;
		$this->stock->oldPrice = $values->old > 0 ? $values->old : NULL;
		
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);
		$this->stock->vat = $vat;
		$this->stock->setDefaltPrice($values->price, $values->with_vat);

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
			'purchase' => $this->stock->purchasePrice,
			'old' => $this->stock->oldPrice,
		];
		if ($this->stock->price) {
			$values += [
				'price' => $this->stock->price->withoutVat,
				'with_vat' => FALSE,
				'vat' => $this->stock->price->vat->id,
			];
		}
		return $values;
	}
}

interface IStockPriceFactory
{

	/** @return StockPrice */
	function create();
}
