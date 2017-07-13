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
			$product->setCurrentLocale($this->translator->getLocale());
			$similars[$product->id] = (string)$product;
			$defaults[] = $product->id;
		}

		$form->addMultiSelect2('similars', 'Similars', $similars)
			->setDefaultValue($defaults)
			->setAutocomplete('autocompleteProducts');

		$newItems = [];
		if ($this->stock->product->novice) {
			$newItems[$this->stock->product->novice->id] = $this->stock->product->novice;
		}
		$form->addSelect2('new', 'New', $newItems)
			->setPrompt(TRUE)
			->setAutocomplete('autocompleteProducts');

		$noviceItems = [];
		if ($this->stock->product->novice) {
			$noviceItems[$this->stock->product->novice->id] = $this->stock->product->novice;
		}
		$form->addSelect2('novice', 'Novice', $noviceItems)
			->setPrompt(TRUE)
			->setAutocomplete('autocompleteProducts');

		$usedItems = [];
		if ($this->stock->product->used) {
			$usedItems[$this->stock->product->used->id] = $this->stock->product->used;
		}
		$form->addSelect2('used', 'Used', $usedItems)
			->setPrompt(TRUE)
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

		if ($values->new) {
			$this->stock->product->new = $productRepo->find($values->new);
		}
		if ($values->novice) {
			$this->stock->product->novice = $productRepo->find($values->novice);
		}
		if ($values->used) {
			$this->stock->product->used = $productRepo->find($values->used);
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
			'new' => $this->stock->product->new ? $this->stock->product->new->id : NULL,
			'novice' => $this->stock->product->novice ? $this->stock->product->novice->id : NULL,
			'used' => $this->stock->product->used ? $this->stock->product->used->id : NULL,
		];
		return $values;
	}

}

interface IStockSimilarFactory
{

	/** @return StockSimilar */
	function create();
}
