<?php

namespace App\Extensions\Products\Components;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\ProducerFacade;

class ProducerFilter extends BaseControl
{
	private $allowNone = TRUE;

	/** @var Producer */
	private $producer;
	/** @var ProducerLine */
	private $line;
	/** @var ProducerModel */
	private $model;
	/** @var array */
	private $productIds;

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

		$productIds = is_array($this->productIds) ? $this->productIds : TRUE;
		$producers = $this->producerFacade->getProducersList(TRUE, FALSE, $productIds);
		$form->addSelect('producer', 'Producer', $this->allowNone ? $notSelected + $producers : $producers)
			->setDisabled(!count($producers))
			->setDefaultValue($this->producer && array_key_exists($this->producer->id, $producers) ? $this->producer->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');

		$lines = $this->producer ? $this->producerFacade->getLinesList($this->producer, FALSE, TRUE, FALSE, $productIds) : [];
		$form->addSelect('line', 'Line', $notSelected + $lines)
			->setDisabled(!count($lines))
			->setDefaultValue($this->line && array_key_exists($this->line->id, $lines) ? $this->line->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');

		$models = $this->line ? $this->producerFacade->getModelsList($this->line, FALSE, TRUE, $productIds) : [];
		$form->addSelect('model', 'Model', $notSelected + $models)
			->setDisabled(!count($models))
			->setDefaultValue($this->model && array_key_exists($this->model->id, $models) ? $this->model->id : NULL)
			->getControlPrototype()->class('input-medium category-selections-select');


		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if (isset($values->producer)) {
			$this->setProducer($values->producer);
		}
		if (isset($values->line)) {
			$this->setLine($values->line);
		}
		if (isset($values->model)) {
			$this->setModel($values->model);
		}

		$this->onAfterSend($this->producer, $this->line, $this->model);
	}

	public function setProducer($producer, $allowNone = TRUE)
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
		$this->allowNone = $allowNone;
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

	public function setProductIds(array $ids)
	{
		$this->productIds = $ids;
		return $this;
	}

}

interface IProducerFilterFactory
{

	/** @return ProducerFilter */
	function create();
}
