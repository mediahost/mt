<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\SelectBased\Select2;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Helpers;
use App\Model\Entity\Category;
use App\Model\Entity\Heureka\Category as HeurekaCategory;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Stock;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\HeurekaFacade;
use App\Model\Facade\ProducerFacade;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

class StockCategory extends StockBase
{
	// <editor-fold desc="variables">

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/** @var HeurekaFacade @inject */
	public $heurekaFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicHorizontalFormRenderer());
//		$form->getElementPrototype()->class('ajax');

		$categories = $this->categoryFacade->getCategoriesList($this->translator->getLocale());

		$form->addSelect2('main_category', 'Main category', $categories)
						->setRequired('Select some category')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addMultiSelect2('categories', 'All categories', $categories);

		$producer = $this->stock->product->producer;
		$producerLine = $this->stock->product->producerLine;
		$producerModel = $this->stock->product->producerModel;

		$producers = $this->producerFacade->getProducersList();
		$lines = $this->producerFacade->getLinesList($producer);
		$models = $this->producerFacade->getModelsList($producerLine);
		$allModels = $this->producerFacade->getModelsList(NULL, TRUE);

		$producerDefault = $producer ? $producer->id : NULL;
		$lineDefault = $producerLine && array_key_exists($producerLine->id, $lines) ? $producerLine->id : NULL;
		$modelDefault = $producerModel && array_key_exists($producerModel->id, $models) ? $producerModel->id : NULL;

		$isAllowedLine = $producerDefault !== NULL;
		$isAllowedModel = $lineDefault !== NULL;

		$select2DependendClasses = Helpers::concatStrings(' ', MetronicTextInputBase::SIZE_XL, 'dependentSelect');
		$form->addSelect2('producer', 'Producer', $producers)
						->setPrompt('Select some producer')
						->setDefaultValue($producerDefault)
						->getControlPrototype()->class[] = $select2DependendClasses;

		$form->addSelect2('line', 'Line', $lines)
						->setDisabled(!$isAllowedLine)
						->setPrompt($isAllowedLine ? 'Select some line' : 'First select producer')
						->setDefaultValue($lineDefault)
						->getControlPrototype()->class[] = $select2DependendClasses;

		$form->addSelect2('model', 'Model', $models)
						->setDisabled(!$isAllowedModel)
						->setPrompt($isAllowedModel ? 'Select some model' : 'First select line')
						->setDefaultValue($modelDefault)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;
		
		$form->addMultiSelect2('accessoriesFor', 'Accessories for', $allModels);

		$heurekaCategories = $this->heurekaFacade->getFullnames($this->translator->getLocale());
		$form->addSelect2('heurekaCategory', 'Heureka Category', [NULL => '--- No Category ---'] + $heurekaCategories);

		$form->addSubmit('save', 'Save');

		$load = $form->addSubmit('load', 'Load');
		$load->getControlPrototype()->class[] = 'ajax';
		$load->getControlPrototype()->id = 'dependentSelect_load';
		$load->onClick[] = $this->reloadItems;

		$form->onSuccess[] = $this->reloadItems;
		$form->onSuccess[] = $this->formSucceeded;
		$form->setDefaults($this->getDefaults());

		return $form;
	}

	public function reloadItems($buttonOrForm)
	{
		$form = $buttonOrForm instanceof SubmitButton ? $buttonOrForm->form : $buttonOrForm;
		$producerControl = $form['producer'];
		$lineControl = $form['line'];
		$modelControl = $form['model'];

		$this->resetSelect2($lineControl, 'First select producer');
		$this->resetSelect2($modelControl, 'First select line');

		$lines = [];
		$producerValue = $producerControl->getValue();
		if ($producerValue) {
			$producerControl->setDefaultValue($producerValue);

			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$producer = $producerRepo->find($producerValue);
			if ($producer) {
				$lines = $this->producerFacade->getLinesList($producer);
				$lineControl
						->setItems($lines)
						->setDisabled(FALSE)
						->setPrompt('Select some line');
			}
		}

		$models = [];
		$lineValue = $lineControl->getValue();
		if ($lineValue && array_key_exists($lineValue, $lines)) {
			$lineControl->setDefaultValue($lineValue);

			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$line = $lineRepo->find($lineValue);
			if ($line) {
				$models = $this->producerFacade->getModelsList($line);
				$modelControl
						->setItems($models)
						->setDisabled(FALSE)
						->setPrompt('Select some model');
			}
		}

		$modelValue = $modelControl->getValue();
		if ($modelValue && array_key_exists($modelValue, $models)) {
			$modelControl->setDefaultValue($modelValue);
		}
	}

	private function resetSelect2(Select2 $select, $prompt, $items = [])
	{
		$select
				->setDisabled()
				->setPrompt($prompt)
				->setItems($items);

		return $this;
	}

	public function formSucceeded(Form $form, $values)
	{
		$values->line = $form['line']->getValue();
		$values->model = $form['model']->getValue();

		if ($form['save']->submittedBy) {
			$this->load($values);
			$this->save();
			$this->onAfterSave($this->stock);
		}
		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		}
	}

	private function load(ArrayHash $values)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());

		$mainCategory = $categoryRepo->find($values->main_category);
		$this->stock->product->mainCategory = $mainCategory;

		$otherCategories = [];
		foreach ($values->categories as $modelId) {
			$otherCategories[] = $categoryRepo->find($modelId);
		}
		$this->stock->product->setCategories($otherCategories, $mainCategory);

		$this->stock->product->producerModel = NULL;
		$this->stock->product->producerLine = NULL;
		$this->stock->product->producer = NULL;
		if (isset($values->model) && $values->model) {
			$model = $modelRepo->find($values->model);
			if ($model) {
				$this->stock->product->producerModel = $model;
				$this->stock->product->producerLine = $model->line;
				$this->stock->product->producer = $model->line->producer;
			}
		} else if (isset($values->line) && $values->line) {
			$line = $lineRepo->find($values->line);
			if ($line) {
				$this->stock->product->producerModel = NULL;
				$this->stock->product->producerLine = $line;
				$this->stock->product->producer = $line->producer;
			}
		} else if ($values->producer) {
			$producer = $producerRepo->find($values->producer);
			if ($producer) {
				$this->stock->product->producerModel = NULL;
				$this->stock->product->producerLine = NULL;
				$this->stock->product->producer = $producer;
			}
		}

		$accesoryModels = [];
		foreach ($values->accessoriesFor as $modelId) {
			$accesoryModels[] = $modelRepo->find($modelId);
		}
		$this->stock->product->setAccessoriesFor($accesoryModels);

		if ($values->heurekaCategory) {
			$heurekaCategoryRepo = $this->em->getRepository(HeurekaCategory::getClassName());
			$heurekaCategory = $heurekaCategoryRepo->find($values->heurekaCategory);
			if ($heurekaCategory) {
				$this->stock->product->heurekaCategory = $heurekaCategory;
			}
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
		$values = [];
		if ($this->stock->product->mainCategory) {
			$values['main_category'] = $this->stock->product->mainCategory->id;
		}
		if ($this->stock->product->heurekaCategory) {
			$values['heurekaCategory'] = $this->stock->product->heurekaCategory->id;
		}
		foreach ($this->stock->product->categories as $category) {
			$values['categories'][] = $category->id;
		}
		foreach ($this->stock->product->accessoriesFor as $model) {
			$values['accessoriesFor'][] = $model->id;
		}
		return $values;
	}

}

interface IStockCategoryFactory
{

	/** @return StockCategory */
	function create();
}
