<?php

namespace App\Components\Parameter\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Parameter;
use Nette\Utils\ArrayHash;

class ParameterEdit extends BaseControl
{

	/** @var Parameter */
	private $parameter;

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
		$form->setRenderer(new MetronicFormRenderer());
		
		$this->parameter->setCurrentLocale($this->getLocale());

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
		$this->onAfterSave($this->parameter);
	}

	private function load(ArrayHash $values)
	{
		if ($this->parameter->isNew()) {
			$this->parameter->name = $values->name;
		} else {
			$this->parameter->translateAdd($this->getLocale())->name = $values->name;
		}
		$this->parameter->mergeNewTranslations();
		return $this;
	}

	private function save()
	{
		$parameterRepo = $this->em->getRepository(Parameter::getClassName());
		$parameterRepo->save($this->parameter);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if (!$this->parameter->isNew()) {
			$values['name'] = $this->parameter->translate($this->getLocale())->name;
		}
		return $values;
	}

	protected function getLocale()
	{
		if ($this->parameter->isNew()) {
			return $this->translator->getDefaultLocale();
		} else {
			return $this->translator->getLocale();
		}
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->parameter) {
			throw new BaseControlException('Use setParameter(\App\Model\Entity\Parameter) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setParameter(Parameter $parameter)
	{
		$this->parameter = $parameter;
		return $this;
	}

	// </editor-fold>
}

interface IParameterEditFactory
{

	/** @return ParameterEdit */
	function create();
}
