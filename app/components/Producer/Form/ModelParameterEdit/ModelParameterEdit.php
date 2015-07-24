<?php

namespace App\Components\Producer\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\ModelParameter;
use App\Model\Entity\Page;
use Nette\Utils\ArrayHash;

class ModelParameterEdit extends BaseControl
{

	/** @var Page */
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

		$form->addText('name', 'Name')
						->setRequired('Name is required')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addTextArea('text', 'Text', 5)
				->setRequired('Text is required');

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
		$lang = $this->parameter->isNew() ? $this->translator->getDefaultLocale() : $this->translator->getLocale();
		$this->parameter->translateAdd($lang)->name = $values->name;
		$this->parameter->translateAdd($lang)->text = $values->text;
		$this->parameter->mergeNewTranslations();
		return $this;
	}

	private function save()
	{
		$parameterRepo = $this->em->getRepository(ModelParameter::getClassName());
		$parameterRepo->save($this->parameter);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if (!$this->parameter->isNew()) {
			$this->parameter->setCurrentLocale($this->translator->getLocale());
			$values = [
				'name' => $this->parameter->name,
				'text' => $this->parameter->text,
			];
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->parameter) {
			throw new BaseControlException('Use setParameter(\App\Model\Entity\ModelParameter) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setParameter(ModelParameter $parameter)
	{
		$this->parameter = $parameter;
		return $this;
	}

	// </editor-fold>
}

interface IModelParameterEditFactory
{

	/** @return ModelParameterEdit */
	function create();
}
