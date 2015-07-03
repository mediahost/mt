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

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$product = $this->stock->product;
		$product->setCurrentLocale($this->lang);

		$form->addText('name', 'Name')
				->setAttribute('placeholder', $product->name);
		$form->addText('code', 'Code');
		$form->addText('barcode', 'Barcode');
		$form->addCheckSwitch('active', 'Active')
				->setDefaultValue(TRUE);
		$form->addTextArea('perex', 'Perex')
				->setAttribute('placeholder', $product->perex);
		$form->addWysiHtml('description', 'Description', 10)
						->setAttribute('placeholder', $product->description)
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
		$this->stock->code = $values->code;
		$this->stock->active = $values->active;

		$this->stock->product->translateAdd($this->lang)->name = $values->name;
		$this->stock->product->translateAdd($this->lang)->perex = $values->perex;
		$this->stock->product->translateAdd($this->lang)->description = $values->description;
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
			'code' => $this->stock->code,
			'barcode' => $this->stock->barcode,
			'active' => $this->stock->active,
		];
		if ($this->stock->product) {
			$this->stock->product->setCurrentLocale($this->lang);
			$values = [
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
