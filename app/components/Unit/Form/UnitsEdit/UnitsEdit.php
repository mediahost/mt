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
		$isLangDefault = $this->translator->getLocale() === $this->translator->getDefaultLocale();
		/* @var $unit Unit */
		$unit = $unitRepo->find($id);
		if ($isLangDefault && empty($value)) {
			// skip
		} else if (empty($value)) { // delete empty translations
			$translation = $unit->translate($this->translator->getLocale());
			if ($translation->getLocale() === $this->translator->getLocale()) {
				$unit->removeTranslation($unit->translateAdd($this->translator->getLocale()));
			}
		} else {
			$unit->translateAdd($this->translator->getLocale())->name = $value;
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
			$unit->setCurrentLocale($this->translator->getLocale());
			if ($unit->translate($this->translator->getLocale())->name !== $unit->translate($this->translator->getDefaultLocale())->name) {
				$values['units'][$unit->id] = $unit->translate($this->translator->getLocale())->name;
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
