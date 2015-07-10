<?php

namespace App\Components\Unit\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Unit;

class UnitsEdit extends BaseControl
{

	/** @var array */
	private $units;

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

		$unitsContainer = $form->addContainer('units');
		foreach ($this->units as $unit) {
			$unitName = $unitsContainer->addText($unit->id, $unit->name)
					->setAttribute('placeholder', $unit->name);
			$unitName->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
		}

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		foreach ($values->units as $id => $name) {
			$this->saveUnit($id, $name);
		}
		$this->onAfterSave();
	}

	private function saveUnit($id, $value)
	{
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$defaultLang = $this->languageService->defaultLanguage;
		$isLangDefault = $this->lang === $defaultLang;
		/* @var $unit Unit */
		$unit = $unitRepo->find($id);
		if ($isLangDefault && empty($value)) {
			// skip
		} else if (empty($value)) { // delete empty translations
			$translation = $unit->translate($this->lang);
			if ($translation->getLocale() === $this->lang) {
				$unit->removeTranslation($unit->translateAdd($this->lang));
			}
		} else {
			$unit->translateAdd($this->lang)->name = $value;
		}
		$unit->mergeNewTranslations();
		$unitRepo->save($unit);
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'units' => [],
		];
		foreach ($this->units as $unit) {
			$unit->setCurrentLocale($this->lang);
			if ($unit->translate($this->lang)->name !== $unit->translate($this->languageService->defaultLanguage)->name) {
				$values['units'][$unit->id] = $unit->translate($this->lang)->name;
			}
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!is_array($this->units)) {
			throw new BaseControlException('Use setUnits(array) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setUnits(array $units)
	{
		$this->units = $units;
		return $this;
	}

	// </editor-fold>
}

interface IUnitsEditFactory
{

	/** @return UnitsEdit */
	function create();
}
