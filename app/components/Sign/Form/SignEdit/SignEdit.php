<?php

namespace App\Components\Sign\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Sign;
use Nette\Utils\ArrayHash;

class SignEdit extends BaseControl
{

	/** @var Sign */
	private $sign;

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

		$defaultLang = $this->languageService->defaultLanguage;

		$form->addText('name', 'Name')
				->setRequired('Name is required')
				->setAttribute('placeholder', $this->sign->translate($defaultLang)->name);

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->sign);
	}

	private function load(ArrayHash $values)
	{
		$defaultLang = $this->languageService->defaultLanguage;
		if ($this->sign->isNew() && $defaultLang !== $this->lang) {
			$this->sign->translateAdd($defaultLang)->name = $values->name;
		}
		$this->sign->translateAdd($this->lang)->name = $values->name;
		$this->sign->mergeNewTranslations();
		return $this;
	}

	private function save()
	{
		$signRepo = $this->em->getRepository(Sign::getClassName());
		$signRepo->save($this->sign);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->sign->translate($this->lang)->name,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->sign) {
			throw new BaseControlException('Use setSign(\App\Model\Entity\Sign) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setSign(Sign $sign)
	{
		$this->sign = $sign;
		return $this;
	}

	// </editor-fold>
}

interface ISignEditFactory
{

	/** @return SignEdit */
	function create();
}
