<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class StockBasic extends StockBase
{
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
				->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class('ajax');

		$product = $this->stock->product;
		$product->setCurrentLocale($this->translator->getLocale());

		$form->addText('name', 'Name')
				->setAttribute('placeholder', $product->name);
		$form->addText('pohodaCode', 'Code for Pohoda', NULL, 20)
				->setOption('description', 'Identification for synchronizing')
				->addRule(Form::FILLED, 'Product must be synchronized');
		$form->addText('barcode', 'Barcode');
		$form->addCheckSwitch('active', 'Active');
		$form->addWysiHtml('perex', 'Perex', 4)
						->getControlPrototype()->class[] = 'page-html-content';
		$form->addWysiHtml('description', 'Description', 10)
						->getControlPrototype()->class[] = 'page-html-content';

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
		$this->stock->barcode = $values->barcode;
		$this->stock->pohodaCode = $values->pohodaCode;
		$this->stock->active = $values->active;

		$this->stock->product->translateAdd($this->translator->getLocale())->name = $values->name;
		$this->stock->product->translateAdd($this->translator->getLocale())->perex = $values->perex;
		$this->stock->product->translateAdd($this->translator->getLocale())->description = $values->description;
		$this->stock->product->mergeNewTranslations();
		$this->stock->product->active = $values->active;

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
			'pohodaCode' => $this->stock->pohodaCode,
			'barcode' => $this->stock->barcode,
			'active' => $this->stock->active,
		];
		if ($this->stock->product) {
			$this->stock->product->setCurrentLocale($this->translator->getLocale());
			$values += [
				'name' => $this->stock->product->name,
				'perex' => $this->stock->product->perex,
				'description' => $this->stock->product->description,
			];
		}
		return $values;
	}

}

interface IStockBasicFactory
{

	/** @return StockBasic */
	function create();
}
