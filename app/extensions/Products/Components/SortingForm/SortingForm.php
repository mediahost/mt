<?php

namespace App\Extensions\Products\Components;

use App\Components\BaseControl;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\ProducerFacade

class SortingForm extends BaseControl
{

	private $sorting;
	private $perPage;
	private $perPageList = [];

	/** @var Producer */
	private $producer;
	/** @var ProducerLine */
	private $line;
	/** @var ProducerModel */
	private $model;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/** @var array */
	public $onAfterSend = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [
			!$this->isSendOnChange ?: 'sendOnChange',
			!$this->isAjax ?: 'ajax'
		];

		$notSelected = [NULL => '--- Not Selected ---'];

		$producers = $this->producerFacade->getProducersList(TRUE, FALSE, TRUE);
		$form->addSelect('producer', 'Producer', $notSelected + $producers)
			->setDisabled(!count($producers))
			->setDefaultValue($this->producer && array_key_exists($this->producer->id, $producers) ? $this->producer->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');

		$lines = $this->producer ? $this->producerFacade->getLinesList($this->producer, FALSE, TRUE) : [];
		$form->addSelect('line', 'Line', $notSelected + $lines)
			->setDisabled(!count($lines))
			->setDefaultValue($this->line && array_key_exists($this->line->id, $lines) ? $this->line->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');

		$models = $this->line ? $this->producerFacade->getModelsList($this->line, FALSE, TRUE) : [];
		$form->addSelect('model', 'Model', $notSelected + $models)
			->setDisabled(!count($models))
			->setDefaultValue($this->model && array_key_exists($this->model->id, $models) ? $this->model->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');

		$form->addSelect('sort', 'Sort by', $this->getSortingMethods())
			->setDefaultValue($this->sorting)
			->getControlPrototype()->class('input-sm category-selections-select');

		$perPage = $form->addSelect('perPage', 'Show', $this->getItemsForCountSelect())
			->getControlPrototype()->class('input-sm category-selections-select');
		$defaultPerPage = array_search($this->perPage, $this->perPageList);
		if ($defaultPerPage !== FALSE) {
			$perPage->setDefaultValue($this->perPage);
		}

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->setSorting($values->sort);

		if (isset($values->producer)) {
			$this->setProducer($values->producer);
		}
		if (isset($values->line)) {
			$this->setLine($values->line);
		}
		if (isset($values->model)) {
			$this->setModel($values->model);
		}

		$key = array_search($values->perPage, $this->perPageList);
		if ($key !== FALSE) {
			$this->perPage = $key ? $values->perPage : NULL;
		}
		$this->onAfterSend($this->sorting, $this->perPage, $this->producer, $this->line, $this->model);
	}

	public function setSorting($value)
	{
		$this->sorting = $value;
		return $this;
	}

	public function setProducer($producer)
	{
		if ($producer instanceof Producer) {
			$this->producer = $producer;
		} else if ($producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$this->producer = $producerRepo->find($producer);
		}
		if (!$this->producer) {
			$this->setLine(NULL);
		}
		return $this;
	}

	public function setLine($line)
	{
		if ($line instanceof Producer) {
			$this->line = $line;
		} else if ($line) {
			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$this->line = $lineRepo->find($line);
		}

		if (!$this->producer || ($this->line && $this->line->producer->id !== $this->producer->id)) {
			$this->line = NULL;
		}

		return $this;
	}

	public function setModel($model)
	{
		if ($model instanceof Producer) {
			$this->model = $model;
		} else if ($model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->find($model);
		}

		if (!$this->line || ($this->model && $this->model->line->id !== $this->line->id)) {
			$this->model = NULL;
		}

		return $this;
	}

	public function setPerPage($perPage, array $perPageList)
	{
		$this->perPage = $perPage;
		$this->perPageList = $perPageList;
		return $this;
	}


	/** @return array */
	private function getSortingMethods()
	{
		return [
			ProductList::SORT_BY_PRICE_ASC => 'Price (Low > High)',
			ProductList::SORT_BY_PRICE_DESC => 'Price (High > Low)',
			ProductList::SORT_BY_NAME_ASC => 'Name (A - Z)',
			ProductList::SORT_BY_NAME_DESC => 'Name (Z - A)',
		];
	}

	/** @return array */
	private function getItemsForCountSelect()
	{
		return array_combine($this->perPageList, $this->perPageList);
	}

}

interface ISortingFormFactory
{

	/** @return SortingForm */
	function create();
}
