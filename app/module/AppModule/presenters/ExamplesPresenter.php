<?php

namespace App\AppModule\Presenters;

use App\Components\Example\Form\FormControl;
use App\Components\Example\Form\IFormControlFactory;
use App\Forms\Form;

/**
 * Examples presenter
 */
class ExamplesPresenter extends BasePresenter
{

	/** @var IFormControlFactory @inject */
	public $iFormControlFactory;

	/**
	 * @secured
	 * @resource('examples')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->redirect('form');
	}

	/**
	 * @secured
	 * @resource('examples')
	 * @privilege('form')
	 */
	public function actionForm()
	{

	}

	// <editor-fold desc="components">

	/** @return FormControl */
	protected function createComponentForm()
	{
		$control = $this->iFormControlFactory->create();
		$control->onAfterSave = function (Form $form, $values) {
			$this->template->values = $values;
		};
		return $control;
	}
	// </editor-fold>

}
