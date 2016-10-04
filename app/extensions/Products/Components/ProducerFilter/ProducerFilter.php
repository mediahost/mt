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

		$form->addSelect('producer', 'Producer')
			->getControlPrototype()->class('input-medium category-selections-select');
		$form->addSelect('line', 'Line')
			->getControlPrototype()->class('input-medium category-selections-select');
		$form->addSelect('model', 'Model')
			->getControlPrototype()->class('input-medium category-selections-select');

		$this->setFormValues($form);

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->setProducer(!isset($values->producer) || !$values->producer ? NULL : $values->producer, $this->allowNone);
		$this->setLine(!isset($values->line) || !$values->line ? NULL : $values->line);
		$this->setModel(!isset($values->model) || !$values->model ? NULL : $values->model);

		$this->setFormValues($form);

		$this->onAfterSend($this->producer, $this->line, $this->model);
	}

	private function setFormValues(Form &$form)
	{
		$notSelected = [NULL => '--- All ---'];

		$productIds = is_array($this->productIds) ? $this->productIds : TRUE;
		$producers = $this->producerFacade->getProducersList(TRUE, FALSE, $productIds);
		$form['producer']
			->setItems($this->allowNone ? $notSelected + $producers : $producers)
			->setDisabled(!count($producers))
			->setDefaultValue($this->producer && array_key_exists($this->producer->id, $producers) ? $this->producer->id : NULL);

		$lines = $this->producer ? $this->producerFacade->getLinesList($this->producer, FALSE, TRUE, FALSE, $productIds) : [];
		$form['line']
			->setItems($notSelected + $lines)
			->setDisabled(!count($lines))
			->setDefaultValue($this->line && array_key_exists($this->line->id, $lines) ? $this->line->id : NULL);

		$models = $this->line ? $this->producerFacade->getModelsList($this->line, FALSE, TRUE, $productIds) : [];
		$form['model']
			->setItems($notSelected + $models)
			->setDisabled(!count($models))
			->setDefaultValue($this->model && array_key_exists($this->model->id, $models) ? $this->model->id : NULL);
	}

	public function setProducer($producer, $allowNone = TRUE)
	{
		if ($producer instanceof Producer) {
			$this->producer = $producer;
		} else if ($producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$this->producer = $producerRepo->find($producer);
		} else {
			$this->producer = NULL;
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
		} else {
			$this->line = NULL;
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
		} else {
			$this->model = NULL;
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
