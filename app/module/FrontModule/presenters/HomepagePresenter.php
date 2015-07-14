<?php

namespace App\FrontModule\Presenters;

use App\Components\Producer\Form\IModelSelectorFactory;
use App\Components\Producer\Form\ModelSelector;
use App\Model\Entity\ProducerModel;

class HomepagePresenter extends BasePresenter
{

	/** @var IModelSelectorFactory @inject */
	public $iModelSelectorFactory;

	public function actionDefault()
	{
		$this->showSlider = TRUE;
		$this->showBrands = TRUE;
//		$this->showSteps = FALSE;
	}

	public function renderDefault()
	{
		
	}

	// <editor-fold desc="forms">

	/** @return ModelSelector */
	public function createComponentModelSelector()
	{
		$control = $this->iModelSelectorFactory->create();
		$control->setAjax(FALSE);
		$control->onAfterSelect = function ($producer, $line, $model) {
			if ($model instanceof ProducerModel) {
				$this->redirect('Category:accessories', $model->id);
			} else {
				$this->flashMessage('This model wasn\'t found.', 'warning');
				$this->redirect('this');
			}
		};
		return $control;
	}

	// </editor-fold>
}
