<?php

namespace App\Components\Product;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Facade\CategoryFacade;
use Nette\Utils\ArrayHash;

class StockCategoryControl extends StockBaseControl
{
	// <editor-fold desc="variables">

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$categories = $this->categoryFacade->getCategoriesList($this->lang);

		$form->addSelect2('main_category', 'Main category', $categories)
						->setRequired('Select some category')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;
		$form->addMultiSelect2('categories', 'Other categories', $categories);

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
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$category = $categoryRepo->find($values->main_category);
		$this->stock->product->mainCategory = $category;

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
			'main_category' => $this->stock->product->mainCategory->id,
		];
		return $values;
	}

}

interface IStockCategoryControlFactory
{

	/** @return StockCategoryControl */
	function create();
}
