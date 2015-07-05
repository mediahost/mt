<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Nette\Utils\ArrayHash;

class ProducerEdit extends BaseControl
{

	/** @var Producer|ProducerLine|ProducerModel */
	private $entity;

	/** @var string */
	private $type;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$form->addText('name', 'Name')
				->setRequired('Name is required');
		
		switch ($this->type) {
			case Producer::ID:
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				break;
		}

		$form->addSubmit('save', 'Save');
		if ($this->entity->isNew()) {
			$form->addSubmit('saveAdd', 'Save & Add next');
		}

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		
		$componentsArray = (array) $form->getComponents();
		$isSubmitedByAdd = array_key_exists('saveAdd', $componentsArray) ? $form['saveAdd']->submittedBy : FALSE;
		$this->onAfterSave($this->entity, $this->type, $isSubmitedByAdd);
	}

	private function load(ArrayHash $values)
	{
		$this->entity->name = $values->name;
		switch ($this->type) {
			case Producer::ID:
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				break;
		}
		return $this;
	}

	private function save()
	{
		switch ($this->type) {
			case Producer::ID:
				$repo = $this->em->getRepository(Producer::getClassName());
				break;
			case ProducerLine::ID:
				$repo = $this->em->getRepository(ProducerLine::getClassName());
				break;
			case ProducerModel::ID:
				$repo = $this->em->getRepository(ProducerModel::getClassName());
				break;
			default:
				return $this;
		}
		$repo->save($this->entity);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->entity->name,
		];
		switch ($this->type) {
			case Producer::ID:
				break;
			case ProducerLine::ID:
				break;
			case ProducerModel::ID:
				break;
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->entity || !$this->type) {
			throw new BaseControlException('Use setProducer() before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setProducer($producerOrLineOrModel)
	{
		if ($producerOrLineOrModel instanceof Producer) {
			$this->type = Producer::ID;
		} else if ($producerOrLineOrModel instanceof ProducerLine) {
			$this->type = ProducerLine::ID;
		} else if ($producerOrLineOrModel instanceof ProducerModel) {
			$this->type = ProducerModel::ID;
		}
		$this->entity = $producerOrLineOrModel;
		return $this;
	}

	// </editor-fold>
}

interface IProducerEditFactory
{

	/** @return ProducerEdit */
	function create();
}
