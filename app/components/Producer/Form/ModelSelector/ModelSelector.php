<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Nette\Utils\ArrayHash;

class ModelSelector extends BaseControl
{

	/** @var Producer */
	private $producer;

	/** @var ProducerLine */
	private $line;

	/** @var ProducerModel */
	private $model;

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

		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());

		$producers = $producerRepo->findPairs('name');
		$lines = $lineRepo->findPairs('name');
		$models = $modelRepo->findPairs('name');

		$form->addSelect2('producer', 'Producer', $producers)
				->setPrompt('Select some producer');
		$form->addSelect2('line', 'Line', $lines)
				->setPrompt('Select some line')
				->setDisabled();
		$form->addSelect2('model', 'Model', $models)
						->setPrompt('Select some model')
						->setDisabled()
						->getControlPrototype()->class[] = 'sendFormOnChange';

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->onAfterSelect($this->producer, $this->line, $this->model);
	}

	private function getProducersTree()
	{
		$producersTree = [];
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		foreach ($producerRepo->findAll() as $producer) {
			$lines = [];
			foreach ($producer->lines as $line) {
				$models = [];
				foreach ($line->models as $model) {
					$models[$model->id] = [
						'name' => (string) $model,
					];
				}
				$lines[$line->id] = [
					'name' => (string) $line,
					'children' => $models,
				];
			}
			$producersTree[$producer->id] = [
				'name' => (string) $producer,
				'children' => $lines,
			];
		}
		return $producersTree;
	}

	private function load(ArrayHash $values)
	{
		if ($values->model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$this->model = $modelRepo->find($values->model);
		}
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	public function render()
	{
		$this->template->producersTree = $this->getProducersTree();
		parent::render();
	}

}

interface IModelSelectorFactory
{

	/** @return ModelSelector */
	function create();
}
