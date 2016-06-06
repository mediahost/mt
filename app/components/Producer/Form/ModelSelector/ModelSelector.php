<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Facade\ProducerFacade;
use Nette\Utils\ArrayHash;

class ModelSelector extends BaseControl
{

	const CACHE_ID = 'model-selector';

	/** @var Producer */
	private $producer;

	/** @var ProducerLine */
	private $line;

	/** @var ProducerModel */
	private $model;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/** @var boolean */
	private $onlyWithChildren = TRUE;

	/** @var boolean */
	private $onlyWithProducts= TRUE;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSelect = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [$this->isAjax ? 'ajax' : '', 'modelSelector'];
		$form->getElementPrototype()->addAttributes(['data-target-loading' => '#loaded-content']);

		$allProducers = $this->producerFacade->getProducersList($this->onlyWithChildren, $this->onlyWithProducts);
		$allLines = $this->producerFacade->getLinesList(NULL, FALSE, $this->onlyWithChildren, $this->onlyWithProducts);
		$allModels = $this->producerFacade->getModelsList(NULL, FALSE, $this->onlyWithProducts);

		$form->addSelect2('producer', 'Producer', $allProducers)
			->setPrompt('Select some producer');

		$selectLine = $form->addSelect2('line', 'Line', $allLines)
			->setPrompt('Select some line');

		if ($this->producer) {
			$filteredLines = $this->producerFacade->getLinesList($this->producer, FALSE, $this->onlyWithChildren, $this->onlyWithProducts);
			$selectLine->setItems($filteredLines);
		} else {
			$selectLine->setDisabled();
		}

		$selectModel = $form->addSelect2('model', 'Model', $allModels)
			->setPrompt('Select some model');

		if ($this->line) {
			$filteredModels = $this->producerFacade->getModelsList($this->line, FALSE, $this->onlyWithProducts);
			$selectModel->setItems($filteredModels);
		} else {
			$selectModel->setDisabled();
		}
		$selectModel->getControlPrototype()->class[] = 'sendFormOnChange';

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$values->line = $form['line']->getRawValue();
		$values->model = $form['model']->getRawValue();

		$this->load($values);
		$this->onAfterSelect($this->producer, $this->line, $this->model);
	}

	public function getProducersTree($onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		$producersTree = [];
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		foreach ($producerRepo->findBy([], ['priority' => 'ASC']) as $producer) {
			$lines = [];
			foreach ($producer->lines as $line) {
				$models = [];
				foreach ($line->models as $model) {
					if (!$onlyWithProducts || $model->hasProducts()) {
						$models[$model->id] = [
							'name' => (string)$model,
							'priority' => $model->priority,
						];
					}
				}
				if ((!$onlyWithChildren || $line->hasModels()) && (!$onlyWithProducts || $line->hasProducts())) {
					$lines[$line->id] = [
						'name' => (string)$line,
						'priority' => $line->priority,
						'children' => $models,
					];
				}
			}
			if ((!$onlyWithChildren || $producer->hasLines(TRUE)) && (!$onlyWithProducts || $producer->hasProducts())) {
				$producersTree[$producer->id] = [
					'name' => (string)$producer,
					'priority' => $producer->priority,
					'children' => $lines,
				];
			}
		}
		return $producersTree;
	}

	private function load(ArrayHash $values)
	{
		if ($values->model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$model = $modelRepo->find($values->model);
			if ($model) {
				$this->setModel($model);
			}
		}
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->producer) {
			$values['producer'] = $this->producer->id;
		}
		if ($this->line) {
			$values['line'] = $this->line->id;
		}
		if ($this->model) {
			$values['model'] = $this->model->id;
		}
		return $values;
	}

	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$this->setLine($model->line);
		return $this;
	}

	public function setLine(ProducerLine $line)
	{
		$this->line = $line;
		$this->setProducer($line->producer);
		return $this;
	}

	public function setProducer(Producer $producer)
	{
		$this->producer = $producer;
		return $this;
	}

	public function setAccessories($value = TRUE)
	{
		$this->onlyWithProducts = $value;
		return $this;
	}

	public function render()
	{
		$this->template->producersTree = $this->getProducersTree($this->onlyWithChildren, $this->onlyWithProducts);
		parent::render();
	}

}

interface IModelSelectorFactory
{

	/** @return ModelSelector */
	function create();
}
