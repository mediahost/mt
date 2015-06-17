<?php

namespace App\AppModule\Presenters;

use App\Components\Example\Form\ExampleForm;
use App\Components\Example\Form\IExampleFormFactory;
use App\Forms\Form;

class ExamplesPresenter extends BasePresenter
{

	/** @var IExampleFormFactory @inject */
	public $iExampleFormFactory;

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

	/** @return ExampleForm */
	protected function createComponentForm()
	{
		$control = $this->iExampleFormFactory->create();
		$control->onAfterSave = function (Form $form, $values) {
			$this->template->values = $values;
		};
		return $control;
	}
	// </editor-fold>

}
