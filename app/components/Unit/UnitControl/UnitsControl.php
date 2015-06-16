<?php

namespace App\Components\Unit;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Unit;
use Nette\Utils\ArrayHash;

class UnitsControl extends BaseControl
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

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);
		
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
		$unitRepo = $this->em->getRepository(Unit::getClassName());
		foreach ($values->units as $id => $name) {
			if (!empty($name)) {
				$unit = $unitRepo->find($id);
				$unit->translate($this->lang)->name = $name;
				$unit->mergeNewTranslations();
				$unitRepo->save($unit);
			}
		}
		$this->onAfterSave();
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

interface IUnitsControlFactory
{

	/** @return UnitsControl */
	function create();
}
