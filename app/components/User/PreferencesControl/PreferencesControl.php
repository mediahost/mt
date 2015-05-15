<?php

namespace App\Components\User;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;

/**
 * Form with user's personal settings.
 */
class PreferencesControl extends BaseControl
{
	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addSelect2('language', 'Language', (array) $this->languageService->allowedLanguages);

		$form->addSubmit('save', 'Save');

		$form->setDefaults([
			'language' => $this->languageService->language,
		]);
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->onAfterSave($values->language);
	}

}

interface IPreferencesControlFactory
{

	/** @return PreferencesControl */
	function create();
}
