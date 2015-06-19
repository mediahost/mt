<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Category;
use App\Model\Entity\Producer;
use App\Model\Entity\Stock;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\ProducerFacade;
use Nette\Utils\ArrayHash;

class StockCategory extends StockBase
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

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer());

		$categories = $this->categoryFacade->getCategoriesList($this->lang);
		$producers = $this->producerFacade->getProducersList($this->lang);

		$form->addSelect2('main_category', 'Main category', $categories)
						->setRequired('Select some category')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;
		$form->addMultiSelect2('categories', 'All categories', $categories);

		$form->addSelect2('producer', 'Producer', $producers)
						->setPrompt('Select some producer')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

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
		$mainCategory = $categoryRepo->find($values->main_category);
		$this->stock->product->mainCategory = $mainCategory;
		
		$otherCategories = [];
		foreach ($values->categories as $categoryId) {
			$otherCategories[] = $categoryRepo->find($categoryId);
		}
		$this->stock->product->setCategories($otherCategories, $mainCategory);
		
		if ($values->producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$producer = $producerRepo->find($values->producer);
			$this->stock->product->producer = $producer;
		} else {
			$this->stock->product->producer = NULL;
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
			'main_category' => $this->stock->product->mainCategory->id,
			'producer' => $this->stock->product->producer ? $this->stock->product->producer->id : NULL,
		];
		foreach ($this->stock->product->categories as $category) {
			$values['categories'][] = $category->id;
		}
		return $values;
	}

}

interface IStockCategoryFactory
{

	/** @return StockCategory */
	function create();
}
