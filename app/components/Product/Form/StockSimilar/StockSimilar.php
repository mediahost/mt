<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\ProducerFacade;
use Nette\Utils\ArrayHash;

class StockSimilar extends StockBase
{
	// <editor-fold desc="variables">

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer());
		$form->getElementPrototype()->class('ajax');

		$similars = [];
		$defaults = [];
		foreach ($this->stock->product->similars as $product) {
			$product->setCurrentLocale($this->lang);
			$similars[$product->id] = (string) $product;
			$defaults[] = $product->id;
		}
		
		$form->addMultiSelect2('similars', 'Similars', $similars)
				->setDefaultValue($defaults)
				->setAutocomplete('autocompleteProducts');

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
		$productRepo = $this->em->getRepository(Product::getClassName());
		$similars = [];
		foreach ($values->similars as $similarId) {
			$similars[] = $productRepo->find($similarId);
		}
		$this->stock->product->similars = $similars;

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
		$values = [];
		return $values;
	}

}

interface IStockSimilarFactory
{

	/** @return StockSimilar */
	function create();
}
