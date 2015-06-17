<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Producer;
use Nette\Utils\ArrayHash;

class ProducerEdit extends BaseControl
{

	/** @var Producer */
	private $producer;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$form->addText('name', 'Name')
				->setRequired('Name is required');

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->producer);
	}

	private function load(ArrayHash $values)
	{
		$this->producer->name = $values->name;
		return $this;
	}

	private function save()
	{
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$producerRepo->save($this->producer);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->producer->name,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->producer) {
			throw new BaseControlException('Use setProducer(\App\Model\Entity\Producer) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setProducer(Producer $producer)
	{
		$this->producer = $producer;
		return $this;
	}

	// </editor-fold>
}

interface IProducerEditFactory
{

	/** @return ProducerEdit */
	function create();
}
